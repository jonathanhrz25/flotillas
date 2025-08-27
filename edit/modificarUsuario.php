<?php
session_name('Flotillas');
session_start();
require("../php/connect.php");

$conn = connectMysqli();

// Verifica sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/inicioSesion.php");
    exit();
}

// Verifica si hay ID proporcionado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID no proporcionado o inválido.";
    exit;
}

$id = intval($_GET['id']);

// Obtener datos actuales del usuario
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo "Usuario no encontrado.";
    exit;
}

$mostrar = $result->fetch_assoc();

// Si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validación de campos
    if ($usuario === '') {
        echo "El campo Usuario es obligatorio.";
        exit;
    }

    // Validar la contraseña
    if ($password !== '' || $confirm_password !== '') {
        if ($password === '') {
            echo "Si deseas cambiar la contraseña, el campo 'Contraseña Nueva' no puede estar vacío.";
            exit;
        }
        if ($confirm_password === '') {
            echo "El campo 'Confirmar Contraseña' es obligatorio si has ingresado una nueva contraseña.";
            exit;
        }
        if ($password !== $confirm_password) {
            echo "Las contraseñas no coinciden.";
            exit;
        }
        // Si las contraseñas coinciden, actualizamos la contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
    }

    // Preparamos la consulta de actualización
    if ($password === '' && $confirm_password === '') {
        // Si no se cambió la contraseña, solo actualizamos el usuario
        $update_sql = "UPDATE usuarios SET usuario = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $usuario, $id);
    } else {
        // Si se cambió la contraseña, actualizamos también la contraseña
        $update_sql = "UPDATE usuarios SET usuario = ?, password = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $usuario, $password_hash, $id);
    }

    if ($update_stmt->execute()) {
        echo "<script>alert('Cambios realizados correctamente.'); window.location.href='../menu/usuarios.php';</script>";
        exit;
    } else {
        echo "Error al actualizar: " . $conn->error;
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="../img/icono2.png" type="image/x-icon">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <title>Modificar Usuario</title>

    <script>
        // JavaScript para verificar que si se ingresa la contraseña, se requiera la confirmación
        $(document).ready(function() {
            $('#password').on('input', function() {
                var passwordValue = $(this).val();
                if (passwordValue !== '') {
                    $('#confirm_password').prop('required', true);
                } else {
                    $('#confirm_password').prop('required', false);
                }
            });
        });
    </script>
</head>

<header>
    <nav class="navbar navbar-left" style="background-color: #081856!important; text-align: left;" role="navigation">
        <div class="container-fluid">
            <a class="navbar-brand d-flex flex-row" href="../menu/usuarios.php">
                <img src="../img/loguito2.png" alt="" height="45" class="d-inline-block align-text-top">
            </a>
        </div>
    </nav>
</header>

<body>
    <div class="container col-8 mt-5"><br>
        <h1 class="display-6">Modificar Usuario</h1>
        <form action="" method="POST">

            <input type="hidden" name="id" value="<?= $mostrar['id'] ?>">

            <div class="form-group">
                <label for="usuario">Usuario:</label>
                <input type="text" class="form-control" name="usuario" value="<?= $mostrar['usuario'] ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña Nueva:</label>
                <input type="password" name="password" class="form-control" id="password" placeholder="Dejar en blanco si no se cambia">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña:</label>
                <input type="password" name="confirm_password" class="form-control" id="confirm_password" placeholder="Confirme la nueva contraseña">
            </div>

            <div class="text-center"><br>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div><br><br><br>
        </form>
    </div>

    <?php include '../css/footer.php' ?>
</body>

</html>
