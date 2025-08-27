<?php
require '../php/connect.php';
$conn = connectMysqli();

header('Content-Type: application/json');

if (!isset($_GET['num_cliente'])) {
    echo json_encode(['status' => 'error', 'message' => 'Número de cliente no proporcionado']);
    exit;
}

$num_cliente = $_GET['num_cliente'];

// Consulta la tabla 'direccion' para ese cliente
$stmt = $conn->prepare("SELECT Id, ubicacion, lat, lng FROM direccion WHERE num_cliente = ?");
$stmt->bind_param("s", $num_cliente);
$stmt->execute();
$result = $stmt->get_result();

$direcciones = [];

while ($row = $result->fetch_assoc()) {
    $direcciones[] = $row;
}

echo json_encode(['status' => 'success', 'direcciones' => $direcciones]);
