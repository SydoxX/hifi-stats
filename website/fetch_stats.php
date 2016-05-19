<?php
class StatsObject
{
   public $table = string;
   public $count = string;
   public $yLabel = string;
}

$statsObj = new StatsObject();
if ($_GET['stats'] == "domains") {
    $statsObj->table = 'stats_domains';
    $statsObj->count = 'domainCount';
    $statsObj->yLabel = 'Domains Online';
} else {
    $statsObj->table = 'stats_users';
    $statsObj->count = 'userCount';
    $statsObj->yLabel = 'Users Online';
}

$mysqli = new mysqli('127.0.0.1', 'hifi', '123456', 'hifi_stats');
if ($mysqli->connect_errno) {
    echo "{}";
    exit;
}
//WHERE WEEKOFYEAR(time) = WEEKOFYEAR(NOW())
$sql = "SELECT time, " . $statsObj->{"count"} . " FROM " . $statsObj->{"table"}. " ORDER BY id ASC";
if (!$result = $mysqli->query($sql)) {
    echo "{}";
    exit;
}

while ($count = $result->fetch_assoc()) {
  $rowsArray[]=array('c' => array(array('v' => "Date(" . strtotime($count['time']) * 1000 . ")"), array('v' =>(int)$count[$statsObj->{"count"}])));
}
$colsArray[] = array("label" => "Time", "type" => "datetime");
$colsArray[] = array("label" => $statsObj->{"yLabel"}, "type" => "number");
echo json_encode(array('cols'=>$colsArray, 'rows' => $rowsArray));

$result->free();
$mysqli->close();
?>