<?php
session_name('Flotillas');
session_start();
require '../php/connect.php';
$conn = connectMysqli();

$usuario_id = $_SESSION['user_id'] ?? 0;
$usuario_rol = $_SESSION['rol'] ?? '';
$usuario_cedis = $_SESSION['cedis'] ?? '';


// Obtener fechas únicas de pedidos cerrados
$fechas = [];
$result = $conn->query("SELECT DISTINCT DATE(fecha) AS fecha FROM data_form WHERE entregado = 'Pedido Entregado' AND eliminado = 0 ORDER BY fecha DESC");
while ($row = $result->fetch_assoc()) {
    $fechas[] = $row['fecha'];
}

// Filtrar por fecha si se selecciona
$fecha = $_GET['fecha'] ?? '';
$cedisSeleccionado = $_GET['cedis'] ?? ''; // <-- PRIMERO se define

// Forzar filtro por CEDIS si el usuario no es TI 1
if (!($usuario_rol === 'TI' && $usuario_id == 1)) {
    $cedisSeleccionado = $usuario_cedis;
}

// Filtrar por cedis si se selecciona
$cedisSeleccionado = $_GET['cedis'] ?? '';

// Forzar filtro por CEDIS si el usuario no es TI 1
if (!($usuario_rol === 'TI' && $usuario_id == 1)) {
    $cedisSeleccionado = $usuario_cedis;
}

$query = "
    SELECT 
        cliente,
        num_cliente,
        ubicacion,
        location,
        operador,
        unidad,
        fecha,
        cedis
    FROM data_form
    WHERE entregado = 'Pedido Entregado'
      AND eliminado = 0
";
if ($fecha) {
    $query .= " AND DATE(fecha) = '$fecha'";
}

if ($cedisSeleccionado) {
    $cedisSeleccionado = $conn->real_escape_string($cedisSeleccionado);
    $query .= " AND cedis = '$cedisSeleccionado'";
}

$res = $conn->query($query);
$pedidos = [];
while ($row = $res->fetch_assoc()) {
    $pedidos[] = $row;
}

