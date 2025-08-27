<?php
session_name('Flotillas');
session_start();
require("../php/connect.php");

$conn = connectMysqli();

// Solo permitir si hay un ID válido
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_usuario = $_GET['id'];

    // Prepara y ejecuta la eliminación
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id_usuario);

    if ($stmt->execute()) {
        // Redirigir de vuelta con éxito y con el parámetro 'deleted=true'
        header("Location: ../menu/usuarios.php?deleted=true");
        exit();
    } else {
        echo "Error al eliminar el usuario: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "ID de usuario no válido.";
}
?>
