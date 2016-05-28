<?php

$username = strtok($_GET['name'], ' ');
$isAlpha = false;
$isStaff = false;
$firstLogin;
$lastLogin;

function hasUserBadge($json, $badgeID)
{
    foreach ($json['user_badges'] as $badges) {
        if ($badges['badge_id'] == $badgeID) {
            return true;
        }
    }

    return false;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://forums.highfidelity.com/users/'.$username.'.json');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$html = curl_exec($ch);
curl_close($ch);

$json = json_decode($html, true);
if (array_key_exists('errors', $json)) {
    echo '{}';
    exit;
}
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
    $data = $result->fetch_assoc();
    $firstLogin = $data['first_login'];
    $lastLogin = $data['last_login'];
}
$isStaff = hasUserBadge($json, 4);
$isAlpha = hasUserBadge($json, 100);
echo json_encode(['username' => strtolower($username), 'firstLogin' => $firstLogin, 'lastLogin' => $lastLogin, 'isAlpha' => $isAlpha, 'isStaff' => $isStaff]);
