<?php
require 'connect.php';

// Usa la conexión con PDO
$conn = connectPdo();

// Inicializamos las variables de sesión
session_name('Flotillas');
session_start();

// Eliminar sesión de administrador para forzar la verificación cada vez
if (isset($_SESSION['admin_verified'])) {
    unset($_SESSION['admin_verified']);
}

$message1 = '';
$message2 = '';

// Verificar si se envió el formulario de administrador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_submit'])) {
    $adminUsuario = $_POST['admin_usuario'];
    $adminPassword = $_POST['admin_password'];

    // Verificar que el usuario con ID 1 sea el administrador
    $sql = "SELECT id, usuario, password FROM usuarios WHERE id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $adminCredentials = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($adminCredentials) {
        if (password_verify($adminPassword, $adminCredentials['password'])) {
            $_SESSION['admin_verified'] = true;
            $message1 = 'Credenciales de administrador verificadas correctamente.';
        } else {
            $message2 = 'Credenciales de administrador incorrectas';
        }
    } else {
        $message2 = 'No se encontró el usuario administrador';
    }
}

// Verificar si el administrador ya ha sido verificado
if (isset($_SESSION['admin_verified']) && $_SESSION['admin_verified']) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_submit'])) {
        if (!empty($_POST['usuario']) && !empty($_POST['password']) && !empty($_POST['rol']) && !empty($_POST['area'])) {
            if ($_POST['password'] === $_POST['confirm_password']) {

                // Insertar usuario en la tabla 'usuarios'
                $sql = "INSERT INTO usuarios (usuario, password, rol, area, cedis) VALUES (:usuario, :password, :rol, :area, :cedis)";
                $stmt = $conn->prepare($sql);

                $stmt->bindParam(':usuario', $_POST['usuario']);
                $hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->bindParam(':rol', $_POST['rol']);
                $stmt->bindParam(':area', $_POST['area']);
                $stmt->bindParam(':cedis', $_POST['cedis']);

                if ($stmt->execute()) {
                    $message1 = 'Nuevo usuario creado correctamente';
                } else {
                    $message2 = 'Error al crear el usuario';
                }
            } else {
                $message2 = 'Las contraseñas no coinciden.';
            }
        } else {
            $message2 = 'Por favor, complete todos los campos.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../img/icono2.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/./libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="./css/footer.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>
    <title>Sistemas Menú</title>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@lottiefiles/lottie-player@1.0.0/dist/lottie-player.js"></script>

    <style>
        /* Estilo general */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Rajdhani', sans-serif;
            background: linear-gradient(135deg, #0057e7, #002e99);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            overflow: hidden;
            flex-direction: column;
        }

        /* Formulario */
        .login-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 20px 25px;
            /* Reducir padding para hacerlo más compacto */
            width: 100%;
            max-width: 380px;
            /* Más pequeño en pantallas grandes */
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            text-align: center;
            color: white;
            animation: fadeIn 1s ease;
        }

        /* Reducir altura de los inputs */
        .input-group input {
            width: 100%;
            padding: 10px;
            /* Menor padding */
            border: none;
            border-radius: 10px;
            font-size: 16px;
            background: #ffffffcc;
            color: #002e99;
            outline: none;
        }

        /* Botón */
        .btn {
            background: #1696ffff;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn:hover {
            background: #2cf1f8ff;
        }

        /* Media Queries para hacer el diseño responsivo */
        @media (max-width: 768px) {
            .login-container {
                max-width: 95%;
                /* Asegura que el formulario no sea demasiado grande en pantallas medianas */
            }

            /* Reducir el tamaño de los inputs */
            .input-group input {
                font-size: 14px;
                /* Reducir tamaño de texto en dispositivos pequeños */
            }

            .btn {
                font-size: 14px;
                /* Reducir tamaño del botón */
            }

            .register-link {
                font-size: 14px;
                /* Ajustar tamaño del enlace */
            }
        }

        @media (max-width: 480px) {
            .login-container {
                max-width: 90%;
                /* Asegura que no se haga demasiado grande en móviles */
            }

            /* Reducir el tamaño de los inputs */
            .input-group input {
                font-size: 14px;
            }

            .btn {
                font-size: 14px;
            }

            .register-link {
                font-size: 14px;
            }
        }

        /* Efecto hover más marcado */
        .register-link:hover {
            color: #2cf1f8ff;
        }

        /* Footer */
        .footer-container {
            background-color: transparent;
            padding: 10px 0;
            position: relative;
        }

        .footer-text {
            color: rgb(8, 24, 86);
            font-size: 12px;
            text-align: center;
        }

        /* Ajustes generales para la animación */
        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: scale(0.95);
            }

            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .content-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-top: -100px;
        }
    </style>
