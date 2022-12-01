<?php
include_once '/socketServer.class.php';

$ws = new socketServer('localhost', 8090);

$ws->SetSessionStructure(['username' => false, 'Auth' => false]);

$ws->OnConnection(function ($socketInfo, $self) {
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
        $_SESSION['username'] = $username;
        $_SESSION['Auth'] = true;
        $self->sendArray(["action" => "Auth", "success" => true]);
    } else {
        $self->sendArray(["action" => "Auth", "success" => false]);
    }
});

$ws->Run();