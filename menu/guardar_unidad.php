<?php
session_name('Flotillas');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/inicioSesion.php");
    exit();
}

require '../php/connect.php';
$conn = connectMysqli();

$usuario = $_SESSION['usuario'] ?? '';

// Obtener CEDIS del usuario actual
$query = $conn->prepare("SELECT cedis FROM usuarios WHERE usuario = ?");
$query->bind_param("s", $usuario);
$query->execute();
$query->bind_result($cedis);
$query->fetch();
$query->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modelo = $conn->real_escape_string($_POST['modelo']);
    $placa = $conn->real_escape_string($_POST['placa']);

    $stmt = $conn->prepare("INSERT INTO unidad (modelo, placa, cedis) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $modelo, $placa, $cedis);

    if ($stmt->execute()) {
        header("Location: ../php/principal.php");
        exit();
    } else {
        echo "Error al guardar la unidad: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
