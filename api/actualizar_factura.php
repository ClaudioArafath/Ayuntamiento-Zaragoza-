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
    $date = $data['date'] ?? '';
    $employee = $data['employee'] ?? '';
    $subtotal = floatval($data['subtotal'] ?? 0);
    $total = floatval($data['total'] ?? 0);
    $payed = floatval($data['payed'] ?? 0);
    $clienteNombre = $data['cliente_nombre'] ?? 'Publico General';
    $clienteDireccion = $data['cliente_direccion'] ?? 'N/A';

    if (empty($id)) {
        throw new Exception('ID de factura no proporcionado');
    }

    // Validaciones
    if ($subtotal < 0 || $total < 0 || $payed < 0) {
        throw new Exception('Los montos no pueden ser negativos');
    }

    if (empty($employee)) {
        throw new Exception('El campo empleado no puede estar vacío');
    }

    $conn = conectarLycaidosPOS();

    // Crear JSON para description con datos del cliente
    $description = json_encode([
        'nombre' => $clienteNombre,
        'direccion' => $clienteDireccion
    ], JSON_UNESCAPED_UNICODE);

    // Convertir fecha de datetime-local a formato MySQL
    $mysqlDate = date('Y-m-d H:i:s', strtotime($date));

    // Actualizar factura
    $sql = "UPDATE invoice 
            SET date = ?, 
                employee = ?, 
                subtotal = ?, 
                total = ?, 
                payed = ?,
                description = ?
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdddsi", $mysqlDate, $employee, $subtotal, $total, $payed, $description, $id);
    
    if ($stmt->execute()) {
        // Log de auditoría
        $logMessage = "[$timestamp] ✏️ Factura actualizada - ID: $id, Empleado: $employee, Total: $total\n";
        file_put_contents($log_file, $logMessage, FILE_APPEND);

        $stmt->close();
        $conn->close();

        echo json_encode([
            'success' => true,
            'message' => 'Factura actualizada exitosamente'
        ]);
    } else {
        throw new Exception('Error al actualizar la factura: ' . $stmt->error);
    }

} catch (Exception $e) {
    $error_msg = $e->getMessage();
    file_put_contents($log_file, "[$timestamp] ❌ ERROR al actualizar factura: $error_msg\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $error_msg
    ]);
}
?>
