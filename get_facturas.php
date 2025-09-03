<?php
$host = "localhost";
$port = 3311;
$user = "root";
$password = "";
$database = "lycaios_pos";

$conn = new mysqli($host, $user, $password, $database, $port);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$sql = "SELECT id, invoicecode, date, total FROM invoice ORDER BY date DESC LIMIT 10";
$result = $conn->query($sql);

$facturas = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $facturas[] = $row;
    }
}

$conn->close();

echo json_encode($facturas);
