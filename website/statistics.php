<?php

require 'common.php';
ini_set('log_errors', 1);
ini_set('error_log', 'logs/statistics.log');

class Response
{
    public $fewestUsersDate;
    public $fewestUsersCount;
    public $mostUsersDate;
    public $mostUsersCount;
    public $fewestDomainsDate;
    public $fewestDomainsCount;
    public $mostDomainsDate;
    public $mostDomainsCount;
}

function queryFewestUsers($sql, $result, $mysqli)
{
    global $response;
    $sql = 'SELECT time, userCount FROM stats_users WHERE userCount = (SELECT MIN(userCount) FROM stats_users)';
    if (!$result = $mysqli->query($sql)) {
        respond404AndExit();
    }
    if ($result->num_rows > 0) {
        $count = $result->fetch_assoc();
        $response->{'fewestUsersDate'} = $count['time'];
        $response->{'fewestUsersCount'} = (int) $count['userCount'];
    } else {
        respond404AndExit();
    }
}

function queryMostUsers($sql, $result, $mysqli)
{
    global $response;
    $sql = 'SELECT time,userCount FROM stats_users WHERE userCount = (SELECT MAX(userCount) FROM stats_users)';
    if (!$result = $mysqli->query($sql)) {
        respond404AndExit();
    }
    if ($result->num_rows > 0) {
        $count = $result->fetch_assoc();
        $response->{'mostUsersDate'} = $count['time'];
        $response->{'mostUsersCount'} = (int) $count['userCount'];
    } else {
        respond404AndExit();
    }
}

function queryFewestDomains($sql, $result, $mysqli)
{
    global $response;
    $sql = 'SELECT time,domainCount FROM stats_domains WHERE domainCount = (SELECT MIN(domainCount) FROM stats_domains)';
    if (!$result = $mysqli->query($sql)) {
        respond404AndExit();
    }
    if ($result->num_rows > 0) {
        $count = $result->fetch_assoc();
        $response->{'fewestDomainsDate'} = $count['time'];
        $response->{'fewestDomainsCount'} = (int) $count['domainCount'];
    } else {
        respond404AndExit();
    }
}

function queryMostDomains($sql, $result, $mysqli)
{
    global $response;
    $sql = 'SELECT time,domainCount FROM stats_domains WHERE domainCount = (SELECT MAX(domainCount) FROM stats_domains)';
    if (!$result = $mysqli->query($sql)) {
        respond404AndExit();
    }
    if ($result->num_rows > 0) {
        $count = $result->fetch_assoc();
        $response->{'mostDomainsDate'} = $count['time'];
        $response->{'mostDomainsCount'} = (int) $count['domainCount'];
    } else {
        respond404AndExit();
    }
}

$response = new Response();
header('Content-Type: application/json');
$mysqli = new mysqli('127.0.0.1', 'hifi', '123456', 'hifi_stats');
if ($mysqli->connect_errno) {
    respond404AndExit();
}

queryFewestUsers($sql, $result, $mysqli);
queryMostUsers($sql, $result, $mysqli);
queryFewestDomains($sql, $result, $mysqli);
queryMostDomains($sql, $result, $mysqli);

echo json_encode(['users' => ['least' => ['time' => $response->{'fewestUsersDate'}, 'count' => $response->{'fewestUsersCount'}], 'most' => ['time' => $response->{'mostUsersDate'}, 'count' => $response->{'mostUsersCount'}]], 'domains' => ['least' => ['time' => $response->{'fewestDomainsDate'}, 'count' => $response->{'fewestDomainsCount'}], 'most' => ['time' => $response->{'mostDomainsDate'}, 'count' => $response->{'mostDomainsCount'}]]]);
exitPhp();
