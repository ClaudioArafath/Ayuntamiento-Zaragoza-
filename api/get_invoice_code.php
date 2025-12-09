<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Obtener el código de orden
    $order_code = $_GET['order_code'] ?? '';
    
    if (empty($order_code)) {
        throw new Exception('No se proporcionó un código de orden válido');
    }
    
    // Conectar a la base de datos lycaios_pos
    $conn = conectarLycaidosPOS();
    
    // Buscar el invoice_code correspondiente al order_code
    $sql = "SELECT invoicecode FROM invoice WHERE ordercode = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $order_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('No se encontró factura para esta orden');
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'invoice_code' => $row['invoicecode']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
