<?php
session_name('Flotillas');
session_start();

require '../php/connect.php';
$conn = connectMysqli();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/inicioSesion.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_submit'])) {
    // Recuperar los datos del formulario
    $usuario = mysqli_real_escape_string($conn, $_POST['usuario']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    $cedis = $_SESSION['cedis']; // Usar el CEDIS del usuario logueado
    $area = mysqli_real_escape_string($conn, $_POST['area']);
    $rol = mysqli_real_escape_string($conn, $_POST['rol']);

    // Validar las contraseñas
    if ($password !== $confirm_password) {
        echo "Las contraseñas no coinciden.";
        exit();
    }

    // Cifrar la contraseña
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insertar el nuevo usuario en la base de datos
    $query = "INSERT INTO usuarios (usuario, password, cedis, area, rol) VALUES ('$usuario', '$hashed_password', '$cedis', '$area', '$rol')";
    if ($conn->query($query)) {
        // Redirigir o mostrar un mensaje de éxito
        header("Location: ../menu/usuarios.php?msg=Usuario creado correctamente");
        exit();
    } else {
        echo "Error al guardar el usuario: " . $conn->error;
    }
}
?>
