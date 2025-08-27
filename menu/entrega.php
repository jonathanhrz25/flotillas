<?php
session_name('Flotillas');
session_start();
include '../php/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/inicioSesion.php");
    exit();
}

$conn = connectMysqli();

$_SESSION['usuario'];
$pedido = $_GET['pedido'] ?? '';

if (!empty($pedido)) {

    $stmt = $conn->prepare("SELECT entregado FROM data_form WHERE pedido = ?");
    $stmt->bind_param("s", $pedido);
    $stmt->execute();
    $stmt->bind_result($estadoEntrega);
    $stmt->fetch();
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="../img/icono2.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/quagga@0.12.1/dist/quagga.min.js"></script> <!-- QuaggaJS -->
    <title>Menú</title>
    <style>
        .nav-link.active {
            background-color: #ccc !important;
            color: #000 !important;
        }

        .modal-body {
            position: relative;
            width: 100%;
            height: 100vh;
            padding: 0;
            margin: 0;
            overflow: hidden;
        }

        .camera-upload {
            border: 2px solid #ccc;
            border-radius: 5px;
            padding: 30px;
            text-align: center;
            position: relative;
            cursor: pointer;
            background-color: #f9f9f9;
        }

        .camera-upload input[type="file"] {
            opacity: 0;
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            cursor: pointer;
        }

        .camera-upload i {
            font-size: 32px;
            color: #666;
        }

        .form-label {
            font-weight: bold;
        }

        .sticky-top {
            top: 0;
        }

        #barcode-scanner {
            width: 100%;
            height: 100%;
            position: relative;
        }

        #barcode-scanner video,
        #barcode-scanner canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100% !important;
            height: 100% !important;
            object-fit: cover;
            z-index: 1;
        }

        #scanner-overlay {
            position: absolute;
            top: 40%;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
            height: 100px;
            border: 2px solid red;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.6);
            background: rgba(255, 255, 255, 0.3);
            z-index: 2;
            pointer-events: none;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-dark fixed-top" style="background-color: #081856!important;">
        <div class="container-fluid">
            <a class="navbar-brand text-white" href="../php/principal.php">
                <img src="../img/loguito2.png" alt="" height="45" class="d-inline-block align-text-top">
            </a>
        </div>
    </nav>

    <main class="main-content" id="mainContent" style="padding-top: 90px;">
        <div class="container col-12">
            <form id="formulario" method="POST" action="../db/database_form_agregar.php" enctype="multipart/form-data">

                <input type="hidden" name="pedido" value="<?= htmlspecialchars($pedido) ?>">

                <div class="form-group mb-3">
                    <label class="form-label">Pedido:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($pedido) ?>" disabled>
                </div>

                <!-- Número de cajas -->
                <div class="form-group mb-3">
                    <label for="cajas" class="form-label">Cant. Cajas: <span style="color: red">*</span></label>
                    <input type="number" name="cajas" class="form-control" id="cajas" placeholder="0" readonly />
                </div>

                <!-- Contenedor dinámico para botones de cajas -->
                <div class="form-group mb-3">
                    <label for="lista-cajas" class="form-label">Números de Cajas:</label>
                    <div id="lista-cajas" class="d-flex flex-wrap gap-2"></div>
                </div>

                <!-- Campo oculto con cajas entregadas validadas -->
                <input type="hidden" id="lista-cajas-input" name="lista-cajas" value="">

                <!-- Foto de Recibido -->
                <div class="form-group mb-3">
                    <label class="form-label">Foto de Recibido <span style="color: red">*</span></label>
                    <label for="fotoRecibido"
                        class="camera-upload w-100 border rounded py-4 text-center position-relative"
                        style="cursor: pointer;">
                        <i class="fas fa-camera fa-2x mb-2"></i><br>
                        <strong>Toque aquí para subir</strong>
                        <input type="file" class="d-none" name="fotoRecibido" id="fotoRecibido" accept="image/*"
                            capture="environment"
                            onchange="previewImagen(event, 'previewRecibido', 'btnEliminarRecibido')" required>
                    </label>

                    <div class="text-center mt-2">
                        <img id="previewRecibido" src="#" alt="Vista previa" class="img-thumbnail d-none"
                            style="max-height: 200px;" />
                        <button type="button" id="btnEliminarRecibido" class="btn btn-sm btn-danger mt-2 d-none"
                            onclick="eliminarImagen('fotoRecibido', 'previewRecibido', 'btnEliminarRecibido')">
                            Eliminar
                        </button>
                    </div>
                </div>

                <!-- Foto de Cajas -->
                <div class="form-group mb-3">
                    <label class="form-label">Foto de Cajas <span style="color: red">*</span></label>
                    <label for="fotoCajas" class="camera-upload w-100 border rounded py-4 text-center position-relative"
                        style="cursor: pointer;">
                        <i class="fas fa-camera fa-2x mb-2"></i><br>
                        <strong>Toque aquí para subir</strong>
                        <input type="file" class="d-none" name="fotoCajas" id="fotoCajas" accept="image/*"
                            capture="environment" onchange="previewImagen(event, 'previewCajas', 'btnEliminarCajas')"
                            required>
                    </label>

                    <div class="text-center mt-2">
                        <img id="previewCajas" src="#" alt="Vista previa" class="img-thumbnail d-none"
                            style="max-height: 200px;" />
                        <button type="button" id="btnEliminarCajas" class="btn btn-sm btn-danger mt-2 d-none"
                            onclick="eliminarImagen('fotoCajas', 'previewCajas', 'btnEliminarCajas')">
                            Eliminar
                        </button>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Estado de Entrega Pedido<span style="color: red">*</span></label>
                    <select name="entregado" id="entregado" class="form-control" required>
                        <option value="">Selecciona una opción</option>
                        <option value="Pedido Entregado" <?= $estadoEntrega === 'Pedido Entregado' ? 'selected' : '' ?>>
                            Pedido Entregado</option>
                        <option value="Cliente Ausente" <?= $estadoEntrega === 'Cliente Ausente' ? 'selected' : '' ?>>
                            Cliente Ausente</option>
                        <option value="Pedido No Entregado" <?= $estadoEntrega === 'Pedido No Entregado' ? 'selected' : '' ?>>Pedido No Entregado</option>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label for="comentarios_y_observaciones" class="form-label">Comentarios y Observaciones:</label>
                    <textarea name="comentarios_y_observaciones" id="comentarios_y_observaciones" class="form-control"
                        rows="2" placeholder="Escribe aquí tus observaciones..."></textarea>
                </div>
            </form><br><br><br>

            <!-- BOTONES FLOTANTES -->
            <div id="botones-flotantes" class="bg-white d-flex justify-content-center gap-3 px-3 py-2 shadow-lg"
                style="position: fixed; bottom: 60px; left: 0; width: 100%; z-index: 1050;">
                <button type="button" class="btn btn-outline-danger fw-bold px-4"
                    onclick="window.location.href='http://localhost/flotillas/php/principal.php';">
                    Cancelar
                </button>
                <button type="submit" form="formulario" class="btn btn-outline-primary fw-bold px-4" id="btnGuardar"
                    disabled>
                    Guardar
                </button>
            </div>
        </div><br><br><br>

        <!-- Modal escáner -->
        <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content bg-dark text-white">
                    <div class="modal-header">
                        <h5 class="modal-title" id="qrModalLabel">Escanear Código de Barras</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Cerrar" onclick="cerrarScanner()"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div id="barcode-scanner" style="position:relative; width:100%; height:100%;"></div>
                        <div id="scanner-overlay"></div>
                    </div>
                    <div class="modal-footer">
                        <small id="scanner-feedback" class="text-light"></small>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../js/entrega.js"></script>

    <script>
        function previewImagen(event, imgId, btnId) {
            const input = event.target;
            const imgPreview = document.getElementById(imgId);
            const btnEliminar = document.getElementById(btnId);

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imgPreview.src = e.target.result;
                    imgPreview.classList.remove("d-none");
                    btnEliminar.classList.remove("d-none");
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function eliminarImagen(inputId, imgId, btnId) {
            const input = document.getElementById(inputId);
            const img = document.getElementById(imgId);
            const btn = document.getElementById(btnId);

            input.value = '';
            img.src = '#';
            img.classList.add("d-none");
            btn.classList.add("d-none");
        }
    </script>


</body>

<?php include '../css/footer.php'; ?>

</html>