<?php
session_start();
include '../php/connect.php';

$conn = connectMysqli();

function subirImagen($inputName) {
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

// Recoger datos del formulario
$fecha           = $_POST['fecha'] ?? '';
$operador        = $_POST['operador'] ?? '';
$unidad_id       = isset($_POST['unidad']) ? (int) $_POST['unidad'] : null;
$km_salida       = $_POST['km_salida'] ?? '';
$km_carga        = $_POST['km_carga'] ?? '';
$precio_litro    = ($_POST['precio_litro'] !== '') ? (float) $_POST['precio_litro'] : null;
$cantidad_litros = ($_POST['cantidad_litros'] !== '') ? (float) $_POST['cantidad_litros'] : null;
$total_cargado   = ($_POST['total_cargado'] !== '') ? (float) $_POST['total_cargado'] : null;

$foto_tablero    = subirImagen('foto_tablero');
$foto_km_carga   = subirImagen('foto_km_carga');

// ✅ OBTENER EL CEDIS DEL USUARIO QUE REGISTRA
$cedis = null;
if (!empty($operador)) {
    $stmtCedis = $conn->prepare("SELECT cedis FROM usuarios WHERE usuario = ?");
    $stmtCedis->bind_param("s", $operador);
    $stmtCedis->execute();
    $stmtCedis->bind_result($cedis);
    $stmtCedis->fetch();
    $stmtCedis->close();
}

// Validación de campos obligatorios
$errores = [];
if (empty($fecha))        $errores[] = 'fecha';
if (empty($operador))     $errores[] = 'operador';
if (empty($unidad_id))    $errores[] = 'unidad';
if (empty($km_salida))    $errores[] = 'km_salida';
if (empty($km_carga))     $errores[] = 'km_carga';
if (!$foto_tablero)       $errores[] = 'foto_tablero';
if (!$foto_km_carga)      $errores[] = 'foto_km_carga';
if (empty($cedis))        $errores[] = 'cedis (interno)';

if (!empty($errores)) {
    http_response_code(400);
    echo "Faltan campos obligatorios: " . implode(', ', $errores);
    exit;
}

// ✅ INSERT con unidad_id
$sql = "INSERT INTO gas (
            fecha, operador, cedis, unidad_id, foto_tablero, km_salida,
            foto_km_carga, km_carga, precio_litro, cantidad_litros, total_cargado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo "Error en la preparación SQL: " . $conn->error;
    exit;
}

// Parámetros
$parametros = [
    &$fecha,
    &$operador,
    &$cedis,
    &$unidad_id,
    &$foto_tablero,
    &$km_salida,
    &$foto_km_carga,
    &$km_carga,
    &$precio_litro,
    &$cantidad_litros,
    &$total_cargado
];

// Determinar los tipos
$tipos = '';
foreach ($parametros as $valor) {
    if (is_int($valor)) {
        $tipos .= 'i';
    } elseif (is_float($valor)) {
        $tipos .= 'd';
    } else {
        $tipos .= 's';
    }
}

array_unshift($parametros, $tipos);
call_user_func_array([$stmt, 'bind_param'], $parametros);

// Ejecutar
if ($stmt->execute()) {
    header('Location: ../php/principal.php');
    exit();
} else {
    http_response_code(500);
    echo "Error al insertar: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