// Direcciones de los CEDIS actualizadas
$direccionesCedis = [
    "Puebla" => "Blvd. Carmen Serdán 22, Sta María la Rivera, 72010 Heroica Puebla de Zaragoza, Pue.",
    "Pachuca" => "Carretera Federal, Pachuca-Tulancingo km 5.5, El Portezuelo, 42181 Hgo.",
    "Cuernavaca" => "Av. Plan de Ayala No. 1502, Chapultepec, 62360 Cuernavaca, Mor."
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mapa de Pedidos</title>
    <link rel="shortcut icon" href="../img/icono2.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDiZcKXugtBRzqpDQ0ax7Zzgt36rXEj1Lw"></script>
    <style>
        body,
        html {
            height: 100%;
            margin: 0;
        }

        .navbar {
            background-color: #081856;
            z-index: 1000;
            height: 70px;
        }

        #map {
            position: absolute;
            top: 70px;
            left: 0;
            right: 0;
            bottom: 0;
        }

        .top-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-right: 1rem;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark fixed-top">
        <div class="container-fluid justify-content-between">
            <a class="navbar-brand text-white" href="../php/principal.php">
                <img src="../img/loguito2.png" alt="" height="45" class="d-inline-block align-text-top">
            </a>
            <div class="top-bar text-white">
                <h6 class="m-0">🗺️ Mapa de Pedidos Entregados por CEDIS</h6>
                <form method="GET" class="d-flex align-items-center">
                    <label for="fecha" class="me-2 mb-0 text-white">📅 Fecha:</label>
                    <input type="date" name="fecha" id="fecha" class="form-control form-control-sm me-3"
                        value="<?= htmlspecialchars($fecha) ?>" onchange="this.form.submit()">

                    <?php if ($usuario_rol === 'TI' && $usuario_id == 1): ?>
                        <label for="cedis" class="me-2 mb-0 text-white">🏢 CEDIS:</label>
                        <select name="cedis" id="cedis" class="form-select form-select-sm me-2"
                            onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <?php foreach (array_keys($direccionesCedis) as $nombreCedis): ?>
                                <option value="<?= $nombreCedis ?>" <?= ($cedisSeleccionado === $nombreCedis) ? 'selected' : '' ?>>
                                    <?= $nombreCedis ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <!-- Campo oculto con CEDIS del usuario -->
                        <input type="hidden" name="cedis" value="<?= htmlspecialchars($usuario_cedis) ?>">
                    <?php endif; ?>

                    <?php if ($fecha || $cedisSeleccionado): ?>
                        <a href="mapa_pedidos_cerrados.php" class="btn btn-sm btn-outline-light">Limpiar</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </nav>

    <div id="map"></div>

    <script>
        function initMap() {
            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 7,
                center: { lat: 19.0, lng: -98.5 }
            });

            const geocoder = new google.maps.Geocoder();
            const directionsService = new google.maps.DirectionsService();

            const pedidos = <?= json_encode($pedidos) ?>;
            const direccionesCedis = <?= json_encode($direccionesCedis) ?>;

            const pedidosPorCedis = {};
            pedidos.forEach(p => {
                const cedis = p.cedis || "Puebla";
                if (!pedidosPorCedis[cedis]) pedidosPorCedis[cedis] = [];
                pedidosPorCedis[cedis].push(p);
            });

            Object.keys(pedidosPorCedis).forEach(nombreCedis => {
                const direccionCedis = direccionesCedis[nombreCedis];
                if (!direccionCedis) return;

                geocoder.geocode({ address: direccionCedis }, (cedisResults, status) => {
                    if (status !== "OK" || !cedisResults[0]) {
                        console.error("No se pudo geocodificar CEDIS:", nombreCedis);
                        return;
                    }

                    const cedisPos = cedisResults[0].geometry.location;

                    new google.maps.Marker({
                        map: map,
                        position: cedisPos,
                        title: "CEDIS " + nombreCedis,
                        icon: "https://maps.google.com/mapfiles/ms/icons/blue-dot.png"
                    });

                    const listaPedidos = pedidosPorCedis[nombreCedis];
                    const geocodedPedidos = [];
                    let procesadas = 0;

                    listaPedidos.forEach(pedido => {
                        const direccion = pedido.location?.trim() !== "" ? pedido.location : pedido.ubicacion;
                        if (!direccion) {
                            procesadas++;
                            return;
                        }

                        geocoder.geocode({ address: direccion }, (result, stat) => {
                            procesadas++;
                            if (stat === "OK" && result[0]) {
                                geocodedPedidos.push({
                                    latLng: result[0].geometry.location,
                                    cliente: pedido.cliente
                                });

                                new google.maps.Marker({
                                    map: map,
                                    position: result[0].geometry.location,
                                    title: pedido.cliente + " (" + nombreCedis + ")",
                                    icon: "https://maps.google.com/mapfiles/ms/icons/red-dot.png"
                                });
                            }

                            if (procesadas === listaPedidos.length) {
                                geocodedPedidos.forEach(ped => {
                                    const renderer = new google.maps.DirectionsRenderer({
                                        map: map,
                                        suppressMarkers: true,
                                        preserveViewport: true,
                                        polylineOptions: {
                                            strokeColor: getColorForCedis(nombreCedis),
                                            strokeWeight: 4
                                        }
                                    });

                                    directionsService.route({
                                        origin: cedisPos,
                                        destination: ped.latLng,
                                        travelMode: google.maps.TravelMode.DRIVING
                                    }, (resp, stat2) => {
                                        if (stat2 === "OK") {
                                            renderer.setDirections(resp);
                                        } else {
                                            console.warn("No se pudo trazar la ruta:", stat2);
                                        }
                                    });
                                });
                            }
                        });
                    });
                });
            });

            function getColorForCedis(nombre) {
                switch (nombre) {
                    case "Pachuca": return "#FF0000";
                    case "Cuernavaca": return "#00AA00";
                    case "Puebla":
                    default: return "#0000FF";
                }
            }
        }

        window.onload = initMap;
    </script>

</body>
<?php include '../css/footer.php'; ?>

</html>