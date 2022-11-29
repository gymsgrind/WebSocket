# WebSocket


    This is a User Friendly WebSocket implementation.
    Easy to set up and configure.
    It Can contain Users Information for multiple users
    connected to the same WebSocket.
    I made this for any type of use.
## Documentation

    Configuration:
        *** The Server comunicate with JSON format ***

        > $UserInfo = 'array of information for the current user sending data'
        ^^^^^^^^^ &$UserInfo is needed for direct access to the object.
        > $socketMessage = 'array containing ['key' => 'value'] from received data'
        > $self = 'access to function in the class'

        Set User Information Structure for storing user information:
            - SetUserInfoStructure(['key' => 'Default Value', 'username' => false]);
        
        Set trigger function when someone connect:
            - OnConnection(function ($socket, $self){
                //Do something
            });
        
        Set trigger function when someone disconnect:
            - OnDisconnection(function (&$UserInfo) {
                                        ^^^^^^^^^^
                //Do something and do not forgot '&' before $UserInfo
            });

        Set trigger function for an action when the server receive data based on
        the key ['action']:
            - NewAction('Value of action key', 
            function ($socketMessage, &$UserInfo, $self){
                                      ^^^^^^^^^^
                //Do something and do not forgot '&' before $UserInfo
            });

        Sending back JSON data to the current User:
            - sendArray(['key' => 'value', 'Auth' => true]);

        Run the Socket Server after the Configuration:
            - Run();
## Usage/Examples

```php
<?php
include_once '/socketServer.class.php';

$ws = new socketServer('localhost', 8090);

$ws->SetUserInfoStructure(['username' => false, 'Auth' => false]);

$ws->OnConnection(function ($socketInfo, $self) {
	$self->sendArray(["action" => "InitNow"]);
});

$ws->OnDisconnection(function (&$UserInfo) {
    if(!$UserInfo['username']) echo "Anonimous user disconnected\n";
    else echo "\nUser ".json_encode($UserInfo['username'])." disconnected\n";
});

$ws->NewAction('InitUser', function ($SocketMsg, &$UserInfo, $self) {

    $username = 'John Doe';
    $password = 'Pa33w0rd';

    if($SocketMsg['username'] == $username && $SocketMsg['password'] == $password) {
        $UserInfo['username'] = $username;
        $UserInfo['Auth'] = true;
        $self->sendArray(["action" => "Auth", "success" => true]);
    } else {
        $self->sendArray(["action" => "Auth", "success" => false]);
    }
});

$ws->Run();


```

