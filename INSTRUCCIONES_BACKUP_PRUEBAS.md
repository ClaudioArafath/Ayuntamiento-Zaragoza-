# Guía de Uso: Scripts de Backup y Prueba de Cobros

## 📋 Resumen

Este documento explica cómo usar los scripts SQL para:
1. Respaldar todas las órdenes de la tabla `ordenes` a `ordenes_backup`
2. Crear órdenes de prueba para simular cobros antiguos
3. Limpiar los datos de prueba sin afectar los datos oficiales

---

## 🗄️ Información de la Base de Datos

- **Base de datos**: `lycaios_pos`
- **Puerto**: 3311
- **Servidor**: localhost
- **Usuario**: root
- **Tablas involucradas**:
  - `ordenes` - Órdenes pendientes (4,751 registros actualmente)
  - `ordenes_backup` - Respaldo de órdenes
  - `invoice` - Facturas generadas después del cobro

---

## 📁 Archivos Creados

1. **`scripts_backup_ordenes.sql`** - Script para respaldar órdenes
2. **`scripts_prueba_cobro.sql`** - Script para pruebas de cobro
3. **`INSTRUCCIONES_BACKUP_PRUEBAS.md`** - Este documento

---

## 🔧 PARTE 1: Backup de Órdenes

### Paso 1: Abrir phpMyAdmin o MySQL

**Opción A: Usar phpMyAdmin**
1. Abrir navegador y ir a: `http://localhost/phpmyadmin`
2. En el panel izquierdo, buscar el servidor en puerto 3311
3. Seleccionar la base de datos `lycaios_pos`
4. Ir a la pestaña "SQL"

**Opción B: Usar línea de comandos**
```bash
C:\xampp\mysql\bin\mysql.exe -u root -P 3311 lycaios_pos
```

### Paso 2: Ejecutar el Script de Backup

1. Abrir el archivo `scripts_backup_ordenes.sql`
2. Copiar y pegar el contenido en la consola SQL
3. Ejecutar paso por paso o todo el script

### Paso 3: Verificar el Backup

El script incluye consultas de verificación que mostrarán:
- Total de registros antes y después del backup
- Órdenes pendientes vs pagadas
- Detección de duplicados (no debería haber)

**Consulta rápida de verificación:**
```sql
SELECT 
    (SELECT COUNT(*) FROM ordenes) as total_ordenes,
    (SELECT COUNT(*) FROM ordenes_backup) as total_backup;
```

### ⚠️ Advertencias Importantes

- El script **NO elimina** datos existentes en `ordenes_backup`
- Solo inserta registros que **NO existan** (evita duplicados)
- Usa el campo `code` como identificador único
- Si necesitas un backup completo desde cero, primero vacía `ordenes_backup`

---

## 🧪 PARTE 2: Simulación de Cobro con Orden Antigua

### Objetivo
Probar el proceso de cobro con una orden antigua sin afectar datos reales.

### Paso 1: Crear Orden de Prueba

1. Abrir el archivo `scripts_prueba_cobro.sql`
2. Ejecutar el **PASO 2** del script para crear una orden de prueba
3. La orden tendrá el código: `TEST-SIMULACION-001`

**Características de la orden de prueba:**
- Código: `TEST-SIMULACION-001`
- Fecha: 2020-01-15 (orden antigua)
- Total: $100.00
- Estatus: 0 (pendiente)
- Empleado: USUARIO_PRUEBA

### Paso 2: Realizar la Prueba de Cobro

1. Ir a la interfaz web del sistema de cobros
2. Buscar la orden con el código: `TEST-SIMULACION-001`
3. Procesar el cobro con estos datos:
   - **Nombre del contribuyente**: PRUEBA SIMULACION
   - **Dirección**: CALLE PRUEBA 123
   - **Monto recibido**: 100.00
   - **Cambio**: 0.00

### Paso 3: Verificar el Cobro

Después de procesar el cobro, verificar que:

```sql
-- Verificar que se creó la factura
SELECT * FROM invoice WHERE ordercode = 'TEST-SIMULACION-001';

-- Verificar que el estatus cambió a 1 (pagado)
SELECT * FROM ordenes_backup WHERE code = 'TEST-SIMULACION-001';

-- Verificar que se eliminó de ordenes
SELECT * FROM ordenes WHERE code = 'TEST-SIMULACION-001';
```

### Paso 4: Limpiar Datos de Prueba

**MUY IMPORTANTE**: Después de completar la prueba, ejecutar el **PASO 5** del script `scripts_prueba_cobro.sql` para eliminar todos los datos de prueba.

```sql
-- Eliminar factura de prueba
DELETE FROM invoice WHERE ordercode = 'TEST-SIMULACION-001';

-- Eliminar de ordenes_backup
DELETE FROM ordenes_backup WHERE code = 'TEST-SIMULACION-001';

-- Eliminar de ordenes (si quedó algo)
DELETE FROM ordenes WHERE code = 'TEST-SIMULACION-001';
```

