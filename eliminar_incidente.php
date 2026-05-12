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

    if (!$id) {
        $response = ['status' => 'error', 'message' => 'ID no proporcionado'];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    $id = (int)$id;

    $sql = "DELETE FROM registros WHERE id = $id";
    $result = pg_query($conn, $sql);

    if ($result) {
        if (pg_affected_rows($result) > 0) {
            $response = ['status' => 'success'];
        } else {
            $response = ['status' => 'error', 'message' => 'No se encontró el registro'];
        }
    } else {
        $response = ['status' => 'error', 'message' => pg_last_error($conn)];
    }
}

ob_end_clean();
echo json_encode($response);
exit;
?>
