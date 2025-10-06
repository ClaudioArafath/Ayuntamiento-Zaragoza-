<?php
header('Content-Type: application/json');

// Incluir la configuración de la base de datos
include '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener el input JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    $folio = $input['folio'] ?? '';
    $motivo = $input['motivo'] ?? ''; // Se recibe pero no se usa por ahora
    
    if (empty($folio)) {
        echo json_encode(['success' => false, 'message' => 'Folio no proporcionado']);
        exit;
    }
    
    $conn = conectarLycaidosPOS();
    
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
        exit;
    }
    
    try {
        // Preparar y ejecutar la consulta para eliminar por la columna 'code'
        $stmt = $conn->prepare("DELETE FROM ordenes_backup WHERE code = ?");
        $stmt->bind_param("s", $folio);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true, 
                'message' => "Orden con folio $folio eliminada correctamente"
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'No se encontró ninguna orden con el folio proporcionado'
            ]);
        }
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        // Log del error sin mostrar detalles sensibles al usuario
        error_log("Error al cancelar orden: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Error al procesar la cancelación'
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>