<?php
// Evitar que se muestren errores HTML
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

try {
    // Incluir database.php con manejo de errores
    if (!@include_once '../config/database.php') {
        throw new Exception("No se pudo incluir database.php");
    }
    
    $conn = conectarLycaidosPOS();
    
    // Calcular fecha límite (5 días hábiles atrás)
    $fechaLimite = calcularFechaLimite();
    
    // Consulta para eliminar solo registros PENDIENTES (estatus = 0) y antiguos
    $query = "DELETE FROM ordenes_backup WHERE date < ? AND estatus = 0";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Error preparando consulta: " . $conn->error);
    }
    
    $stmt->bind_param("s", $fechaLimite);
    
    if ($stmt->execute()) {
        $eliminados = $stmt->affected_rows;
        
        $response = [
            'success' => true,
            'eliminados' => $eliminados,
            'fechaLimite' => $fechaLimite,
            'mensaje' => $eliminados > 0 ? 
                "Eliminados $eliminados registros pendientes antiguos" : 
                "No hay registros pendientes para eliminar"
        ];
    } else {
        throw new Exception("Error en la ejecución: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log del error (opcional)
    error_log("Error en eliminar_registros_antiguos: " . $e->getMessage());
    
    // Respuesta de error en JSON
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}

// Función para calcular 5 días hábiles atrás
function calcularFechaLimite() {
    $fecha = new DateTime();
    $diasRestantes = 5;
    
    while ($diasRestantes > 0) {
        $fecha->modify('-1 day');
        $diaSemana = $fecha->format('N'); // 1 (lunes) - 7 (domingo)
        
        // Si no es sábado (6) ni domingo (7), cuenta como día hábil
        if ($diaSemana < 6) {
            $diasRestantes--;
        }
    }
    
    return $fecha->format('Y-m-d 23:59:59');
}
?>