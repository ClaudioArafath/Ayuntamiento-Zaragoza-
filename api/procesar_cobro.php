<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$log_file = '../logs/cobros.log';
$timestamp = date('Y-m-d H:i:s');
file_put_contents($log_file, "[$timestamp] === INICIANDO PROCESO COBRO ===\n", FILE_APPEND);

try {
    // Log del input
    $input = file_get_contents('php://input');
    file_put_contents($log_file, "[$timestamp] Input RAW: $input\n", FILE_APPEND);
    
    $data = json_decode($input, true);
    if (!$data) {
        throw new Exception('JSON inválido');
    }
    
    $folio = $data['folio'] ?? '';
    $montoRecibido = floatval($data['monto_recibido'] ?? 0);
    $cambio = floatval($data['cambio'] ?? 0);
    
    file_put_contents($log_file, "[$timestamp] Folio: $folio, Monto: $montoRecibido, Cambio: $cambio\n", FILE_APPEND);
    
    if (empty($folio)) {
        throw new Exception('Folio vacío');
    }

    // Conexión
    $conn = conectarLycaidosPOS();
    file_put_contents($log_file, "[$timestamp] Conexión OK\n", FILE_APPEND);

    // Buscar orden
    $sql = "SELECT code, date, items, employee, total FROM ordenes_backup WHERE code = ? AND estatus = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $folio);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Orden no encontrada: $folio");
    }

    $orden = $result->fetch_assoc();
    $stmt->close();
    file_put_contents($log_file, "[$timestamp] Orden encontrada: " . $orden['code'] . "\n", FILE_APPEND);

    // TRANSACCIÓN
    $conn->begin_transaction();
    file_put_contents($log_file, "[$timestamp] Transacción iniciada\n", FILE_APPEND);

    try {
        // PASO 1: Actualizar ordenes_backup
        file_put_contents($log_file, "[$timestamp] Paso 1: Actualizando ordenes_backup\n", FILE_APPEND);
        $sql1 = "UPDATE ordenes_backup SET estatus = 1 WHERE code = ?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("s", $folio);
        $stmt1->execute();
        $stmt1->close();
        file_put_contents($log_file, "[$timestamp] ✅ ordenes_backup actualizado\n", FILE_APPEND);

        // PASO 2: Eliminar de ordenes
        file_put_contents($log_file, "[$timestamp] Paso 2: Eliminando de ordenes\n", FILE_APPEND);
        $sql2 = "DELETE FROM ordenes WHERE code = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("s", $folio);
        $stmt2->execute();
        $stmt2->close();
        file_put_contents($log_file, "[$timestamp] ✅ ordenes eliminado\n", FILE_APPEND);

        // PASO 3: Insertar en invoice - SIN COMENTARIOS EN EL SQL
        file_put_contents($log_file, "[$timestamp] Paso 3: Insertando en invoice\n", FILE_APPEND);
        
        // Obtener próximo invoicecode
        $sql_max_invoice = "SELECT MAX(CAST(invoicecode AS UNSIGNED)) as max_code FROM invoice";
        $result_max = $conn->query($sql_max_invoice);
        $next_invoice_code = "0009643";
        
        if ($result_max && $row = $result_max->fetch_assoc()) {
            $max_code = intval($row['max_code']);
            $next_invoice_code = str_pad($max_code + 1, 7, '0', STR_PAD_LEFT);
        }
        
        file_put_contents($log_file, "[$timestamp] Próximo invoicecode: $next_invoice_code\n", FILE_APPEND);
        
        // Query SIN comentarios dentro del string SQL
        $sql3 = "INSERT INTO invoice (
            userid, 
            cajaid, 
            invoicecode, 
            ordercode, 
            facturacode,
            fecha,
            date,
            time,
            subtotal,
            total,
            employee,
            paid,
            payed,
            `change`,
            items,
            moneys
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        file_put_contents($log_file, "[$timestamp] Query preparado\n", FILE_APPEND);
        
        $stmt3 = $conn->prepare($sql3);
        
        if (!$stmt3) {
            throw new Exception("Error preparando INSERT: " . $conn->error);
        }
        
        // Valores para campos obligatorios
        $userid = 1;
        $cajaid = 0;
        $facturacode = '';
        $fecha = '';
        $current_date = date('Y-m-d H:i:s');
        $time = '00:00:00';
        $subtotal = $orden['total'];
        $paid = 1;
        
        // Preparar moneys (JSON)
        $moneys_data = json_encode([[
            "Type" => 0,
            "Currency" => "MX", 
            "Date" => date('c'),
            "Exchange" => 0.0,
            "Total" => $montoRecibido,
            "Change" => $cambio
        ]]);
        
        file_put_contents($log_file, "[$timestamp] Bind parameters...\n", FILE_APPEND);
        
        $stmt3->bind_param(
            "iisssssssddsidsss",
            $userid,
            $cajaid,  
            $next_invoice_code,
            $orden['code'],
            $facturacode,
            $fecha,
            $current_date,
            $time,
            $subtotal,
            $orden['total'],
            $orden['employee'],
            $paid,
            $montoRecibido,
            $cambio,
            $orden['items'],
            $moneys_data
        );
        
        file_put_contents($log_file, "[$timestamp] Ejecutando INSERT...\n", FILE_APPEND);
        
        if ($stmt3->execute()) {
            $invoice_id = $conn->insert_id;
            file_put_contents($log_file, "[$timestamp] ✅ Invoice insertado - ID: $invoice_id, Code: $next_invoice_code\n", FILE_APPEND);
        } else {
            throw new Exception("Error ejecutando INSERT: " . $stmt3->error);
        }
        
        $stmt3->close();

        // CONFIRMAR
        $conn->commit();
        file_put_contents($log_file, "[$timestamp] ✅ TRANSACCIÓN COMPLETADA\n", FILE_APPEND);

        // RESPUESTA EXITOSA
        $response = [
            'success' => true,
            'message' => 'Cobro exitoso',
            'data' => [
                'invoice_id' => $invoice_id,
                'invoice_code' => $next_invoice_code
            ]
        ];
        
        file_put_contents($log_file, "[$timestamp] Respuesta: " . json_encode($response) . "\n", FILE_APPEND);
        echo json_encode($response);

    } catch (Exception $e) {
        $conn->rollback();
        file_put_contents($log_file, "[$timestamp] ❌ ROLLBACK: " . $e->getMessage() . "\n", FILE_APPEND);
        throw $e;
    }

    $conn->close();

} catch (Exception $e) {
    $error_msg = $e->getMessage();
    file_put_contents($log_file, "[$timestamp] ❌ ERROR FINAL: $error_msg\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $error_msg
    ]);
}
?>