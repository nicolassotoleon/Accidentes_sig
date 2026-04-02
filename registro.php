<?php
include("conexion.php");

$conn = conexion();

$nombres = $_POST['nombres'];
$apellidos = $_POST['apellidos'];
$correo = $_POST['correo'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$rol = $_POST['rol'];

$query = "INSERT INTO usuarios (nombres, apellidos, correo, password, rol)
VALUES ('$nombres','$apellidos','$correo','$password','$rol')";

$result = pg_query($conn, $query);

if($result){
    echo "ok";
}else{
    echo "error: " . pg_last_error($conn);
}
?>