### Paso 5: Verificación Final

Ejecutar la consulta de verificación para confirmar que no quedan rastros:

```sql
SELECT 'ordenes' as tabla, COUNT(*) as registros_prueba
FROM ordenes WHERE code = 'TEST-SIMULACION-001'
UNION ALL
SELECT 'ordenes_backup', COUNT(*) FROM ordenes_backup WHERE code = 'TEST-SIMULACION-001'
UNION ALL
SELECT 'invoice', COUNT(*) FROM invoice WHERE ordercode = 'TEST-SIMULACION-001';
```

**Resultado esperado**: Todos los conteos deben ser 0.

---

## 🎯 PARTE 3: Probar con Orden Real Antigua

Si quieres probar con una orden real antigua (en lugar de la orden de prueba genérica):

### Paso 1: Buscar Orden Antigua

```sql
-- Buscar órdenes antiguas pendientes
SELECT id, code, date, employee, total, estatus,
       DATEDIFF(NOW(), date) as dias_antiguedad
FROM ordenes_backup
WHERE estatus = 0
ORDER BY date ASC
LIMIT 10;
```

### Paso 2: Crear Copia de Prueba

Usa la plantilla del script `scripts_prueba_cobro.sql` (al final del archivo) para crear una copia de la orden real con prefijo `TEST-`.

**Ejemplo:**
```sql
INSERT INTO ordenes_backup (code, userid, clientid, date, items, piezas, employee, description, total, estatus)
SELECT 
    CONCAT('TEST-', code) as code,
    userid, clientid, date, items, piezas,
    CONCAT('PRUEBA-', employee) as employee,
    CONCAT('PRUEBA: ', description) as description,
    total, 0 as estatus
FROM ordenes_backup 
WHERE code = 'CODIGO_ORDEN_REAL'
LIMIT 1;
```

### Paso 3: Probar y Limpiar

Seguir los mismos pasos de la PARTE 2, pero usando el código `TEST-CODIGO_ORDEN_REAL`.

---

## 📊 Consultas Útiles

### Ver estadísticas de órdenes por año
```sql
SELECT 
    YEAR(date) as año,
    COUNT(*) as total_ordenes,
    SUM(CASE WHEN estatus = 0 THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estatus = 1 THEN 1 ELSE 0 END) as pagadas,
    SUM(total) as monto_total
FROM ordenes_backup
GROUP BY YEAR(date)
ORDER BY año DESC;
```

### Ver órdenes más antiguas
```sql
SELECT id, code, date, employee, total, estatus
FROM ordenes_backup
WHERE estatus = 0
ORDER BY date ASC
LIMIT 20;
```

### Buscar todas las pruebas activas
```sql
SELECT * FROM ordenes_backup 
WHERE code LIKE 'TEST-%' OR employee LIKE 'PRUEBA-%';
```

---

## ⚠️ Reglas de Seguridad

### ✅ HACER:
- Siempre usar códigos con prefijo `TEST-` para pruebas
- Verificar los datos antes y después de cada operación
- Limpiar los datos de prueba inmediatamente después de usarlos
- Hacer backup antes de operaciones masivas

### ❌ NO HACER:
- NO usar órdenes reales para pruebas (siempre crear copias)
- NO dejar datos de prueba en la base de datos
- NO ejecutar DELETE sin WHERE clause
- NO modificar órdenes que no tengan prefijo `TEST-`

---

## 🔍 Troubleshooting

### Problema: "Table doesn't exist"
**Solución**: Verificar que estás conectado a la base de datos `lycaios_pos` en el puerto 3311.

### Problema: Duplicados en ordenes_backup
**Solución**: El script usa `NOT EXISTS` para evitar duplicados. Si hay duplicados, ejecutar:
```sql
-- Ver duplicados
SELECT code, COUNT(*) as duplicados
FROM ordenes_backup
GROUP BY code
HAVING COUNT(*) > 1;
```

### Problema: No se puede eliminar la orden de prueba
**Solución**: Verificar que el código sea exactamente `TEST-SIMULACION-001` (case-sensitive).

---

## 📞 Soporte

Si tienes problemas:
1. Verificar que el servidor MySQL en puerto 3311 está activo
2. Revisar los logs en `c:\xampp\htdocs\DB_lycaios\logs\cobros.log`
3. Consultar el archivo `procesar_cobro.php` para entender el flujo

---

## 📝 Notas Finales

- Los scripts están diseñados para ser seguros y no destructivos
- Siempre verifica los resultados después de cada operación
- Mantén un registro de las pruebas realizadas
- El sistema usa transacciones, así que si algo falla, se revierte automáticamente

**Fecha de creación**: 2025-12-05  
**Versión**: 1.0
