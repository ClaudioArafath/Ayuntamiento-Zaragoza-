# Guía de Sincronización de Órdenes

## Problema

Lycaios POS crea órdenes en su propia base de datos (`lycaios_pos` en puerto 3311), pero el sistema de cobros del Ayuntamiento necesita acceder a esas órdenes desde la base de datos `ayuntamiento` (puerto 3306).

Como los triggers no funcionan entre bases de datos en diferentes servidores MySQL, necesitamos sincronizar manualmente las órdenes.

---

## Solución: Script de Sincronización PHP

Se crearon 3 archivos para sincronizar automáticamente las órdenes:

### 1. [sincronizar_ordenes.php](file:///c:/xampp/htdocs/DB_lycaios/sincronizar_ordenes.php)

Script principal que:
- Lee todas las órdenes de `lycaios_pos.ordenes`
- Verifica cuáles ya existen en `ayuntamiento.ordenes_backup`
- Inserta solo las órdenes nuevas
- Registra todo en `logs/sincronizacion.log`

### 2. [sincronizacion_auto.bat](file:///c:/xampp/htdocs/DB_lycaios/sincronizacion_auto.bat)

Script de Windows que ejecuta la sincronización automáticamente cada 5 minutos.

### 3. [api/sincronizar_manual.php](file:///c:/xampp/htdocs/DB_lycaios/api/sincronizar_manual.php)

Endpoint para ejecutar sincronización manual desde el dashboard.

---

## Opciones de Uso

### Opción 1: Sincronización Automática (Recomendada)

Ejecutar el script automático que sincroniza cada 5 minutos:

1. **Abrir CMD como Administrador**
2. **Ejecutar**:
```cmd
cd c:\xampp\htdocs\DB_lycaios
sincronizacion_auto.bat
```

3. **Dejar la ventana abierta** - El script seguirá ejecutándose en segundo plano

> **Nota**: Para detener la sincronización, simplemente cierra la ventana CMD.

#### Ejecutar como Servicio de Windows (Opcional)

Para que se ejecute automáticamente al iniciar Windows:

1. Descargar **NSSM** (Non-Sucking Service Manager): https://nssm.cc/download
2. Abrir CMD como Administrador
3. Ejecutar:
```cmd
nssm install SincronizacionLycaios "c:\xampp\htdocs\DB_lycaios\sincronizacion_auto.bat"
nssm start SincronizacionLycaios
```

---

### Opción 2: Sincronización Manual

Ejecutar cuando sea necesario:

```cmd
cd c:\xampp\htdocs\DB_lycaios
php sincronizar_ordenes.php
```

**Resultado esperado**:
```json
{
    "success": true,
    "insertadas": 15,
    "omitidas": 4736,
    "total": 4751
}
```

---

### Opción 3: Desde el Dashboard (Próximamente)

Se puede agregar un botón en el dashboard de administrador para ejecutar la sincronización manualmente.

---

## Cómo Funciona

```mermaid
graph LR
    A[Lycaios POS<br/>crea orden] --> B[lycaios_pos.ordenes<br/>Puerto 3311]
    B --> C[Script de<br/>Sincronización]
    C --> D[ayuntamiento.ordenes_backup<br/>Puerto 3306]
    D --> E[Sistema de Cobros<br/>puede procesar]
```

### Flujo Detallado

1. **Lycaios POS** crea una orden en `lycaios_pos.ordenes`
2. **Script de sincronización** (cada 5 minutos):
   - Lee todas las órdenes de `lycaios_pos.ordenes`
   - Compara con `ayuntamiento.ordenes_backup`
   - Inserta solo las órdenes nuevas
3. **Sistema de cobros** puede procesar las órdenes desde `ayuntamiento.ordenes_backup`

---

## Verificación

### Ver Log de Sincronización

```cmd
type c:\xampp\htdocs\DB_lycaios\logs\sincronizacion.log
```

