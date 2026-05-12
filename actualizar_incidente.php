<?php
ob_start();
header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(E_ALL);

include 'conexion.php';
$conn = conexion();

$response = ['status' => 'error', 'message' => 'Error desconocido'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id        = $_POST['id'] ?? null;
    $tipo      = $_POST['tipo_incidente'] ?? null;
    $prioridad = $_POST['prioridad'] ?? null;
    $lat       = $_POST['latitud'] ?? null;
    $lng       = $_POST['longitud'] ?? null;

    // Validación básica
    if (!$id || !$tipo || !$prioridad) {
        $response = ['status' => 'error', 'message' => 'Faltan datos obligatorios'];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    // Sanitización
    $id        = (int)$id;
    $tipo      = pg_escape_string($conn, $tipo);
    $prioridad = pg_escape_string($conn, strtoupper($prioridad));

    // Validación de valores permitidos
    $tipos_validos      = ['inundacion', 'alumbrado_publico', 'huecos', 'transito', 'otro'];
    $prioridades_validas = ['ALTA', 'MEDIA', 'BAJA'];

    if (!in_array($tipo, $tipos_validos)) {
        $response = ['status' => 'error', 'message' => 'Tipo de incidente inválido'];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    if (!in_array($prioridad, $prioridades_validas)) {
        $response = ['status' => 'error', 'message' => 'Prioridad inválida'];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    // Si se proveen coordenadas, actualizar también la ubicación
    if ($lat !== null && $lng !== null && $lat !== '' && $lng !== '') {
        $lat = (float)$lat;
        $lng = (float)$lng;

        $sql = "UPDATE registros 
                SET tipo_incidente = '$tipo', 
                    prioridad      = '$prioridad',
                    latitud        = $lat,
                    longitud       = $lng,
                    geom           = ST_SetSRID(ST_MakePoint($lng, $lat), 4326)
                WHERE id = $id";
    } else {
        $sql = "UPDATE registros 
                SET tipo_incidente = '$tipo', 
                    prioridad      = '$prioridad'
                WHERE id = $id";
    }

    $result = pg_query($conn, $sql);

    if ($result) {
        if (pg_affected_rows($result) > 0) {
            $response = ['status' => 'success'];
        } else {
            $response = ['status' => 'error', 'message' => 'No se encontró el registro o no hubo cambios'];
        }
    } else {
        $response = ['status' => 'error', 'message' => pg_last_error($conn)];
    }
}

ob_end_clean();
echo json_encode($response);
exit;
?>
