-- =============================================
-- SCRIPT DE VERIFICACIÓN DE ÍNDICES
-- Ejecuta este script DESPUÉS de aplicar performance_indexes.sql
-- =============================================

-- 1. Verificar índices en tabla invoice
SHOW INDEX FROM invoice;

-- Deberías ver estos índices:
-- - idx_date (columna: date)
-- - idx_date_employee (columnas: date, employee)
-- - idx_items_not_null (columnas: date, items)

-- 2. Verificar índices en tabla ordenes_backup
SHOW INDEX FROM ordenes_backup;

-- Deberías ver estos índices:
-- - idx_date_estatus (columnas: date, estatus)
-- - idx_code (columna: code)

-- =============================================
-- VERIFICACIÓN DETALLADA
-- =============================================

-- Ver información completa de índices de invoice
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    INDEX_TYPE,
    NON_UNIQUE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'lycaios_pos' 
  AND TABLE_NAME = 'invoice'
  AND INDEX_NAME IN ('idx_date', 'idx_date_employee', 'idx_items_not_null')
ORDER BY INDEX_NAME, SEQ_IN_INDEX;

-- Ver información completa de índices de ordenes_backup
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    INDEX_TYPE,
    NON_UNIQUE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'lycaios_pos' 
  AND TABLE_NAME = 'ordenes_backup'
  AND INDEX_NAME IN ('idx_date_estatus', 'idx_code')
ORDER BY INDEX_NAME, SEQ_IN_INDEX;

-- =============================================
-- PRUEBA DE RENDIMIENTO
-- =============================================

-- Probar query de ingresos por mes (debería usar idx_date)
EXPLAIN SELECT DATE_FORMAT(date, '%Y-%m') as periodo, 
               DATE_FORMAT(date, '%b %Y') as etiqueta, 
               SUM(total) as ingresos
FROM invoice
GROUP BY periodo
ORDER BY periodo ASC;

-- Busca en la columna "key" o "possible_keys" para ver si usa idx_date

-- Probar query de cobros por departamento (debería usar idx_date_employee)
EXPLAIN SELECT employee as categoria, SUM(total) as ingresos
FROM invoice
WHERE DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
GROUP BY employee
ORDER BY ingresos DESC;

-- Busca en la columna "key" para ver si usa idx_date_employee

-- Probar query de últimas órdenes (debería usar idx_date_estatus)
EXPLAIN SELECT id, code, date, total, items, employee, estatus 
FROM ordenes_backup 
ORDER BY date DESC 
LIMIT 10;

-- Busca en la columna "key" para ver si usa idx_date_estatus

-- =============================================
-- RESULTADO ESPERADO
-- =============================================

/*
Si los índices se crearon correctamente, deberías ver:

1. SHOW INDEX FROM invoice:
   - 3 índices nuevos (idx_date, idx_date_employee, idx_items_not_null)
   - Más los índices que ya existían (PRIMARY, etc.)

2. SHOW INDEX FROM ordenes_backup:
   - 2 índices nuevos (idx_date_estatus, idx_code)
   - Más los índices que ya existían

3. EXPLAIN queries:
   - En la columna "type" deberías ver "index" o "range" en lugar de "ALL"
   - En la columna "key" deberías ver el nombre del índice usado
   - En la columna "rows" deberías ver menos filas escaneadas

ANTES de índices:
- type: ALL (escaneo completo de tabla)
- rows: 10000+ (todas las filas)

DESPUÉS de índices:
- type: index o range (usa índice)
- rows: 100-1000 (solo filas necesarias)
*/
