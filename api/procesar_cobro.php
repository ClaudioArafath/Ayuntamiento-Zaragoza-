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

    // Verificar que la orden existe y está pendiente
    $sql = "SELECT id, estatus FROM ordenes_backup WHERE code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $folio);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Orden no encontrada');
    }

    $orden = $result->fetch_assoc();

    if ($orden['estatus'] == 1) {
        throw new Exception('La orden ya ha sido cobrada');
    }

    // Actualizar el estatus a pagado (1)
    $sqlUpdate = "UPDATE ordenes_backup SET estatus = 1 WHERE code = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("s", $folio);

    if ($stmtUpdate->execute()) {
        // Registrar el cobro en una tabla de transacciones (opcional)
        // Aquí puedes agregar lógica para registrar en una tabla de transacciones
        
        $response = [
            'success' => true,
            'message' => 'Cobro realizado exitosamente',
            'data' => [
                'folio' => $folio,
                'monto_recibido' => $montoRecibido,
                'cambio' => $cambio
            ]
        ];
    } else {
        throw new Exception('Error al actualizar el estatus de la orden');
    }

    $stmt->close();
    $stmtUpdate->close();
    $conn->close();

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>