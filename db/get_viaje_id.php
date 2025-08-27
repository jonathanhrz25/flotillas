<?php
session_name('Flotillas');
session_start();
require '../php/connect.php';

header('Content-Type: application/json');
$conn = connectMysqli();

$operador = $_POST['operador'] ?? '';
$unidad = $_POST['unidad'] ?? '';
$cedis = $_SESSION['cedis'] ?? null;

if (empty($operador) || empty($unidad) || empty($cedis)) {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
    exit;
}

// Buscar si hay un viaje abierto
$sql = "SELECT id, unidad FROM viajes WHERE operador = ? AND estado = 'abierto' ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $operador);
$stmt->execute();
$result = $stmt->get_result();
$viaje = $result->fetch_assoc();

if ($viaje) {
    // Solo informamos que existe, no creamos uno nuevo
    echo json_encode([
        'status' => 'existing',
        'viaje_id' => $viaje['id'],
        'message' => "Tienes un viaje abierto (#{$viaje['id']}) en la unidad “{$viaje['unidad']}”."
    ]);
    exit;
}

// No hay viaje abierto, creamos uno nuevo
$insert = "INSERT INTO viajes (operador, unidad, fecha_inicio, estado, cedis) VALUES (?, ?, NOW(), 'abierto', ?)";
$stmt2 = $conn->prepare($insert);
$stmt2->bind_param("sss", $operador, $unidad, $cedis);

if ($stmt2->execute()) {
    echo json_encode([
        'status' => 'success',
        'viaje_id' => $stmt2->insert_id
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No se pudo crear el viaje']);
}

$stmt->close();
$stmt2->close();
$conn->close();
