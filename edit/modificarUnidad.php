<?php
session_name('Flotillas');
session_start();

require '../php/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/inicioSesion.php");
    exit();
}

$conn = connectMysqli();
$id = $_GET['id'] ?? null; // Obtener el ID de la unidad que se quiere modificar

if (!$id) {
    header("Location: unidad.php");
    exit();
}

// Obtener los datos de la unidad
$stmt = $conn->prepare("SELECT * FROM unidad WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Unidad no encontrada";
    exit();
}

$unidad = $result->fetch_assoc();

// Si el formulario es enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $modelo = $_POST['modelo'];
    $placa = $_POST['placa'];

    $updateStmt = $conn->prepare("UPDATE unidad SET modelo = ?, placa = ? WHERE id = ?");
    $updateStmt->bind_param("ssi", $modelo, $placa, $id);
    if ($updateStmt->execute()) {
        header("Location: ../php/principal.php");
        exit();
    } else {
        echo "Error al actualizar la unidad.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="../img/icono2.png" type="image/x-icon">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <title>Modificar Unidad</title>
</head>

<header>
    <nav class="navbar navbar-left" style="background-color: #081856!important; text-align: left;" role="navigation">
        <div class="container-fluid">
            <a class="navbar-brand d-flex flex-row" href="../php/principal.php">
                <img src="../img/loguito2.png" alt="" height="45" class="d-inline-block align-text-top">
            </a>
        </div>
    </nav>
</header>

<body>

    <div class="container mt-5">
        <h3 class="mb-4">Modificar Unidad</h3>
        <form method="POST">
            <div class="mb-3">
                <label for="modelo" class="form-label">Modelo</label>
                <input type="text" class="form-control" id="modelo" name="modelo"
                    value="<?= htmlspecialchars($unidad['modelo']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="placa" class="form-label">Placa</label>
                <input type="text" class="form-control" id="placa" name="placa"
                    value="<?= htmlspecialchars($unidad['placa']); ?>" required>
            </div>
            <div class="text-center"><br>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

<?php include '../css/footer.php' ?>

</html>