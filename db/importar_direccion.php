<?php
session_name('Flotillas');
session_start();

require '../php/connect.php';
$conn = connectMysqli();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/inicioSesion.php");
    exit();
}

// Función para obtener lat/lng con Google Maps
function getCoordinates($address)
{
    $apiKey = "AIzaSyDiZcKXugtBRzqpDQ0ax7Zzgt36rXEj1Lw"; // <-- Reemplazar con tu clave
    $address = urlencode($address);
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$apiKey}";

    $response = file_get_contents($url);
    if ($response === FALSE) {
        return [0, 0]; // En caso de error
    }

    $data = json_decode($response, true);

    if (!empty($data['results'][0]['geometry']['location'])) {
        $lat = $data['results'][0]['geometry']['location']['lat'];
        $lng = $data['results'][0]['geometry']['location']['lng'];
        return [$lat, $lng];
    }

    return [0, 0]; // Si no encuentra la dirección
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $fileName = $_FILES['csv_file']['tmp_name'];

    if ($_FILES['csv_file']['size'] > 0) {
        $file = fopen($fileName, "r");

        // Saltar encabezado
        $header = fgetcsv($file, 1000, ",");

        $insertados = 0;
        $errores = 0;

        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            $num_cliente = $conn->real_escape_string(trim($data[0]));
            $cliente = strtoupper($conn->real_escape_string(trim($data[1])));
            $telefono = $conn->real_escape_string(trim($data[2]));
            $ubicacion = $conn->real_escape_string(trim($data[3]));

            if ($num_cliente != "" && $cliente != "" && $ubicacion != "") {
                // Obtener coordenadas desde Google Maps
                list($lat, $lng) = getCoordinates($ubicacion);

                $sql = "INSERT INTO direccion (num_cliente, cliente, telefono, ubicacion, lat, lng) 
                        VALUES ('$num_cliente', '$cliente', '$telefono', '$ubicacion', '$lat', '$lng')";

                if ($conn->query($sql)) {
                    $insertados++;
                } else {
                    $errores++;
                }
            } else {
                $errores++;
            }
        }

        fclose($file);

        header("Location: http://localhost/flotillas/php/principal.php?msg=Se importaron $insertados registros. Errores: $errores");
        exit();
    }
}
header("Location: http://localhost/flotillas/php/principal.php?msg=No se pudo procesar el archivo CSV");
exit();
?>