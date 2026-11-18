<?php

ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_OFF);

$databaseHost = getenv('DB_SERVER') ?: 'db:3306';
$databaseName = getenv('DB_DATABASE') ?: 'opendoors';
$databaseUser = getenv('DB_USER') ?: 'opendoors';
$databasePassword = getenv('DB_PASSWORD') ?: '';

$connection = @new mysqli(
    $databaseHost,
    $databaseUser,
    $databasePassword,
    $databaseName,
);

$healthy = $connection->connect_errno === 0 && $connection->query('SELECT 1') !== false;
$connection->close();

http_response_code($healthy ? 200 : 503);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

echo $healthy ? "ok\n" : "unavailable\n";
