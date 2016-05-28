<?php

$mysqli;
$result;
require('common.php');
ini_set("log_errors", 1);
ini_set("error_log", "logs/stats.log");

class StatsObject
{
    public $table = string;
    public $count = string;
    public $yLabel = string;
}

$statsObj = new StatsObject();
if ($_GET['stats'] == 'domains') {
    $statsObj->table = 'stats_domains';
    $statsObj->count = 'domainCount';
    $statsObj->yLabel = 'Domains Online';
} else {
    $statsObj->table = 'stats_users';
    $statsObj->count = 'userCount';
    $statsObj->yLabel = 'Users Online';
}

//Set content header to JSON, so formatting by the browser is done right.
header('Content-Type: application/json');
$mysqli = new mysqli('127.0.0.1', 'hifi', '123456', 'hifi_stats');
if ($mysqli->connect_errno) {
    respond404AndExit();
}
//WHERE WEEKOFYEAR(time) = WEEKOFYEAR(NOW())
$sql = 'SELECT time, '.$statsObj->{'count'}.' FROM '.$statsObj->{'table'}.' ORDER BY id ASC';
if (!$result = $mysqli->query($sql)) {
    respond404AndExit();
}

while ($count = $result->fetch_assoc()) {
    $rowsArray[] = ['c' => [['v' => 'Date('.strtotime($count['time']) * 1000 .')'], ['v' => (int) $count[$statsObj->{'count'}]]]];
}
$colsArray[] = ['label' => 'Time', 'type' => 'datetime'];
$colsArray[] = ['label' => $statsObj->{'yLabel'}, 'type' => 'number'];
echo json_encode(['cols' => $colsArray, 'rows' => $rowsArray]);
exitPhp();
