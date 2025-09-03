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

$filtro = $_GET['filtro'] ?? 'mes'; // por defecto "mes"

switch ($filtro) {
    case 'dia':
        $sql = "SELECT DATE(date) as periodo, SUM(total) as ingresos
                FROM invoice
                GROUP BY DATE(date)
                ORDER BY DATE(date) ASC";
        break;
    case 'semana':
        $sql = "SELECT YEARWEEK(date) as periodo, SUM(total) as ingresos
                FROM invoice
                GROUP BY YEARWEEK(date)
                ORDER BY YEARWEEK(date) ASC";
        break;
    default: // mes
        $sql = "SELECT DATE_FORMAT(date, '%Y-%m') as periodo, SUM(total) as ingresos
                FROM invoice
                GROUP BY DATE_FORMAT(date, '%Y-%m')
                ORDER BY periodo ASC";
}

$result = $conn->query($sql);
$labels = [];
$data = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['periodo'];
        $data[] = $row['ingresos'];
    }
}

$conn->close();

echo json_encode(['labels' => $labels, 'data' => $data]);
