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
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $folio = $data['folio'] ?? '';
    $montoRecibido = floatval($data['monto_recibido'] ?? 0);
    $cambio = floatval($data['cambio'] ?? 0);
    
    // Obtener datos del cliente desde client_data
    $clientData = $data['client_data'] ?? null;
    if ($clientData) {
        $nombreContribuyente = $clientData['name'] ?? 'Publico General';
        $direccionContribuyente = $clientData['address'] ?? 'N/A';
    } else {
        // Valores por defecto si no hay client_data
        $nombreContribuyente = 'Publico General';
        $direccionContribuyente = 'N/A';
    }
    
    if (empty($folio)) {
        throw new Exception('Folio vacío');
    }

    $conn = conectarAyuntamiento();
    $connLycaios = conectarLycaidosPOS();

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

    // TRANSACCIÓN
    $conn->begin_transaction();
    $connLycaios->begin_transaction();

    try {
        // PASO 1: Actualizar ordenes_backup
        $sql1 = "UPDATE ordenes_backup SET estatus = 1 WHERE code = ?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("s", $folio);
        $stmt1->execute();
        $stmt1->close();

        // PASO 2: Eliminar de ordenes
        $sql2 = "DELETE FROM ordenes WHERE code = ?";
        $stmt2 = $connLycaios->prepare($sql2);
        $stmt2->bind_param("s", $folio);
        $stmt2->execute();
        $stmt2->close();

        // PASO 3: Insertar en invoice
        $sql_max_invoice = "SELECT MAX(CAST(invoicecode AS UNSIGNED)) as max_code FROM invoice";
        $result_max = $connLycaios->query($sql_max_invoice);
        $next_invoice_code = "0009643";
        
        if ($result_max && $row = $result_max->fetch_assoc()) {
            $max_code = intval($row['max_code']);
            $next_invoice_code = str_pad($max_code + 1, 7, '0', STR_PAD_LEFT);
        }
        
        // TODOS LOS CAMPOS REQUERIDOS
        $columns = [
            'userid', 'cajaid', 'invoicecode', 'ordercode', 'facturacode', 'fecha', 'date', 'time',
            'subtotal', 'total', 'priceunit', 'impuesto', 'ieps', 'ganancia', 'employee', 'paid',
            'descuento', 'promocion', 'copynumber', 'ready', 'description', 'revision', 'invoicetype',
            'paytype', 'paymoney', 'paycard', 'paydebit', 'paytransfer', 'paycredits', 'payed',
            '`change`', 'moneymn', 'moneyusd', 'credit', 'voucher', 'money', 'usepromocioncode',
            'isabono', 'asabono', 'status', 'ubicationid', 'changetype', 'call_last', 'call_agend',
            'hide', 'amidomicilio', 'amicode', 'items', 'moneys', 'usocfdi', 'facturado', 'uuid',
            'paymentterms', 'paymentmethod', 'cfdisign', 'satcertnumber', 'satsign', 'rfcprovcertif',
            'satoriginal', 'factura_id', 'tips', 'custom_use', 'custom_use_entrega', 'custom_venta',
            'custom_pendiente', 'custom_messasge', 'custom_entrega', 'custom_days'
        ];
        
        $placeholders = str_repeat('?, ', count($columns) - 1) . '?';
        $sql3 = "INSERT INTO invoice (" . implode(', ', $columns) . ") VALUES ($placeholders)";
        
        file_put_contents($log_file, "[$timestamp] Total columnas: " . count($columns) . "\n", FILE_APPEND);
        
        $stmt3 = $connLycaios->prepare($sql3);
        
        if (!$stmt3) {
            throw new Exception("Error preparando INSERT: " . $connLycaios->error);
        }
        
        // Valores con defaults apropiados
        $userid = 1;
        $cajaid = 0;
        $facturacode = '';
        $fecha = '';
        $current_date = date('Y-m-d H:i:s');
        $time = '00:00:00';
        $subtotal = $orden['total'];
        $total = $orden['total'];
        $priceunit = 0.0;
        $impuesto = 0.0;
        $ieps = 0.0;
        $ganancia = 0.0;
        $employee = $orden['employee'];
        $paid = 1;
        $descuento = 0.0;
        $promocion = 0.0;
        $copynumber = 0;
        $ready = 0;
        // Guardar nombre y dirección del contribuyente en el campo description
        $description = json_encode([
            'nombre' => $nombreContribuyente,
            'direccion' => $direccionContribuyente
        ], JSON_UNESCAPED_UNICODE);
        $revision = 0;
        $invoicetype = 0;
        $paytype = 0;
        $paymoney = $montoRecibido;
        $paycard = 0.0;
        $paydebit = 0.0;
        $paytransfer = 0.0;
        $paycredits = 0.0;
        $payed = $montoRecibido;
        $change = $cambio;
        $moneymn = 0.0;
        $moneyusd = 0.0;
        $credit = 0.0;
        $voucher = 0.0;
        $money = 0.0;
        $usepromocioncode = '';
        $isabono = 0;
        $asabono = 0;
        $status = 0;
        $ubicationid = 0;
        $changetype = 0.0;
        $call_last = '';
        $call_agend = '';
        $hide = 0;
        $amidomicilio = 0;
        $amicode = '';
        $items = $orden['items'];
        
        $moneys_data = json_encode([[
            "Type" => 0,
            "Currency" => "MX", 
            "Date" => date('c'),
            "Exchange" => 0.0,
            "Total" => $montoRecibido,
            "Change" => $cambio
        ]]);
        
        $usocfdi = '';
        $facturado = 0;
        $uuid = '';
        $paymentterms = '';
        $paymentmethod = '';
        $cfdisign = '';
        $satcertnumber = '';
        $satsign = '';
        $rfcprovcertif = '';
        $satoriginal = '';
        $factura_id = '';
        $tips = 0.0;
        $custom_use = 0;
        $custom_use_entrega = 0;
        $custom_venta = 0;
        $custom_pendiente = 0;
        $custom_messasge = '';
        $custom_entrega = '';
        $custom_days = 0;
        
        // String de tipos (68 campos)
        $types = "ii" .      // userid, cajaid
                 "ssss" .    // invoicecode, ordercode, facturacode, fecha
                 "ss" .      // date, time
                 "dddddd" .  // subtotal, total, priceunit, impuesto, ieps, ganancia
                 "si" .      // employee, paid
                 "ddii" .    // descuento, promocion, copynumber, ready
                 "sii" .     // description, revision, invoicetype
                 "i" .       // paytype
                 "ddddd" .   // paymoney, paycard, paydebit, paytransfer, paycredits
                 "dd" .      // payed, change
                 "ddddd" .   // moneymn, moneyusd, credit, voucher, money
                 "siii" .    // usepromocioncode, isabono, asabono, status
                 "id" .      // ubicationid, changetype
                 "ssii" .    // call_last, call_agend, hide, amidomicilio
                 "sss" .     // amicode, items, moneys
                 "sis" .     // usocfdi, facturado, uuid
                 "sssss" .   // paymentterms, paymentmethod, cfdisign, satcertnumber, satsign
                 "sss" .     // rfcprovcertif, satoriginal, factura_id
                 "diiii" .   // tips, custom_use, custom_use_entrega, custom_venta, custom_pendiente
                 "ssi";      // custom_messasge, custom_entrega, custom_days
        
        $stmt3->bind_param(
            $types,
            $userid, $cajaid, $next_invoice_code, $orden['code'], $facturacode, $fecha, $current_date, $time,
            $subtotal, $total, $priceunit, $impuesto, $ieps, $ganancia, $employee, $paid,
            $descuento, $promocion, $copynumber, $ready, $description, $revision, $invoicetype,
            $paytype, $paymoney, $paycard, $paydebit, $paytransfer, $paycredits, $payed,
            $change, $moneymn, $moneyusd, $credit, $voucher, $money, $usepromocioncode,
            $isabono, $asabono, $status, $ubicationid, $changetype, $call_last, $call_agend,
            $hide, $amidomicilio, $amicode, $items, $moneys_data, $usocfdi, $facturado, $uuid,
            $paymentterms, $paymentmethod, $cfdisign, $satcertnumber, $satsign, $rfcprovcertif,
            $satoriginal, $factura_id, $tips, $custom_use, $custom_use_entrega, $custom_venta,
            $custom_pendiente, $custom_messasge, $custom_entrega, $custom_days
        );
        
        if ($stmt3->execute()) {
            $invoice_id = $connLycaios->insert_id;
            file_put_contents($log_file, "[$timestamp] ✅ Invoice insertado - ID: $invoice_id\n", FILE_APPEND);
        } else {
            throw new Exception("Error ejecutando INSERT: " . $stmt3->error);
        }
        
        $stmt3->close();

        $conn->commit();
        $connLycaios->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Cobro exitoso',
            'data' => [
                'invoice_id' => $invoice_id,
                'invoice_code' => $next_invoice_code
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        $connLycaios->rollback();
        throw $e;
    }

    $conn->close();
    $connLycaios->close();

} catch (Exception $e) {
    $error_msg = $e->getMessage();
    file_put_contents($log_file, "[$timestamp] ❌ ERROR: $error_msg\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $error_msg
    ]);
}
?>