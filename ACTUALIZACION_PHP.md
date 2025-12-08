# Guía de Actualización Manual de Archivos PHP

## Instrucciones

Después de ejecutar el script SQL `migracion_ayuntamiento.sql`, debes actualizar los archivos PHP para que usen la base de datos `ayuntamiento` en lugar de `lycaios_pos` para las tablas `ordenes_backup` y `sanitarios`.

---

## OPCIÓN 1: Actualización Manual (Recomendada)

Abre cada archivo listado abajo y realiza los cambios indicados.

### Archivos que Solo Usan `ordenes_backup`

Estos archivos solo necesitan cambiar la conexión de `conectarLycaidosPOS()` a `conectarAyuntamiento()`.

#### 1. `api/buscar_orden.php`
**Línea 35** - Cambiar:
```php
$conn = conectarLycaidosPOS();
```
Por:
```php
$conn = conectarAyuntamiento();
```

#### 2. `api/buscar_comprobante.php`
Buscar la línea que dice:
```php
$conn = conectarLycaidosPOS();
```
Cambiar por:
```php
$conn = conectarAyuntamiento();
```

#### 3. `api/actualizar_datos.php`
Buscar la línea que dice:
```php
$conn = conectarLycaidosPOS();
```
Cambiar por:
```php
$conn = conectarAyuntamiento();
```

#### 4. `api/eliminar_registros_antiguos.php`
Buscar la línea que dice:
```php
$conn = conectarLycaidosPOS();
```
Cambiar por:
```php
$conn = conectarAyuntamiento();
```

#### 5. `includes/cancelar_orden.php`
Buscar la línea que dice:
```php
$conn = conectarLycaidosPOS();
```
Cambiar por:
```php
$conn = conectarAyuntamiento();
```

#### 6. `includes/queries_common.php`
Buscar la línea que dice:
```php
$conn = conectarLycaidosPOS();
```
Cambiar por:
```php
$conn = conectarAyuntamiento();
```

#### 7. `diagnostico_charset.php`
Buscar la línea que dice:
```php
$conn = conectarLycaidosPOS();
```
Cambiar por:
```php
$conn = conectarAyuntamiento();
```

---

### Archivos que Solo Usan `sanitarios`

Estos archivos solo necesitan cambiar la conexión de `conectarLycaidosPOS()` a `conectarAyuntamiento()`.

#### 8. `api/guardar_orden_personalizada.php`
Buscar la línea que dice:
```php
$conn = conectarLycaidosPOS();
```
Cambiar por:
```php
$conn = conectarAyuntamiento();
```

#### 9. `api/obtener_siguiente_folio.php`
Buscar la línea que dice:
```php
$conn = conectarLycaidosPOS();
```
Cambiar por:
```php
$conn = conectarAyuntamiento();
```

#### 10. `api/verificar_sanitario.php`
Buscar la línea que dice:
```php
$conn = conectarLycaidosPOS();
```
Cambiar por:
```php
$conn = conectarAyuntamiento();
```

#### 11. `comprobante_sanitarios.php`
Buscar la línea que dice:
```php
$conn = conectarLycaidosPOS();
```
Cambiar por:
```php
$conn = conectarAyuntamiento();
```

---

### Archivo Especial: `api/procesar_cobro.php`

Este archivo es más complejo porque usa AMBAS bases de datos:
- `ayuntamiento` para `ordenes_backup`
- `lycaios_pos` para `ordenes` e `invoice`

**Cambios necesarios:**

1. **Línea ~36** - Cambiar:
```php
$conn = conectarLycaidosPOS();
```
Por:
```php
$connAyuntamiento = conectarAyuntamiento();  // Para ordenes_backup
$connLycaios = conectarLycaidosPOS();        // Para ordenes e invoice
```

2. **Línea ~39** - Cambiar:
```php
$sql = "SELECT code, date, items, employee, total FROM ordenes_backup WHERE code = ? AND estatus = 0";
$stmt = $conn->prepare($sql);
```
Por:
```php
$sql = "SELECT code, date, items, employee, total FROM ordenes_backup WHERE code = ? AND estatus = 0";
$stmt = $connAyuntamiento->prepare($sql);
```

3. **Línea ~45** - Cambiar:
```php
if ($result->num_rows === 0) {
    throw new Exception("Orden no encontrada: $folio");
}
```
Por:
```php
if ($result->num_rows === 0) {
    $connAyuntamiento->close();
    $connLycaios->close();
    throw new Exception("Orden no encontrada: $folio");
}
```

4. **Línea ~52** - Cambiar:
```php
// TRANSACCIÓN
$conn->begin_transaction();
```
Por:
```php
// TRANSACCIONES EN AMBAS BASES DE DATOS
$connAyuntamiento->begin_transaction();
$connLycaios->begin_transaction();
```

5. **Línea ~56-61** - Cambiar:
```php
// PASO 1: Actualizar ordenes_backup
$sql1 = "UPDATE ordenes_backup SET estatus = 1 WHERE code = ?";
$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param("s", $folio);
$stmt1->execute();
$stmt1->close();
```
Por:
```php
// PASO 1: Actualizar ordenes_backup en ayuntamiento
$sql1 = "UPDATE ordenes_backup SET estatus = 1 WHERE code = ?";
$stmt1 = $connAyuntamiento->prepare($sql1);
$stmt1->bind_param("s", $folio);
$stmt1->execute();
$stmt1->close();
```

