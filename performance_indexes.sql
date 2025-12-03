-- =============================================
-- Performance Optimization - Database Indexes
-- Sprint 1: Quick Wins
-- Expected Impact: -200ms on queries
-- =============================================

-- Verificar índices existentes
SHOW INDEX FROM invoice;
SHOW INDEX FROM ordenes_backup;

-- =============================================
-- ÍNDICES PARA TABLA invoice
-- =============================================

-- Índice para filtros por fecha (usado en queries_admin.php)
-- Mejora queries de ingresos por día/semana/mes
ALTER TABLE invoice 
ADD INDEX idx_date (date);

-- Índice compuesto para filtros por mes y empleado
-- Mejora query de cobros por departamento
-- NOTA: employee es TEXT, se limita a 100 caracteres para el índice
ALTER TABLE invoice 
ADD INDEX idx_date_employee (date, employee(100));

-- Índice para columna items (si se usa en WHERE)
-- Mejora query de condonaciones
-- NOTA: items es TEXT, se limita a 100 caracteres
ALTER TABLE invoice 
ADD INDEX idx_items_not_null (date, items(100));

-- =============================================
-- ÍNDICES PARA TABLA ordenes_backup
-- =============================================

-- Índice compuesto para fecha y estatus
-- Mejora query de últimas órdenes en queries_common.php
ALTER TABLE ordenes_backup 
ADD INDEX idx_date_estatus (date DESC, estatus);

-- Índice para búsqueda por código
-- Mejora búsquedas de órdenes
-- NOTA: code es TEXT, se limita a 50 caracteres
ALTER TABLE ordenes_backup 
ADD INDEX idx_code (code(50));

-- =============================================
-- VERIFICACIÓN DE ÍNDICES CREADOS
-- =============================================

-- Ver índices de invoice
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    INDEX_TYPE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'lycaios_pos' 
  AND TABLE_NAME = 'invoice'
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- Ver índices de ordenes_backup
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    INDEX_TYPE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'lycaios_pos' 
  AND TABLE_NAME = 'ordenes_backup'
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- =============================================
-- ANÁLISIS DE RENDIMIENTO (OPCIONAL)
-- =============================================

-- Analizar query de ingresos por mes
EXPLAIN SELECT DATE_FORMAT(date, '%Y-%m') as periodo, 
               DATE_FORMAT(date, '%b %Y') as etiqueta, 
               SUM(total) as ingresos
FROM invoice
GROUP BY periodo
ORDER BY periodo ASC;

-- Analizar query de cobros por departamento
EXPLAIN SELECT employee as categoria, SUM(total) as ingresos
FROM invoice
WHERE DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
GROUP BY employee
ORDER BY ingresos DESC;

-- Analizar query de últimas órdenes
EXPLAIN SELECT id, code, date, total, items, employee, estatus 
FROM ordenes_backup 
ORDER BY date DESC 
LIMIT 10;

-- =============================================
-- NOTAS IMPORTANTES
-- =============================================

/*
1. Estos índices mejorarán significativamente el rendimiento de lectura
2. Pueden ralentizar ligeramente las operaciones INSERT/UPDATE (impacto mínimo)
3. Ocuparán espacio adicional en disco (estimado: 5-10MB)
4. Se recomienda ejecutar ANALYZE TABLE después de crear índices
5. Para columnas TEXT se especifica longitud de índice (employee(100), code(50))

Para aplicar optimizaciones:
ANALYZE TABLE invoice;
ANALYZE TABLE ordenes_backup;

Para eliminar índices si es necesario:
ALTER TABLE invoice DROP INDEX idx_date;
ALTER TABLE invoice DROP INDEX idx_date_employee;
ALTER TABLE invoice DROP INDEX idx_items_not_null;
ALTER TABLE ordenes_backup DROP INDEX idx_date_estatus;
ALTER TABLE ordenes_backup DROP INDEX idx_code;
*/
