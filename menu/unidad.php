<?php
session_name('Flotillas');
session_start();

require '../php/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/inicioSesion.php");
    exit();
}

$conn = connectMysqli();

$usuario = $_SESSION['usuario'] ?? '';
$rol = $_SESSION['rol'] ?? '';

// Obtener el CEDIS y ID del usuario logueado
$stmtCedis = $conn->prepare("SELECT cedis, id FROM usuarios WHERE usuario = ?");
$stmtCedis->bind_param("s", $usuario);
$stmtCedis->execute();
$stmtCedis->bind_result($cedis_usuario, $user_id);
$stmtCedis->fetch();
$stmtCedis->close();

// Obtener operadores con el mismo CEDIS
$stmtOperadores = $conn->prepare("SELECT usuario FROM usuarios WHERE cedis = ?");
$stmtOperadores->bind_param("s", $cedis_usuario);
$stmtOperadores->execute();
$operadores_result = $stmtOperadores->get_result();
$stmtOperadores->close();

// Obtener unidades dependiendo del rol y ID del usuario
if ($user_id == 1) {
    // Si el usuario es el con ID 1, puede ver todas las unidades
    $stmt = $conn->prepare("SELECT * FROM unidad");
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // TI o cualquier otro rol ve solo las unidades de su mismo CEDIS
    $stmt = $conn->prepare("SELECT * FROM unidad WHERE cedis = ?");
    $stmt->bind_param("s", $cedis_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
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
    <title>Unidades</title>
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
    </style>
</head>

<body style="padding-top: 90px;">

    <?php if (isset($_GET['deleted'])): ?>
        <div id="alerta-usuario-eliminado" class="alert alert-success alert-dismissible fade show" role="alert"
            style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            ✅ Unidad eliminada correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <main class="main-content" id="mainContent">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Unidades Registradas</h3>
                <?php if ($rol === 'TI'): ?>
                    <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalAgregarUnidad">
                        <i class="bi bi-plus-circle"></i> Añadir Unidad
                    </button>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead">
                        <tr>
                            <th>ID</th>
                            <th>MODELO</th>
                            <th>PLACA</th>
                            <?php if ($user_id == 1) { ?> <!-- Verificación si el usuario es el con ID 1 -->
                                <th>CEDIS</th>
                            <?php } ?>
                            <?php if ($rol !== 'Operador'): ?>
                                <th>Modificar</th>
                                <th>Eliminar</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Obtener la información de las unidades
                        while ($row = $result->fetch_assoc()) {
                            ?>
                            <tr>
                                <td><?= $row['Id']; ?></td>
                                <td><?= htmlspecialchars($row['modelo']); ?></td>
                                <td><?= htmlspecialchars($row['placa']); ?></td>
                                <?php if ($user_id == 1): ?>
                                    <td><?= htmlspecialchars($row['cedis']); ?></td>
                                <?php endif; ?>
                                <?php if ($rol !== 'Operador'): ?>
                                    <td class="text-center">
                                        <a href="../edit/modificarUnidad.php?id=<?= urlencode($row['Id']); ?>">
                                            <i class="fa fa-pencil-square-o fa-2x"></i>
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <a href="../edit/eliminarUnidad.php?id=<?= urlencode($row['Id']); ?>"
                                            onclick="return confirm('¿Estás seguro de que deseas eliminar esta unidad?')">
                                            <i class="fa fa-trash-o fa-2x"></i>
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <?php if ($rol === 'TI'): ?>
        <!-- Modal -->
        <div style="padding-top: 90px;" class="modal fade" id="modalAgregarUnidad" tabindex="-1"
            aria-labelledby="modalAgregarUnidadLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="../menu/guardar_unidad.php" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAgregarUnidadLabel">Añadir Nueva Unidad</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="modelo" class="form-label">Modelo</label>
                            <input type="text" class="form-control" name="modelo" id="modelo"
                                placeholder="Ingresa el modelo de la unidad" required>
                        </div>
                        <div class="mb-3">
                            <label for="placa" class="form-label">Placa</label>
                            <input type="text" class="form-control" name="placa" id="placa"
                                placeholder="Ingresa las placas de la unidad" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../js/main.js"></script>

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

    <?php include '../css/footer.php'; ?>

</body>

</html>