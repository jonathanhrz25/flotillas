<?php
session_name('Flotillas');
session_start();
require '../php/connect.php';

$conn = connectMysqli();

if (!isset($_SESSION['user_id'])) {
    // Si no hay sesión activa, redirige a la página de inicio de sesión
    header("Location: ../php/inicioSesion.php");
    exit();
}

// Obtener datos del operador desde la sesión
$usuario = $_SESSION['usuario'];
$cedis_usuario = $_SESSION['cedis'] ?? ''; // Asegúrate de que el CEDIS esté disponible en la sesión

// Obtener las unidades asociadas al CEDIS del operador
$sql = "SELECT modelo, placa FROM unidad WHERE cedis = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $cedis_usuario);
$stmt->execute();
$result = $stmt->get_result();

$unidades = [];
while ($row = $result->fetch_assoc()) {
    $unidades[] = $row['modelo'] . ' => ' . $row['placa'];
}
?>

<!-- ✅ Bootstrap y estilos necesarios -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/quagga@0.12.1/dist/quagga.min.js"></script> <!-- QuaggaJS -->
<link rel="stylesheet" href="../css/style2.css">

<!-- ✅ ESTILOS PERSONALIZADOS -->
<style>
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

    body {
        background-color: white;
        color: #212529;
        /* Color estándar de texto Bootstrap */
        overflow-x: hidden;
        /* Desactiva scroll horizontal dentro del iframe */
        overflow-y: auto;
        /* Activa scroll vertical dentro del iframe */
        margin: 0;
        padding: 0;
    }

    label,
    .form-label {
        color: #212529 !important;
        /* Fuerza color oscuro para visibilidad */
    }

    /* Ajustar tamaño y fuente para el campo de fecha */
    @media (max-width: 576px) {
        .form-control {
            font-size: 0.8rem;
            /* Reducir tamaño de la fuente */
            width: 100%;
            /* Asegurar que ocupe todo el ancho disponible */
        }

        .form-label {
            font-size: 0.9rem;
            /* Reducir el tamaño de las etiquetas */
        }

        /* Ajuste específico para el campo de fecha */
        #fecha {
            width: 120%;
            /* Asegurarse que el campo de fecha ocupe el 100% del contenedor */
        }
    }
</style>

