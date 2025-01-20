<?php
// conexion.php

// Incluir el archivo de configuración
include_once 'config.php';

// Crear la conexión
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

$conn->set_charset('utf8mb4');

// Comprobar la conexión
if ($conn->connect_error) {
    die("Error en la conexión a la base de datos: " . $conn->connect_error);
}

//echo "Versión de MySQL: " . $conn->server_info;

?>
