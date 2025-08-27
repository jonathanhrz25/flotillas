<?php
header('Content-Type: application/json');
session_name('Flotillas');
session_start();

require '../php/connect.php';
$conn = connectMysqli();

// Verificar sesión activa
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado']);
    exit;
}

// Verificar que el usuario sea TI
if ($_SESSION['rol'] !== 'TI') {
    echo json_encode(['status' => 'error', 'message' => 'Permisos insuficientes para cerrar el viaje.']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Validación del viaje_id
if (!isset($_POST['viaje_id']) || !is_numeric($_POST['viaje_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID de viaje inválido']);
    exit;
}

$viaje_id = (int) $_POST['viaje_id'];

// Validar si todos los pedidos del viaje están entregados
$check_sql = "SELECT COUNT(*) AS pendientes FROM data_form WHERE viaje_id = ? AND entregado != 'Pedido Entregado' AND eliminado = 0";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $viaje_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$row = $check_result->fetch_assoc();

if ($row['pendientes'] > 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se puede cerrar el viaje hasta que todos los pedidos estén entregados.'
    ]);
    exit;
}
$check_stmt->close();

// Cerrar el viaje si todo fue entregado
$sql = "UPDATE viajes SET estado = 'cerrado', fecha_fin = NOW(), cerrado_por = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $viaje_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Viaje cerrado correctamente']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al cerrar el viaje']);
}

$stmt->close();
$conn->close();
