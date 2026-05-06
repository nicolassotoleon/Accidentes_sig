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
        $_SESSION['rol'] = $row['rol']; // Guardamos el rol en la sesión 
        
        echo $row['rol']; 
    } else {
        echo "error";
    }
} else {
    echo "error";
}
?>