<?php
// guardar_incidente.php
header('Content-Type: application/json');
include 'conexion.php';

$conn = conexion();

// Recibir datos del FormData
$tipo        = $_POST['tipo'];
$prioridad   = strtoupper($_POST['prioridad']); // Se guarda en MAYÚSCULAS para la DB
$descripcion = $_POST['descripcion'];
$lat         = $_POST['latitud'];
$lng         = $_POST['longitud'];
$direccion   = $_POST['direccion'];
$comuna      = isset($_POST['comuna']) ? $_POST['comuna'] : null;
$barrio      = isset($_POST['barrio']) ? $_POST['barrio'] : null;

$rutaFoto = null;

// Manejo de la fotografía
if (isset($_FILES['fotografia']) && $_FILES['fotografia']['error'] == 0) {
    $nombre = time() . "_" . basename($_FILES['fotografia']['name']);
    $ruta = "uploads/" . $nombre;
    if (!file_exists("uploads")) { 
        mkdir("uploads", 0777, true); 
    }
    if (move_uploaded_file($_FILES['fotografia']['tmp_name'], $ruta)) {
        $rutaFoto = $ruta;
    }
}

$sql = "INSERT INTO registros 
(tipo_incidente, prioridad, descripcion, latitud, longitud, direccion, comuna, barrio, fotografia, fecha_reporte, estado)
VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, NOW(), 'Pendiente')";

$result = pg_query_params($conn, $sql, [
    $tipo, $prioridad, $descripcion, $lat, $lng, $direccion, $comuna, $barrio, $rutaFoto
]);

if ($result) {
    echo json_encode(["status" => "ok", "message" => "Incidente registrado correctamente"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => pg_last_error($conn)]);
}
?>