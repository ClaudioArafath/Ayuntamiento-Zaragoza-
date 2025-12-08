-- ============================================================================
-- SCRIPT DE MIGRACIÓN: LYCAIOS_POS → AYUNTAMIENTO
-- ============================================================================
-- Propósito: Migrar tablas ordenes_backup y sanitarios a la base de datos
--            ayuntamiento para evitar interferencia con el software Lycaios POS
-- Fecha: 2025-12-08
-- ============================================================================

-- ============================================================================
-- PASO 0: VERIFICACIÓN INICIAL
-- ============================================================================
-- Verificar estado actual antes de la migración

SELECT '=== VERIFICACIÓN INICIAL ===' as paso;

-- Contar registros en lycaios_pos
SELECT 'lycaios_pos.ordenes_backup' as tabla, COUNT(*) as total_registros
FROM lycaios_pos.ordenes_backup
UNION ALL
SELECT 'lycaios_pos.sanitarios', COUNT(*)
FROM lycaios_pos.sanitarios;

-- Verificar triggers existentes
SELECT 'Triggers en tabla ordenes:' as info;
SHOW TRIGGERS FROM lycaios_pos WHERE `Table` = 'ordenes';

-- ============================================================================
-- PASO 1: CREAR TABLA ordenes_backup EN AYUNTAMIENTO
-- ============================================================================
SELECT '=== PASO 1: CREAR TABLA ordenes_backup ===' as paso;

USE ayuntamiento;

-- Eliminar tabla si existe (para re-ejecución del script)
DROP TABLE IF EXISTS ordenes_backup;

