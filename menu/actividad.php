<?php
session_name('Flotillas'); // Debe ser el mismo nombre de sesión
session_start();

if (!isset($_SESSION['user_id'])) {
    // Si no hay sesión activa, redirige a la página de inicio de sesión
    header("Location: ../php/inicioSesion.php");
    exit();
}

// Si la sesión está activa, puedes mostrar el contenido de la página
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="../img/icono2.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <title>Actividad</title>
    <style>
        .nav-link {
            color: ;
        }
    </style>
</head>

<body>

    <body style="padding-top: 90px;"></body>

    <main class="main-content" id="mainContent">
        <div class="container">
            <div class="btn-group" role="group" aria-label="Basic mixed styles example">
                <button type="button" class="btn btn-danger">Left</button>
                <button type="button" class="btn btn-warning">Middle</button>
                <button type="button" class="btn btn-success">Right</button>
            </div>




        </div><br><br><br><br>
    </main>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../js/main.js"></script>

    <!-- <script>
        // Bloquear clic derecho y teclas específicas
        $(document).ready(function () {
            // Bloquear clic derecho
            $(document).bind("contextmenu", function (e) {
                e.preventDefault();
            });

            // Bloquear ciertas teclas (F12, Ctrl+U, Ctrl+Shift+I)
            $(document).keydown(function (e) {
                if (e.which === 123) { // F12
                    return false;
                }
                if (e.ctrlKey && (e.shiftKey && e.keyCode === 73)) { // Ctrl+Shift+I
                    return false;
                }
                if (e.ctrlKey && (e.keyCode === 85 || e.keyCode === 117)) { // Ctrl+U
                    return false;
                }
            });
        });
    </script> -->

    <?php include '../css/footer.php'; ?>

</body>

</html>