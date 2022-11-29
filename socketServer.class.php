<?php

class SocketServer
{

	private $address;
	private $port;
	public $socketResource;
	private $clientSocketArray;
	private $clientSocketInfo;
	private $newSocketArray;
	private $ActiveSocketInfo; //Active Socket that contains all users infos
	private $recMsg; // the received message in object form
	private $db;
	private $actions;
	private $ClientInfoStructure;
	private $newClientAction;
	private $disconnectionAction;
	private $debug = false;

	public function __construct($address, $port, $debug = false)
	{
		// **** change Actions in ActionHandler() to match your needs **** //

		$this->port = $port;
		$this->address = $address;
		if($debug) $this->debug = true;
		$this->socketResource = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_set_option($this->socketResource, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($this->socketResource, 0, $this->port);
		socket_listen($this->socketResource);

		$this->clientSocketArray = array($this->socketResource);
		$this->clientSocketInfo = array();

		$this->newClientAction = ['callback' => false];
		$this->disconnectionAction = ['callback' => false];
		$this->ClientInfoStructure = ['socket' => null];
	}
	public function Run()
	{
		while (true) {
			$this->newSocketArray = $this->clientSocketArray;
			socket_select($this->newSocketArray, $null, $null, 0, 10);

			if (in_array($this->socketResource, $this->newSocketArray)) {
				$newSocket = socket_accept($this->socketResource);
				$this->clientSocketArray[] = $newSocket;

				// Set the User Info Structure to the new socket
				$this->ClientInfoStructure['socket'] = $newSocket;
				$this->clientSocketInfo[] = $this->ClientInfoStructure;

				$header = socket_read($newSocket, 1024);
				$this->doHandshake($header, $newSocket, $this->address, $this->port);

				//socket_getpeername($newSocket, $client_ip_address);
				//$connectionACK = $this->newConnectionACK($client_ip_address);
				//$this->send($connectionACK, $newSocket);
				$this->SetActiveSocket($newSocket);
				if($this->newClientAction['callback']) {
					$this->newClientAction['callback']($newSocket, $this);
				}

				$newSocketIndex = array_search($this->socketResource, $this->newSocketArray);
				unset($this->newSocketArray[$newSocketIndex]);
			}

			foreach ($this->newSocketArray as $newSocketArrayResource) {
				while (@socket_recv($newSocketArrayResource, $socketData, 1024, 0) >= 1) {
					$socketMessage = $this->unseal($socketData);
					try {
						$this->recMsg = json_decode($socketMessage);
						$this->SetActiveSocket($newSocketArrayResource);
						$this->MsgHandler();
					} catch (Exception $e) {
						$this->ReportError($e->getMessage());
					}
					//receive from client
					break 2;
				}

				$socketData = @socket_read($newSocketArrayResource, 1024, PHP_NORMAL_READ);
				if ($socketData === false) {

					if($this->disconnectionAction['callback']) {
						$this->disconnectionAction['callback']($this->ActiveSocketInfo);
					}

					$newSocketIndex = array_search($newSocketArrayResource, $this->clientSocketArray);
					unset($this->clientSocketArray[$newSocketIndex]);
					unset($this->ActiveSocketInfo);
				}
			}
		}
		//socket_close($socketResource);
	}
	public function doHandshake($received_header, $client_socket_resource, $host_name, $port)
	{
		$headers = array();
		$lines = preg_split("/\r\n/", $received_header);
		foreach ($lines as $line) {
			$line = chop($line);
			if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
				$headers[$matches[1]] = $matches[2];
			}
		}

		$secKey = $headers['Sec-WebSocket-Key'];
		$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
		$buffer = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
			"Upgrade: websocket\r\n" .
			"Connection: Upgrade\r\n" .
			"WebSocket-Origin: $host_name\r\n" .
			"WebSocket-Location: ws://$host_name:$port/demo/shout.php\r\n" .
			"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
		socket_write($client_socket_resource, $buffer, strlen($buffer));
	}
	public function unseal($socketData)
	{
		$length = ord($socketData[1]) & 127;
		if ($length == 126) {
			$masks = substr($socketData, 4, 4);
			$data = substr($socketData, 8);
		} elseif ($length == 127) {
			$masks = substr($socketData, 10, 4);
			$data = substr($socketData, 14);
		} else {
			$masks = substr($socketData, 2, 4);
			$data = substr($socketData, 6);
		}
		$socketData = "";
		for ($i = 0; $i < strlen($data); ++$i) {
			$socketData .= $data[$i] ^ $masks[$i % 4];
		}
		return $socketData;
	}
	public function seal($socketData)
	{
		$b1 = 0x80 | (0x1 & 0x0f);
		$length = strlen($socketData);

		if ($length <= 125)
			$header = pack('CC', $b1, $length);
		elseif ($length > 125 && $length < 65536)
			$header = pack('CCn', $b1, 126, $length);
		elseif ($length >= 65536)
			$header = pack('CCNN', $b1, 127, $length);
		return $header . $socketData;
	}
	public function send($message)
	{

		$messageLength = strlen($message);
		
		@socket_write($this->ActiveSocketInfo['socket'], $message, $messageLength);
		return true;
	}
	public function sendArray($array)
	{
		//send array that is converted to json

		$this->send($this->seal(json_encode($array)));
	}
	private function SetActiveSocket($ClientSocket)
	{
		for ($i = 0; $i < count($this->clientSocketInfo); $i++) {
			if ($this->clientSocketInfo[$i]['socket'] == $ClientSocket) {
				$this->ActiveSocketInfo = &$this->clientSocketInfo[$i];
				return true;
			}
		}
		$this->ActiveSocketInfo = false;
		return false;
	}
	private function ReportError($message) 
	{
		if (!$this->ActiveSocketInfo) echo "Socket Error: No Active Socket\n";
		else echo "Error: " . $message . "\nActive Socket: " . var_dump($this->ActiveSocketInfo);
	}
	private function MsgHandler()
	{
		/***************
			[ 'action' => something, 'data' => something ]
		***************/
		if(!isset($this->recMsg->action)){
			$this->sendArray(['action' => 'Error', 'data' => 'The key action is not set']);
			return false;
		}
		foreach ($this->actions as $action) {
			if ($action['name'] == $this->recMsg->action) {
				$action['callback']($this->recMsg, $this->ActiveSocketInfo, $this);
				if($this->debug) echo 'Action: ' . $action['name'] . ' was called' . "\n";
				return true;
			}
		} 
		$this->sendArray(["action" => "Error", "data" => "Action value not found for Action: " . $this->recMsg->action]);
		return false;
	}
	public function NewAction($actionName, $callback)
	{
		/************
		 * Set a new action to be handled by the server
		 * 
		 * The callback receives:
			(
				the socket_message received from the client, 
				the UserInfoStructure assosiated with the socket that sent the message, 
				the access to the server class (this)
			)
		 **********/

		$this->actions[] = [
			'name' => $actionName,
			'callback' => $callback,
		];
	}
	public function SetUserInfoStructure($infoArray)
	{
		// Set the user info Structure that is accessible via the  Ex: ['key' => 'defaultValue']

		foreach ($infoArray as $key => $DefValue) {
			$this->ClientInfoStructure[$key] = $DefValue;
		}
	}
	public function OnConnection($callback)
	{
		// Set a new action to be handled when a new client connects to the server

		$this->newClientAction['callback'] = $callback;
	}
	public function OnDisconnection($callback)
	{
		// Set a new action to be handled when a client disconnects from the server

		$this->disconnectionAction['callback'] = $callback;
	}
}


