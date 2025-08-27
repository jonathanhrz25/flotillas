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
$user_id = $_SESSION['user_id'] ?? 0;

$viajes = [];

if ($rol === 'TI' && $user_id == 1) {
    // Usuario TI con ID 1 - ve todo
    $sql = "
        SELECT v.operador, DATE(df.fecha) as fecha, COUNT(df.id) AS total_pedidos
        FROM viajes v
        LEFT JOIN data_form df ON v.id = df.viaje_id
        GROUP BY v.operador, DATE(df.fecha)
        ORDER BY fecha DESC, operador DESC
    ";
    $result = $conn->query($sql);

} elseif ($rol === 'TI') {
    // Usuario TI que ve todos los datos de su CEDIS
    $cedis = $_SESSION['cedis'] ?? '';
    $sql = "
        SELECT v.operador, DATE(df.fecha) as fecha, COUNT(df.id) AS total_pedidos
        FROM viajes v
        LEFT JOIN data_form df ON v.id = df.viaje_id
        WHERE v.cedis = ?
        GROUP BY v.operador, DATE(df.fecha)
        ORDER BY fecha DESC, operador DESC
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error al preparar consulta: " . $conn->error);
    }
    $stmt->bind_param("s", $cedis);
    $stmt->execute();
    $result = $stmt->get_result();

} else {
    // Usuarios normales - ven solo sus propios pedidos
    $sql = "
        SELECT v.operador, DATE(df.fecha) as fecha, COUNT(df.id) AS total_pedidos
        FROM viajes v
        LEFT JOIN data_form df ON v.id = df.viaje_id
        WHERE v.operador = ?
        GROUP BY v.operador, DATE(df.fecha)
        ORDER BY fecha DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Rellenar arreglo
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $viajes[] = $row;
    }
} else {
    echo "Error en consulta: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Pedidos Entregados - Google Charts</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <style>
        /* Asegura que el gráfico ocupe todo el ancho disponible */
        #chart_div {
            width: 100%;
            height: 300px;
            /* Puedes subirlo a 600px si quieres aún más grande */
            max-width: 100%;
        }

        /* Asegura que el cuerpo tenga un tamaño mínimo y máximo en el eje y */
        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
        }

        h4 {
            text-align: center;
            margin: 20px 0;
        }
    </style>

    <!-- 🔹 Selector de fecha encima de la gráfica -->
    <div style="text-align:center; margin-bottom: 20px;">
        <label for="fechaSeleccionada"><strong>Seleccionar fecha:</strong></label>
        <input type="date" id="fechaSeleccionada" value="<?php echo date('Y-m-d'); ?>">
    </div>

    <script type="text/javascript">
        google.charts.load('current', { packages: ['corechart'] });
        google.charts.setOnLoadCallback(inicializarGrafica);

        var viajes = <?php echo json_encode($viajes); ?>;
        var chart, options;

        function inicializarGrafica() {
            options = {
                title: 'Pedidos entregados por usuario',
                legend: { position: 'none' },
                hAxis: { title: 'Viajes' },
                vAxis: { title: 'Pedidos', minValue: 0 },
                bar: { groupWidth: '75%' },
                colors: ['#007bff']
            };

            chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));

            // Dibuja la gráfica para la fecha actual
            actualizarGrafica(document.getElementById('fechaSeleccionada').value);

            // Evento para cuando el usuario cambie la fecha
            document.getElementById('fechaSeleccionada').addEventListener('change', function () {
                actualizarGrafica(this.value);
            });

            // Redibujar si la ventana cambia de tamaño
            window.addEventListener('resize', function () {
                actualizarGrafica(document.getElementById('fechaSeleccionada').value);
            });
        }

        function actualizarGrafica(fecha) {
            var filtrados = viajes.filter(v => v.fecha === fecha);

            if (filtrados.length === 0) {
                document.getElementById('chart_div').innerHTML = "No hay datos para la fecha seleccionada.";
                return;
            }

            var data = google.visualization.arrayToDataTable([
                ['Usuario - Fecha', 'Pedidos Entregados'],
                ...filtrados.map(v => [`${v.operador} - ${v.fecha}`, parseInt(v.total_pedidos)])
            ]);

            chart.draw(data, options);
        }
    </script>

</head>

<body>
    <h4>Pedidos entregados por usuario y fecha</h4>
    <div id="chart_div"></div><br><br><br>

    <h4>Resumen Detallado</h4>
    <div class="table-responsive">
        <table id="tabla-resumen" border="1" cellpadding="10" cellspacing="0"
            style="width:100%; border-collapse: collapse; margin: auto;">
            <thead class="text-center">
                <tr style="background-color: #007bff; color: white;">
                    <th>Operador</th>
                    <th>Fecha</th>
                    <th>Total de Pedidos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($viajes as $viaje): ?>
                    <tr>
                        <td class="text-center"><?= htmlspecialchars($viaje['operador']) ?></td>
                        <td class="text-center"><?= htmlspecialchars($viaje['fecha']) ?></td>
                        <td class="text-center"><?= (int) $viaje['total_pedidos'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div><br><br>

    <div style="text-align: center; margin-top: 10px;">
        <button onclick="exportarTablaExcel()"
            style="padding: 8px 16px; background-color: green; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Exportar a Excel
        </button>
    </div><br><br>


    <script>
        function exportarTablaExcel() {
            let tabla = document.getElementById("tabla-resumen");
            let html = tabla.outerHTML;

            let url = 'data:application/vnd.ms-excel,' + escape(html);

            let link = document.createElement("a");
            link.href = url;
            link.download = 'resumen_pedidos.xls';
            link.click();
        }
    </script>

</body>

</html>