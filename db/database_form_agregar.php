<?php
session_start();
include '../php/connect.php';

$conn = connectMysqli();

function subirImagen($inputName)
{
    $carpetaFisica = 'C:/xampp/htdocs/flotillas/data_img/';
    $carpetaDB = 'data_img/';

    if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
        $nombreTmp = $_FILES[$inputName]['tmp_name'];
        $nombreOriginal = basename($_FILES[$inputName]['name']);
        $nombreUnico = uniqid() . '_' . $nombreOriginal;
        $rutaFisica = $carpetaFisica . $nombreUnico;
        $rutaDB = $carpetaDB . $nombreUnico;

        if (!file_exists($carpetaFisica)) {
            mkdir($carpetaFisica, 0777, true);
        }

        if (move_uploaded_file($nombreTmp, $rutaFisica)) {
            return $rutaDB;
        }
    }
    return null;
}

// Recoger datos
$pedido           = $_POST['pedido'] ?? '';
$entregado        = $_POST['entregado'] ?? '';
$comentarios      = $_POST['comentarios_y_observaciones'] ?? '';
$lista_cajas_raw  = $_POST['lista-cajas'] ?? '[]';

// Convertir JSON a string formateado
$cajas_array = json_decode($lista_cajas_raw, true);
$lista_cajas_formateado = 'Caja: ' . implode(', ', $cajas_array) . ' ✅';

// Subir imágenes
$fotoRecibido = subirImagen('fotoRecibido');
$fotoCajas    = subirImagen('fotoCajas');

// Validación
$errores = [];

if (empty($pedido))    $errores[] = 'pedido';
if (empty($entregado)) $errores[] = 'entregado';
if (!$fotoRecibido)    $errores[] = 'fotoRecibido';
if (!$fotoCajas)       $errores[] = 'fotoCajas';

if (!empty($errores)) {
    echo "<script>alert('Faltan campos obligatorios: " . implode(', ', $errores) . "'); window.history.back();</script>";
    exit;
}

// Verificar si existe ese pedido
$check = $conn->prepare("SELECT id FROM data_form WHERE pedido = ?");
$check->bind_param("s", $pedido);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $sql = "UPDATE data_form
            SET 
                foto_recibido = ?,
                foto_cajas = ?,
                entregado = ?,
                comentarios_y_observaciones = ?,
                cajas_validadas = ?
            WHERE pedido = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $fotoRecibido, $fotoCajas, $entregado, $comentarios, $lista_cajas_formateado, $pedido);

    if ($stmt->execute()) {
        echo "<script>alert('Datos actualizados correctamente'); window.location.href = '../php/principal.php';</script>";
    } else {
        echo "<script>alert('Error al actualizar: " . addslashes($stmt->error) . "'); window.history.back();</script>";
    }

    $stmt->close();
} else {
    echo "<script>alert('El número de pedido no existe en la base de datos'); window.history.back();</script>";
}

$check->close();
$conn->close();
?>
