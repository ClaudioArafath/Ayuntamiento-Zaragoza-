<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $folio = $_GET['folio'] ?? '';
    
    if (empty($folio)) {
        throw new Exception('Folio no proporcionado');
    }

    $conn = conectarLycaidosPOS();

    // Buscar factura por invoicecode
    $sql = "SELECT id, invoicecode, date, employee, subtotal, total, payed, description, items 
            FROM invoice 
            WHERE invoicecode = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $folio);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("No se encontró ninguna factura con el folio: $folio");
    }

    $factura = $result->fetch_assoc();
    $stmt->close();

    // Extraer datos del cliente del campo description (JSON)
    $clienteNombre = 'Publico General';
    $clienteDireccion = 'N/A';
    
    if (!empty($factura['description'])) {
        $descriptionData = json_decode($factura['description'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($descriptionData)) {
            $clienteNombre = $descriptionData['nombre'] ?? $clienteNombre;
            $clienteDireccion = $descriptionData['direccion'] ?? $clienteDireccion;
        }
    }

    // Agregar datos del cliente al resultado
    $factura['cliente_nombre'] = $clienteNombre;
    $factura['cliente_direccion'] = $clienteDireccion;

    $conn->close();

    echo json_encode([
        'success' => true,
        'factura' => $factura
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
