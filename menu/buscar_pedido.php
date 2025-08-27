<?php
include '../php/connect.php';
header('Content-Type: application/json');

$conn = connectMysqli();

$pedido = $_GET['pedido'] ?? '';

if (empty($pedido)) {
    echo json_encode(['status' => 'error', 'message' => 'Pedido no proporcionado']);
    exit;
}

$sql = "SELECT fecha, operador, unidad, cajas, lista_cajas FROM data_form WHERE pedido = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $pedido);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $datos = $result->fetch_assoc();
    echo json_encode([
        'status' => 'success',
        'data' => $datos
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Pedido no encontrado']);
}

$stmt->close();
$conn->close();
?>
