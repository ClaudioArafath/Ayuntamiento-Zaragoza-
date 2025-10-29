<?php
// api/guardar_orden_personalizada.php
header('Content-Type: application/json');

require_once '../config/database.php';

// Obtener datos del POST
$folio = $_POST['folio'] ?? '';
$nombre_cliente = $_POST['nombre_cliente'] ?? '';
$cantidad_total = $_POST['cantidad_total'] ?? '';
$descripcion = $_POST['descripcion'] ?? 'Servicio de sanitarios';

// Validar datos
if (empty($folio) || empty($nombre_cliente) || empty($cantidad_total)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

try {
    // Conectar a la base de datos
    $conn = conectarLycaidosPOS();
    
    // Verificar si el folio ya existe
    $stmt = $conn->prepare("SELECT id FROM sanitarios WHERE folio = ?");
    $stmt->bind_param("s", $folio);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'El folio ya existe']);
        exit;
    }
    $stmt->close();
    
    // Generar código QR único
    $qr_data = json_encode([
        'folio' => $folio,
        'monto' => $cantidad_total,
        'tipo' => 'sanitarios'
    ]);
    $qr_code = base64_encode($qr_data);
    
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