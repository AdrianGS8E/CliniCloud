<?php
// conexion.php

// Detectar subdominio
$host_http = $_SERVER['HTTP_HOST'];
$subdominio = explode('.', $host_http)[0];

// Valores por defecto (localhost)
$host = 'localhost';


$base_datos = 'clinicloud_db';
$usuario = 'root';
$contrasena = '';

// Cambiar si el subdominio es "rosseth"
if ($subdominio === 'odontic') {
    $host = 'localhost'; // o el host remoto si aplica
    $usuario = 'u279478716_odontic_cs';
    $contrasena = 'RxTY*0q*';
    $base_datos = 'u279478716_odontic_cs';
}





// Crear conexión

$link = mysqli_connect($host, $usuario, $contrasena, $base_datos);

// Verificar conexión
if (!$link) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Establecer el charset
mysqli_set_charset($link, "utf8");

//echo "Conexión exitosa.";
?>
