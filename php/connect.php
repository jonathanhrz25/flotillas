<?php
function connectMysqli() {
    $server = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'flotillas';

    $conn = new mysqli($server, $username, $password, $database);
    $conn->set_charset("utf8");

    if ($conn->connect_errno) {
        die('Connection Failed: ' . $conn->connect_errno . ", " . $conn->connect_error);
    } else {
        // echo "conexion exitosa (MySQLi)";
        return $conn;
    }
}

function connectPdo() {
    $server = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'flotillas';

    try {
        $conn = new PDO("mysql:host=$server;dbname=$database;", $username, $password);
        // echo "conexion exitosa (PDO)";
        return $conn;
    } catch (PDOException $e) {
        die('Connection Failed: ' . $e->getMessage());
    }
}
?>
