<?php
session_name('Flotillas');
session_start();
require '../php/connect.php';

$conn = connectMysqli();

$usuario = $_SESSION['usuario'] ?? '';
$rol = $_SESSION['rol'] ?? '';

$data = [];

if ($rol === 'TI') {
    $sql = "
        SELECT v.id AS viaje_id, v.operador, COUNT(df.id) AS total_pedidos
        FROM viajes v
        LEFT JOIN data_form df ON v.id = df.viaje_id
        GROUP BY v.id
        ORDER BY v.id ASC
    ";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "
        SELECT v.id AS viaje_id, v.operador, COUNT(df.id) AS total_pedidos
        FROM viajes v
        LEFT JOIN data_form df ON v.id = df.viaje_id
        WHERE v.operador = ?
        GROUP BY v.id
        ORDER BY v.id ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
