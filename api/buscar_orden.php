<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    $folio = $input['folio'] ?? '';

    if (empty($folio)) {
        throw new Exception('Folio no proporcionado');
    }

    // Conectar a la base de datos
    $conn = conectarLycaidosPOS();

    // Buscar la orden en ordenes_backup
    $sql = "SELECT id, code, date, items, employee, total, estatus 
            FROM ordenes_backup 
            WHERE code = ? 
            ORDER BY id DESC 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $folio);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Orden no encontrada');
    }

    $orden = $result->fetch_assoc();

    // Procesar items para obtener descripción
    $descripcion_articulos = 'Sin descripción';
    if (!empty($orden['items'])) {
        $items = json_decode($orden['items'], true);
        if (is_array($items) && count($items) > 0) {
            $nombres = array_column($items, 'name');
            $descripcion_articulos = implode(', ', $nombres);
            // Limitar longitud
            if (strlen($descripcion_articulos) > 100) {
                $descripcion_articulos = substr($descripcion_articulos, 0, 100) . '...';
            }
        }
    }

    // Formatear respuesta
    $response = [
        'success' => true,
        'orden' => [
            'id' => $orden['id'],
            'code' => $orden['code'],
            'date' => $orden['date'],
            'employee' => $orden['employee'],
            'total' => floatval($orden['total']),
            'estatus' => intval($orden['estatus']),
            'descripcion_articulos' => $descripcion_articulos
        ]
    ];

    $stmt->close();
    $conn->close();

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>