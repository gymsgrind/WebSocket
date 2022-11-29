<?php
include_once '/var/www/gymsgrind_private/php/config.php';
include_once CLASS_PATH . '/database.class.php';
include_once CLASS_PATH . '/socketServer.class.php';


$ws = new socketServer('localhost', 8090);

$ws->SetUserInfoStructure(['username' => false, 'Auth' => false]);

$ws->OnConnection(function ($socketInfo, $self) {
	$self->sendArray(["action" => "InitNow"]);
});
$ws->OnDisconnection(function ($UserInfo) {
    if(!$UserInfo['username']) echo "Anonimous user disconnected\n";
    else echo "\nUser ".json_encode($UserInfo['username'])." disconnected\n";
});

$ws->NewAction('InitUser', function ($SocketMsg, &$UserInfo, $self) {

    // Create a new token for the user before sending it for authentication

    $db = new Database_me();
    $token = bin2hex(random_bytes(32));
    try {
        $query = 'UPDATE users SET auth_token = ? WHERE username = ?';
        $for = [$token, $SocketMsg->username];
        $db->query_me($query, $for);
        $UserInfo['username'] = $SocketMsg->username;
        $self->sendArray(["action" => "SendAuthToken"]);
    } catch (Exception $e) {
        $this->sendArray(["action" => "Error", "message" => "Username Not Found"]);
    }
});
$ws->NewAction('Auth', function ($SocketMsg, &$UserInfo, $self) {

    // Verify the token sent by the user by [Action => InitUser] and set the user as authenticated

    $db = new Database_me();
    $query = 'SELECT auth_token FROM users WHERE username = ?';
    $for = [$UserInfo['username']];
    $result = $db->query_me($query, $for);
    $username = $UserInfo['username'];
    echo "User ".json_encode($username)." is trying to authenticate\n with token: $SocketMsg->auth_token\n the db result: ".json_encode($result[0]['auth_token'])."\n";
    if (!$result || $result[0]['auth_token'] != $SocketMsg->auth_token || $result[0]['auth_token'] == null) {
        $self->sendArray(["action" => "AuthFailed"]);
        $UserInfo['username'] = null;
        $UserInfo['auth'] = false;
    } else {
        $self->sendArray(["action" => "AuthSuccess"]);
        $UserInfo['auth'] = true;
    }

    $query = 'UPDATE users SET auth_token = NULL WHERE username = ?';
    $for = [$username];
    $db->query_me($query, $for);
});

$ws->Run();