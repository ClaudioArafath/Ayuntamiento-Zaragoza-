-- ============================================================================
-- SCRIPT DE PRUEBA: SIMULACIÓN DE COBRO CON ORDEN ANTIGUA
-- ============================================================================
-- Base de datos: lycaios_pos (Puerto 3311)
-- Propósito: Crear una orden de prueba para simular cobro sin afectar datos reales
-- Fecha: 2025-12-05
-- ============================================================================

USE lycaios_pos;

-- ============================================================================
-- PASO 1: SELECCIONAR UNA ORDEN ANTIGUA PARA PRUEBA
-- ============================================================================
-- Buscar órdenes antiguas pendientes de pago (estatus = 0)
SELECT 
    id, code, date, employee, total, estatus,
    DATEDIFF(NOW(), date) as dias_antiguedad
FROM ordenes_backup
WHERE estatus = 0
ORDER BY date ASC
LIMIT 10;

-- ============================================================================
-- PASO 2: CREAR ORDEN DE PRUEBA
-- ============================================================================
-- Esta orden será una copia de una orden antigua pero con un código especial
-- para identificarla fácilmente y poder eliminarla después

-- IMPORTANTE: La tabla ordenes_backup solo tiene estos campos:
-- id, code, date, items, employee, total, estatus

-- Ejemplo de inserción de orden de prueba:
INSERT INTO ordenes_backup (code, date, items, employee, total, estatus)
VALUES (
    'TEST-SIMULACION-001',  -- Código único para identificar la prueba
    '2020-01-15 10:30:00',  -- Fecha antigua para simular orden vieja
    '[{"Description":"Prueba de cobro antiguo","Price":100.00,"Units":1,"Real_Price":100.00,"Descuento":0}]', -- Items en formato JSON
    'USUARIO_PRUEBA',        -- employee
    100.00,                  -- total
    0                        -- estatus (0 = pendiente)
);

-- Verificar que se insertó correctamente
SELECT * FROM ordenes_backup WHERE code = 'TEST-SIMULACION-001';

-- ============================================================================
-- PASO 3: TAMBIÉN INSERTAR EN TABLA ORDENES (si es necesario)
-- ============================================================================
-- Según el código de procesar_cobro.php, el sistema busca en ordenes_backup
-- pero también elimina de la tabla ordenes después del cobro
-- Por seguridad, insertamos también en ordenes

-- NOTA: La tabla 'ordenes' tiene estructura diferente (10 campos, sin estatus)
INSERT INTO ordenes (code, userid, clientid, date, items, piezas, employee, description, total)
VALUES (
    'TEST-SIMULACION-001',
    1,                       -- userid
    0,                       -- clientid
    '2020-01-15 10:30:00',
    '[{"Description":"Prueba de cobro antiguo","Price":100.00,"Units":1,"Real_Price":100.00,"Descuento":0}]',
    1,                       -- piezas
    'USUARIO_PRUEBA',
    'Orden de prueba para simulación de cobro',
    100.00
);

-- Verificar inserción
SELECT * FROM ordenes WHERE code = 'TEST-SIMULACION-001';

-- ============================================================================
-- PASO 4: INSTRUCCIONES PARA REALIZAR LA PRUEBA DE COBRO
-- ============================================================================
/*
PASOS PARA PROBAR EL COBRO:

1. Ejecutar los PASOS 2 y 3 de este script para crear la orden de prueba

2. Ir a la interfaz web del sistema de cobros

3. Buscar la orden con el código: TEST-SIMULACION-001

4. Procesar el cobro con los siguientes datos de prueba:
   - Nombre del contribuyente: PRUEBA SIMULACION
   - Dirección: CALLE PRUEBA 123
   - Monto recibido: 100.00
   - Cambio: 0.00

5. Verificar que el cobro se procesó correctamente:
   - Debe aparecer un registro en la tabla 'invoice'
   - El estatus en ordenes_backup debe cambiar a 1
   - El registro debe eliminarse de la tabla 'ordenes'

6. Ejecutar el PASO 5 de este script para limpiar los datos de prueba
*/

-- ============================================================================
-- PASO 5: LIMPIEZA DE DATOS DE PRUEBA
-- ============================================================================
-- EJECUTAR DESPUÉS DE COMPLETAR LA PRUEBA DE COBRO

