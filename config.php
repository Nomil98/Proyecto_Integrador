<?php
session_start();

$credentials = json_decode(file_get_contents('credentials.json'))->development;

require 'Database.php';

Database::$host = $credentials->host;
Database::$password = $credentials->pass;
Database::$username = $credentials->user;
Database::$database = $credentials->name;
Database::$port = $credentials->port;
?>