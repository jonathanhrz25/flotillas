<?php
session_name('Flotillas');
session_start();

require '../php/connect.php';
$conn = connectMysqli();

// Verifica que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/inicioSesion.php");
    exit();
}

// Recupera el ID del usuario logueado
$user_id = $_SESSION['user_id'];

// Verifica si el usuario es el administrador (ID 1)
if ($user_id == 1) {
    // Si es el administrador, mostrar todos los usuarios
    $result = $conn->query("SELECT * FROM usuarios");
} else {

    // Muestra todos los usuarios respecto a Cedis
    /* $result = $conn->query("SELECT * FROM usuarios"); */

    // Si no es el administrador, mostrar solo los usuarios asociados a su CEDIS
    $cedis_usuario = $_SESSION['cedis']; // Asegúrate que 'cedis' esté disponible en la sesión
    $result = $conn->query("SELECT * FROM usuarios WHERE cedis = '$cedis_usuario'");
}

if (!$result) {
    die("Error en la consulta: " . $conn->error);
}
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
    <title>Usuarios</title>
    <style>
        .nav-link {
            color: ;
        }

        .table tbody tr:hover {
            background-color: #f5f5f5;
        }

        .table tfoot {
            background-color: #d9edf7;
            font-weight: bold;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        .table th,
        .table td {
            border: none;
        }

        .table th,
        .table td {
            padding: 15px;
            text-align: center;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f2f2f2;
        }

        .table-responsive {
            max-height: 500px;
            overflow-y: scroll;
        }

        .table thead th {
            position: sticky;
            top: 0;
            z-index: 100;
            background-color: #081856;
            color: white;
        }

        .modal-backdrop.show {
            background-color: rgba(0, 0, 0, 0.3);
            /* Cambia el valor de la opacidad aquí */
        }

        #alerta-usuario-eliminado {
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1050;
            width: 90%;
            max-width: 700px;
            text-align: center;
        }
    </style>
</head>

<header>
    <nav class="navbar navbar-left" style="background-color: #081856!important; text-align: left;" role="navigation">
        <div class="container-fluid">
            <a class="navbar-brand d-flex flex-row" href="../php/inicioSesion.php">
                <img src="../img/loguito2.png" alt="" height="45" class="d-inline-block align-text-top">
                <h7 class="display-7 ms-4" style="color: #ffffff!important; font-family: 'Montserrat', sans-serif;">
                </h7>
            </a>
        </div>
    </nav>
</header>

<body>

    <?php if (isset($_GET['deleted'])): ?>
        <div id="alerta-usuario-eliminado" class="alert alert-success alert-dismissible fade show" role="alert"
            style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            ✅ Usuario eliminado correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <main class="main-content" id="mainContent" style="padding-top: 20px;">
        <div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="display-6">Usuarios/Operadores</h1>
                <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalAgregarUsuarios">
                    <i class="bi bi-plus-circle"></i> Añadir Usuario
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead">
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Contraseña</th>
                            <th>Cedis</th>
                            <th>Modificar</th>
                            <th>Eliminar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()) { ?>
                            <tr>
                                <td><?= $row['id']; ?></td>
                                <td><?= htmlspecialchars($row['usuario']); ?></td>
                                <td>••••••••</td>
                                <td><?= htmlspecialchars($row['cedis']); ?></td>
                                <td class="text-center"><a href="../edit/modificarUsuario.php?id=<?= $row['id']; ?>"><i
                                            class="fa fa-pencil-square-o fa-2x"></i></a></td>
                                <td class="text-center"><a href="../edit/eliminarUsuario.php?id=<?= $row['id']; ?>"
                                        onclick="return confirm('¿Estás seguro?')"><i class="fa fa-trash-o fa-2x"></i></a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal -->
    <div class="modal fade" id="modalAgregarUsuarios" tabindex="-1" aria-labelledby="modalAgregarUsuariosLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="../db/guardar_usuario.php" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAgregarUsuariosLabel">Añadir Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="user_submit" value="1">
                    <input type="hidden" name="cedis" value="<?= htmlspecialchars($cedis_usuario); ?>">

                    <!-- Usuario -->
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuario</label>
                        <input name="usuario" type="text" id="usuario" class="form-control"
                            placeholder="Ingresa tu usuario" required>
                    </div>

                    <!-- Contraseña -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input name="password" type="password" id="password" class="form-control"
                            placeholder="Ingrese su contraseña" required>
                    </div>

                    <!-- Confirmar Contraseña -->
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                        <input name="confirm_password" type="password" id="confirm_password" class="form-control"
                            placeholder="Confirme su contraseña" required>
                    </div>

                    <!-- Área -->
                    <div class="mb-3">
                        <label for="area" class="form-label">Área del Usuario</label>
                        <input class="form-control" id="area" name="area" value="CEDIS" readonly>
                    </div>

                    <!-- Rol -->
                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol del Usuario</label>
                        <select name="rol" class="form-select" id="rol" required>
                            <option value="">Seleccione el Rol</option>
                            <option value="Operador">Operador</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div><br><br><br><br>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        // Ocultar la alerta automáticamente después de 5 segundos
        setTimeout(function () {
            const alerta = document.getElementById('alerta-usuario-eliminado');
            if (alerta) {
                alerta.classList.remove('show');
                alerta.classList.add('fade');
                setTimeout(() => alerta.remove(), 500); // Se elimina del DOM tras el fade
            }
        }, 3000);
    </script>

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