<!-- ✅ CONTENIDO PRINCIPAL -->
<main class="main-content" style="padding-top: 10px;">

    <div id="notificaciones" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

    <div>
        <form id="formulario" method="POST" action="../db/database_form_carga.php" enctype="multipart/form-data">
            <!-- Campo oculto (vacío inicialmente, se llenará con JS antes de enviar) -->
            <input type="hidden" name="viaje_id" id="viaje_id" value="">
            <input type="hidden" id="operador" name="operador" value="<?php echo $_SESSION['usuario']; ?>">
            <input type="hidden" id="cedis" name="cedis" value="<?php echo htmlspecialchars($cedis_usuario); ?>">
            <input type="hidden" id="fecha" name="fecha" value="...">

            <div class="row g-1 mb-1 align-items-end justify-content-center">
                <!-- Operador -->
                <div class="col-2 col-md-3 text-center">
                    <label for="operador" class="form-label">Operador:</label>
                    <p id="operador" class="form-control-static" style="font-size: 15px;">
                        <?php echo $_SESSION['usuario']; ?>
                    </p>
                </div>

                <!-- Fecha y Hora -->
                <div class="col-6 col-md-3 text-center">
                    <label for="fecha" class="form-label">Fecha y Hora:</label>
                    <p id="fecha" class="form-control-static" style="font-size: 12px;">
                        <?php
                        // Establecer zona horaria
                        date_default_timezone_set('America/Mexico_City');

                        // Mostrar fecha según variable o actual
                        if (isset($_SESSION['fecha']) && !empty($_SESSION['fecha'])) {
                            echo date('Y-m-d\ / h:i A', strtotime($_SESSION['fecha']));
                        } else {
                            echo date('Y-m-d\ / h:i A');
                        }
                        ?>
                    </p>
                </div>
            </div>

            <!-- Línea divisora -->
            <hr class="my-2">

            <div class="form-group mb-3">
                <label for="unidad" class="form-label">Seleccione su Unidad de transporte:</label>
                <select name="unidad" id="unidad" class="form-control" required>
                    <option value="">Selecciona una unidad</option>
                    <?php foreach ($unidades as $u): ?>
                        <option value="<?php echo $u; ?>"><?php echo $u; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-3">
                <label for="pedido" class="form-label">No. Pedido a Entregar: <span style="color: red">*</span></label>
                <div class="input-group">
                    <!-- El campo actúa como botón también -->
                    <input type="text" class="form-control" id="pedido" name="pedido"
                        placeholder="Escanea con lector o toca para escanear con cámara" style="cursor: pointer;"
                        autocomplete="off">
                    <!-- Botón de eliminar pedido dentro del grupo -->
                    <button type="button" class="btn btn-outline-danger btn-sm" id="btn-borrar-pedido">❌</button>
                </div>
            </div>

            <div class="form-group mb-3">
                <label for="cajas" class="form-label">Cant. Cajas: <span style="color: red">*</span></label>
                <input type="number" name="cajas" class="form-control" id="cajas" placeholder="0" readonly />
            </div>

            <!-- Contenedor dinámico para botones de cajas -->
            <div class="form-group mb-3">
                <label for="lista-cajas" class="form-label">Números de Cajas:</label>
                <div id="lista-cajas" class="d-flex flex-wrap gap-2"></div>
            </div>

            <!-- Campo oculto para enviar la lista de cajas validadas como JSON -->
            <input type="hidden" id="lista-cajas-input" name="lista-cajas" value="">

            <input type="text" id="scanner-pistola" style="position:absolute; left:-1000px; top:-1000px;"
                autocomplete="off">

            <div class="form-group mb-3">
                <label for="num_cliente" class="form-label">Numero de Cliente: <span style="color: red">*</span></label>
                <input type="text" name="num_cliente" class="form-control" id="num_cliente"
                    placeholder="Numero de cliente" />
            </div>

            <div class="form-group mb-3">
                <label for="cliente" class="form-label">Cliente a Entregar: <span style="color: red">*</span></label>
                <input type="text" name="cliente" id="cliente" class="form-control" readonly
                    placeholder="Nombre del cliente" />
            </div>

            <!-- Dirección Alterna -->
            <div class="form-group mb-3">
                <label for="location_alterna" class="form-label">Dirección Alterna Cliente: (Opcional)</label>
                <select class="form-control" id="location_alterna">
                    <option value="">Seleccionar una dirección alterna</option>
                </select>
            </div>

            <!-- Campo Ubicación -->
            <div class="form-group mb-3">
                <label for="ubicacion" class="form-label">Ubicación (Coordenadas actuales): <span
                        style="color: red">*</span></label>
                <div class="input-group">
                    <input type="text" name="ubicacion" class="form-control" id="ubicacion"
                        placeholder="Ingresa las coordenadas: lat, lng o una dirección">
                    <button class="btn btn-primary" id="buscarUbicacion" type="button">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
                <div id="map" style="width: 100%; height: 300px; margin-top: 10px;"></div>
            </div>

            <!-- Dirección Cliente -->
            <div class="form-group mb-3">
                <label for="location" class="form-label">Dirección Cliente:</label>
                <input type="text" name="location" class="form-control" id="location" readonly>
            </div>

            <div class="form-group mb-3">
                <label for="km" class="form-label">Km de llegada con cliente: <span style="color: red">*</span></label>
                <input type="number" name="km" class="form-control" id="km" placeholder="0" readonly />
            </div>

            <div class="text-center mt-2">
                <button type="button" class="btn btn-success" id="btn-confirmar">Ingresar Pedido</button>
            </div>
        </form>
    </div>

    <!-- Modal escáner -->
    <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title" id="qrModalLabel">Escanear Código de Barras</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="barcode-scanner">
                        <div id="scanner-overlay"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    const operador = "<?php echo $_SESSION['usuario']; ?>";
</script>

<script>
    const usuarioRol = "<?= $_SESSION['rol'] ?? '' ?>";
</script>

<script>
    document.getElementById("num_cliente").addEventListener("change", function () {
        const numCliente = this.value.trim();

        if (numCliente === "") return;

        fetch(`../menu/buscar_direcciones.php?num_cliente=${numCliente}`)
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById("location_alterna");
                select.innerHTML = '<option value="">Seleccionar una dirección alterna</option>';

                if (data.status === "success") {
                    if (data.direcciones.length === 0) {
                        console.log("No se encontraron direcciones para el cliente:", numCliente);
                        return;
                    }

                    data.direcciones.forEach(dir => {
                        const option = document.createElement("option");
                        option.value = dir.Id;
                        option.textContent = dir.ubicacion;
                        option.setAttribute("data-lat", dir.lat);
                        option.setAttribute("data-lng", dir.lng);
                        option.setAttribute("data-direccion", dir.ubicacion);
                        select.appendChild(option);
                    });
                } else {
                    console.error("Error al cargar direcciones:", data.message);
                }
            })
            .catch(error => {
                console.error("Error en la solicitud:", error);
            });
    });
</script>

<script>
    document.getElementById("location_alterna").addEventListener("change", function () {
        const selectedOption = this.options[this.selectedIndex];

        const lat = selectedOption.getAttribute("data-lat");
        const lng = selectedOption.getAttribute("data-lng");
        const direccion = selectedOption.getAttribute("data-direccion");

        if (lat && lng) {
            const ubicacionInput = document.getElementById("ubicacion");
            ubicacionInput.value = `${lat}, ${lng}`;

            // Opcional: simular clic en el botón de búsqueda
            document.getElementById("buscarUbicacion").click();
        }
    });
</script>


<!-- Google Maps -->
<script src="https://maps.googleapis.com/maps/api/js?key=API_KEY" async defer></script>


<!-- ✅ JS principal -->

<script src="/flotillas/js/carga.js"></script>
