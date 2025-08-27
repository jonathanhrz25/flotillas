<?php
require '../php/connect.php';
$conn = connectMysqli();

if (!isset($_GET['num_cliente'])) {
    echo json_encode(['status' => 'error', 'message' => 'Número de cliente no proporcionado']);
    exit;
}

$num_cliente = $conn->real_escape_string($_GET['num_cliente']);
$sql = "SELECT * FROM direccion WHERE num_cliente = '$num_cliente'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo json_encode(['status' => 'exists']);
} else {
    echo json_encode(['status' => 'not_found']);
}
