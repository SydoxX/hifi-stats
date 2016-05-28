<?php

function cleanupMySQL()
{
  global $result, $mysqli;
  $result->free();
  $mysqli->close();
}

function respond404AndExit()
{
  http_response_code(404);
  echo '{}';
  exitPhp();
}

function exitPhp()
{
  cleanupMySQL();
  exit;
}
