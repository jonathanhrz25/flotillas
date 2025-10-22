<?php
header('Content-Type: application/json');

if (!isset($_GET['pedido'])) {
    echo json_encode(['error' => 'No se proporcionó el número de pedido']);
    exit;
}

$pedido = $_GET['pedido'];

$serverName = "192.168.1.112";
$connectionOptions = array(
    "Database" => "ALMACEN",
    "Uid" => "sa",
    "PWD" => "S3rv4*",
    "Encrypt" => false,
    "TrustServerCertificate" => true
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    echo json_encode(['error' => 'Error de conexión']);
    exit;
}

$sql = "SELECT C2 FROM [ALMACEN].[dbo].[PEDIDOS] WHERE PEDIDO = ?";
$params = [$pedido];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(['error' => 'Error en la consulta']);
    exit;
}

if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $c2 = $row['C2'];

    if (!empty($c2)) {
        // Elimina espacios y cajas vacías
        $cajas = array_filter(array_map('trim', explode(',', $c2)));

        echo json_encode([
            'cantidad' => count($cajas),
            'cajas' => $cajas
        ]);
    } else {
        echo json_encode(['error' => 'No se encontraron cajas en el pedido.']);
    }
} else {
    echo json_encode(['error' => 'Pedido no encontrado.']);
}