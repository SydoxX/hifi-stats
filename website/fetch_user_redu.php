<?php

$username = $_GET['name'];
$username = strtok($username, ' ');
$mysqli = new mysqli('127.0.0.1', 'hifi', '123456', 'hifi_stats');
if ($mysqli->connect_errno) {
    echo '{}';
    exit;
}
//WHERE WEEKOFYEAR(time) = WEEKOFYEAR(NOW())
$sql = "SELECT name, first_login, last_login FROM users WHERE name='$username'";
if (!$result = $mysqli->query($sql)) {
    echo '{}';
    exit;
}

if ($result->num_rows > 0) {
    $count = $result->fetch_assoc();
    echo json_encode(['username' => strtolower($count['name']), 'firstLogin' => $count['first_login'], 'lastLogin' => $count['last_login']]);
} else {
    echo '{}';
}

$result->free();
$mysqli->close();
