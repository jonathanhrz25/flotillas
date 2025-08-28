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

$rol = $_SESSION['rol'] ?? '';

$result = $conn->query("SELECT * FROM direccion");
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
    <title>Direcciónes</title>
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

<body style="padding-top: 90px;">

    <?php if (isset($_GET['deleted'])): ?>
        <div id="alerta-usuario-eliminado" class="alert alert-success alert-dismissible fade show" role="alert"
            style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            ✅ Cliente y Dirección eliminado correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <main class="main-content" id="mainContent">
        <div class="container">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Dirección Clientes</h3>
                <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalAgregarDireccion">
                    <i class="bi bi-plus-circle"></i> Añadir Dirección
                </button>
            </div>

            <?php if ($_SESSION['rol'] === 'TI'): ?>
                <div class="mb-4">
                    <h5>📂 Importar direcciones masivamente</h5>
                    <form action="../db/importar_direccion.php" method="POST" enctype="multipart/form-data"
                        class="d-flex gap-2">
                        <input type="file" name="csv_file" accept=".csv" class="form-control" required>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-file-earmark-arrow-up"></i> Subir CSV
                        </button>
                    </form>
                    <small class="text-muted">Formato requerido: <b>Numero de Cliente, Nombre de Cliente, Telefono,
                            Ubicacion</b></small>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead">
                        <tr>
                            <th>ID</th>
                            <th>No. de Cliente</th>
                            <th>Nombre de Cliente</th>
                            <th>Num. de Telefono</th>
                            <th>Dirección</th>
                            <?php if ($rol !== 'Operador'): ?>
                                <th>Eliminar</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="tablaDirecciones">
                        <?php while ($row = $result->fetch_assoc()) { ?>
                            <tr>
                                <td><?= $row['Id']; ?></td>
                                <td><?= htmlspecialchars($row['num_cliente']); ?></td>
                                <td><?= htmlspecialchars($row['cliente']); ?></td>
                                <td><?= htmlspecialchars($row['telefono']); ?></td>
                                <td><?= htmlspecialchars($row['ubicacion']); ?></td>
                                <?php if ($rol !== 'Operador'): ?>
                                    <td class="text-center">
                                        <a href="../edit/eliminarDireccion.php?id=<?= urlencode($row['Id']); ?>"
                                            onclick="return confirm('¿Estás seguro de que deseas eliminar esta Ubicación?')">
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

    <!-- Modal -->
    <div style="padding-top: 20px;" class="modal fade" id="modalAgregarDireccion" tabindex="-1"
        aria-labelledby="modalAgregarDireccionLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="formAgregarDireccion" class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalAgregarDireccionLabel">Añadir Nueva Dirección</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="num_cliente" class="form-label">Numero de Cliente</label>
                        <input type="text" class="form-control" name="num_cliente" id="num_cliente"
                            placeholder="Ingresa el num. de cliente" required>
                    </div>

                    <div class="mb-3">
                        <label for="cliente" class="form-label">Nombre del Cliente</label>
                        <input type="text" class="form-control" name="cliente" id="cliente"
                            placeholder="Ingresa el nombre del cliente" required style="text-transform:uppercase;">
                    </div>

                    <div class="form-group mb-3" id="telefono_promotor">
                        <label for="telefono_promotor" class="form-label">No. de Telefono (Preferencia
                            WhatsApp): </label>
                        <input type="tel" name="telefono" class="form-control" id="telefono" aria-describedby="nameHelp"
                            placeholder="No. de telefono" pattern="[0-9]*" inputmode="numeric" />
                    </div>

                    <div class="form-group mb-3">
                        <label for="ubicacion" class="form-label">Dirección Cliente:</label>
                        <div class="input-group">
                            <input type="text" name="ubicacion" class="form-control" id="ubicacion">
                            <button class="btn btn-primary" id="buscarUbicacion" type="button">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>
                        <div id="map" style="width: 100%; height: 200px; margin-top: 10px;"></div>
                        <input type="hidden" id="lat" name="lat">
                        <input type="hidden" id="lng" name="lng">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div><br><br><br><br>

    <!-- Verificar cliente Direccón -->
    <script>
        const numClienteInput = document.getElementById("num_cliente");
        const nombreInput = document.getElementById("cliente");
        const ubicacionInput = document.getElementById("ubicacion");
        const buscarBtn = document.getElementById("buscarUbicacion");
        const guardarBtn = document.querySelector("#modalAgregarDireccion button[type='submit']");

        function limpiarAlertaYHabilitar() {
            nombreInput.disabled = false;
            ubicacionInput.disabled = false;
            buscarBtn.disabled = false;
            guardarBtn.disabled = false;

            const mensaje = document.getElementById("mensajeExiste");
            if (mensaje) mensaje.remove();
        }

        numClienteInput.addEventListener("blur", function () {
            const numCliente = this.value.trim();

            // Si está vacío, limpia alerta y desbloquea
            if (numCliente === "") {
                limpiarAlertaYHabilitar();
                return;
            }

            // Consultar si ya existe
            fetch(`../menu/verificar_cliente_direccion.php?num_cliente=${encodeURIComponent(numCliente)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === "exists") {
                        nombreInput.disabled = true;
                        ubicacionInput.disabled = true;
                        buscarBtn.disabled = true;
                        guardarBtn.disabled = true;

                        if (!document.getElementById("mensajeExiste")) {
                            const mensaje = document.createElement("div");
                            mensaje.className = "alert alert-warning mt-2";
                            mensaje.id = "mensajeExiste";
                            mensaje.innerHTML = `⚠️ Este número de cliente ya tiene una dirección registrada.`;
                            document.querySelector("#modalAgregarDireccion .modal-body").appendChild(mensaje);
                        }
                    } else {
                        limpiarAlertaYHabilitar();
                    }
                })
                .catch(err => console.error("Error al verificar cliente:", err));
        });

        // También detectar cuando se borra en tiempo real
        numClienteInput.addEventListener("input", function () {
            if (this.value.trim() === "") {
                limpiarAlertaYHabilitar();
            }
        });
    </script>

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

    <script src="../js/direccion.js"></script>

    <script>
        document.getElementById('formAgregarDireccion').addEventListener('submit', function (e) {
            e.preventDefault(); // Evita envío tradicional

            const form = e.target;
            const formData = new FormData(form);

            fetch('../db/guardar_direccion.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        window.location.href = data.redirect; // Redirige usando JS
                    } else {
                        alert(data.message);
                    }
                })
                .catch(err => {
                    console.error("Error en el guardado:", err);
                    alert("Ocurrió un error al guardar la dirección.");
                });
        });
    </script>

    <!-- Google Maps -->
    <script src="https://maps.googleapis.com/maps/api/js?key=API_KEY&callback=initMap"
        async defer></script>

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
