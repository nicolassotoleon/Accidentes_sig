<?php
// consultar_incidentes.php
header('Content-Type: application/json');
error_reporting(0); // Desactivar errores en output
include 'conexion.php';

$conn = conexion();

// Verificar que la conexión sea válida
if (!$conn) {
    echo json_encode(["error" => "Error de conexión a la base de datos"]);
    exit;
}

// Cambia 'id_registro' por el nombre correcto de tu columna ID
// Si tu tabla no tiene id_registro, usa 'id' o como se llame
$query = "SELECT * FROM registros ORDER BY id_registro DESC";
$result = pg_query($conn, $query);

if (!$result) {
    echo json_encode(["error" => "Error en la consulta: " . pg_last_error($conn)]);
    exit;
}

$incidentes = pg_fetch_all($result);

// Si no hay datos, devolvemos un array vacío
if (!$incidentes) {
    echo json_encode([]);
} else {
    echo json_encode($incidentes);
}
?>