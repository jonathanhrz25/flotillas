<?php
session_name('Flotillas');
session_start();
include '../php/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/inicioSesion.php");
    exit();
}

$conn = connectMysqli();

$usuario_id = $_SESSION['user_id'] ?? 0;
$usuario_rol = $_SESSION['rol'] ?? '';
$usuario_nombre = $_SESSION['usuario'] ?? '';

// Captura de búsqueda (si existe)
$searchTerm = $_GET['search'] ?? ''; // El término de búsqueda desde el formulario

if ($usuario_rol === 'TI') {
    if ($usuario_id == 1) {
        $filtroUsuario = "1";
    } else {
        $cedis = $conn->real_escape_string($_SESSION['cedis'] ?? '');
        $filtroUsuario = "df.cedis = '$cedis'";
    }
} else {
    $filtroUsuario = "df.operador = '" . $conn->real_escape_string($usuario_nombre) . "'";
}

// Preparar la consulta con el filtro de búsqueda
$sql = "SELECT df.*, v.estado, v.fecha_inicio, v.fecha_fin AS fecha_cierre, v.cerrado_por, u.usuario AS nombre_cierre
        FROM data_form df 
        LEFT JOIN viajes v ON df.viaje_id = v.id
        LEFT JOIN usuarios u ON v.cerrado_por = u.id
        WHERE $filtroUsuario 
          AND v.estado = 'cerrado'
          AND DATE(v.fecha_fin) < CURDATE()";

if ($searchTerm) {
    // Agregar condición de búsqueda si existe un término
    $searchTerm = $conn->real_escape_string($searchTerm);
    $sql .= " AND (df.num_cliente LIKE '%$searchTerm%' OR df.cliente LIKE '%$searchTerm%' OR df.pedido LIKE '%$searchTerm%' OR df.location LIKE '%$searchTerm%')";
}

$sql .= " ORDER BY v.fecha_fin DESC, df.num_cliente, df.fecha DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Error en la consulta SQL: " . $conn->error . "<br><pre>$sql</pre>");
}

$pedidos = [];
while ($row = $result->fetch_assoc()) {
    $pedidos[$row['viaje_id']][] = $row;
}

?>

<?php
// Ruta física para verificar imágenes
$rutaFisica = $_SERVER['DOCUMENT_ROOT'] . '/flotillas/';

