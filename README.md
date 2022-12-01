# WebSocket


    This is a User Friendly WebSocket implementation.
    Easy to set up and configure.
    It Can contain Users Information for multiple users
    connected to the same WebSocket.
    I made this for any type of use.
## Documentation

    Configuration:
        *** The Server comunicate with JSON format ***

        Set the structure of the session:
         ->   SetSessionStructure()

        Set the function to execute when a user connect the server:
         ->   OnConnection()

        Set the function to execute when a user disconnect the server:
         ->   OnDisconnection()

        Set the function to execute when a user 
        send a [action => value] to the server:
         ->   NewAction()

        Run the server:
         ->   Run()

## Usage/Examples

```php
<?php
include_once '/socketServer.class.php';

$ws = new socketServer('localhost', 8090);

$ws->SetSessionStructure(['username' => false, 'Auth' => false]);

$ws->OnConnection(function ($self) {
	$self->sendArray(["action" => "InitNow"]);
});

$ws->OnDisconnection(function () {
    if(!$_SESSION['username']) echo "Anonimous user disconnected\n";
    else echo "\nUser ".json_encode($_SESSION['username'])." disconnected\n";
});

$ws->NewAction('InitUser', function ($SocketMsg, $self) {

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

