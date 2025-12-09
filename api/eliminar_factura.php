<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$log_file = '../logs/facturas.log';
$timestamp = date('Y-m-d H:i:s');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Datos inválidos');
    }

    $id = $data['id'] ?? '';

    if (empty($id)) {
        throw new Exception('ID de factura no proporcionado');
    }

    $conn = conectarLycaidosPOS();

    // Primero obtener información de la factura para el log
    $sqlInfo = "SELECT invoicecode, total FROM invoice WHERE id = ?";
    $stmtInfo = $conn->prepare($sqlInfo);
    $stmtInfo->bind_param("i", $id);
    $stmtInfo->execute();
    $resultInfo = $stmtInfo->get_result();
    
    if ($resultInfo->num_rows === 0) {
        throw new Exception('Factura no encontrada');
    }
    
    $facturaInfo = $resultInfo->fetch_assoc();
    $stmtInfo->close();

    // Eliminar factura
    $sql = "DELETE FROM invoice WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Log de auditoría
        $logMessage = "[$timestamp] 🗑️ Factura ELIMINADA - ID: $id, Folio: {$facturaInfo['invoicecode']}, Total: {$facturaInfo['total']}\n";
        file_put_contents($log_file, $logMessage, FILE_APPEND);

        $stmt->close();
        $conn->close();

        echo json_encode([
            'success' => true,
            'message' => 'Factura eliminada exitosamente'
        ]);
    } else {
        throw new Exception('Error al eliminar la factura: ' . $stmt->error);
    }

} catch (Exception $e) {
    $error_msg = $e->getMessage();
    file_put_contents($log_file, "[$timestamp] ❌ ERROR al eliminar factura: $error_msg\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $error_msg
    ]);
}
?>