</head>

<body>
    <div class="content-wrapper">
        <!-- Lottie Player (registro de usuario) -->
        <lottie-player src="https://assets10.lottiefiles.com/packages/lf20_jcikwtux.json" background="transparent"
            speed="2" style="width: 250px; height: 250px;" autoplay loop>
        </lottie-player>

        <div class="login-container" href="principal.php">
            <a class="navbar-brand text-white" href="principal.php">
                <img src="../img/loguito2.png" alt="" height="45" class="logo">
            </a>

            <h2> Registrar Usuario</h2>

            <!-- Mensajes -->
            <?php if (!empty($message1)): ?>
                <div style="background: rgba(0,255,0,0.2); padding: 10px; border-radius: 10px; margin-bottom: 15px;">
                    <?= $message1 ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($message2)): ?>
                <div style="background: rgba(255,0,0,0.2); padding: 10px; border-radius: 10px; margin-bottom: 15px;">
                    <?= $message2 ?>
                </div>
            <?php endif; ?>

            <?php if (!isset($_SESSION['admin_verified']) || !$_SESSION['admin_verified']): ?>
                <!-- Formulario admin -->
                <form action="registrar.php" method="POST">
                    <input type="hidden" name="admin_submit" value="1">
                    <div class="input-group">
                        <label for="admin_usuario">Usuario Administrador:</label>
                        <input name="admin_usuario" type="text" required>
                    </div>
                    <div class="input-group">
                        <label for="admin_password">Contraseña Administrador:</label>
                        <input name="admin_password" type="password" required>
                    </div><br>
                    <button type="submit" class="btn">Verificar Administrador</button>
                </form>
            <?php else: ?>
                <!-- Formulario de registro -->
                <form action="registrar.php" method="POST" onsubmit="return validateForm();">
                    <input type="hidden" name="user_submit" value="1">

                    <div class="input-group">
                        <label for="usuario">Usuario:</label>
                        <input name="usuario" type="text" required>
                    </div>

                    <div class="input-group">
                        <label for="password">Contraseña:</label>
                        <input id="password" name="password" type="password" required>
                    </div>

                    <div class="input-group">
                        <label for="confirm_password">Confirmar Contraseña:</label>
                        <input id="confirm_password" name="confirm_password" type="password" required>
                    </div>

                    <!-- Selección de Cedis -->
                    <div class="form-group">
                        <label for="cedis">Cedis:</label>
                        <select class="form-control" id="cedis" name="cedis" required>
                            <option value="">Seleccione el Cedis</option>
                            <option value="Pachuca">Pachuca</option>
                            <option value="Cancun">Cancun</option>
                            <option value="Chihuahua">Chihuahua</option>
                            <option value="Culiacan">Culiacan</option>
                            <option value="Cuernavaca">Cuernavaca</option>
                            <option value="Cordoba">Cordoba</option>
                            <option value="Guadalajara">Guadalajara</option>
                            <option value="Hermosillo">Hermosillo</option>
                            <option value="León">León</option>
                            <option value="Merida">Merida</option>
                            <option value="Monterrey">Monterrey</option>
                            <option value="Oaxaca">Oaxaca</option>
                            <option value="Puebla">Puebla</option>
                            <option value="Queretaro">Queretaro</option>
                            <option value="San_Luis">San Luis Potosi</option>
                            <option value="Tuxtla">Tuxtla Gutierrez</option>
                            <option value="Veracruz">Veracruz</option>
                            <option value="Villahermosa">Villahermosa</option>
                        </select>
                    </div>

                    <!-- Selección de Área -->
                    <div class="form-group">
                        <label for="area">Área:</label>
                        <select class="form-control" id="area" name="area" required>
                            <option value="">Seleccione el área del Usuario</option>
                            <option value="ADQUISICIONES">Adquisiciones</option>
                            <option value="ADMINISTRACION CEDIS">Administracion Cedis</option>
                            <option value="ADMINISTRACION REFACCIONARIA">Administracion Refaccionaria</option>
                            <option value="ALMACEN">Almacen</option>
                            <option value="CENTRO DE ATENCION AL CLIENTES">Centro de Atención al Cliente</option>
                            <option value="BODEGAS">Bodegas</option>
                            <option value="CEDIS">Cedis</option>
                            <option value="COMPRAS">Compras</option>
                            <option value="CONTABILIDAD">Contabilidad</option>
                            <option value="CREDITO Y COBRANZA">Credito y Cobranza</option>
                            <option value="DEVOLUCIONES">Devoluciones</option>
                            <option value="EMBARQUES">Embarques</option>
                            <option value="FACTURACION">Facturacion</option>
                            <option value="FINANZAS">Finanzas</option>
                            <option value="FLOTILLAS">Flotillas</option>
                            <option value="IFUEL">IFuel</option>
                            <option value="INVENTARIOS">Inventarios</option>
                            <option value="JURIDICO">Juridico</option>
                            <option value="MERCADOTECNIA">Mercadotecnia</option>
                            <option value="MODELADO DE PRODUCTOS">Modelado de Productos</option>
                            <option value="PICKING">Picking</option>
                            <option value="PRECIOS ESPECIALES">Precios Especiales</option>
                            <option value="RECURSOS HUMANOS">Recursos Humanos</option>
                            <option value="RECEPCION">Recepcion</option>
                            <option value="RECEPCION DE MATERIALES">Recepcion de Materiales</option>
                            <option value="REABASTOS">Reabastos</option>
                            <option value="SERVICIO MEDICO">Servicio Medico</option>
                            <option value="SISTEMAS">Sistemas</option>
                            <option value="SURTIDO CEDIS">Surtido Cedis</option>
                            <option value="TELEMARKETING">Telemarketing</option>
                            <option value="VIGILANCIA">Vigilancia</option>
                            <option value="VENTAS">Ventas</option>
                        </select>
                    </div>

                    <!-- Selección de Rol -->
                    <div class="form-group">
                        <label for="rol">Rol del Usuario:</label>
                        <select name="rol" class="form-control" id="rol" required>
                            <option value="">Seleccione el Rol del Usuario</option>
                            <option value="TI">TI</option>
                            <option value="Operador">Operador</option>
                        </select>
                    </div><br>

                    <button type="submit" class="btn">Registrar Usuario</button>
                </form>
            <?php endif; ?>

            <!-- Link para volver -->
            <!-- <div style="margin-top: 30px; text-align: center;">
                <a href="inicioSesion.php" class="register-link">¿Ya tienes cuenta? Inicia sesión aquí</a>
            </div> -->
        </div>
    </div>

    <!-- Footer -->
    <footer class="fixed-bottom">
        <div class="footer-container">
            <div class="text-center footer-text text-white">
                © 2025 Copyright:
                <label>Automotriz Serva</label>
            </div>
        </div>
    </footer>
</body>

<script>
    // Función de validación para confirmar contraseña
    function validateForm() {
        var password = document.getElementById("password").value;
        var confirmPassword = document.getElementById("confirm_password").value;

        if (password !== confirmPassword) {
            alert("Las contraseñas no coinciden. Por favor, verifica.");
            return false; // Evita que el formulario se envíe
        }
        return true;
    }
</script>

</html>