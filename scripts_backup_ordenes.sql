-- ============================================================================
-- SCRIPT DE BACKUP: COPIAR ORDENES A ORDENES_BACKUP
-- ============================================================================
-- Base de datos: lycaios_pos (Puerto 3311)
-- Propósito: Respaldar todas las órdenes antes de realizar pruebas
-- Fecha: 2025-12-05
-- ============================================================================

-- IMPORTANTE: Este script debe ejecutarse en la base de datos lycaios_pos
USE lycaios_pos;

-- ============================================================================
-- PASO 1: VERIFICACIÓN PREVIA
-- ============================================================================
-- Verificar cuántos registros hay en cada tabla antes del backup
SELECT 
    'ANTES DEL BACKUP' as momento,
    (SELECT COUNT(*) FROM ordenes) as total_ordenes,
    (SELECT COUNT(*) FROM ordenes_backup) as total_ordenes_backup,
    (SELECT COUNT(*) FROM ordenes WHERE estatus = 0) as ordenes_pendientes,
    (SELECT COUNT(*) FROM ordenes_backup WHERE estatus = 0) as backup_pendientes;

-- ============================================================================
-- PASO 2: BACKUP DE SEGURIDAD
-- ============================================================================
-- ADVERTENCIA: Este comando copiará TODOS los registros de 'ordenes' a 'ordenes_backup'
-- Si ordenes_backup ya tiene datos, se agregarán los nuevos (no se eliminan los existentes)

-- Opción A: Insertar solo registros que NO existan en ordenes_backup (recomendado)
-- Esto evita duplicados basándose en el campo 'code'
-- NOTA: La tabla 'ordenes' NO tiene el campo 'estatus', se establece en 0 por defecto
INSERT INTO ordenes_backup (code, date, items, employee, total, estatus)
SELECT o.code, o.date, o.items, o.employee, o.total, 0 as estatus
FROM ordenes o
WHERE NOT EXISTS (
    SELECT 1 FROM ordenes_backup ob 
    WHERE ob.code = o.code
);

-- ============================================================================
-- PASO 3: VERIFICACIÓN POSTERIOR
-- ============================================================================
-- Verificar cuántos registros hay después del backup
SELECT 
    'DESPUES DEL BACKUP' as momento,
    (SELECT COUNT(*) FROM ordenes) as total_ordenes,
    (SELECT COUNT(*) FROM ordenes_backup) as total_ordenes_backup,
    (SELECT COUNT(*) FROM ordenes WHERE estatus = 0) as ordenes_pendientes,
    (SELECT COUNT(*) FROM ordenes_backup WHERE estatus = 0) as backup_pendientes;

-- Verificar registros duplicados (no debería haber ninguno)
SELECT code, COUNT(*) as duplicados
FROM ordenes_backup
GROUP BY code
HAVING COUNT(*) > 1;

-- ============================================================================
-- PASO 4 (OPCIONAL): BACKUP COMPLETO SIN VERIFICAR DUPLICADOS
-- ============================================================================
-- SOLO EJECUTAR ESTE PASO SI QUIERES COPIAR TODO SIN IMPORTAR DUPLICADOS
-- ADVERTENCIA: Esto puede crear registros duplicados en ordenes_backup

-- DESCOMENTAR LAS SIGUIENTES LÍNEAS SOLO SI ESTÁS SEGURO:
/*
INSERT INTO ordenes_backup (code, date, items, employee, total, estatus)
SELECT code, date, items, employee, total, 0 as estatus
FROM ordenes;
*/

-- ============================================================================
-- CONSULTAS ÚTILES PARA VERIFICACIÓN
-- ============================================================================

-- Ver las órdenes más antiguas pendientes de pago
SELECT id, code, date, employee, total, estatus
FROM ordenes_backup
WHERE estatus = 0
ORDER BY date ASC
LIMIT 20;

-- Ver estadísticas por año
SELECT 
    YEAR(date) as año,
    COUNT(*) as total_ordenes,
    SUM(CASE WHEN estatus = 0 THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estatus = 1 THEN 1 ELSE 0 END) as pagadas,
    SUM(total) as monto_total
FROM ordenes_backup
GROUP BY YEAR(date)
ORDER BY año DESC;

-- ============================================================================
-- NOTAS IMPORTANTES:
-- ============================================================================
-- 1. Este script usa INSERT ... SELECT para copiar datos
-- 2. La columna 'estatus' se establece en 0 si es NULL (pendiente de pago)
-- 3. Se recomienda usar la Opción A para evitar duplicados
-- 4. Después de ejecutar, verifica que los conteos sean correctos
-- 5. La tabla ordenes_backup debe tener la misma estructura que ordenes
-- ============================================================================
