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

$searchTerm = $_GET['search'] ?? '';

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

// Consulta
$sql = "SELECT df.*, v.fecha_inicio, v.fecha_fin AS fecha_cierre, u.usuario AS nombre_cierre
        FROM data_form df
        LEFT JOIN viajes v ON df.viaje_id = v.id
        LEFT JOIN usuarios u ON v.cerrado_por = u.id
        WHERE $filtroUsuario AND df.eliminado = 1";

if ($searchTerm) {
    $searchTerm = $conn->real_escape_string($searchTerm);
    $sql .= " AND (df.num_cliente LIKE '%$searchTerm%' OR df.cliente LIKE '%$searchTerm%' OR df.pedido LIKE '%$searchTerm%' OR df.location LIKE '%$searchTerm%' OR df.motivo_eliminacion LIKE '%$searchTerm%')";
}

$sql .= " ORDER BY df.fecha DESC";

$result = $conn->query($sql);
if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

$pedidos = [];
while ($row = $result->fetch_assoc()) {
    $fecha = date('Y-m-d', strtotime($row['fecha'] ?? 'Sin fecha'));
    $pedidos[$fecha][] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Pedidos Eliminados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style2.css">
    <link rel="stylesheet" href="../css/dark.css">
    <style>
        .grupo {
            border: 1px solid #ccc;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        .encabezado {
            background-color: #ffe2e2;
            padding: 10px;
            cursor: pointer;
            font-weight: bold;
            border-left: 5px solid red;
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

        .motivo {
            font-style: italic;
            color: #c0392b;
            margin-top: 5px;
        }

        h4.text-danger {
            border-bottom: 2px solid #dc3545;
            padding-bottom: 5px;
        }
    </style>
</head>

<body style="padding-top: 90px;">
    <main class="main-content" id="mainContent">
        <div class="container">
            <h3 class="mb-4 text-danger"><i class="bi bi-trash"></i> Pedidos Eliminados</h3>

            <form id="searchForm" class="mb-4">
                <div class="input-group">
                    <input type="text" name="search" id="search" class="form-control"
                        placeholder="Buscar por Numero de cliente, nombre ó pedido"
                        value="<?= htmlspecialchars($searchTerm) ?>">
                    <button class="btn btn-outline-danger" type="submit">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
            </form>

            <div id="resultContainer">
                <?php if (empty($pedidos)): ?>
                    <div class="text-center text-muted">No hay pedidos eliminados.</div>
                <?php else: ?>
                    <?php foreach ($pedidos as $fecha => $lista): ?>
                        <h4 class="text-danger mt-4"><i class="bi bi-calendar-minus"></i>
                            <?= date('d/m/Y', strtotime($fecha)) ?></h4>
                        <div class="grupo">
                            <div class="encabezado">Pedidos Eliminados (<?= count($lista) ?>)</div>
                            <div class="detalle">
                                <?php foreach ($lista as $pedido): ?>
                                    <div class="pedido">
                                        <strong><?= htmlspecialchars($pedido['num_cliente']) ?> -
                                            <?= htmlspecialchars($pedido['cliente']) ?></strong><br>
                                        Pedido: <?= htmlspecialchars($pedido['pedido']) ?> | Fecha:
                                        <?= htmlspecialchars($pedido['fecha']) ?><br>
                                        Ubicación: <?= htmlspecialchars($pedido['location']) ?> | Cajas:
                                        <?= htmlspecialchars($pedido['cajas']) ?><br>
                                        <span class="motivo">📝 Motivo:
                                            <?= htmlspecialchars($pedido['motivo_eliminacion']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        $(document).ready(function () {
            // Oculta todos los detalles al iniciar
            $('.detalle').hide();

            $('#searchForm').on('submit', function (e) {
                e.preventDefault();
                const search = $('#search').val();

                $.get('../menu/pedidos_eliminados.php', { search }, function (data) {
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