**Ejemplo de log**:
```
[2025-12-08 14:50:00] === INICIANDO SINCRONIZACIÓN ===
[2025-12-08 14:50:00] Conexiones establecidas
[2025-12-08 14:50:00] Total órdenes en lycaios_pos.ordenes: 150
[2025-12-08 14:50:00] Total órdenes en ayuntamiento.ordenes_backup: 4751
[2025-12-08 14:50:00] ✓ Insertada orden: 0009644
[2025-12-08 14:50:00] ✓ Insertada orden: 0009645
[2025-12-08 14:50:00] === SINCRONIZACIÓN COMPLETADA ===
[2025-12-08 14:50:00] Órdenes insertadas: 2
[2025-12-08 14:50:00] Órdenes omitidas (ya existían): 148
```

### Verificar Sincronización en Base de Datos

```sql
-- Contar órdenes en ambas bases de datos
SELECT 
    (SELECT COUNT(*) FROM lycaios_pos.ordenes) as ordenes_lycaios,
    (SELECT COUNT(*) FROM ayuntamiento.ordenes_backup WHERE estatus = 0) as ordenes_ayuntamiento;
```

---

## Ventajas de Esta Solución

✅ **No interfiere con Lycaios POS** - Solo lee, no modifica  
✅ **Sincronización automática** - No requiere intervención manual  
✅ **Evita duplicados** - Solo inserta órdenes nuevas  
✅ **Registro completo** - Todo queda en el log  
✅ **Fácil de monitorear** - Puedes ver el log en cualquier momento  
✅ **Reversible** - Puedes detener la sincronización cuando quieras

---

## Frecuencia de Sincronización

**Actual**: Cada 5 minutos (300 segundos)

### Cambiar Frecuencia

Editar `sincronizacion_auto.bat`, línea 7:

```batch
timeout /t 300 /nobreak    REM 300 segundos = 5 minutos
```

**Opciones**:
- 1 minuto: `timeout /t 60`
- 2 minutos: `timeout /t 120`
- 10 minutos: `timeout /t 600`

---

## Troubleshooting

### Problema: "No se pueden conectar a las bases de datos"

**Solución**: Verificar que ambos servidores MySQL estén activos:
```cmd
netstat -an | findstr "3306 3311"
```

### Problema: "Error preparando INSERT"

**Solución**: Verificar que la tabla `ordenes_backup` existe en `ayuntamiento`:
```sql
SHOW TABLES FROM ayuntamiento LIKE 'ordenes_backup';
```

### Problema: No se sincronizan órdenes nuevas

**Solución**: 
1. Verificar que el script está ejecutándose
2. Revisar el log: `logs/sincronizacion.log`
3. Ejecutar manualmente para ver errores

---

## Integración con el Sistema Actual

### Flujo Completo

1. **Empleado en Lycaios POS** crea un pedido
2. **Lycaios POS** guarda en `lycaios_pos.ordenes`
3. **Script de sincronización** (cada 5 min) copia a `ayuntamiento.ordenes_backup`
4. **Empleado en sistema web** busca la orden
5. **Sistema web** encuentra la orden en `ayuntamiento.ordenes_backup`
6. **Empleado procesa el cobro**
7. **Sistema web** actualiza `ayuntamiento.ordenes_backup.estatus = 1`
8. **Sistema web** elimina de `lycaios_pos.ordenes` (si existe)
9. **Sistema web** crea factura en `lycaios_pos.invoice`

---

## Próximos Pasos

1. ✅ Ejecutar migración SQL
2. ✅ Actualizar archivos PHP
3. ✅ Configurar sincronización automática
4. ⏳ Probar flujo completo
5. ⏳ Monitorear logs por 1 semana

---

## Alternativas Consideradas

### ❌ Triggers entre bases de datos
**Problema**: No funcionan entre diferentes servidores MySQL

### ❌ Replicación MySQL
**Problema**: Requiere configuración compleja del servidor

### ✅ Script PHP con cron/tarea programada
**Ventaja**: Simple, confiable, fácil de mantener

---

## Notas Importantes

- El script solo **lee** de `lycaios_pos.ordenes`, nunca modifica
- El script solo **inserta** en `ayuntamiento.ordenes_backup`, nunca actualiza
- Las órdenes se insertan con `estatus = 0` (pendientes)
- El sistema de cobros actualiza el estatus cuando se procesa el pago
- El log se guarda en `logs/sincronizacion.log`

**Fecha de creación**: 2025-12-08  
**Versión**: 1.0
