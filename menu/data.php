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
$usuario_nombre = $_SESSION['usuario'] ?? '';  // <- Nombre del usuario logueado

// Filtro dinámico según el rol
if ($usuario_rol === 'TI') {
    if ($usuario_id == 1) {
        // Usuario TI con ID 1: puede ver todo
        $filtroUsuario = "1"; // siempre verdadero
    } else {
        // Otro usuario TI: filtra por CEDIS
        $cedis = $_SESSION['cedis'] ?? '';
        $filtroUsuario = "df.cedis = '" . $conn->real_escape_string($cedis) . "'";
    }
} else {
    // Otros usuarios (como operadores): solo ven sus propios pedidos
    $filtroUsuario = "df.operador = '" . $conn->real_escape_string($usuario_nombre) . "'";
}


$hoy = date('Y-m-d'); // Fecha de hoy sin hora

$sql = "SELECT df.*, v.estado, v.fecha_inicio, v.fecha_fin AS fecha_cierre, v.cerrado_por, u.usuario AS nombre_cierre
        FROM data_form df 
        LEFT JOIN viajes v ON df.viaje_id = v.id
        LEFT JOIN usuarios u ON v.cerrado_por = u.id
        WHERE 
            $filtroUsuario
            AND df.eliminado = 0
            AND (
                v.estado = 'abierto' 
                OR (v.estado = 'cerrado' AND DATE(v.fecha_fin) = CURDATE())
            )
        ORDER BY v.estado = 'cerrado', v.fecha_inicio DESC, df.num_cliente, df.fecha DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Error en la consulta SQL: " . $conn->error . "<br><pre>$sql</pre>");
}


$pedidos = [];
while ($row = $result->fetch_assoc()) {
    $pedidos[$row['viaje_id']][] = $row;
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
    <title>Data</title>
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
            /* verde claro */
            border-left: 5px solid #28a745;
            /* verde más intenso */
        }

        .viaje-cerrado {
            background-color: #f8d7da;
            /* rojo claro */
            border-left: 5px solid #dc3545;
            /* rojo más intenso */
        }

        .pedido-entregado {
            background-color: #e0e0e0;
            /* gris claro */
            border-radius: 8px;
            opacity: 0.7;
            padding: 10px;
            transition: background-color 0.3s ease;
        }

        .pedido-ausente {
            background-color: #fff3cd;
            /* Amarillo claro */
            border-left: 5px solid #ffc107;
            /* Amarillo fuerte */
            border-radius: 8px;
            padding: 10px;
            transition: background-color 0.3s ease;
        }

        /* .bg-warning {
            transition: background-color 0.5s ease;
        } */
    </style>
</head>

