<?php
header('Content-Type: application/json');
include 'conexion.php';
$conn = conexion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibimos los datos del formulario
    $tipo        = $_POST['tipo_incidente'] ?? '';
    $prioridad   = $_POST['prioridad'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $lat         = $_POST['latitud'] ?? '';
    $lng         = $_POST['longitud'] ?? '';
    $direccion   = $_POST['direccion'] ?? '';

    // Procesar la foto
    $rutaFoto = null;
    if (isset($_FILES['fotografia']) && $_FILES['fotografia']['error'] === 0) {
        $dir = "uploads/";
        if (!file_exists($dir)) mkdir($dir, 0777, true);
        $nombreFoto = time() . "_" . basename($_FILES['fotografia']['name']);
        $rutaDestino = $dir . $nombreFoto;
        if (move_uploaded_file($_FILES['fotografia']['tmp_name'], $rutaDestino)) {
            $rutaFoto = $rutaDestino;
        }
    }

    // SQL ajustado a tu tabla (comuna y barrio se omiten por el trigger)
    // Usamos ST_SetSRID y ST_MakePoint para el campo geom
    $sql = "INSERT INTO registros (
                tipo_incidente, prioridad, descripcion, latitud, longitud, 
                direccion, fotografia, geom, fecha_registro
            ) VALUES ($1, $2, $3, $4, $5, $6, $7, 
                ST_SetSRID(ST_MakePoint($5, $4), 4326), 
                NOW()
            )";

    $params = [$tipo, $prioridad, $descripcion, $lat, $lng, $direccion, $rutaFoto];
    $result = pg_query_params($conn, $sql, $params);

    if ($result) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => pg_last_error($conn)]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Acceso no permitido"]);
}
?>