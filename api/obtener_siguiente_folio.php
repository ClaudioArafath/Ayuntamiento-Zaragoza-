<?php
// api/obtener_siguiente_folio.php
header('Content-Type: application/json');

require_once '../config/database.php';

try {
    // Conectar a la base de datos
    $conn = conectarLycaidosPOS();
    
    // Obtener el último folio
    $stmt = $conn->prepare("SELECT folio FROM sanitarios ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $last_record = $result->fetch_assoc();
        $last_folio = $last_record['folio'];
        
        // Extraer el número del folio (COM-001 -> 001)
        preg_match('/COM-(\d+)/', $last_folio, $matches);
        $last_number = isset($matches[1]) ? intval($matches[1]) : 0;
        $next_number = $last_number + 1;
    } else {
        // Si no hay registros, empezar desde 1
        $next_number = 1;
    }
    
    // Formatear el siguiente folio
    $next_folio = sprintf("COM-%03d", $next_number);
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'next_folio' => $next_folio
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener el siguiente folio: ' . $e->getMessage()
    ]);
}
?>
