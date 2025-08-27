<?php
// Mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

session_start();
require '../php/connect.php';

$conn = connectMysqli();

// Recibir variables
$fecha       = $_POST['fecha'] ?? '';
$operador    = $_POST['operador'] ?? '';
$unidad      = $_POST['unidad'] ?? '';
$pedido      = $_POST['pedido'] ?? '';
$cajas       = $_POST['cajas'] ?? '';
$lista_cajas_json = $_POST['lista-cajas'] ?? '';
$viaje_id    = $_POST['viaje_id'] ?? '';

$num_cliente = $_POST['num_cliente'] ?? '';
$cliente     = $_POST['cliente'] ?? '';
$ubicacion = $_POST['ubicacion'] ?? '';
$location  = $_POST['location'] ?? '';
$km        = $_POST['km'] ?? '';
$cedis       = $_POST['cedis'] ?? '';
if (empty($cedis)) $errores[] = 'cedis';

$errores = [];

// Validar campos obligatorios
if (empty($fecha))             $errores[] = 'fecha';
if (empty($operador))          $errores[] = 'operador';
if (empty($unidad))            $errores[] = 'unidad';
if (empty($pedido))            $errores[] = 'pedido';
if (empty($cajas))             $errores[] = 'cajas';
if (empty($lista_cajas_json))  $errores[] = 'lista-cajas';
if (empty($viaje_id))          $errores[] = 'viaje_id';
if (empty($num_cliente))       $errores[] = 'num_cliente';
if (empty($cliente))           $errores[] = 'cliente';
if (empty($ubicacion)) $errores[] = 'ubicacion';
if (empty($location))  $errores[] = 'location';
if (empty($km))        $errores[] = 'km';

if (!empty($errores)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Faltan campos obligatorios: ' . implode(', ', $errores)
    ]);
    exit;
}

// Decodificar lista de cajas
$lista_cajas_array = json_decode($lista_cajas_json, true);
if (!is_array($lista_cajas_array)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Lista de cajas inválida.'
    ]);
    exit;
}

$lista_cajas = implode(',', $lista_cajas_array);

// Insertar en la base de datos
$sql = "INSERT INTO data_form (
    fecha, operador, unidad, pedido, cajas, lista_cajas, viaje_id,
    num_cliente, cliente, cedis,
    ubicacion, location, km
)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al preparar SQL: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param("sssssssssssss", 
    $fecha, $operador, $unidad, $pedido, $cajas, $lista_cajas, $viaje_id,
    $num_cliente, $cliente, $cedis,
    $ubicacion, $location, $km
);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Datos guardados correctamente',
        'redirect' => '../menu/carga.php'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al guardar los datos: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
