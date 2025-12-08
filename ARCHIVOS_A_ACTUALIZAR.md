# Archivos PHP a Actualizar para Migración de Base de Datos

## Archivos que usan `ordenes_backup` - Cambiar a `conectarAyuntamiento()`

### 1. api/procesar_cobro.php
**Cambios necesarios**:
- Usar `conectarAyuntamiento()` para consultas/updates de `ordenes_backup`
- Usar `conectarLycaidosPOS()` para `ordenes` e `invoice`
- Manejar transacciones en ambas bases de datos

### 2. api/buscar_orden.php
**Cambios necesarios**:
- Cambiar conexión de `conectarLycaidosPOS()` a `conectarAyuntamiento()`
- Actualizar consultas a `ordenes_backup`

### 3. api/buscar_comprobante.php
**Cambios necesarios**:
- Cambiar conexión de `conectarLycaidosPOS()` a `conectarAyuntamiento()`
- Actualizar consultas a `ordenes_backup`

### 4. api/actualizar_datos.php
**Cambios necesarios**:
- Cambiar conexión de `conectarLycaidosPOS()` a `conectarAyuntamiento()`
- Actualizar consultas a `ordenes_backup`

### 5. api/eliminar_registros_antiguos.php
**Cambios necesarios**:
- Cambiar conexión de `conectarLycaidosPOS()` a `conectarAyuntamiento()`
- Actualizar consultas a `ordenes_backup`

### 6. includes/cancelar_orden.php
**Cambios necesarios**:
- Cambiar conexión de `conectarLycaidosPOS()` a `conectarAyuntamiento()`
- Actualizar consultas a `ordenes_backup`

### 7. includes/queries_common.php
**Cambios necesarios**:
- Cambiar conexión de `conectarLycaidosPOS()` a `conectarAyuntamiento()`
- Actualizar consultas a `ordenes_backup`

### 8. diagnostico_charset.php
**Cambios necesarios**:
- Cambiar conexión de `conectarLycaidosPOS()` a `conectarAyuntamiento()`
- Actualizar consultas a `ordenes_backup`

## Archivos que usan `sanitarios` - Cambiar a `conectarAyuntamiento()`

### 9. api/guardar_orden_personalizada.php
**Cambios necesarios**:
- Cambiar conexión de `conectarLycaidosPOS()` a `conectarAyuntamiento()`
- Actualizar consultas a `sanitarios`

### 10. api/obtener_siguiente_folio.php
**Cambios necesarios**:
- Cambiar conexión de `conectarLycaidosPOS()` a `conectarAyuntamiento()`
- Actualizar consultas a `sanitarios`

### 11. api/verificar_sanitario.php
**Cambios necesarios**:
- Cambiar conexión de `conectarLycaidosPOS()` a `conectarAyuntamiento()`
- Actualizar consultas a `sanitarios`

### 12. comprobante_sanitarios.php
**Cambios necesarios**:
- Cambiar conexión de `conectarLycaidosPOS()` a `conectarAyuntamiento()`
- Actualizar consultas a `sanitarios`

## Estrategia de Actualización

1. **Archivos simples** (solo usan una tabla): Reemplazo directo de `conectarLycaidosPOS()` por `conectarAyuntamiento()`

2. **Archivo complejo** (`procesar_cobro.php`): Requiere dos conexiones simultáneas
   - `$connAyuntamiento` para `ordenes_backup`
   - `$connLycaios` para `ordenes` e `invoice`
