<?php
// Evitar que se muestren errores en el output
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/database.php';

// Establecer headers
header('Content-Type: application/json; charset=utf-8');

try {
    // Obtener folio desde GET
    $folio = $_GET['folio'] ?? '';

    if (empty($folio)) {
        throw new Exception('Folio no proporcionado');
    }

    // Conectar a ambas bases de datos
    $conn_lycaios = conectarLycaidosPOS();      // Para invoice
    $conn_ayuntamiento = conectarAyuntamiento(); // Para ordenes_backup
    
    // Limpiar el folio (remover ceros a la izquierda si es necesario)
    $folio_limpio = ltrim($folio, '0');
    $folio_limpio = str_pad($folio_limpio, 7, '0', STR_PAD_LEFT);

    // Buscar el comprobante en la tabla invoice
    $sql = "SELECT id, code, date, employee, total, estatus 
            FROM invoice 
            WHERE code = ? 
            ORDER BY id DESC 
            LIMIT 1";
    
    $stmt = $conn_lycaios->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . $conn_lycaios->error);
    }
    
    $stmt->bind_param("s", $folio_limpio);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Si no se encuentra en invoice, buscar en ordenes_backup
        $sql_backup = "SELECT id, code, date, employee, total, estatus 
                       FROM ordenes_backup 
                       WHERE code = ? 
                       ORDER BY id DESC 
                       LIMIT 1";
        
        $stmt_backup = $conn_ayuntamiento->prepare($sql_backup);
        $stmt_backup->bind_param("s", $folio_limpio);
        $stmt_backup->execute();
        $result_backup = $stmt_backup->get_result();
        
        if ($result_backup->num_rows === 0) {
            throw new Exception('No se encontró ningún comprobante con el folio: ' . $folio);
        }
        
        $comprobante = $result_backup->fetch_assoc();
        $stmt_backup->close();
    } else {
        $comprobante = $result->fetch_assoc();
    }

    // Formatear fecha
    $fecha_formateada = date('d/m/Y H:i', strtotime($comprobante['date']));
    
    // Formatear respuesta
    $response = [
        'success' => true,
        'comprobante' => [
            'id' => $comprobante['id'],
            'folio' => $comprobante['code'],
            'fecha' => $fecha_formateada,
            'departamento' => $comprobante['employee'],
            'total' => number_format(floatval($comprobante['total']), 2),
            'estatus' => intval($comprobante['estatus']) == 1 ? 'Pagado' : 'Pendiente'
        ]
    ];

    $stmt->close();
    $conn_lycaios->close();
    $conn_ayuntamiento->close();

    echo json_encode($response);

} catch (Exception $e) {
    $error_response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    echo json_encode($error_response);
}
?>
