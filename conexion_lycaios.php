<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "lycaios_pos";
$port       = 3311;

// Crear conexión
$conn_lycaios = new mysqli($servername, $username, $password, $dbname, $port);

// Verificar conexión
if ($conn_lycaios->connect_error) {
    die("Error de conexión: " . $conn_lycaios->connect_error);
}
?>
