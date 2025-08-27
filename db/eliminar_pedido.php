<?php
session_name('Flotillas');
session_start();
require '../php/connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
    exit;
}

$pedido = $_POST['pedido'] ?? '';
$motivo = $_POST['motivo'] ?? '';

if (empty($pedido)) {
    echo json_encode(['status' => 'error', 'message' => 'Falta el número de pedido']);
    exit;
}

if (empty($motivo)) {
    echo json_encode(['status' => 'error', 'message' => 'Debe ingresar un motivo de eliminación']);
    exit;
}

$conn = connectMysqli();

// Verificar si el pedido existe
$check = $conn->prepare("SELECT id FROM data_form WHERE pedido = ?");
$check->bind_param("s", $pedido);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'El pedido no existe']);
    exit;
}

// Actualizar el estado a eliminado
$sql = "UPDATE data_form SET eliminado = 1, motivo_eliminacion = ? WHERE pedido = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $motivo, $pedido);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Pedido marcado como eliminado']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al marcar el pedido como eliminado']);
}

$stmt->close();
$conn->close();
