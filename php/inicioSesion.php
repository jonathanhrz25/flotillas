<?php
session_name('Flotillas');
session_start();
require 'connect.php';

$conn = connectPdo();

if (isset($_SESSION['user_id'])) {
    header("Location: ./principal.php");
    exit();
}

$message = '';

if (!empty($_POST['usuario']) && !empty($_POST['password'])) {
    $records = $conn->prepare('SELECT * FROM usuarios WHERE usuario = :usuario');
    $records->bindParam(':usuario', $_POST['usuario']);
    $records->execute();
    $results = $records->fetch(PDO::FETCH_ASSOC);

    if ($results && password_verify($_POST['password'], $results['password'])) {
        $_SESSION['user_id'] = $results['id'];
        $_SESSION['usuario'] = $results['usuario'];
        $_SESSION['rol'] = $results['rol'];
        $_SESSION['cedis'] = isset($results['cedis']) ? $results['cedis'] : null;
        header("Location: ../php/principal.php");
        exit();
    } elseif (empty($results)) {
        $message = 'Lo sentimos, no hemos encontrado el usuario ingresado en nuestra base de datos.';
    } else {
        $message = 'Lo sentimos, esas credenciales no coinciden.';
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
    <title>Flotillas</title>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <style>
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

        .login-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 30px 25px;
            width: 100%;
            max-width: 360px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            text-align: center;
            color: white;
            animation: fadeIn 1s ease;
        }

        .logo {
            max-width: 300px;
            margin-bottom: 20px;
        }

        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group label {
            display: block;
            font-size: 20px;
            margin-bottom: 6px;
        }

        .input-group input {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            background: #ffffffcc;
            color: #002e99;
            outline: none;
        }

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

        .footer-container {
            background-color: transparent;
            /* Fondo transparente */
            padding: 10px 0;
            /* Ajuste del padding */
            position: relative;
        }

        .footer-container .footer-text {
            color: rgb(8, 24, 86);
            /* Color de texto azul */
        }

        @media (min-width: 992px) {
            .footer-content .container {
                max-width: 90%;
            }

            .footer-content .row {
                align-items: center;
            }

            .footer-content .col-md-2,
            .footer-content .col-md-5 {
                text-align: center;
            }

            .footer-content .col-md-5 {
                text-align: left;
            }
        }

        @media screen and (max-width: 480px) {
            .login-container {
                padding: 25px 20px;
            }

            .login-container h2 {
                font-size: 20px;
            }

            .input-group input {
                font-size: 15px;
            }

            .btn {
                font-size: 15px;
            }
        }

        /* AGREGA ESTO EN TU <style> */
        .content-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-top: -100px;
            /* Ajusta el valor si quieres subirlo más o menos */
        }

        .register-link {
            font-size: 16px;
            color: #ffffff;
            cursor: pointer;
            /* Asegura que el texto esté centrado */

        }

        /* Efecto hover más marcado */
        .register-link:hover {
            color: #2cf1f8ff;
            /* Cambia el color al pasar el ratón */
        }
    </style>
</head>

<body>
    <div class="content-wrapper">
        <lottie-player src="https://assets10.lottiefiles.com/packages/lf20_jcikwtux.json" background="transparent"
            speed="1" style="width: 250px; height: 250px;" autoplay loop>
        </lottie-player>

        <div class="login-container">
            <img src="../img/loguito2.png" alt="Logo Automotriz Serva" class="logo" />

            <?php if (!empty($message)): ?>
                <div style="background: rgba(255,0,0,0.2); padding: 10px; border-radius: 10px; margin-bottom: 15px;">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form action="inicioSesion.php" method="POST">
                <div class="input-group">
                    <label for="usuario">Usuario:</label>
                    <input type="text" name="usuario" placeholder="Ingresa tu usuario" required />
                </div>

                <div class="input-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" name="password" placeholder="Ingresa tu contraseña" required />
                </div>

                <button type="submit" class="btn">Entrar</button>
            </form>
        </div>
    </div>

    <footer class="fixed-bottom">
        <div class="footer-container">
            <div class="text-center footer-text text-white">
                © 2025 Copyright:
                <label>Automotriz Serva</label>
            </div>
        </div>
    </footer>
</body>


</html>