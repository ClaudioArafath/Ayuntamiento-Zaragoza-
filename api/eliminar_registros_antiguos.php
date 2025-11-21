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
    
    // 1. PRIMERO: Contar registros que se eliminarán (para logging)
    $queryContar = "SELECT COUNT(*) as total FROM ordenes_backup WHERE date < ? AND estatus = 0";
    $stmtContar = $conn->prepare($queryContar);
    $stmtContar->bind_param("s", $fechaLimite);
    $stmtContar->execute();
    $resultado = $stmtContar->get_result();
    $fila = $resultado->fetch_assoc();
    $totalPendientes = $fila['total'];
    $stmtContar->close();
    
    $eliminadosBackup = 0;
    $eliminadosOriginal = 0;
    
    // 2. ELIMINAR de ordenes_backup (tu tabla)
    $queryBackup = "DELETE FROM ordenes_backup WHERE date < ? AND estatus = 0";
    $stmtBackup = $conn->prepare($queryBackup);
    $stmtBackup->bind_param("s", $fechaLimite);
    
    if ($stmtBackup->execute()) {
        $eliminadosBackup = $stmtBackup->affected_rows;
    }
    $stmtBackup->close();
    
    // 3. ELIMINAR de ordenes (tabla original de Lycaios)
    $queryOriginal = "DELETE FROM ordenes WHERE date < ? AND estatus = 0";
    $stmtOriginal = $conn->prepare($queryOriginal);
    $stmtOriginal->bind_param("s", $fechaLimite);
    
    if ($stmtOriginal->execute()) {
        $eliminadosOriginal = $stmtOriginal->affected_rows;
    }
    $stmtOriginal->close();
    
    $conn->close();
    
    // Respuesta con detalles de ambas eliminaciones
    $response = [
        'success' => true,
        'eliminados_backup' => $eliminadosBackup,
        'eliminados_original' => $eliminadosOriginal,
        'total_pendientes' => $totalPendientes,
        'fechaLimite' => $fechaLimite,
        'mensaje' => "Eliminados: $eliminadosBackup de backup, $eliminadosOriginal de original"
    ];
    
    // Log detallado
    error_log("Limpieza automática: $eliminadosBackup de backup, $eliminadosOriginal de original (anteriores a $fechaLimite)");
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log del error
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