<?php
session_name('Flotillas');
session_start();
require '../php/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/inicioSesion.php");
    exit();
}

$conn = connectMysqli();

$rol = $_SESSION['rol'] ?? '';    // Asumo que guardas el rol en sesión
$usuario = $_SESSION['usuario'] ?? '';

// Si es usuario TI, obtenemos todos los viajes abiertos para mostrar en el select
$viajes = [];
if ($rol === 'TI') {
    $sql = "SELECT id, operador, fecha_inicio FROM viajes WHERE estado = 'abierto' ORDER BY fecha_inicio DESC";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $viajes[] = $row;
    }
} else {
    // Usuario operador: cargamos sus pedidos del viaje abierto (suponiendo 1 solo viaje abierto por operador)
    $sql = "
        SELECT df.pedido, df.cliente, df.ubicacion 
        FROM data_form df
        INNER JOIN viajes v ON df.viaje_id = v.id
        WHERE v.estado = 'abierto' AND v.operador = '" . $conn->real_escape_string($usuario) . "'
    ";
    $result = $conn->query($sql);

    $datos = [];
    while ($row = $result->fetch_assoc()) {
        $datos[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Ruta de Entregas</title>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDiZcKXugtBRzqpDQ0ax7Zzgt36rXEj1Lw&callback=initMap"
        async defer></script>
    <style>
        #map {
            height: 70vh;
            width: 100%;
            margin-bottom: 15px;
        }

        /* Contenedor para alinear los botones al centro y en línea */
        .botonera {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
        }

        /* Estilo general para ambos botones */
        #trazar-btn,
        #iniciar-recorrido-btn {
            padding: 10px 20px;
            font-size: 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        /* Estilo específico para el botón "Trazar Ruta" */
        #trazar-btn {
            background-color: #28a745;
            color: white;
        }

        #trazar-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        /* Estilo específico para el botón "Iniciar Recorrido" */
        #iniciar-recorrido-btn {
            background-color: #ccc;
            color: white;
            cursor: not-allowed;
        }

        #iniciar-recorrido-btn:not(:disabled) {
            background-color: #007bff;
            cursor: pointer;
        }

        #lista-ruta {
            max-width: 600px;
            margin: 0 auto;
            font-family: sans-serif;
        }

        #lista-ruta li {
            margin-bottom: 5px;
        }

        #viaje-select {
            display: block;
            margin: 10px auto;
            font-size: 16px;
            padding: 5px;
        }
    </style>

</head>

