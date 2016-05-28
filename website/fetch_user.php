<?php

$mysqli;
$result;
require 'common.php';
ini_set('log_errors', 1);
ini_set('error_log', 'logs/user_redu.log');

$username = strtolower(strtok($_GET['name'], ' '));
header('Content-Type: application/json');

$mysqli = new mysqli('127.0.0.1', 'hifi', '123456', 'hifi_stats');
if ($mysqli->connect_errno) {
    respond404AndExit();
}
//WHERE WEEKOFYEAR(time) = WEEKOFYEAR(NOW())
$sql = "SELECT first_login, last_login FROM users WHERE name='$username'";
if (!$result = $mysqli->query($sql)) {
    respond404AndExit();
}

if ($result->num_rows > 0) {
    $count = $result->fetch_assoc();
    echo json_encode(['username' => $count['name'], 'firstLogin' => $count['first_login'], 'lastLogin' => $count['last_login']]);
    exitPhp();
} else {
    respond404AndExit();
}
