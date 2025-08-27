<?php
session_name('Flotillas');
session_start();
include '../php/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/inicioSesion.php");
    exit();
}

$conn = connectMysqli();
$pedido = $_GET['pedido'] ?? '';

if (empty($pedido)) {
    echo "Pedido no especificado.";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM data_form WHERE pedido = ?");
$stmt->bind_param("s", $pedido);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Pedido no encontrado.";
    exit;
}

$pedidoData = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visualizar Pedido</title>
    <link rel="shortcut icon" href="../img/icono2.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .card {
            margin-top: 30px;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .table th {
            width: 35%;
        }

        .imagenes {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .imagenes div {
            text-align: center;
        }

        .imagenes img {
            max-height: 200px;
            border: 1px solid #ccc;
            border-radius: 8px;
            max-width: 100%;
        }

        body {
            padding-top: 80px;
        }
    </style>
</head>

<body class="container">

    <!-- Barra superior -->
    <nav class="navbar navbar-dark bg-dark fixed-top" style="background-color: #081856!important;">
        <div class="container-fluid">
            <a class="navbar-brand text-white" href="../php/principal.php">
                <img src="../img/loguito2.png" alt="" height="45" class="d-inline-block align-text-top">
            </a>
        </div>
    </nav>

    <!-- Contenido principal -->
    <div class="card shadow">
        <div class="card-body">
            <h5 class="card-title mb-4">
                <i class="bi bi-clipboard-check"></i> Detalles del Pedido #
                <?= htmlspecialchars($pedidoData['pedido']) ?>
            </h5>

            <table class="table table-bordered table-striped">
                <?php foreach ($pedidoData as $campo => $valor): ?>
                    <?php if (in_array($campo, ['foto_recibido', 'foto_cajas']))
                        continue; ?>
                    <tr>
                        <th><?= ucwords(str_replace('_', ' ', htmlspecialchars($campo))) ?></th>
                        <td><?= nl2br(htmlspecialchars($valor)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <!-- Imágenes -->
            <div class="mt-4">
                <h6 class="text-center"><i class="bi bi-images"></i> Fotografías:</h6>
                <div class="imagenes mt-3">
                    <?php if (!empty($pedidoData['foto_recibido']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/flotillas/' . $pedidoData['foto_recibido'])): ?>
                        <div>
                            <strong>Recibido</strong><br>
                            <img src="../<?= $pedidoData['foto_recibido'] ?>" alt="Foto Recibido"
                                class="img-fluid mx-auto d-block">
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($pedidoData['foto_cajas']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/flotillas/' . $pedidoData['foto_cajas'])): ?>
                        <div>
                            <strong>Cajas</strong><br>
                            <img src="../<?= $pedidoData['foto_cajas'] ?>" alt="Foto Cajas"
                                class="img-fluid mx-auto d-block">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div><br><br><br><br>

    <?php include '../css/footer.php'; ?>
</body>

</html>