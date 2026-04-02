<?php
session_start();
include("conexion.php");

$conn = conexion();

$correo = $_POST['usuario'];
$password = $_POST['password'];

$query = "SELECT * FROM usuarios WHERE correo='$correo'";
$result = pg_query($conn, $query);

if($row = pg_fetch_assoc($result)){
    if(password_verify($password, $row['password'])){
        $_SESSION['usuario'] = $correo;
        echo "ok";
    } else {
        echo "error";
    }
}else{
    echo "error";
}
?>