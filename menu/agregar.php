<?php
session_name('Flotillas');
session_start();

// Verifica que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/inicioSesion.php");
    exit();
}

// Obtener el número de pedido de la URL
$pedido_id = $_GET['pedido'] ?? null;

if ($pedido_id) {
    // Aquí puedes realizar una consulta para obtener los datos asociados al pedido
    include '../php/connect.php';
    $conn = connectMysqli();
    $sql = "SELECT * FROM data_form WHERE pedido = '$pedido_id' LIMIT 1";
    $result = $conn->query($sql);

    // Si se encuentra el pedido, se guardan los datos
    if ($result->num_rows > 0) {
        $pedido_data = $result->fetch_assoc();
    }
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

        .form-label {
            font-weight: bold;
        }

        .sticky-top {
            top: 0;
        }

        .form-control[readonly] {
            background-color: #e9ecef !important;
            color: #6c757d;
            font-weight: bold;
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
            <form id="formulario" method="POST" action="../db/modificar_form_carga.php" enctype="multipart/form-data">

                <!-- Campo oculto para el pedido -->
                <?php if (isset($pedido_data['pedido'])): ?>
                    <input type="hidden" name="pedido" value="<?= htmlspecialchars($pedido_data['pedido']) ?>">
                <?php endif; ?>

                <div class="form-group"><br>
                    <label for="fecha" class="form-label">Fecha y Hora de reparto: <span
                            style="color: red">*</span></label>
                    <input type="datetime-local" name="fecha" id="fecha" readonly
                        value="<?= isset($pedido_data['fecha']) ? $pedido_data['fecha'] : '' ?>">
                </div><br>

                <div class="form-group mb-3">
                    <label for="operador" class="form-label">Operador: <span style="color: red">*</span></label>
                    <input type="text" name="operador" id="operador" class="form-control"
                        value="<?= isset($pedido_data['operador']) ? $pedido_data['operador'] : '' ?>"
                        placeholder="Nombre Operador" readonly>
                </div>

                <!-- Unidad -->
                <div class="form-group mb-3">
                    <label for="unidad" class="form-label">Seleccione Unidad: <span style="color: red">*</span></label>
                    <input type="text" name="unidad" id="unidad" class="form-control"
                        value="<?= isset($pedido_data['unidad']) ? $pedido_data['unidad'] : '' ?>" placeholder="Unidad"
                        readonly>
                </div>

                <div class="form-group mb-3">
                    <label for="cajas" class="form-label">Cant. Cajas: <span style="color: red">*</span></label>
                    <input type="number" name="cajas" class="form-control" id="cajas" placeholder="0"
                        value="<?= isset($pedido_data['cajas']) ? $pedido_data['cajas'] : '' ?>" readonly />
                </div>

                <div class="form-group mb-3">
                    <label for="lista-cajas" class="form-label">Números de Cajas: <span
                            style="color: red">*</span></label>
                    <textarea id="lista-cajas" class="form-control" rows="2" readonly
                        placeholder="Esperando escaneo..."><?= isset($pedido_data['lista_cajas']) ? $pedido_data['lista_cajas'] : '' ?></textarea>
                </div>

                <div class="form-group mb-3">
                    <label for="num_cliente" class="form-label">Numero de Cliente: <span
                            style="color: red">*</span></label>
                    <input type="text" name="num_cliente" class="form-control" id="num_cliente" readonly
                        placeholder="Numero de cliente"
                        value="<?= isset($pedido_data['num_cliente']) ? $pedido_data['num_cliente'] : '' ?>" />
                </div>

                <div class="form-group mb-3">
                    <label for="cliente" class="form-label">Cliente a Entregar: <span
                            style="color: red">*</span></label>
                    <input type="text" name="cliente" id="cliente" class="form-control" readonly
                        placeholder="Nombre del cliente"
                        value="<?= isset($pedido_data['cliente']) ? $pedido_data['cliente'] : '' ?>" />
                </div>

                <!-- Dirección Alterna -->
                <div class="form-group mb-3">
                    <label for="location_alterna" class="form-label">Dirección Alterna Cliente: (Opcional)</label>
                    <select class="form-control" id="location_alterna">
                        <option value="">Seleccionar una dirección alterna</option>
                        <?php
                        // Obtener las direcciones alternativas de la tabla direccion para el num_cliente actual
                        $sql_direcciones = "SELECT Id, ubicacion, lat, lng FROM direccion WHERE num_cliente = '" . $pedido_data['num_cliente'] . "'";
                        $result_direcciones = $conn->query($sql_direcciones);

                        if ($result_direcciones->num_rows > 0) {
                            while ($row = $result_direcciones->fetch_assoc()) {
                                echo "<option value='{$row['Id']}' data-lat='{$row['lat']}' data-lng='{$row['lng']}' data-direccion='{$row['ubicacion']}'>{$row['ubicacion']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Campo Ubicación -->
                <div class="form-group mb-3">
                    <label for="ubicacion" class="form-label">Ubicación (Coordenadas actuales): <span
                            style="color: red">*</span></label>
                    <div class="input-group">
                        <input type="text" name="ubicacion" class="form-control" id="ubicacion"
                            value="<?= isset($pedido_data['ubicacion']) ? $pedido_data['ubicacion'] : '' ?>"
                            placeholder="Ingresa las coordenadas: lat, lng o una dirección">
                        <button class="btn btn-primary" id="buscarUbicacion" type="button">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                    <div id="map" style="width: 100%; height: 300px; margin-top: 10px;"></div>
                </div>

                <!-- Campo Dirección Cliente -->
                <div class="form-group mb-3">
                    <label for="location" class="form-label">Dirección Cliente: <span
                            style="color: red">*</span></label>
                    <input type="text" name="location" class="form-control" id="location"
                        value="<?= isset($pedido_data['location']) ? $pedido_data['location'] : '' ?>"
                        placeholder="Dirección Cliente" readonly />
                </div>


                <div class="form-group mb-3">
                    <label for="km" class="form-label">Km de llegada con cliente: <span
                            style="color: red">*</span></label>
                    <input type="number" name="km" class="form-control" id="km" step="any"
                        value="<?= isset($pedido_data['km']) ? str_replace(',', '.', $pedido_data['km']) : '' ?>"
                        placeholder="0" readonly/>
                </div>
            </form><br><br><br>

            <!-- BOTONES FLOTANTES -->
            <div id="botones-flotantes" class="bg-white d-flex justify-content-center gap-3 px-3 py-2 shadow-lg"
                style="position: fixed; bottom: 60px; left: 0; width: 100%; z-index: 1050;">
                <button type="button" class="btn btn-outline-danger fw-bold px-4"
                    onclick="window.location.href='http://localhost/flotillas/php/principal.php';">
                    Cancelar
                </button>
                <button type="submit" form="formulario" class="btn btn-outline-primary fw-bold px-4">
                    Guardar
                </button>
            </div>
        </div><br><br><br>
    </main>

    <script src="../js/agregar.js"></script>

    <script>
        document.getElementById('formulario').addEventListener('submit', function (e) {
            e.preventDefault(); // Evita que el formulario se envíe normalmente

            const form = e.target;
            const formData = new FormData(form);

            fetch(form.action, {
                method: form.method,
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Sus datos se han actualizado correctamente');
                        window.location.href = 'http://localhost/flotillas/php/principal.php';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error en la conexión: ' + error.message);
                });
        });
    </script>

    <!-- Google Maps API con callback -->
    <script src="https://maps.googleapis.com/maps/api/js?key=APY_KEY&callback=initMap"
        async defer></script>
</body>

<?php include '../css/footer.php'; ?>


</html>
