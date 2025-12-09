# Cambios Necesarios para procesar_cobro.php

## Líneas 18-34: Modificar extracción de datos del cliente

### ANTES:
```php
    $folio = $data['folio'] ?? '';
    $montoRecibido = floatval($data['monto_recibido'] ?? 0);
    $cambio = floatval($data['cambio'] ?? 0);
    $nombreContribuyente = $data['nombre_contribuyente'] ?? '';
    $direccionContribuyente = $data['direccion_contribuyente'] ?? '';
    
    if (empty($folio)) {
        throw new Exception('Folio vacío');
    }
    
    if (empty($nombreContribuyente)) {
        throw new Exception('Nombre del contribuyente requerido');
    }
    
    if (empty($direccionContribuyente)) {
        throw new Exception('Dirección del contribuyente requerida');
    }
```

### DESPUÉS:
```php
    $folio = $data['folio'] ?? '';
    $montoRecibido = floatval($data['monto_recibido'] ?? 0);
    $cambio = floatval($data['cambio'] ?? 0);
    
    // Recibir datos del cliente (puede venir como objeto o como campos individuales)
    $clientData = $data['client_data'] ?? null;
    if ($clientData) {
        // Datos del cliente vienen como objeto
        $nombreContribuyente = $clientData['name'] ?? 'Publico General';
        $direccionContribuyente = $clientData['address'] ?? 'N/A';
        $telefonoContribuyente = $clientData['phone'] ?? '';
        $emailContribuyente = $clientData['email'] ?? '';
        $rfcContribuyente = $clientData['rfc'] ?? '';
        $razonsocialContribuyente = $clientData['razonsocial'] ?? '';
    } else {
        // Compatibilidad con versión anterior (campos individuales)
        $nombreContribuyente = $data['nombre_contribuyente'] ?? 'Publico General';
        $direccionContribuyente = $data['direccion_contribuyente'] ?? 'N/A';
        $telefonoContribuyente = '';
        $emailContribuyente = '';
        $rfcContribuyente = '';
        $razonsocialContribuyente = '';
    }
    
    if (empty($folio)) {
        throw new Exception('Folio vacío');
    }
```

## Líneas 36-39: Cambiar conexión a base de datos

### ANTES:
```php
    $conn = conectarLycaidosPOS();

    // Buscar orden
    $sql = "SELECT code, date, items, employee, total FROM ordenes_backup WHERE code = ? AND estatus = 0";
```

### DESPUÉS:
```php
    // Conectar a ambas bases de datos
    $connAyuntamiento = conectarAyuntamiento();  // Para ordenes_backup
    $connLycaios = conectarLycaidosPOS();        // Para ordenes e invoice

    // Buscar orden
    $sql = "SELECT code, date, items, employee, total FROM ordenes_backup WHERE code = ? AND estatus = 0";
```

## Líneas 40-50: Actualizar referencias de conexión

### ANTES:
```php
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $folio);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Orden no encontrada: $folio");
    }

    $orden = $result->fetch_assoc();
    $stmt->close();
```

### DESPUÉS:
```php
    $stmt = $connAyuntamiento->prepare($sql);
    $stmt->bind_param("s", $folio);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Orden no encontrada: $folio");
    }

    $orden = $result->fetch_assoc();
    $stmt->close();
```

## Líneas 52-68: Actualizar transacciones

### ANTES:
```php
    // TRANSACCIÓN
    $conn->begin_transaction();

    try {
        // PASO 1: Actualizar ordenes_backup
        $sql1 = "UPDATE ordenes_backup SET estatus = 1 WHERE code = ?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("s", $folio);
        $stmt1->execute();
        $stmt1->close();

        // PASO 2: Eliminar de ordenes
        $sql2 = "DELETE FROM ordenes WHERE code = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("s", $folio);
        $stmt2->execute();
        $stmt2->close();
```