// Función para renderizar pedidos
function renderPedido($pedido, $rutaFisica)
{
    $clasePedido = ($pedido['entregado'] === 'Pedido Entregado') ? 'pedido-entregado' :
        (($pedido['entregado'] === 'Cliente Ausente') ? 'pedido-ausente' : '');

    $fotoRecibido = ltrim($pedido['foto_recibido'], '/');
    $fotoCajas = ltrim($pedido['foto_cajas'], '/');

    ?>
    <div class="pedido <?= $clasePedido ?>">
        <div class="d-flex justify-content-between align-items-center">
            <strong><?= htmlspecialchars($pedido['num_cliente']) ?> -> <?= htmlspecialchars($pedido['cliente']) ?></strong>
            <?php if ($pedido['entregado'] === 'Pedido Entregado'): ?>
                <a href="../menu/visualizar_pedido.php?pedido=<?= urlencode($pedido['pedido']) ?>"
                    class="btn btn-outline-primary btn-sm" title="Ver detalles del pedido">
                    <i class="bi bi-eye"></i>
                </a>
            <?php endif; ?>
        </div><br>

        Pedido: <?= htmlspecialchars($pedido['pedido']) ?> |
        Fecha: <?= htmlspecialchars($pedido['fecha']) ?><br>
        Ubicación: <?= htmlspecialchars($pedido['location']) ?> |
        Cajas: <?= htmlspecialchars($pedido['cajas']) ?><br>
        Comentarios: <?= htmlspecialchars($pedido['comentarios_y_observaciones']) ?><br>

        <?php if (!empty($fotoRecibido) && file_exists($rutaFisica . $fotoRecibido)): ?>
            <a href="../<?= htmlspecialchars($fotoRecibido) ?>" target="_blank">
                <img src="../<?= htmlspecialchars($fotoRecibido) ?>" class="foto" alt="Recibido">
            </a>
        <?php else: ?>
            <span class="text-muted"><i class="bi bi-image"></i> Sin imagen de recibido</span>
        <?php endif; ?>

        <?php if (!empty($fotoCajas) && file_exists($rutaFisica . $fotoCajas)): ?>
            <a href="../<?= htmlspecialchars($fotoCajas) ?>" target="_blank">
                <img src="../<?= htmlspecialchars($fotoCajas) ?>" class="foto" alt="Cajas">
            </a>
        <?php else: ?>
            <span class="text-muted"><i class="bi bi-image"></i> Sin imagen de cajas</span>
        <?php endif; ?>
    </div>
    <?php
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
    <link rel="stylesheet" href="../css/style2.css">
    <link rel="stylesheet" href="../css/dark.css">
    <title>Viajes Cerrados</title>
    <style>
        .grupo {
            border: 1px solid #ccc;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        .encabezado {
            background-color: #f2f2f2;
            padding: 10px;
            cursor: pointer;
            font-weight: bold;
        }

        .detalle {
            display: block;
            padding: 10px;
            background-color: #fff;
        }

        .pedido {
            border-top: 1px solid #eee;
            padding: 5px 0;
        }

        .foto {
            width: 80px;
            height: auto;
            margin-right: 10px;
        }

        .viaje {
            background-color: #ddd;
            padding: 10px;
            font-size: 1.2em;
            font-weight: bold;
        }

        .no-viajes {
            text-align: center;
            font-size: 1.5em;
            color: #888;
            margin-top: 20px;
        }

        .viaje-abierto {
            background-color: #d4edda;
            border-left: 5px solid #28a745;
        }

        .viaje-cerrado {
            background-color: #f8d7da;
            border-left: 5px solid #dc3545;
        }

        .pedido-entregado {
            background-color: #e0e0e0;
            border-radius: 8px;
            opacity: 0.7;
            padding: 10px;
            transition: background-color 0.3s ease;
        }

        .pedido-ausente {
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
            border-radius: 8px;
            padding: 10px;
            transition: background-color 0.3s ease;
        }

        h4.text-primary {
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
            font-weight: bold;
        }
    </style>
</head>

<body style="padding-top: 90px;">
    <main class="main-content" id="mainContent">
        <div class="container">
            <h3 class="mb-4">Viajes Cerrados</h3>
            <!-- Formulario de búsqueda -->
            <form id="searchForm" class="mb-4">
                <div class="input-group">
                    <input type="text" name="search" id="search" class="form-control"
                        placeholder="Buscar por número de cliente, pedido, nombre..."
                        value="<?= htmlspecialchars($searchTerm) ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
            </form>

            <div id="resultadoBusqueda" class="mb-3 text-center fw-bold"></div>

            <div id="resultContainer">
                <?php
                // Si no hay pedidos, mostrar el mensaje de que no hay viajes cerrados
                if (empty($pedidos)) {
                    echo '<div class="no-viajes text-center"><p>No hay viajes cerrados por el momento.</p></div>';
                } else {
                    // Agrupar los pedidos por fecha de cierre
                    setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain.1252');
                    $agrupados_por_fecha = [];

                    foreach ($pedidos as $viaje_id => $detalles) {
                        $fecha_cierre_raw = $detalles[0]['fecha_cierre'] ?? '';
                        $fecha_cierre = $fecha_cierre_raw ? date('Y-m-d', strtotime($fecha_cierre_raw)) : 'Sin fecha';
                        $agrupados_por_fecha[$fecha_cierre][$viaje_id] = $detalles;
                    }
                    krsort($agrupados_por_fecha);

                    foreach ($agrupados_por_fecha as $fecha => $viajes_en_fecha) {
                        echo '<h4 class="mt-5 mb-3 text-primary"><i class="bi bi-calendar-event"></i> ' .
                            (($fecha === 'Sin fecha') ? 'Sin fecha de cierre' : strftime('%A %e de %B de %Y', strtotime($fecha))) .
                            '</h4>';

                        foreach ($viajes_en_fecha as $viaje_id => $detalles) {
                            $fecha_inicio = $detalles[0]['fecha_inicio'] ?? '';
                            $fecha_cierre = $detalles[0]['fecha_cierre'] ?? '';
                            $nombre_cierre = $detalles[0]['nombre_cierre'] ?? '';
                            $operador = $detalles[0]['operador'] ?? '';

                            $viaje_texto = "Viaje #$viaje_id (cerrado) — " . count($detalles) . " pedidos";
                            if ($fecha_inicio)
                                $viaje_texto .= " | Inicio: " . date('Y-m-d H:i', strtotime($fecha_inicio));
                            if ($fecha_cierre) {
                                $viaje_texto .= " | Cierre: " . date('Y-m-d H:i', strtotime($fecha_cierre));
                                if (!empty($nombre_cierre))
                                    $viaje_texto .= " | Cerrado por: " . htmlspecialchars($nombre_cierre);
                            }

                            echo '<div class="grupo">';
                            echo '<div class="encabezado viaje-cerrado">' . $viaje_texto . '</div>';
                            echo '<div class="detalle">';
                            foreach ($detalles as $pedido) {
                                renderPedido($pedido, $rutaFisica);
                            }
                            echo '</div></div>';
                        }
                    }
                }
                ?>
            </div>
        </div><br><br>
    </main>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        $(document).ready(function () {
            // Oculta todos los detalles al iniciar
            $('.detalle').hide();

            $('#searchForm').on('submit', function (e) {
                e.preventDefault();
                const search = $('#search').val();

                $.get('../menu/pedidos_cerrados.php', { search }, function (data) {
                    const content = $(data).find('#resultContainer').html();
                    $('#resultContainer').html(content);

                    // Mueve resultado fuera para que no se reemplace
                    const totalCoincidencias = $(data).find('.detalle:visible').length;
                    const resultadoTexto = $('#resultadoBusqueda');

                    if (totalCoincidencias > 0) {
                        resultadoTexto
                            .text(`🔍 ${totalCoincidencias} pedido(s) con coincidencias encontradas.`)
                            .removeClass('text-danger')
                            .addClass('text-success');
                    } else {
                        resultadoTexto
                            .text('❌ No se encontraron coincidencias.')
                            .removeClass('text-success')
                            .addClass('text-danger');
                    }
                });
            });

            // Desplegar/cerrar manualmente
            $(document).on('click', '.encabezado', function () {
                $(this).next('.detalle').slideToggle();
            });
        });
    </script>

    <?php include '../css/footer.php'; ?>
</body>

</html>