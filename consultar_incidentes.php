<?php
header('Content-Type: application/json');
include 'conexion.php';
$conn = conexion();

// Consultamos todos los registros ordenados por los más recientes
$query = "SELECT * FROM registros ORDER BY fecha_registro DESC";
$result = pg_query($conn, $query);

if (!$result) {
    echo json_encode(["error" => pg_last_error($conn)]);
    exit;
}

$incidentes = pg_fetch_all($result) ?: [];
echo json_encode($incidentes);
?>