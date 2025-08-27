<?php
session_name('Flotillas');
session_start();

require '../php/connect.php';
$conn = connectMysqli();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/inicioSesion.php");
    exit();
}

$usuario = $_SESSION['usuario'] ?? '';
$rol = $_SESSION['rol'] ?? '';

// Obtener el CEDIS y el ID del usuario
$stmt = $conn->prepare("SELECT cedis, id FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$stmt->bind_result($cedis, $user_id);
$stmt->fetch();
$stmt->close();

// Obtener las unidades asociadas al CEDIS del usuario
$unidades_result = $conn->prepare("SELECT id, modelo, placa FROM unidad WHERE cedis = ?");
$unidades_result->bind_param("s", $cedis);
$unidades_result->execute();
$unidades = $unidades_result->get_result();
$unidades_result->close();

// Filtrar los registros de GAS según el rol
if ($user_id == 1) {
    // Ver todos los registros con JOIN a unidad
    $query = $conn->prepare("
        SELECT gas.*, unidad.modelo 
        FROM gas 
        JOIN unidad ON gas.unidad_id = unidad.id
    ");
} elseif ($rol === 'Operador') {
    // Solo registros del operador actual
    $query = $conn->prepare("
        SELECT gas.*, unidad.modelo 
        FROM gas 
        JOIN unidad ON gas.unidad_id = unidad.id
        WHERE gas.operador = ?
    ");
    $query->bind_param("s", $usuario);
} elseif ($rol === 'TI') {
    // Solo registros del CEDIS del usuario
    $query = $conn->prepare("
        SELECT gas.*, unidad.modelo 
        FROM gas 
        JOIN unidad ON gas.unidad_id = unidad.id
        WHERE gas.cedis = ?
    ");
    $query->bind_param("s", $cedis);
} else {
    // No mostrar registros
    $query = $conn->prepare("SELECT * FROM gas WHERE 1=0");
}

$query->execute();
$result = $query->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gas | Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>

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
    <main class="container">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Registros de Carga de Gas</h3>
            <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalAgregarGas">
                <i class="bi bi-plus-circle"></i> Añadir Registro
            </button>
        </div>

        <!-- Div para los registros (mantener visible después de agregar un nuevo registro) -->
        <div id="gasContent" class="tabContent">
            <div id="alertaContainer"></div>

            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Operador</th>
                            <th>Unidad</th>
                            <th>Tablero (Foto)</th>
                            <th>KM Salida</th>
                            <th>KM Carga (Foto)</th>
                            <th>KM Carga</th>
                            <th>Precio Litro</th>
                            <th>Litros</th>
                            <th>Total</th>
                            <?php if ($user_id == 1) { ?> <!-- Verificación si el usuario es el con ID 1 -->
                                <th>CEDIS</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody id="tablaGas">
                        <?php
                        // Obtener los registros de gas
                        while ($row = $result->fetch_assoc()) {
                            // Si el ID es 1, mostrar el CEDIS correspondiente
                            if ($user_id == 1) { // Verificamos si el usuario es el de ID 1
                                $cedis_query = $conn->prepare("SELECT cedis FROM usuarios WHERE usuario = ?");
                                $cedis_query->bind_param("s", $row['operador']);
                                $cedis_query->execute();
                                $cedis_query->bind_result($cedis);
                                $cedis_query->fetch();
                                $cedis_query->close();
                            }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['fecha']); ?></td>
                                <td><?= htmlspecialchars($row['operador']); ?></td>
                                <td><?= htmlspecialchars($row['modelo']); ?></td>
                                <td>
                                    <?php if (!empty($row['foto_tablero'])): ?>
                                        <a href="../<?= $row['foto_tablero']; ?>" target="_blank">
                                            <img src="../<?= $row['foto_tablero']; ?>" alt="Tablero" class="img-thumbnail"
                                                style="max-width: 100px;">
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Sin foto</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['km_salida']); ?></td>
                                <td>
                                    <?php if (!empty($row['foto_km_carga'])): ?>
                                        <a href="../<?= $row['foto_km_carga']; ?>" target="_blank">
                                            <img src="../<?= $row['foto_km_carga']; ?>" alt="KM Carga" class="img-thumbnail"
                                                style="max-width: 100px;">
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Sin foto</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['km_carga']); ?></td>
                                <td>$<?= htmlspecialchars($row['precio_litro']); ?></td>
                                <td><?= htmlspecialchars($row['cantidad_litros']); ?></td>
                                <td>$<?= number_format($row['total_cargado'], 2); ?></td>
                                <?php if ($user_id == 1) { ?> <!-- Solo mostrar CEDIS si el usuario es el con ID 1 -->
                                    <td><?= htmlspecialchars($cedis); ?></td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div><br><br><br>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="modalAgregarGas" tabindex="-1" aria-labelledby="modalAgregarGasLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form id="formularioGas" method="POST" action="../db/database_form_gas.php"
                    enctype="multipart/form-data" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAgregarGasLabel">Nuevo Registro de Gas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="fecha" class="form-label">Fecha y hora</label>
                            <input type="datetime-local" class="form-control" name="fecha" id="fecha" required>
                        </div>

                        <div class="mb-3">
                            <label for="operador" class="form-label">Operador</label>
                            <input type="text" class="form-control" name="operador"
                                value="<?= htmlspecialchars($usuario) ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="unidad" class="form-label">Unidad</label>
                            <select class="form-control" name="unidad" required>
                                <option value="">Seleccione una unidad</option>
                                <?php
                                // Mostrar las unidades disponibles para el CEDIS
                                while ($unidad = $unidades->fetch_assoc()) {
                                    echo '<option value="' . $unidad['id'] . '">' . htmlspecialchars($unidad['modelo']) . ' => ' . htmlspecialchars($unidad['placa']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <!-- FOTO TABLERO SALIDA CEDIS -->
                        <div class="form-group mb-3">
                            <label class="form-label">Foto Tablero de Salida <span style="color: red">*</span></label>
                            <label for="foto_tablero"
                                class="camera-upload w-100 border rounded py-4 text-center position-relative"
                                style="cursor: pointer;">
                                <i class="fas fa-camera fa-2x mb-2"></i><br>
                                <strong>Toque aquí para subir</strong>
                                <input type="file" class="d-none" name="foto_tablero" id="foto_tablero" accept="image/*"
                                    capture="environment"
                                    onchange="previewImagen(event, 'preview_tablero', 'btnEliminar_tablero')" required>
                            </label>

                            <div class="text-center mt-2">
                                <img id="preview_tablero" src="#" alt="Vista previa" class="img-thumbnail d-none"
                                    style="max-height: 200px;" />
                                <button type="button" id="btnEliminar_tablero" class="btn btn-sm btn-danger mt-2 d-none"
                                    onclick="eliminarImagen('foto_tablero', 'preview_tablero', 'btnEliminar_tablero')">
                                    Eliminar
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="km_salida" class="form-label">KM Salida</label>
                            <input type="number" class="form-control" name="km_salida" required>
                        </div>

                        <!-- FOTO KM CARGA GAS -->
                        <div class="form-group mb-3">
                            <label class="form-label">Foto KM carga Gas <span style="color: red">*</span></label>
                            <label for="foto_km_carga"
                                class="camera-upload w-100 border rounded py-4 text-center position-relative"
                                style="cursor: pointer;">
                                <i class="fas fa-camera fa-2x mb-2"></i><br>
                                <strong>Toque aquí para subir</strong>
                                <input type="file" class="d-none" name="foto_km_carga" id="foto_km_carga"
                                    accept="image/*" capture="environment"
                                    onchange="previewImagen(event, 'preview_km', 'btnEliminar_km')" required>
                            </label>

                            <div class="text-center mt-2">
                                <img id="preview_km" src="#" alt="Vista previa" class="img-thumbnail d-none"
                                    style="max-height: 200px;" />
                                <button type="button" id="btnEliminar_km" class="btn btn-sm btn-danger mt-2 d-none"
                                    onclick="eliminarImagen('foto_km_carga', 'preview_km', 'btnEliminar_km')">
                                    Eliminar
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="km_carga" class="form-label">KM Carga</label>
                            <input type="number" class="form-control" name="km_carga" id="km_carga" required>
                        </div>
                        <div class="mb-3">
                            <label for="precio_litro" class="form-label">Precio por Litro</label>
                            <input type="number" class="form-control" name="precio_litro" id="precio_litro" step="any"
                                placeholder="Ingresa el precio por litro de la Gasolina">
                        </div>
                        <div class="mb-3">
                            <label for="cantidad_litros" class="form-label">Cantidad Litros</label>
                            <input type="number" class="form-control" name="cantidad_litros" id="cantidad_litros"
                                step="any">
                        </div>
                        <div class="mb-3">
                            <label for="total_cargado" class="form-label">Total $</label>
                            <input type="number" class="form-control" name="total_cargado" id="total_cargado" step="any"
                                readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

    </main>

    <script src="/flotillas/js/gas.js"></script>

</body>

</html>