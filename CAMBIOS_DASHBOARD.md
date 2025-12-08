# Cambios Realizados - Dashboard Fix

## Problema Identificado

El dashboard mostraba datos antiguos porque seguía consultando `lycaios_pos.ordenes_backup` en lugar de `ayuntamiento.ordenes_backup`.

---

## Cambios Aplicados

### 1. ✅ Ajuste de Frecuencia de Sincronización

**Archivo**: `sincronizacion_auto.bat`

**Cambio**: Reducido de 5 minutos a 1 minuto

```batch
# Antes
timeout /t 300 /nobreak

# Después  
timeout /t 60 /nobreak
```

---

### 2. ✅ Actualización de `api/actualizar_datos.php`

**Problema**: Usaba solo `conectarLycaidosPOS()` para todas las consultas

**Solución**: Ahora usa dos conexiones:
- `$conn_lycaios` → Para consultas de `invoice` (ingresos, facturas, condonaciones)
- `$conn_ayuntamiento` → Para consultas de `ordenes_backup` (tabla de órdenes)

**Cambios específicos**:

```php
// Línea 14-15: Agregar segunda conexión
$conn_lycaios = conectarLycaidosPOS();      // Para invoice
$conn_ayuntamiento = conectarAyuntamiento(); // Para ordenes_backup

// Línea 17: Verificar ambas conexiones
if (!$conn_lycaios || !$conn_ayuntamiento) {

// Línea 185: Usar conexión correcta para ordenes_backup
$result_facturas = $conn_ayuntamiento->query($sql_facturas);

// Línea 254-255: Cerrar ambas conexiones
$conn_lycaios->close();
$conn_ayuntamiento->close();
```

---

### 3. ✅ Actualización de `includes/queries_common.php`

**Problema**: Usaba `$conn_lycaios` para consultar `ordenes_backup`

**Solución**: Cambiado a `$conn_ayuntamiento`

```php
// Línea 13: Cambiar conexión
$result_facturas = $conn_ayuntamiento->query($sql_facturas);
```

> **Nota**: Este archivo es incluido por los dashboards de admin y empleado, por lo que ambos necesitan tener `$conn_ayuntamiento` disponible.

---

## Archivos que Incluyen `queries_common.php`

Estos archivos necesitan tener ambas conexiones (`$conn_lycaios` y `$conn_ayuntamiento`) antes de incluir `queries_common.php`:

1. `admin_dashboard.php`
2. `employee_dashboard.php`
3. Cualquier otro dashboard que use este archivo

---

## Verificación

### Probar Dashboard

1. Abrir el dashboard: `http://localhost/DB_lycaios`
2. Verificar que la tabla "Nuevas Órdenes" muestra datos actualizados
3. Verificar que los contadores de órdenes pendientes son correctos

### Verificar Sincronización

```cmd
# Ver log de sincronización
type c:\xampp\htdocs\DB_lycaios\logs\sincronizacion.log
```

### Verificar en Base de Datos

```sql
-- Comparar conteos
SELECT 
    (SELECT COUNT(*) FROM lycaios_pos.ordenes) as lycaios_ordenes,
    (SELECT COUNT(*) FROM ayuntamiento.ordenes_backup WHERE estatus = 0) as ayuntamiento_pendientes;
```

---

## Próximos Pasos

Si el dashboard aún no muestra datos correctos:

1. Verificar que los archivos de dashboard tienen ambas conexiones
2. Revisar logs del navegador (F12 → Console)
3. Verificar que la sincronización está ejecutándose
4. Verificar que hay datos en `ayuntamiento.ordenes_backup`

---

**Fecha**: 2025-12-08  
**Hora**: 15:10
