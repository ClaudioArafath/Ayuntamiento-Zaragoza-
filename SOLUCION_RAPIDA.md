# SOLUCIÓN RÁPIDA - Cambios Críticos

## Problema 1: Error "Table 'ayuntamiento.ordenes' doesn't exist"

### Archivo: `api/procesar_cobro.php`

**Línea 36** - Cambiar:
```php
$conn = conectarAyuntamiento();
```

Por:
```php
$conn = conectarAyuntamiento();
$connLycaios = conectarLycaidosPOS(); // Para eliminar de ordenes
```

**Línea 53** - Cambiar:
```php
$conn->begin_transaction();
```

Por:
```php
$conn->begin_transaction();
$connLycaios->begin_transaction();
```

**Línea 65** - Cambiar:
```php
$stmt2 = $conn->prepare($sql2);
```

Por:
```php
$stmt2 = $connLycaios->prepare($sql2);
```

**Línea 72** - Cambiar:
```php
$result_max = $conn->query($sql_max_invoice);
```

Por:
```php
$result_max = $connLycaios->query($sql_max_invoice);
```

**Línea 99** - Cambiar:
```php
$stmt3 = $conn->prepare($sql3);
```

Por:
```php
$stmt3 = $connLycaios->prepare($sql3);
```

**Línea 102** - Cambiar:
```php
throw new Exception("Error preparando INSERT: " . $conn->error);
```

Por:
```php
throw new Exception("Error preparando INSERT: " . $connLycaios->error);
```

**Línea 218** - Cambiar:
```php
$invoice_id = $conn->insert_id;
```

Por:
```php
$invoice_id = $connLycaios->insert_id;
```

**Línea 225** - Cambiar:
```php
$conn->commit();
```

Por:
```php
$conn->commit();
$connLycaios->commit();
```

**Línea 237** - Cambiar:
```php
$conn->rollback();
```

Por:
```php
$conn->rollback();
$connLycaios->rollback();
```

**Línea 241** - Cambiar:
```php
$conn->close();
```

Por:
```php
$conn->close();
$connLycaios->close();
```

---

## Problema 2: Eliminar campos de nombre y dirección del formulario

### Archivo: `components/modalCobrarOrden.php`

**Eliminar las líneas 36-50** (los dos divs de nombre y dirección del contribuyente y el hr)

Dejar solo el campo de "Monto Recibido" en la sección de pago.

---

## Problema 3: Actualizar JavaScript para no validar campos eliminados

### Archivo: `assets/js/cobrarOrden.js`

**Líneas 54-79** - En la función `resetearModal()`, eliminar las líneas que referencian:
```javascript
const nombreInput = document.getElementById('nombre-contribuyente');
const direccionInput = document.getElementById('direccion-contribuyente');
if (nombreInput) nombreInput.value = '';
if (direccionInput) direccionInput.value = '';
```

**Líneas 218-232** - En la función `confirmarCobroOrden()`, eliminar las validaciones:
```javascript
const nombreContribuyente = document.getElementById('nombre-contribuyente').value.trim();
const direccionContribuyente = document.getElementById('direccion-contribuyente').value.trim();

// Validar campos requeridos
if (!nombreContribuyente) {
    mostrarError('Por favor ingrese el nombre del contribuyente');
    return;
}

if (!direccionContribuyente) {
    mostrarError('Por favor ingrese la dirección del contribuyente');
    return;
}
```

**Líneas 260-265** - Cambiar el body del fetch para enviar datos del cliente:
```javascript
body: JSON.stringify({
    folio: ordenActual.code,
    monto_recibido: montoRecibido,
    cambio: montoRecibido - total,
    client_data: ordenActual.client  // Enviar datos del cliente desde la orden
})
```

---

## RESUMEN

Estos cambios solucionan:
1. ✅ Error de base de datos - ahora usa dos conexiones correctamente
2. ✅ Elimina campos manuales del formulario
3. ✅ Envía datos del cliente automáticamente desde la base de datos
