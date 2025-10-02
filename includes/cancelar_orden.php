<?php
// cancelar_orden.php
include 'config/database.php'; // Asegúrate de tener un archivo de conexión

header('Content-Type: application/json');

// Leer el input JSON
$input = json_decode(file_get_contents('php://input'), true);

$folio = $input['folio'] ?? '';
$motivo = $input['motivo'] ?? '';

if (empty($folio)) {
    echo json_encode(['success' => false, 'message' => 'Folio no proporcionado']);
    exit;
}

try {
    // Preparar la consulta para eliminar la orden
    $stmt = $pdo->prepare("DELETE FROM ordenes_backup WHERE folio = ?");
    $stmt->execute([$folio]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Orden cancelada y eliminada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró la orden con el folio proporcionado']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar la orden: ' . $e->getMessage()]);
}