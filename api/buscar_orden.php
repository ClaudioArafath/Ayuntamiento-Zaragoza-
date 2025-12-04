<?php
// Evitar que se muestren errores en el output
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/database.php';
require_once '../includes/functions.php';

// Establecer headers primero
header('Content-Type: application/json; charset=utf-8');

// Log para debugging en archivo
$log_file = '../logs/busquedas.log';
$timestamp = date('Y-m-d H:i:s');
file_put_contents($log_file, "[$timestamp] Iniciando buscar_orden.php\n", FILE_APPEND);

try {
    // Obtener datos del POST de manera segura
    $json_input = file_get_contents('php://input');
    file_put_contents($log_file, "[$timestamp] Input recibido: $json_input\n", FILE_APPEND);
    
    $input = json_decode($json_input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }
    
    $folio = $input['folio'] ?? '';

    if (empty($folio)) {
        throw new Exception('Folio no proporcionado');
    }

    // Conectar a la base de datos
    $conn = conectarLycaidosPOS();
    file_put_contents($log_file, "[$timestamp] Conexión a BD establecida\n", FILE_APPEND);
    
    // Limpiar el folio (remover ceros a la izquierda si es necesario)
    $folio_limpio = ltrim($folio, '0');
    $folio_limpio = str_pad($folio_limpio, 7, '0', STR_PAD_LEFT);

    file_put_contents($log_file, "[$timestamp] Buscando orden con folio: $folio_limpio\n", FILE_APPEND);

    // Buscar la orden en ordenes_backup
    $sql = "SELECT id, code, date, items, employee, total, estatus 
            FROM ordenes_backup 
            WHERE code = ? 
            ORDER BY id DESC 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $folio_limpio);
    $stmt->execute();
    $result = $stmt->get_result();

    file_put_contents($log_file, "[$timestamp] Resultados encontrados: " . $result->num_rows . "\n", FILE_APPEND);

    if ($result->num_rows === 0) {
        throw new Exception('Orden no encontrada con el folio: ' . $folio);
    }

    $orden = $result->fetch_assoc();

    // MEJORADO: Procesar items para obtener descripción de manera más robusta
    $descripcion_articulos = 'Productos varios';
    if (!empty($orden['items'])) {
        file_put_contents($log_file, "[$timestamp] Items crudos: " . substr($orden['items'], 0, 200) . "\n", FILE_APPEND);
        
        $items = json_decode($orden['items'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Intentar limpiar el JSON si está mal formado
            $items_clean = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $orden['items']);
            $items = json_decode($items_clean, true);
            file_put_contents($log_file, "[$timestamp] JSON limpiado, error previo: " . json_last_error_msg() . "\n", FILE_APPEND);
        }
        
        if (is_array($items) && count($items) > 0) {
            $nombres = [];
            foreach ($items as $item) {
                // Intentar diferentes posibles nombres de campo
                $nombre = $item['name'] ?? $item['nombre'] ?? $item['description'] ?? $item['descripcion'] ?? null;
                if ($nombre) {
                    $nombres[] = $nombre;
                }
            }
            
            if (count($nombres) > 0) {
                $descripcion_articulos = implode(', ', $nombres);
                // Limitar longitud
                if (strlen($descripcion_articulos) > 100) {
                    $descripcion_articulos = substr($descripcion_articulos, 0, 100) . '...';
                }
            }
        }
    }

    file_put_contents($log_file, "[$timestamp] Descripción final: $descripcion_articulos\n", FILE_APPEND);

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

    file_put_contents($log_file, "[$timestamp] ✅ Orden encontrada: " . $orden['code'] . " - Total: " . $orden['total'] . " - Estatus: " . $orden['estatus'] . "\n", FILE_APPEND);

    $stmt->close();
    $conn->close();

    file_put_contents($log_file, "[$timestamp] Enviando respuesta: " . json_encode($response) . "\n", FILE_APPEND);
    echo json_encode($response);

} catch (Exception $e) {
    $error_message = $e->getMessage();
    file_put_contents($log_file, "[$timestamp] ❌ Error: $error_message\n", FILE_APPEND);
    
    $error_response = [
        'success' => false,
        'message' => $error_message
    ];
    
    echo json_encode($error_response);
}
?>