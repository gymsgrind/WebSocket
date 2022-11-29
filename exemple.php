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