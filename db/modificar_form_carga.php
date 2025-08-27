<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

session_start();
require '../php/connect.php';

$conn = connectMysqli();

// Recibir las variables
$pedido = $_POST['pedido'] ?? '';
$ubicacion = $_POST['ubicacion'] ?? '';  // Coordenadas recibidas (lat, lng)
$km = $_POST['km'] ?? '';  // Kilómetros
$location = $_POST['location'] ?? '';  // Dirección Cliente (ahora es "location")

// Verificación de campos obligatorios
if (empty($pedido) || empty($ubicacion) || empty($km) || empty($location)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Faltan campos obligatorios: pedido, ubicación, km, location'
    ]);
    exit;
}

// Preparar la consulta SQL para actualizar la base de datos
$stmt = $conn->prepare("UPDATE data_form SET ubicacion = ?, km = ?, location = ? WHERE pedido = ?");
if (!$stmt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al preparar la consulta: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param("sdss", $ubicacion, $km, $location, $pedido);

// Ejecutar la actualización
if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Ubicación, kilometros y Dirección actualizados correctamente',
        'redirect' => '../php/principal.php'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al actualizar: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