-- Crear tabla con estructura idéntica
CREATE TABLE ordenes_backup (
    id INT(11) NOT NULL AUTO_INCREMENT,
    code VARCHAR(255) DEFAULT NULL,
    date DATETIME NOT NULL,
    items TEXT DEFAULT NULL,
    employee VARCHAR(255) DEFAULT NULL,
    total DECIMAL(20,6) NOT NULL,
    estatus TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SELECT 'Tabla ordenes_backup creada exitosamente' as resultado;

-- ============================================================================
-- PASO 2: MIGRAR DATOS DE ordenes_backup
-- ============================================================================
SELECT '=== PASO 2: MIGRAR DATOS ordenes_backup ===' as paso;

-- Copiar todos los registros de lycaios_pos a ayuntamiento
INSERT INTO ayuntamiento.ordenes_backup (id, code, date, items, employee, total, estatus)
SELECT id, code, date, items, employee, total, estatus
FROM lycaios_pos.ordenes_backup;

-- Verificar migración
SELECT CONCAT('Registros migrados: ', COUNT(*)) as resultado
FROM ayuntamiento.ordenes_backup;

-- ============================================================================
-- PASO 3: CREAR TABLA sanitarios EN AYUNTAMIENTO
-- ============================================================================
SELECT '=== PASO 3: CREAR TABLA sanitarios ===' as paso;

USE ayuntamiento;

-- Eliminar tabla si existe (para re-ejecución del script)
DROP TABLE IF EXISTS sanitarios;

-- Crear tabla con estructura idéntica
CREATE TABLE sanitarios (
    id INT(11) NOT NULL AUTO_INCREMENT,
    folio VARCHAR(20) NOT NULL,
    nombre_cliente VARCHAR(255) NOT NULL,
    cantidad_total DECIMAL(10,2) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    qr_code VARCHAR(500) DEFAULT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY folio (folio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SELECT 'Tabla sanitarios creada exitosamente' as resultado;

-- ============================================================================
-- PASO 4: MIGRAR DATOS DE sanitarios
-- ============================================================================
SELECT '=== PASO 4: MIGRAR DATOS sanitarios ===' as paso;

-- Copiar todos los registros de lycaios_pos a ayuntamiento
INSERT INTO ayuntamiento.sanitarios (id, folio, nombre_cliente, cantidad_total, descripcion, qr_code, fecha_creacion)
SELECT id, folio, nombre_cliente, cantidad_total, descripcion, qr_code, fecha_creacion
FROM lycaios_pos.sanitarios;

-- Verificar migración
SELECT CONCAT('Registros migrados: ', COUNT(*)) as resultado
FROM ayuntamiento.sanitarios;

-- ============================================================================
-- PASO 5: ELIMINAR TRIGGERS DE lycaios_pos
-- ============================================================================
SELECT '=== PASO 5: ELIMINAR TRIGGERS ===' as paso;

USE lycaios_pos;

-- Eliminar trigger de INSERT
DROP TRIGGER IF EXISTS ordenes_after_insert;
SELECT 'Trigger ordenes_after_insert eliminado' as resultado;

-- Eliminar trigger de DELETE
DROP TRIGGER IF EXISTS ordenes_after_delete;
SELECT 'Trigger ordenes_after_delete eliminado' as resultado;

-- ============================================================================
-- PASO 6: VERIFICACIÓN POST-MIGRACIÓN
-- ============================================================================
SELECT '=== PASO 6: VERIFICACIÓN POST-MIGRACIÓN ===' as paso;

-- Verificar que no hay triggers
SELECT 'Verificando triggers (debe estar vacío):' as info;
SHOW TRIGGERS FROM lycaios_pos WHERE `Table` = 'ordenes';

-- Comparar conteos entre bases de datos
SELECT '=== COMPARACIÓN DE REGISTROS ===' as paso;

SELECT 
    'ordenes_backup' as tabla,
    (SELECT COUNT(*) FROM lycaios_pos.ordenes_backup) as lycaios_pos,
    (SELECT COUNT(*) FROM ayuntamiento.ordenes_backup) as ayuntamiento,
    CASE 
        WHEN (SELECT COUNT(*) FROM lycaios_pos.ordenes_backup) = (SELECT COUNT(*) FROM ayuntamiento.ordenes_backup)
        THEN '✓ CORRECTO'
        ELSE '✗ ERROR: Conteos no coinciden'
    END as verificacion
UNION ALL
SELECT 
    'sanitarios',
    (SELECT COUNT(*) FROM lycaios_pos.sanitarios),
    (SELECT COUNT(*) FROM ayuntamiento.sanitarios),
    CASE 
        WHEN (SELECT COUNT(*) FROM lycaios_pos.sanitarios) = (SELECT COUNT(*) FROM ayuntamiento.sanitarios)
        THEN '✓ CORRECTO'
        ELSE '✗ ERROR: Conteos no coinciden'
    END;

-- Verificar integridad de datos (muestra de registros)
SELECT '=== MUESTRA DE DATOS MIGRADOS ===' as paso;

SELECT 'ordenes_backup - Primeros 5 registros:' as info;
SELECT id, code, date, employee, total, estatus
FROM ayuntamiento.ordenes_backup
ORDER BY id ASC
LIMIT 5;

SELECT 'ordenes_backup - Últimos 5 registros:' as info;
SELECT id, code, date, employee, total, estatus
FROM ayuntamiento.ordenes_backup
ORDER BY id DESC
LIMIT 5;

SELECT 'sanitarios - Todos los registros:' as info;
SELECT id, folio, nombre_cliente, cantidad_total, fecha_creacion
FROM ayuntamiento.sanitarios
ORDER BY id DESC;

-- ============================================================================
-- PASO 7: ESTADÍSTICAS FINALES
-- ============================================================================
SELECT '=== ESTADÍSTICAS FINALES ===' as paso;

-- Estadísticas de ordenes_backup
SELECT 
    'ordenes_backup' as tabla,
    COUNT(*) as total_registros,
    SUM(CASE WHEN estatus = 0 THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estatus = 1 THEN 1 ELSE 0 END) as pagadas,
    MIN(date) as fecha_mas_antigua,
    MAX(date) as fecha_mas_reciente,
    SUM(total) as monto_total
FROM ayuntamiento.ordenes_backup;

-- Estadísticas de sanitarios
SELECT 
    'sanitarios' as tabla,
    COUNT(*) as total_registros,
    MIN(fecha_creacion) as fecha_mas_antigua,
    MAX(fecha_creacion) as fecha_mas_reciente,
    SUM(cantidad_total) as monto_total
FROM ayuntamiento.sanitarios;

-- ============================================================================
-- PASO 8 (OPCIONAL): ELIMINAR TABLAS DE lycaios_pos
-- ============================================================================
-- ADVERTENCIA: Solo ejecutar este paso después de verificar que todo funciona
-- correctamente en producción. Se recomienda mantener las tablas originales
-- por al menos 1 semana como respaldo.

/*
SELECT '=== PASO 8: ELIMINAR TABLAS ORIGINALES (OPCIONAL) ===' as paso;

USE lycaios_pos;

-- Descomentar las siguientes líneas solo después de verificar que todo funciona
-- DROP TABLE IF EXISTS ordenes_backup;
-- SELECT 'Tabla ordenes_backup eliminada de lycaios_pos' as resultado;

-- DROP TABLE IF EXISTS sanitarios;
-- SELECT 'Tabla sanitarios eliminada de lycaios_pos' as resultado;
*/

-- ============================================================================
-- RESUMEN DE MIGRACIÓN
-- ============================================================================
SELECT '=== MIGRACIÓN COMPLETADA ===' as paso;
SELECT 'Las tablas ordenes_backup y sanitarios han sido migradas exitosamente' as mensaje;
SELECT 'Los triggers han sido eliminados de lycaios_pos' as mensaje;
SELECT 'Siguiente paso: Actualizar archivos PHP para usar conectarAyuntamiento()' as mensaje;

-- ============================================================================
-- NOTAS IMPORTANTES
-- ============================================================================
/*
NOTAS POST-MIGRACIÓN:

1. Las tablas originales en lycaios_pos NO han sido eliminadas automáticamente
   - Esto permite un rollback fácil si hay problemas
   - Se recomienda mantenerlas por al menos 1 semana

2. Los triggers han sido eliminados permanentemente
   - El software Lycaios POS ahora funcionará sin interferencias
   - La funcionalidad de los triggers se implementará en PHP

3. Próximos pasos:
   - Actualizar todos los archivos PHP para usar conectarAyuntamiento()
   - Probar el flujo completo de cobros
   - Verificar que Lycaios POS funciona correctamente
   - Probar el dashboard y las gráficas

4. Rollback (si es necesario):
   - Los datos originales siguen en lycaios_pos
   - Se pueden recrear los triggers si es necesario
   - Revertir cambios en archivos PHP usando git

5. Limpieza final (después de 1 semana de pruebas exitosas):
   - Ejecutar el PASO 8 para eliminar tablas de lycaios_pos
   - Hacer backup final de lycaios_pos antes de eliminar
*/
