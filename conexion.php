<?php
$servername = "127.0.0.1";
$dbuser = "root";
$dbpass = "";
$dbname = "ayuntamiento";

$conn = new mysqli($servername, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
