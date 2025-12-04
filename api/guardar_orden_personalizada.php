<?php
// api/guardar_orden_personalizada.php
header('Content-Type: application/json');

require_once '../config/database.php';

// Obtener datos del POST
$nombre_cliente = $_POST['nombre_cliente'] ?? '';
$cantidad_total = $_POST['cantidad_total'] ?? '';
$descripcion = $_POST['descripcion'] ?? 'Servicio de sanitarios';

// Validar datos
if (empty($nombre_cliente) || empty($cantidad_total)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

try {
    // Conectar a la base de datos
    $conn = conectarLycaidosPOS();
    
    // Generar folio automático incremental
    $stmt = $conn->prepare("SELECT folio FROM sanitarios ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $last_record = $result->fetch_assoc();
        $last_folio = $last_record['folio'];
        
        // Extraer el número del folio (COM-001 -> 001)
        preg_match('/COM-(\d+)/', $last_folio, $matches);
        $last_number = isset($matches[1]) ? intval($matches[1]) : 0;
        $new_number = $last_number + 1;
    } else {
        // Si no hay registros, empezar desde 1
        $new_number = 1;
    }
    
    // Formatear el nuevo folio con ceros a la izquierda (COM-001, COM-002, etc.)
    $folio = sprintf("COM-%03d", $new_number);
    $stmt->close();
    
    // Generar URL de verificación para el QR (similar a comprobantes)
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    $verification_url = $base_url . "/DB_lycaios/api/verificar_sanitario.php?code=" . urlencode($folio);
    
    // Guardar la URL de verificación como qr_code
    $qr_code = $verification_url;
    
    // Insertar en la base de datos
    $stmt = $conn->prepare("INSERT INTO sanitarios (folio, nombre_cliente, cantidad_total, descripcion, qr_code) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdss", $folio, $nombre_cliente, $cantidad_total, $descripcion, $qr_code);
    
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        
        // Generar URL del comprobante
        $comprobante_url = "comprobante_sanitarios.php?id=" . $id;
        
        echo json_encode([
            'success' => true,
            'id' => $id,
            'folio' => $folio,
            'comprobante_url' => $comprobante_url
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar en la base de datos: ' . $conn->error]);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Excepción: ' . $e->getMessage()]);
}
?>