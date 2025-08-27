<?php
require '../php/connect.php';

if (!isset($_GET['num_cliente'])) {
    echo json_encode(['error' => 'Falta el número de cliente']);
    exit;
}

$input = trim($_GET['num_cliente']);
$conn = connectMysqli();

$sql = "SELECT nombre_cliente FROM clientes WHERE num_cliente LIKE ?";
$stmt = $conn->prepare($sql);

$like = '%' . ltrim($input, '0');
$stmt->bind_param("s", $like);
$stmt->execute();
$stmt->bind_result($nombre);
$stmt->fetch();
$stmt->close();
$conn->close();

if ($nombre) {
    echo json_encode(['nombre' => $nombre]);
} else {
    echo json_encode(['error' => 'Cliente no encontrado']);
}
