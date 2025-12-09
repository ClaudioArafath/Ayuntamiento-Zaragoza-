<?php
header('Content-Type: application/json');

// Incluir la configuración de la base de datos
include '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener el input JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    $folio = $input['folio'] ?? '';
    $motivo = $input['motivo'] ?? '';
    
    if (empty($folio)) {
        echo json_encode(['success' => false, 'message' => 'Folio no proporcionado']);
        exit;
    }
    
    // Conectar a ambas bases de datos
    $connAyuntamiento = conectarAyuntamiento(); // Para ordenes_backup
    $connLycaios = conectarLycaidosPOS();       // Para ordenes
    
    if ($connAyuntamiento->connect_error || $connLycaios->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
        exit;
    }
    
    try {
        $eliminadosBackup = 0;
        $eliminadosOriginal = 0;
        
        // PASO 1: Eliminar de ordenes_backup en ayuntamiento
        $stmt1 = $connAyuntamiento->prepare("DELETE FROM ordenes_backup WHERE code = ?");
        $stmt1->bind_param("s", $folio);
        $stmt1->execute();
        $eliminadosBackup = $stmt1->affected_rows;
        $stmt1->close();
        
        // PASO 2: Eliminar de ordenes en lycaios_pos (para que no aparezca en el software)
        $stmt2 = $connLycaios->prepare("DELETE FROM ordenes WHERE code = ?");
        $stmt2->bind_param("s", $folio);
        $stmt2->execute();
        $eliminadosOriginal = $stmt2->affected_rows;
        $stmt2->close();
        
        if ($eliminadosBackup > 0 || $eliminadosOriginal > 0) {
            echo json_encode([
                'success' => true, 
                'message' => "Orden $folio cancelada correctamente"
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'No se encontró ninguna orden con el folio proporcionado'
            ]);
        }
        
        $connAyuntamiento->close();
        $connLycaios->close();
        
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