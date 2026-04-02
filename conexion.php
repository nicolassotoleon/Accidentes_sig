<?php
function conexion(){
$host = 'localhost';
$port = '5432';
$base_datos = 'incidentes_cali';
$usuario = 'postgres';
$pass = 'admin';
$conexion = pg_connect("host=$host port=$port dbname=$base_datos user=$usuario password=$pass")
            or die("Error de Conexion".pg_last_error());
			
return($conexion);
}
?>