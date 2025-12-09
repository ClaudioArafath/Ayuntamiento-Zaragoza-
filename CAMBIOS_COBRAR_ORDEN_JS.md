# Cambios para cobrarOrden.js

## Ubicación
`c:\xampp\htdocs\DB_lycaios\assets\js\cobrarOrden.js`

## Cambio 1: Modificar función `mostrarInformacionOrden` (líneas 128-169)

### Agregar después de la línea 144 (después de auto-completar monto recibido):

```javascript
        // Auto-completar datos del cliente si están disponibles
        const nombreInput = document.getElementById('nombre-contribuyente');
        const direccionInput = document.getElementById('direccion-contribuyente');
        
        if (orden.client && nombreInput && direccionInput) {
            // Auto-completar nombre y dirección
            nombreInput.value = orden.client.name || 'Publico General';
            direccionInput.value = orden.client.address || 'N/A';
            
            // Hacer los campos de solo lectura
            nombreInput.readOnly = true;
            direccionInput.readOnly = true;
            nombreInput.classList.add('bg-gray-100', 'cursor-not-allowed');
            direccionInput.classList.add('bg-gray-100', 'cursor-not-allowed');
            
            // Guardar datos del cliente en la orden actual para enviarlos después
            ordenActual.client = orden.client;
        }
```

## Cambio 2: Modificar función `confirmarCobroOrden` (líneas 207-267)

### Reemplazar las líneas 247-266 (el body del fetch) con:

```javascript
            // Preparar datos para enviar
            const datosEnvio = {
                folio: ordenActual.code,
                monto_recibido: montoRecibido,
                cambio: montoRecibido - total
            };

            // Si hay datos de cliente, enviarlos como objeto
            if (ordenActual.client) {
                datosEnvio.client_data = ordenActual.client;
            } else {
                // Compatibilidad: enviar campos individuales
                datosEnvio.nombre_contribuyente = nombreContribuyente;
                datosEnvio.direccion_contribuyente = direccionContribuyente;
            }

            console.log('Enviando datos al servidor...', datosEnvio);

            const response = await fetch('api/procesar_cobro.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(datosEnvio)
            });
```

## Cambio 3: Modificar función `resetearModal` (líneas 54-79)

### Agregar después de la línea 78 (después de limpiar valores):

```javascript
        // Restaurar campos a estado editable
        if (nombreInput) {
            nombreInput.readOnly = false;
            nombreInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
        }
        if (direccionInput) {
            direccionInput.readOnly = false;
            direccionInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
        }
```

---

# Resumen de Cambios

Los cambios en `cobrarOrden.js` permiten:

1. **Auto-completar datos del cliente**: Cuando se busca una orden, si tiene datos de cliente asociados (clientid > 1), los campos de nombre y dirección se llenan automáticamente.

2. **Campos de solo lectura**: Los campos auto-completados se vuelven de solo lectura (readonly) y cambian su apariencia visual para indicar que no son editables.

3. **Envío de datos completos**: Al confirmar el cobro, se envían todos los datos del cliente (nombre, dirección, teléfono, email, RFC, razón social) al backend.

4. **Compatibilidad**: Se mantiene compatibilidad con el sistema anterior para órdenes sin cliente asociado (Publico General).

5. **Reset correcto**: Al cerrar el modal, los campos vuelven a su estado editable original.
