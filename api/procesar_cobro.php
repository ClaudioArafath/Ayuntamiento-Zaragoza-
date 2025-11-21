<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    $folio = $input['folio'] ?? '';
    $montoRecibido = $input['monto_recibido'] ?? 0;
    $cambio = $input['cambio'] ?? 0;

    if (empty($folio)) {
        throw new Exception('Folio no proporcionado');
    }

    // Conectar a la base de datos
    $conn = conectarLycaidosPOS();

    // Verificar que la orden existe y está pendiente en ordenes_backup
    $sql = "SELECT id, estatus FROM ordenes_backup WHERE code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $folio);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Orden no encontrada en el backup');
    }

    $orden = $result->fetch_assoc();

    if ($orden['estatus'] == 1) {
        throw new Exception('La orden ya ha sido cobrada');
    }

    // INICIAR TRANSACCIÓN para asegurar consistencia
    $conn->begin_transaction();

    try {
        // 1. Actualizar el estatus a pagado (1) en ordenes_backup
        $sqlUpdateBackup = "UPDATE ordenes_backup SET estatus = 1 WHERE code = ?";
        $stmtUpdateBackup = $conn->prepare($sqlUpdateBackup);
        $stmtUpdateBackup->bind_param("s", $folio);
        
        if (!$stmtUpdateBackup->execute()) {
            throw new Exception('Error al actualizar el estatus en ordenes_backup');
        }

        // 2. Eliminar la orden de la tabla ordenes (tabla original)
        $sqlDeleteOrden = "DELETE FROM ordenes WHERE code = ?";
        $stmtDeleteOrden = $conn->prepare($sqlDeleteOrden);
        $stmtDeleteOrden->bind_param("s", $folio);
        
        if (!$stmtDeleteOrden->execute()) {
            throw new Exception('Error al eliminar la orden de la tabla original');
        }

        // 3. Verificar si se eliminó correctamente
        if ($stmtDeleteOrden->affected_rows === 0) {
            // La orden no existía en la tabla original, pero continuamos
            error_log("Orden con folio $folio no encontrada en tabla ordenes, pero se marcó como pagada en backup");
        }

        // CONFIRMAR TRANSACCIÓN
        $conn->commit();

        // Registrar el cobro en una tabla de transacciones (opcional)
        // Aquí puedes agregar lógica para registrar en una tabla de transacciones
        
        $response = [
            'success' => true,
            'message' => 'Cobro realizado exitosamente y orden eliminada del sistema activo',
            'data' => [
                'folio' => $folio,
                'monto_recibido' => $montoRecibido,
                'cambio' => $cambio,
                'orden_eliminada' => $stmtDeleteOrden->affected_rows > 0
            ]
        ];

        $stmtUpdateBackup->close();
        $stmtDeleteOrden->close();

    } catch (Exception $e) {
        // REVERTIR TRANSACCIÓN en caso de error
        $conn->rollback();
        throw $e;
    }

    $stmt->close();
    $conn->close();

    echo json_encode($response);

} catch (Exception $e) {
    // Asegurarse de cerrar conexión si hay error
    if (isset($conn)) {
        $conn->close();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>