<body>

    <?php if ($rol === 'TI'): ?>
        <div class="mb-3">
            <select id="viaje-select" class="text-center form-select">
                <option value="">Seleccione una ruta de entrega</option>
                <?php foreach ($viajes as $viaje): ?>
                    <option value="<?= $viaje['id'] ?>">
                        Ruta #<?= $viaje['id'] ?> - <?= htmlspecialchars($viaje['operador']) ?> |
                        <?= date('Y-m-d H:i', strtotime($viaje['fecha_inicio'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <div id="map"></div>

    <div class="botonera">
        <button id="trazar-btn" disabled>Trazar Ruta 🏁</button>
        <button id="iniciar-recorrido-btn" disabled>Iniciar Recorrido 🚗</button>
    </div>

    <div id="lista-ruta">
        <ul id="lista-pedidos"></ul>
    </div>
    <br><br><br>


    <script>
        let map, repartidorCoords = null;
        let clientMarkers = [];
        let clientInfo = [];
        let directionsRenderer, directionsService;
        let pedidos = [];

        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 6,
                center: { lat: 23.6345, lng: -102.5528 }
            });

            directionsRenderer = new google.maps.DirectionsRenderer({ map: map, suppressMarkers: true });
            directionsService = new google.maps.DirectionsService();

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    repartidorCoords = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };

                    new google.maps.Marker({
                        position: repartidorCoords,
                        map: map,
                        label: "R",
                        title: "Tu ubicación",
                        icon: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
                    });

                    map.setCenter(repartidorCoords);

                    <?php if ($rol !== 'TI'): ?>
                        pedidos = <?= json_encode($datos ?? []) ?>;
                        cargarMarcadores();
                    <?php endif; ?>

                }, () => {
                    alert("No se pudo obtener la ubicación del repartidor.");
                });
            } else {
                alert("Geolocalización no soportada.");
            }
        }

        function cargarMarcadores() {
            clearMarkers();

            pedidos.forEach((pedido, index) => {
                const ubicacion = pedido.ubicacion;
                let clienteCoord = null;

                if (ubicacion.includes('|')) {
                    const partes = ubicacion.split('|');
                    if (partes[1]) {
                        const [lat, lng] = partes[1].split(',').map(parseFloat);
                        if (!isNaN(lat) && !isNaN(lng)) {
                            clienteCoord = { lat, lng };
                        }
                    }
                } else if (ubicacion.includes(',')) {
                    const [lat, lng] = ubicacion.split(',').map(parseFloat);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        clienteCoord = { lat, lng };
                    }
                }

                if (clienteCoord) {
                    clientMarkers.push(new google.maps.Marker({
                        position: clienteCoord,
                        map: map,
                        label: (index + 1).toString(),
                        title: `Cliente: ${pedido.cliente} | Pedido: ${pedido.pedido}`
                    }));

                    clientInfo.push({
                        cliente: pedido.cliente,
                        pedido: pedido.pedido,
                        coord: clienteCoord
                    });
                }
            });

            document.getElementById("trazar-btn").disabled = clientMarkers.length === 0;
        }

        function clearMarkers() {
            clientMarkers.forEach(marker => marker.setMap(null));
            clientMarkers = [];
            clientInfo = [];
            directionsRenderer.set('directions', null);
            document.getElementById("lista-pedidos").innerHTML = '';
            document.getElementById("trazar-btn").disabled = true;
            document.getElementById("iniciar-recorrido-btn").disabled = true;
        }

        <?php if ($rol === 'TI'): ?>
            document.getElementById('viaje-select').addEventListener('change', function () {
                clearMarkers();
                const viajeId = this.value;
                if (!viajeId) return;
                fetch(`get_puntos_viaje.php?viaje_id=${viajeId}`)
                    .then(res => res.json())
                    .then(data => {
                        pedidos = data;
                        cargarMarcadores();
                    })
                    .catch(() => alert('Error al cargar los datos del viaje.'));
            });
        <?php endif; ?>

        document.getElementById("trazar-btn").addEventListener("click", () => {
            const boton = document.getElementById("trazar-btn");
            const iniciarBtn = document.getElementById("iniciar-recorrido-btn");
            boton.disabled = true;

            if (!repartidorCoords || clientMarkers.length === 0) {
                alert("Ubicación del repartidor o puntos de entrega no disponibles.");
                return;
            }

            const ul = document.getElementById("lista-pedidos");
            ul.innerHTML = "";

            clientInfo.sort((a, b) => {
                const dA = getDistance(repartidorCoords, a.coord);
                const dB = getDistance(repartidorCoords, b.coord);
                return dA - dB;
            });

            const origin = repartidorCoords;
            const destination = clientInfo[clientInfo.length - 1].coord;

            const waypoints = clientInfo.slice(0, -1).map(info => ({
                location: info.coord,
                stopover: true
            }));

            // Mostrar lista
            const uniqueClientInfo = [];
            const seen = new Set();
            clientInfo.forEach(info => {
                if (!seen.has(info.cliente)) {
                    seen.add(info.cliente);
                    uniqueClientInfo.push(info);
                }
            });

            uniqueClientInfo.forEach((info, i) => {
                const li = document.createElement("li");
                li.textContent = `${i + 1}. 📦 Cliente: ${info.cliente} | Pedido: ${info.pedido}`;
                ul.appendChild(li);
            });

            // Ruta en el mapa
            directionsService.route({
                origin,
                destination,
                waypoints,
                optimizeWaypoints: false,
                travelMode: google.maps.TravelMode.DRIVING
            }, (result, status) => {
                if (status === "OK") {
                    directionsRenderer.setDirections(result);

                    // Generar link a Google Maps
                    let mapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${origin.lat},${origin.lng}`;
                    if (clientInfo.length > 1) {
                        const waypointsParam = clientInfo.slice(0, -1)
                            .map(p => `${p.coord.lat},${p.coord.lng}`)
                            .join('|');
                        mapsUrl += `&waypoints=${encodeURIComponent(waypointsParam)}`;
                    }
                    mapsUrl += `&destination=${destination.lat},${destination.lng}&travelmode=driving`;

                    iniciarBtn.dataset.url = mapsUrl;
                    iniciarBtn.disabled = false;
                } else {
                    alert("No se pudo trazar la ruta: " + status);
                }
            });
        });

        document.getElementById("iniciar-recorrido-btn").addEventListener("click", () => {
            const url = document.getElementById("iniciar-recorrido-btn").dataset.url;
            if (url) {
                window.open(url, "_blank");
            }
        });

        function getDistance(p1, p2) {
            const R = 6371e3;
            const φ1 = p1.lat * Math.PI / 180;
            const φ2 = p2.lat * Math.PI / 180;
            const Δφ = (p2.lat - p1.lat) * Math.PI / 180;
            const Δλ = (p2.lng - p1.lng) * Math.PI / 180;

            const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                Math.cos(φ1) * Math.cos(φ2) *
                Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

            return R * c;
        }
    </script>

</body>

</html>