<?php
session_name('Flotillas');
session_start();

require '../php/connect.php';

// Verificamos que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/inicioSesion.php");
    exit();
}

// Obtener los datos del formulario
$num_cliente = $_POST['num_cliente'] ?? '';
$cliente     = strtoupper($_POST['cliente'] ?? '');
$telefono    = $_POST['telefono'] ?? '';
$ubicacion   = $_POST['ubicacion'] ?? '';
$lat         = isset($_POST['lat']) ? floatval($_POST['lat']) : 0;
$lng         = isset($_POST['lng']) ? floatval($_POST['lng']) : 0;

// Validar que los campos no estén vacíos
if (empty($num_cliente) || empty($cliente) || empty($ubicacion) || $lat == 0 || $lng == 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Todos los campos son obligatorios.'
    ]);
    exit();
}

// Conexión a la base de datos
$conn = connectMysqli();

$query = "INSERT INTO direccion (num_cliente, cliente, telefono, ubicacion, lat, lng) 
          VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error preparando la consulta: ' . $conn->error
    ]);
    exit();
}

// Bind con tipos correctos
$stmt->bind_param("ssssdd", $num_cliente, $cliente, $telefono, $ubicacion, $lat, $lng);

// Ejecutar la consulta
if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Datos guardados correctamente',
        'redirect' => 'http://localhost/flotillas/php/principal.php'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al guardar: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