6. **Línea ~63-68** - Cambiar:
```php
// PASO 2: Eliminar de ordenes
$sql2 = "DELETE FROM ordenes WHERE code = ?";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("s", $folio);
$stmt2->execute();
$stmt2->close();
```
Por:
```php
// PASO 2: Eliminar de ordenes en lycaios_pos (si existe)
$sql2 = "DELETE FROM ordenes WHERE code = ?";
$stmt2 = $connLycaios->prepare($sql2);
$stmt2->bind_param("s", $folio);
$stmt2->execute();
$stmt2->close();
```

7. **Línea ~70-72** - Cambiar:
```php
// PASO 3: Insertar en invoice
$sql_max_invoice = "SELECT MAX(CAST(invoicecode AS UNSIGNED)) as max_code FROM invoice";
$result_max = $conn->query($sql_max_invoice);
```
Por:
```php
// PASO 3: Insertar en invoice en lycaios_pos
$sql_max_invoice = "SELECT MAX(CAST(invoicecode AS UNSIGNED)) as max_code FROM invoice";
$result_max = $connLycaios->query($sql_max_invoice);
```

8. **Línea ~99-102** - Cambiar:
```php
$stmt3 = $conn->prepare($sql3);

if (!$stmt3) {
    throw new Exception("Error preparando INSERT: ". $conn->error);
}
```
Por:
```php
$stmt3 = $connLycaios->prepare($sql3);

if (!$stmt3) {
    throw new Exception("Error preparando INSERT: " . $connLycaios->error);
}
```

9. **Línea ~222-224** - Cambiar:
```php
if ($stmt3->execute()) {
    $invoice_id = $conn->insert_id;
    file_put_contents($log_file, "[$timestamp] ✅ Invoice insertado - ID: $invoice_id\n", FILE_APPEND);
}
```
Por:
```php
if ($stmt3->execute()) {
    $invoice_id = $connLycaios->insert_id;
    file_put_contents($log_file, "[$timestamp] ✅ Invoice insertado - ID: $invoice_id\n", FILE_APPEND);
}
```

10. **Línea ~229-231** - Cambiar:
```php
$stmt3->close();

$conn->commit();
```
Por:
```php
$stmt3->close();

// Commit en ambas bases de datos
$connAyuntamiento->commit();
$connLycaios->commit();
```

11. **Línea ~242-247** - Cambiar:
```php
} catch (Exception $e) {
    $conn->rollback();
    throw $e;
}

$conn->close();
```
Por:
```php
} catch (Exception $e) {
    // Rollback en ambas bases de datos
    $connAyuntamiento->rollback();
    $connLycaios->rollback();
    throw $e;
}

// Cerrar ambas conexiones
$connAyuntamiento->close();
$connLycaios->close();
```

---

## OPCIÓN 2: Script de Búsqueda y Reemplazo (PowerShell)

Si prefieres usar un script automatizado, guarda el siguiente código como `actualizar_conexiones.ps1` y ejecútalo:

```powershell
# Script para actualizar conexiones de base de datos
$archivos_simples = @(
    "api\buscar_orden.php",
    "api\buscar_comprobante.php",
    "api\actualizar_datos.php",
    "api\eliminar_registros_antiguos.php",
    "includes\cancelar_orden.php",
    "includes\queries_common.php",
    "diagnostico_charset.php",
    "api\guardar_orden_personalizada.php",
    "api\obtener_siguiente_folio.php",
    "api\verificar_sanitario.php",
    "comprobante_sanitarios.php"
)

foreach ($archivo in $archivos_simples) {
    $ruta = "c:\xampp\htdocs\DB_lycaios\$archivo"
    if (Test-Path $ruta) {
        $contenido = Get-Content $ruta -Raw
        $contenido = $contenido -replace 'conectarLycaidosPOS\(\)', 'conectarAyuntamiento()'
        Set-Content $ruta -Value $contenido -NoNewline
        Write-Host "✓ Actualizado: $archivo"
    } else {
        Write-Host "✗ No encontrado: $archivo"
    }
}

Write-Host "`n⚠ IMPORTANTE: El archivo api\procesar_cobro.php requiere actualización manual."
Write-Host "Consulta la guía ACTUALIZACION_PHP.md para los cambios específicos.`n"
```

---

## Verificación

Después de hacer los cambios, verifica que todo funciona:

1. **Ejecutar el script SQL** `migracion_ayuntamiento.sql`
2. **Actualizar los archivos PHP** usando esta guía
3. **Probar el sistema**:
   - Buscar una orden
   - Procesar un cobro
   - Crear un cobro de sanitarios
   - Verificar el dashboard

---

## Rollback

Si algo sale mal:

1. Restaurar archivos PHP:
```bash
git checkout api/*.php includes/*.php *.php
```

2. Las tablas originales en `lycaios_pos` NO fueron eliminadas, así que puedes revertir la migración si es necesario.

---

## Notas

- **NO elimines** las tablas originales de `lycaios_pos` hasta confirmar que todo funciona correctamente
- Los triggers ya fueron eliminados por el script SQL
- Mantén un backup de los archivos originales antes de hacer cambios
