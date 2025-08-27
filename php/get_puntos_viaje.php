<?php
require 'connect.php';
$conn = connectMysqli();

$viaje_id = $_GET['viaje_id'] ?? 0;

$sql = "SELECT pedido, cliente, ubicacion FROM data_form WHERE viaje_id = " . intval($viaje_id);
$result = $conn->query($sql);

$datos = [];
while ($row = $result->fetch_assoc()) {
    $datos[] = $row;
}

header('Content-Type: application/json');
echo json_encode($datos);
