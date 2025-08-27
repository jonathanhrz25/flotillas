<?php
session_name('Flotillas');
session_start();

require '../php/connect.php';
$conn = connectMysqli();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/inicioSesion.php");
    exit();
}

if (!isset($conn)) {
    die("No se pudo establecer la conexión con la base de datos.");
}

// Verificar que el parámetro 'id' esté presente
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Validar que el ID sea un número entero
    if (!filter_var($id, FILTER_VALIDATE_INT)) {
        $_SESSION['message'] = "ID de dirección inválido.";
        $_SESSION['message_type'] = "danger";
        header("Location: ../php/principal.php");
        exit();
    }

    // Preparamos la consulta para eliminar la dirección
    $stmt = $conn->prepare("DELETE FROM direccion WHERE Id = ?");
    $stmt->bind_param("i", $id); // "i" significa que el parámetro es un entero (int)

    // Ejecutamos la consulta y verificamos si la eliminación fue exitosa
    if ($stmt->execute()) {
        $_SESSION['message'] = "Dirección eliminada correctamente.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error al eliminar la dirección. Inténtalo nuevamente.";
        $_SESSION['message_type'] = "danger";
    }

    // Redirigir a la sección direccionContent después de la eliminación
    header("Location: ../php/principal.php");
    exit();
} else {
    // Si no se pasa el 'id', redirigir a la página principal
    $_SESSION['message'] = "ID de dirección no especificado.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../php/principal.php");
    exit();
}
?>
