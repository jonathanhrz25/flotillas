<?php
session_name('Flotillas');
session_start();

require '../php/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/inicioSesion.php");
    exit();
}

$conn = connectMysqli();
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: unidad.php");
    exit();
}

// Eliminar la unidad
$stmt = $conn->prepare("DELETE FROM unidad WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    header("Location: ../menu/unidad.php?deleted=true");
    exit();
} else {
    echo "Error al eliminar la unidad.";
}
?>
