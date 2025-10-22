<?php
session_name('Flotillas');
session_start();
include '../php/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/inicioSesion.php");
    exit();
}

$conn = connectMysqli();

// ==================================================
//  Permisos / usuario
// ==================================================
$usuario_id = $_SESSION['user_id'] ?? 0;
$usuario_rol = $_SESSION['rol'] ?? '';
$usuario_nombre = $_SESSION['usuario'] ?? '';
$session_cedis = $_SESSION['cedis'] ?? '';

// Construir filtro de usuario (se aplica a todas las consultas)
if ($usuario_rol === 'TI') {
    if ($usuario_id == 1) {
        // TI admin: ve todo
        $filtroUsuario = "1";
    } else {
        // TI limitado a su cedis de sesión
        $filtroUsuario = "df.cedis = '" . $conn->real_escape_string($session_cedis) . "'";
    }
} else {
    // Usuarios normales por operador
    $filtroUsuario = "df.operador = '" . $conn->real_escape_string($usuario_nombre) . "'";
}

// ==================================================
//  Parámetros de filtro recibidos (GET)
// ==================================================
$selected_cedis = $_GET['cedis'] ?? '';
$tipo_filtro = $_GET['tipo_filtro'] ?? '';
$valor_filtro = $_GET['valor_filtro'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// Filtro cedis (si se especificó)
$filtroCedisSQL = '';
if (!empty($selected_cedis)) {
    $filtroCedisSQL = " AND df.cedis = '" . $conn->real_escape_string($selected_cedis) . "'";
}

// Filtro por fecha (día o mes)
$filtroFechaSQL = '';
if ($tipo_filtro === 'dia' && !empty($valor_filtro)) {
    $filtroFechaSQL = " AND DATE(v.fecha_fin) = '" . $conn->real_escape_string($valor_filtro) . "'";
} elseif ($tipo_filtro === 'mes' && !empty($valor_filtro)) {
    $filtroFechaSQL = " AND DATE_FORMAT(v.fecha_fin, '%Y-%m') = '" . $conn->real_escape_string($valor_filtro) . "'";
}

// ==================================================
//  Obtener lista de CEDIS para el select (según permisos)
// ==================================================
$cedisOptions = [];
if ($usuario_rol === 'TI' && $usuario_id != 1) {
    // Mostrar sólo el cedis de sesión
    if (!empty($session_cedis))
        $cedisOptions[] = $session_cedis;
} else {
    // Obtener todos los cedis disponibles en la tabla (no eliminados)
    $qCedis = "SELECT DISTINCT cedis FROM data_form WHERE (eliminado = 0 OR eliminado IS NULL) AND cedis IS NOT NULL AND cedis <> '' ORDER BY cedis ASC";
    $resC = $conn->query($qCedis);
    if ($resC) {
        while ($r = $resC->fetch_assoc()) {
            $cedisOptions[] = $r['cedis'];
        }
    }
}

// ==================================================
//  Consulta resumen (viajes / pedidos entregados) respetando filtros y permisos
// ==================================================
$sqlResumen = "
    SELECT 
        df.cedis,
        COUNT(DISTINCT df.viaje_id) AS total_viajes,
        COUNT(CASE WHEN df.entregado = 'Pedido Entregado' THEN 1 END) AS pedidos_entregados
    FROM data_form df
    INNER JOIN viajes v ON df.viaje_id = v.id
    WHERE $filtroUsuario
      AND v.estado = 'cerrado'
      AND (df.eliminado = 0 OR df.eliminado IS NULL)
      $filtroFechaSQL
      $filtroCedisSQL
    GROUP BY df.cedis
    ORDER BY df.cedis ASC
";

$resumen = $conn->query($sqlResumen);
$resumenData = [];
if ($resumen) {
    while ($r = $resumen->fetch_assoc()) {
        $resumenData[] = $r;
    }
}

// ==================================================
//  Consulta principal de pedidos (lista)
//  Incluye filtros de búsqueda, fecha y cedis
// ==================================================
$sql = "SELECT df.*, v.estado, v.fecha_inicio, v.fecha_fin AS fecha_cierre, v.cerrado_por, u.usuario AS nombre_cierre
        FROM data_form df 
        LEFT JOIN viajes v ON df.viaje_id = v.id
        LEFT JOIN usuarios u ON v.cerrado_por = u.id
        WHERE $filtroUsuario
          AND v.estado = 'cerrado'
          AND DATE(v.fecha_fin) < CURDATE()
          AND df.entregado = 'Pedido Entregado'
          AND (df.eliminado = 0 OR df.eliminado IS NULL)
          $filtroFechaSQL
          $filtroCedisSQL";

if (!empty($searchTerm)) {
    $st = $conn->real_escape_string($searchTerm);
    $sql .= " AND (df.num_cliente LIKE '%$st%' OR df.cliente LIKE '%$st%' OR df.pedido LIKE '%$st%' OR df.location LIKE '%$st%')";
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

// Ruta física para verificar imágenes
$rutaFisica = $_SERVER['DOCUMENT_ROOT'] . '/flotillas/';

// Función para renderizar un pedido
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
            display: none;
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

        .viaje-cerrado {
            background-color: #f8d7da;
            border-left: 5px solid #dc3545;
        }

        .pedido-entregado {
            background-color: #e0e0e0;
            border-radius: 8px;
            opacity: 0.9;
            padding: 10px;
        }

        .pedido-ausente {
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
            border-radius: 8px;
            padding: 10px;
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="../menu/mapa_pedidos_cerrados.php" class="btn btn-outline-success">
                    <i class="bi bi-map"></i> Ver mapa de pedidos cerrados
                </a>
            </div>

            <!-- Filtros de periodo + CEDIS -->
            <form id="filtroForm" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Filtrar por</label>
                    <select name="tipo_filtro" id="tipo_filtro" class="form-select">
                        <option value="">Sin filtro de fecha</option>
                        <option value="dia" <?= ($tipo_filtro === 'dia') ? 'selected' : '' ?>>Día</option>
                        <option value="mes" <?= ($tipo_filtro === 'mes') ? 'selected' : '' ?>>Mes</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Valor</label>
                    <input
                        type="<?= ($tipo_filtro === 'dia') ? 'date' : (($tipo_filtro === 'mes') ? 'month' : 'text') ?>"
                        name="valor_filtro" id="valor_filtro" class="form-control"
                        placeholder="Ejemplo: 2025-10-09 o 2025-10" value="<?= htmlspecialchars($valor_filtro) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">CEDIS</label>
                    <select name="cedis" id="cedis" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($cedisOptions as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= ($selected_cedis === $c) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary me-2" type="submit"><i class="bi bi-filter"></i> Aplicar
                        filtro</button>
                    <button id="btnLimpiar" type="button" class="btn btn-secondary"><i class="bi bi-x-circle"></i>
                        Limpiar</button>
                </div>
            </form>

            <!-- Contadores (con id para actualización por AJAX) -->
            <div id="contadores" class="row text-center mb-4">
                <?php if (!empty($resumenData)): ?>
                    <?php foreach ($resumenData as $dato): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card shadow-sm border-primary">
                                <div class="card-body">
                                    <h5 class="card-title text-primary fw-bold"><?= htmlspecialchars($dato['cedis']) ?></h5>
                                    <p><strong>🚚 Viajes:</strong> <?= $dato['total_viajes'] ?></p>
                                    <p><strong>📦 Pedidos entregados:</strong> <?= $dato['pedidos_entregados'] ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-muted"><em>No hay datos para el filtro seleccionado.</em></div>
                <?php endif; ?>
            </div>

            <h3 class="mb-4">Viajes Cerrados</h3>

            <!-- Búsqueda -->
            <form id="searchForm" class="mb-4">
                <div class="input-group">
                    <input type="text" name="search" id="search" class="form-control"
                        placeholder="Buscar por número de cliente, pedido, nombre..."
                        value="<?= htmlspecialchars($searchTerm) ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
                </div>
            </form>

            <!-- Resultados (lista de viajes/pedidos) -->
            <div id="resultContainer">
                <?php
                if (empty($pedidos)) {
                    echo '<div class="no-viajes text-center"><p>No hay viajes cerrados por el momento.</p></div>';
                } else {
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

                            $viaje_texto = "Viaje #$viaje_id (cerrado) — " . count($detalles) . " pedidos";
                            if ($fecha_inicio)
                                $viaje_texto .= " | Inicio: " . date('Y-m-d H:i', strtotime($fecha_inicio));
                            if ($fecha_cierre)
                                $viaje_texto .= " | Cierre: " . date('Y-m-d H:i', strtotime($fecha_cierre));
                            if (!empty($nombre_cierre))
                                $viaje_texto .= " | Cerrado por: " . htmlspecialchars($nombre_cierre);

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
            // Helper: armar filtros desde el DOM
            function obtenerFiltrosDesdeDOM() {
                return {
                    tipo_filtro: $('#tipo_filtro').val() || '',
                    valor_filtro: $('#valor_filtro').val() || '',
                    cedis: $('#cedis').val() || '',
                    search: $('#search').val() || ''
                };
            }

            // Ajustar tipo de input según selección al cargar y al cambiar
            function ajustarTipoValorInput() {
                const tipo = $('#tipo_filtro').val();
                const $valor = $('#valor_filtro');
                if (tipo === 'dia') $valor.attr('type', 'date');
                else if (tipo === 'mes') $valor.attr('type', 'month');
                else $valor.attr('type', 'text');
            }
            ajustarTipoValorInput();
            $('#tipo_filtro').on('change', ajustarTipoValorInput);

            // Limpiar filtros
            $('#btnLimpiar').on('click', function () {
                $('#tipo_filtro').val('');
                $('#valor_filtro').val('').attr('type', 'text');
                $('#cedis').val('');
                $('#search').val('');
                cargarPedidos(obtenerFiltrosDesdeDOM());
            });

            // Función AJAX para recargar contadores y lista sin recargar toda la página
            function cargarPedidos(filtros = {}) {
                // Usar la misma ruta que carga el contenido desde principal.php
                $.ajax({
                    url: '../menu/pedidos_cerrados.php',
                    type: 'GET',
                    data: filtros,
                    beforeSend: function () {
                        $('#contadores').html('<div class="text-center p-3 text-muted">Cargando resumen...</div>');
                        $('#resultContainer').html('<div class="text-center p-3 text-muted">Cargando resultados...</div>');
                    },
                    success: function (data) {
                        // Extraer el HTML del contenedor #contadores y #resultContainer desde la respuesta completa
                        const nuevoContadores = $(data).find('#contadores').html();
                        const nuevosResultados = $(data).find('#resultContainer').html();

                        if (typeof nuevoContadores !== 'undefined' && nuevoContadores !== null) {
                            $('#contadores').html(nuevoContadores);
                        }
                        if (typeof nuevosResultados !== 'undefined' && nuevosResultados !== null) {
                            $('#resultContainer').html(nuevosResultados);
                        }

                        // Ocultar detalles por defecto (se reinyecta nuevo HTML)
                        $('.detalle').hide();
                    },
                    error: function () {
                        $('#resultContainer').html('<div class="text-danger text-center">Error al cargar los datos.</div>');
                    }
                });
            }

            // Eventos
            $('#filtroForm').on('submit', function (e) {
                e.preventDefault();
                cargarPedidos(obtenerFiltrosDesdeDOM());
            });

            $('#searchForm').on('submit', function (e) {
                e.preventDefault();
                cargarPedidos(obtenerFiltrosDesdeDOM());
            });

            // Toggle de detalles
            $(document).on('click', '.encabezado', function () {
                $(this).next('.detalle').slideToggle();
            });

            // Cargar datos iniciales al abrir (opcional, ya vienen en página renderizada)
            // cargarPedidos(obtenerFiltrosDesdeDOM());
        });
    </script>

    <?php include '../css/footer.php'; ?>
</body>

</html>