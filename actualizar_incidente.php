<?php
ob_start();
header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(E_ALL);

include 'conexion.php';
$conn = conexion();

$response = ['status' => 'error', 'message' => 'Error desconocido'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id'] ?? null;
    $tipo = $_POST['tipo_incidente'] ?? null;
    $prioridad = $_POST['prioridad'] ?? null;

    // 🔎 Validación básica
    if (!$id || !$tipo || !$prioridad) {
        $response = ['status' => 'error', 'message' => 'Faltan datos obligatorios'];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    // 🔒 Sanitización
    $id = (int)$id;
    $tipo = pg_escape_string($conn, $tipo);
    $prioridad = pg_escape_string($conn, strtoupper($prioridad)); // fuerza consistencia

    // 🧠 Validación de valores permitidos (importante)
    $tipos_validos = ['inundacion', 'alumbrado_publico', 'huecos', 'transito', 'otro'];
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

    // 💾 Query
    $sql = "UPDATE registros 
            SET tipo_incidente = '$tipo', prioridad = '$prioridad' 
            WHERE id = $id";

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

// 🧹 Limpiar buffer
ob_end_clean();

// 📤 Respuesta final
echo json_encode($response);
exit;
?>