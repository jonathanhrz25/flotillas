<?php
session_name('Flotillas');
session_start();

require '../php/connect.php'; // Asegúrate de que esta ruta sea la correcta para tu archivo de conexión

// Verificamos que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/inicioSesion.php");
    exit();
}

// Obtener los datos del formulario
$num_cliente = $_POST['num_cliente'] ?? '';
$cliente = strtoupper($_POST['cliente']);
$telefono = $_POST['telefono'] ?? '';
$ubicacion = $_POST['ubicacion'] ?? '';
$lat = $_POST['lat'] ?? '';
$lng = $_POST['lng'] ?? '';

// Validar que los campos no estén vacíos
if (empty($num_cliente) || empty($cliente) || empty($ubicacion) || empty($lat) || empty($lng)) {
    // Si hay campos vacíos, enviar un mensaje de error en JSON
    echo json_encode([
        'status' => 'error',
        'message' => 'Todos los campos son obligatorios.'
    ]);
    exit();
}

// Conexión a la base de datos
$conn = connectMysqli();

// Consulta para insertar la nueva dirección
$query = "INSERT INTO direccion (num_cliente, cliente, telefono, ubicacion, lat, lng) 
          VALUES (?, ?, ?, ?, ?, ?)";

// Preparar la sentencia SQL
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparando la consulta: " . $conn->error);
}

// Bind de los parámetros
$stmt->bind_param("ssissd", $num_cliente, $cliente, $telefono, $ubicacion, $lat, $lng);

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
        'message' => 'Hubo un problema al guardar la dirección. Intenta nuevamente.'
    ]);
}

// Cerrar la conexión
$stmt->close();
$conn->close();
?>
