<?php
session_start();

// Validar sesión
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Conexión a la base de datos
$host = "localhost";
$port = 3311;
$user = "root";
$password = "";
$database = "lycaios_pos";

$conn_lycaios = new mysqli($host, $user, $password, $database, $port);
if ($conn_lycaios->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

// Obtener folio desde parámetro GET
$folio = isset($_GET['folio']) ? trim($_GET['folio']) : '';

if (empty($folio)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Folio no especificado']);
    exit();
}

// Buscar comprobante por folio
$sql_concepto = "SELECT c.name as categoria 
                 FROM topseller t 
                 LEFT JOIN categorias c ON t.categoryid = c.id 
                 WHERE t.itemid = ? 
                 LIMIT 1";

$stmt = $conn_lycaios->prepare($sql);
$stmt->bind_param("s", $folio);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $comprobante = $result->fetch_assoc();
    
    // Intentar obtener el concepto principal
    $sql_concepto = "SELECT c.name as categoria 
                     FROM topseller t 
                     LEFT JOIN categorias c ON t.categoryid = c.id 
                     WHERE t.invoiceid = ? 
                     LIMIT 1";
    
    $stmt_concepto = $conn_lycaios->prepare($sql_concepto);
    $stmt_concepto->bind_param("i", $comprobante['id']);
    $stmt_concepto->execute();
    $result_concepto = $stmt_concepto->get_result();
    
    if ($result_concepto->num_rows > 0) {
        $concepto_data = $result_concepto->fetch_assoc();
        $comprobante['concepto'] = $concepto_data['categoria'];
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'comprobante' => $comprobante
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'No se encontró ningún comprobante con ese folio'
    ]);
}

$conn_lycaios->close();
?>