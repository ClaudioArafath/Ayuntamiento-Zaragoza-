-- =============================================
-- Migración: Agregar clientid a ordenes_backup
-- Fecha: 2025-12-08
-- Descripción: Agrega la columna clientid a la tabla ordenes_backup
--              para mantener la referencia del cliente en las órdenes respaldadas
-- =============================================

USE ayuntamiento;

-- Agregar columna clientid después de code
ALTER TABLE ordenes_backup 
ADD COLUMN clientid INT(11) DEFAULT 0 AFTER code;

-- Verificar que la columna se agregó correctamente
DESCRIBE ordenes_backup;

SELECT 'Migración completada exitosamente' AS status;