-- Eliminar el registro de invoice generado por la prueba
-- NOTA: Reemplaza <INVOICE_CODE> con el código de factura generado
DELETE FROM invoice 
WHERE ordercode = 'TEST-SIMULACION-001';

-- Verificar eliminación
SELECT * FROM invoice WHERE ordercode = 'TEST-SIMULACION-001';

-- Eliminar de ordenes_backup (si no se eliminó automáticamente)
DELETE FROM ordenes_backup 
WHERE code = 'TEST-SIMULACION-001';

-- Verificar eliminación
SELECT * FROM ordenes_backup WHERE code = 'TEST-SIMULACION-001';

-- Eliminar de ordenes (si no se eliminó automáticamente)
DELETE FROM ordenes 
WHERE code = 'TEST-SIMULACION-001';

-- Verificar eliminación
SELECT * FROM ordenes WHERE code = 'TEST-SIMULACION-001';

-- ============================================================================
-- PASO 6: VERIFICACIÓN FINAL
-- ============================================================================
-- Confirmar que no quedan rastros de la prueba
SELECT 'ordenes' as tabla, COUNT(*) as registros_prueba
FROM ordenes 
WHERE code = 'TEST-SIMULACION-001'
UNION ALL
SELECT 'ordenes_backup' as tabla, COUNT(*) as registros_prueba
FROM ordenes_backup 
WHERE code = 'TEST-SIMULACION-001'
UNION ALL
SELECT 'invoice' as tabla, COUNT(*) as registros_prueba
FROM invoice 
WHERE ordercode = 'TEST-SIMULACION-001';

-- Si todos los conteos son 0, la limpieza fue exitosa

-- ============================================================================
-- CONSULTAS ADICIONALES ÚTILES
-- ============================================================================

-- Ver todas las órdenes de prueba (por si se crearon múltiples)
SELECT * FROM ordenes_backup 
WHERE code LIKE 'TEST-%' OR employee = 'USUARIO_PRUEBA';

-- Ver facturas de prueba
SELECT * FROM invoice 
WHERE ordercode LIKE 'TEST-%';

-- Limpiar TODAS las órdenes de prueba (usar con precaución)
/*
DELETE FROM invoice WHERE ordercode LIKE 'TEST-%';
DELETE FROM ordenes_backup WHERE code LIKE 'TEST-%';
DELETE FROM ordenes WHERE code LIKE 'TEST-%';
*/

-- ============================================================================
-- PLANTILLA PARA CREAR ORDEN DE PRUEBA DESDE ORDEN REAL
-- ============================================================================
-- Si quieres crear una orden de prueba basada en una orden real existente:

/*
-- 1. Primero, selecciona la orden real que quieres copiar
SELECT * FROM ordenes_backup WHERE code = '<CODIGO_ORDEN_REAL>' LIMIT 1;

-- 2. Luego, inserta una copia con código de prueba en ordenes_backup
INSERT INTO ordenes_backup (code, date, items, employee, total, estatus)
SELECT 
    CONCAT('TEST-', code) as code,  -- Agrega prefijo TEST-
    date,
    items,
    CONCAT('PRUEBA-', employee) as employee,  -- Marca el empleado como prueba
    total,
    0 as estatus  -- Asegura que esté pendiente
FROM ordenes_backup 
WHERE code = '<CODIGO_ORDEN_REAL>' 
LIMIT 1;

-- 3. También insertar en ordenes (con estructura diferente)
INSERT INTO ordenes (code, userid, clientid, date, items, piezas, employee, description, total)
SELECT 
    CONCAT('TEST-', code) as code,
    userid,
    clientid,
    date,
    items,
    piezas,
    CONCAT('PRUEBA-', employee) as employee,
    CONCAT('PRUEBA: ', description) as description,
    total
FROM ordenes 
WHERE code = '<CODIGO_ORDEN_REAL>' 
LIMIT 1;
*/

-- ============================================================================
-- NOTAS IMPORTANTES:
-- ============================================================================
-- 1. SIEMPRE usa códigos con prefijo 'TEST-' para órdenes de prueba
-- 2. Después de cada prueba, LIMPIA los datos usando el PASO 5
-- 3. Verifica que la limpieza fue exitosa con el PASO 6
-- 4. NO uses órdenes reales para pruebas, siempre crea copias
-- 5. El sistema procesar_cobro.php actualiza ordenes_backup y elimina de ordenes
-- 6. Guarda el código de factura (invoice_code) generado para la limpieza
-- ============================================================================