### DESPUÉS:
```php
    // TRANSACCIÓN
    $connAyuntamiento->begin_transaction();
    $connLycaios->begin_transaction();

    try {
        // PASO 1: Actualizar ordenes_backup
        $sql1 = "UPDATE ordenes_backup SET estatus = 1 WHERE code = ?";
        $stmt1 = $connAyuntamiento->prepare($sql1);
        $stmt1->bind_param("s", $folio);
        $stmt1->execute();
        $stmt1->close();

        // PASO 2: Eliminar de ordenes
        $sql2 = "DELETE FROM ordenes WHERE code = ?";
        $stmt2 = $connLycaios->prepare($sql2);
        $stmt2->bind_param("s", $folio);
        $stmt2->execute();
        $stmt2->close();
```

## Líneas 70-78: Actualizar referencia de conexión

### ANTES:
```php
        // PASO 3: Insertar en invoice
        $sql_max_invoice = "SELECT MAX(CAST(invoicecode AS UNSIGNED)) as max_code FROM invoice";
        $result_max = $conn->query($sql_max_invoice);
        $next_invoice_code = "0009643";
        
        if ($result_max && $row = $result_max->fetch_assoc()) {
            $max_code = intval($row['max_code']);
            $next_invoice_code = str_pad($max_code + 1, 7, '0', STR_PAD_LEFT);
        }
```

### DESPUÉS:
```php
        // PASO 3: Insertar en invoice
        $sql_max_invoice = "SELECT MAX(CAST(invoicecode AS UNSIGNED)) as max_code FROM invoice";
        $result_max = $connLycaios->query($sql_max_invoice);
        $next_invoice_code = "0009643";
        
        if ($result_max && $row = $result_max->fetch_assoc()) {
            $max_code = intval($row['max_code']);
            $next_invoice_code = str_pad($max_code + 1, 7, '0', STR_PAD_LEFT);
        }
```

## Líneas 99-128: Actualizar preparación de statement y description

### ANTES:
```php
        $stmt3 = $conn->prepare($sql3);
        
        if (!$stmt3) {
            throw new Exception("Error preparando INSERT: " . $conn->error);
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
```

### DESPUÉS:
```php
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
        
        // Guardar todos los datos del contribuyente en el campo description
        // Solo incluir campos que no estén vacíos
        $description_data = [
            'nombre' => $nombreContribuyente,
            'direccion' => $direccionContribuyente
        ];
        if (!empty($telefonoContribuyente)) {
            $description_data['telefono'] = $telefonoContribuyente;
        }
        if (!empty($emailContribuyente)) {
            $description_data['email'] = $emailContribuyente;
        }
        if (!empty($rfcContribuyente)) {
            $description_data['rfc'] = $rfcContribuyente;
        }
        if (!empty($razonsocialContribuyente)) {
            $description_data['razonsocial'] = $razonsocialContribuyente;
        }
        $description = json_encode($description_data, JSON_UNESCAPED_UNICODE);
```

## Líneas 218-222: Actualizar ejecución y cierre

### ANTES:
```php
        if ($stmt3->execute()) {
            $invoice_id = $conn->insert_id;
            file_put_contents($log_file, "[$timestamp] ✅ Invoice insertado - ID: $invoice_id\n", FILE_APPEND);
        } else {
            throw new Exception("Error ejecutando INSERT: " . $stmt3->error);
        }
```

### DESPUÉS:
```php
        if ($stmt3->execute()) {
            $invoice_id = $connLycaios->insert_id;
            file_put_contents($log_file, "[$timestamp] ✅ Invoice insertado - ID: $invoice_id\n", FILE_APPEND);
        } else {
            throw new Exception("Error ejecutando INSERT: " . $stmt3->error);
        }
```

## Líneas 225-245: Actualizar commit y cierre de conexiones

### ANTES:
```php
        $stmt3->close();

        // Commit
        $conn->commit();

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
        throw $e;
    }

    $conn->close();
```

### DESPUÉS:
```php
        $stmt3->close();

        // Commit en ambas bases de datos
        $connAyuntamiento->commit();
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
        $connAyuntamiento->rollback();
        $connLycaios->rollback();
        throw $e;
    }

    $connAyuntamiento->close();
    $connLycaios->close();
```