<body style="padding-top: 90px;">

    <main class="main-content" id="mainContent">
        <div class="container">
            <div class="mb-4">
                <input type="text" id="buscador" class="form-control form-control-lg"
                    placeholder="Buscar por Número de Cliente, Nombre o Num de Pedido">
            </div>
            <div id="resultadoBusqueda" class="text-center text-muted mb-3" style="font-size: 1.1em;"></div>
            <?php if (empty($pedidos)): ?>
                <div class="no-viajes">
                    <p>No hay viajes por el momento, ingrese a Carga para generar un nuevo viaje.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pedidos as $viaje_id => $detalles): ?>
                    <div class="grupo">
                        <?php
                        $estado = $detalles[0]['estado'] ?? 'desconocido';
                        $fecha_inicio = $detalles[0]['fecha_inicio'] ?? '';
                        $fecha_cierre = $detalles[0]['fecha_cierre'] ?? '';
                        $nombre_cierre = $detalles[0]['nombre_cierre'] ?? '';
                        $operador = $detalles[0]['operador'] ?? '';

                        $viaje_texto = "Viaje #$viaje_id ($estado) — " . count($detalles) . " pedidos";

                        if ($fecha_inicio)
                            $viaje_texto .= " | Inicio de Viaje: " . date('Y-m-d H:i', strtotime($fecha_inicio));
                        if ($fecha_cierre) {
                            $viaje_texto .= " | Cierre: " . date('Y-m-d H:i', strtotime($fecha_cierre));
                            if (!empty($nombre_cierre)) {
                                $viaje_texto .= " | Cerrado por: " . htmlspecialchars($nombre_cierre);
                            }
                        }

                        $linea_operador = '';
                        if ($usuario_rol === 'TI' && !empty($detalles[0]['operador'])) {
                            $cedis_operador = $detalles[0]['cedis'] ?? 'Cedis desconocido';
                            $linea_operador = '<div class="text-success fw-bold">Operador: '
                                . htmlspecialchars($detalles[0]['operador'])
                                . ' - Cedis ' . htmlspecialchars($cedis_operador)
                                . '</div>';
                        }
                        ?>
                        <div
                            class="encabezado viaje d-flex justify-content-between align-items-center <?= $estado === 'abierto' ? 'viaje-abierto' : 'viaje-cerrado' ?>">
                            <div>
                                <?= $linea_operador ?>
                                <div><?= $viaje_texto ?></div>
                            </div>
                            <?php if ($usuario_rol !== 'Operador'): ?>
                                <?php if ($estado === 'abierto'): ?>
                                    <button class="btn btn-sm btn-info cerrar-viaje" data-viaje="<?= $viaje_id ?>">Cerrar
                                        viaje</button>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Cerrado</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div class="detalle">
                            <?php
                            // Separar los pedidos entregados y no entregados
                            $noEntregados = [];
                            $entregados = [];

                            foreach ($detalles as $pedido) {
                                if ($pedido['entregado'] === 'Pedido Entregado') {
                                    $entregados[] = $pedido;
                                } else {
                                    $noEntregados[] = $pedido;
                                }
                            }

                            // Función para renderizar los pedidos
                            if (!function_exists('renderPedido')) {
                                function renderPedido($pedido, $rutaFisica)
                                {
                                    if ($pedido['entregado'] === 'Pedido Entregado') {
                                        $clasePedido = 'pedido-entregado';
                                    } elseif ($pedido['entregado'] === 'Cliente Ausente') {
                                        $clasePedido = 'pedido-ausente';
                                    } else {
                                        $clasePedido = '';
                                    }
                                    ?>
                                    <div class="pedido <?= $clasePedido ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong><?= htmlspecialchars($pedido['num_cliente']) ?> ->
                                                <?= htmlspecialchars($pedido['cliente']) ?></strong>
                                            <?php if ($pedido['entregado'] === 'Pedido Entregado'): ?>
                                                <a href="../menu/visualizar_pedido.php?pedido=<?= urlencode($pedido['pedido']) ?>"
                                                    class="btn btn-outline-primary btn-sm" title="Ver detalles del pedido">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div><br>

                                        Pedido: <?= htmlspecialchars($pedido['pedido']) ?> | Fecha ingreso de Pedido:
                                        <?= htmlspecialchars($pedido['fecha']) ?><br>
                                        Ubicación: <?= htmlspecialchars($pedido['location']) ?> | Cant. Cajas:
                                        <?= htmlspecialchars($pedido['cajas']) ?><br>
                                        Comentarios: <?= htmlspecialchars($pedido['comentarios_y_observaciones']) ?><br>

                                        <?php
                                        $fotoRecibido = $pedido['foto_recibido'];
                                        $fotoCajas = $pedido['foto_cajas'];
                                        ?>

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

                                        <!-- Botones -->
                                        <div class="d-flex justify-content-end align-items-center mt-2">
                                            <?php if ($pedido['entregado'] === 'Pedido Entregado'): ?>
                                                <div>
                                                    <button class="btn btn-secondary btn-sm me-2" disabled>Modificar</button>
                                                    <button class="btn btn-secondary btn-sm" disabled>Entregado</button>
                                                </div>
                                            <?php else: ?>
                                                <div>
                                                    <a href="../menu/agregar.php?pedido=<?= urlencode($pedido['pedido']) ?>"
                                                        class="btn btn-warning btn-sm me-2">Modificar</a>
                                                    <a href="../menu/entrega.php?pedido=<?= urlencode($pedido['pedido']) ?>"
                                                        class="btn btn-success btn-sm">Entregar</a>
                                                    <?php if ($_SESSION['rol'] === 'TI'): ?>
                                                        <button class="btn btn-danger btn-sm eliminar-pedido"
                                                            data-pedido="<?= htmlspecialchars($pedido['pedido']) ?>">Eliminar</button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }

                            $rutaFisica = $_SERVER['DOCUMENT_ROOT'] . '/flotillas/';

                            // Mostrar primero los no entregados
                            foreach ($noEntregados as $pedido) {
                                renderPedido($pedido, $rutaFisica);
                            }

                            // Luego los entregados
                            foreach ($entregados as $pedido) {
                                renderPedido($pedido, $rutaFisica);
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div><br><br><br><br>
    </main>

    <!-- Scripts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <!-- Cerrar Viaje -->
    <script>
        document.querySelectorAll(".cerrar-viaje").forEach(button => {
            button.addEventListener("click", function () {
                const viaje_id = this.dataset.viaje;
                if (!confirm("¿Seguro que deseas cerrar este viaje?")) return;

                fetch("../db/cerrar_viaje.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "viaje_id=" + encodeURIComponent(viaje_id)
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert("Viaje cerrado correctamente");

                            const grupo = this.closest('.grupo');
                            const encabezado = grupo.querySelector('.encabezado');
                            const detalle = grupo.querySelector('.detalle');

                            // Cambiar visualmente a cerrado
                            encabezado.classList.remove('viaje-abierto');
                            encabezado.classList.add('viaje-cerrado');

                            // Eliminar botón y mostrar estado cerrado
                            this.remove(); // Elimina el botón "Cerrar viaje"

                            const cerradoBadge = document.createElement('span');
                            cerradoBadge.className = "badge bg-secondary";
                            cerradoBadge.textContent = "Cerrado";
                            encabezado.appendChild(cerradoBadge);

                            // Ocultar detalle del grupo
                            detalle.style.display = "none";
                        } else {
                            alert("Error al cerrar viaje: " + data.message);
                        }
                    });
            });
        });

        // Expandir/cerrar detalles
        document.querySelectorAll('.encabezado').forEach(header => {
            const detail = header.nextElementSibling;

            // Oculta al inicio si es un viaje cerrado
            if (header.classList.contains('viaje-cerrado')) {
                detail.style.display = 'none';
            }

            // Alterna visibilidad al hacer clic
            header.addEventListener('click', () => {
                detail.style.display = detail.style.display === 'none' ? 'block' : 'none';
            });
        });
    </script>

    <!-- Eliminar Pedido -->
    <script>
        let pedidoSeleccionado = null;

        document.querySelectorAll('.eliminar-pedido').forEach(button => {
            button.addEventListener('click', function () {
                pedidoSeleccionado = this.closest('.pedido');
                const pedidoId = this.dataset.pedido;
                document.getElementById('modalPedidoId').value = pedidoId;
                document.getElementById('motivo').value = '';
                const modal = new bootstrap.Modal(document.getElementById('modalEliminarPedido'));
                modal.show();
            });
        });

        document.getElementById('formEliminarPedido').addEventListener('submit', function (e) {
            e.preventDefault();
            const pedido = document.getElementById('modalPedidoId').value;
            const motivo = document.getElementById('motivo').value.trim();

            if (motivo === "") {
                alert("Por favor ingresa el motivo.");
                return;
            }

            fetch("../db/eliminar_pedido.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({ pedido, motivo })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEliminarPedido'));
                        modal.hide();
                        alert("Pedido marcado como eliminado.");
                        if (pedidoSeleccionado) pedidoSeleccionado.remove();
                    } else {
                        alert("Error: " + data.message);
                    }
                });
        });
    </script>

    <!-- Buscador -->
    <script>
        document.getElementById('buscador').addEventListener('input', function () {
            const valorBusqueda = this.value.toLowerCase().trim();
            const grupos = document.querySelectorAll('.grupo');
            const resultadoTexto = document.getElementById('resultadoBusqueda');

            if (!valorBusqueda) {
                resultadoTexto.textContent = '';
                grupos.forEach(grupo => {
                    grupo.style.display = 'block';
                    const pedidos = grupo.querySelectorAll('.pedido');
                    pedidos.forEach(pedido => {
                        pedido.style.display = 'block'; // Aseguramos que todos los pedidos sean visibles nuevamente
                    });
                });
                return;
            }

            let totalCoincidencias = 0;

            grupos.forEach(grupo => {
                const encabezado = grupo.querySelector('.encabezado');
                const pedidos = grupo.querySelectorAll('.pedido');
                let coincidenciaViaje = false; // Para saber si el viaje tiene coincidencias

                // Iterar sobre los pedidos del grupo
                pedidos.forEach(pedido => {
                    const nombreCliente = pedido.querySelector('strong')?.innerText.toLowerCase() || '';
                    const textoPedido = pedido.innerText.toLowerCase();
                    const pedidoId = textoPedido.match(/pedido:\s*([^\s]+)/i)?.[1] || '';
                    const numCliente = textoPedido.match(/cliente:\s*(\d+)/i)?.[1] || '';

                    // Si encontramos coincidencias en el nombre del cliente, número de cliente o ID de pedido
                    if (
                        nombreCliente.includes(valorBusqueda) ||
                        numCliente.includes(valorBusqueda) ||
                        pedidoId.includes(valorBusqueda)
                    ) {
                        coincidenciaViaje = true; // Marcamos que el viaje tiene coincidencias
                        pedido.style.display = 'block'; // Mostrar solo el pedido que coincide
                        totalCoincidencias++;
                    } else {
                        pedido.style.display = 'none'; // Ocultar los pedidos que no coinciden
                    }
                });

                // Si el viaje tiene algún pedido con coincidencia, mostramos el viaje
                if (coincidenciaViaje) {
                    grupo.style.display = 'block'; // Mostrar el grupo de viaje
                } else {
                    grupo.style.display = 'none'; // Ocultar el viaje si no hay coincidencias
                }
            });

            if (totalCoincidencias > 0) {
                resultadoTexto.textContent = `🔍 ${totalCoincidencias} pedido(s) con coincidencias encontradas.`;
            } else {
                resultadoTexto.textContent = '❌ No se encontraron coincidencias.';
            }
        });
    </script>

    <!-- Modal para eliminar pedido -->
    <div class="modal fade" id="modalEliminarPedido" tabindex="-1" aria-labelledby="modalEliminarLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="formEliminarPedido" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Eliminar Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="pedido" id="modalPedidoId">
                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo de eliminación</label>
                        <textarea class="form-control" name="motivo" id="motivo" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</body>

<?php include '../css/footer.php'; ?>

</html>