# Cambios para generar_comprobante.php

## Ubicación
`c:\xampp\htdocs\DB_lycaios\api\generar_comprobante.php`

## Cambio: Modificar procesamiento de datos del contribuyente (líneas 43-51)

### ANTES:
```php
    // Procesar datos del contribuyente desde el campo description
    $contribuyente = ['nombre' => 'No especificado', 'direccion' => 'No especificada'];
    if (!empty($factura_data['description'])) {
        $desc_decoded = json_decode($factura_data['description'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($desc_decoded)) {
            $contribuyente['nombre'] = $desc_decoded['nombre'] ?? 'No especificado';
            $contribuyente['direccion'] = $desc_decoded['direccion'] ?? 'No especificada';
        }
    }
```

### DESPUÉS:
```php
    // Procesar datos del contribuyente desde el campo description
    $contribuyente = [
        'nombre' => 'No especificado',
        'direccion' => 'No especificada',
        'telefono' => '',
        'email' => '',
        'rfc' => '',
        'razonsocial' => ''
    ];
    
    if (!empty($factura_data['description'])) {
        $desc_decoded = json_decode($factura_data['description'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($desc_decoded)) {
            $contribuyente['nombre'] = $desc_decoded['nombre'] ?? 'No especificado';
            $contribuyente['direccion'] = $desc_decoded['direccion'] ?? 'No especificada';
            $contribuyente['telefono'] = $desc_decoded['telefono'] ?? '';
            $contribuyente['email'] = $desc_decoded['email'] ?? '';
            $contribuyente['rfc'] = $desc_decoded['rfc'] ?? '';
            $contribuyente['razonsocial'] = $desc_decoded['razonsocial'] ?? '';
        }
    }
```

## Cambio 2: Agregar campos adicionales en el HTML (después de la línea 232)

### Agregar después de la línea 232 (después de mostrar dirección):

```php
                    <?php if (!empty($contribuyente['telefono'])): ?>
                    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($contribuyente['telefono']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($contribuyente['email'])): ?>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($contribuyente['email']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($contribuyente['rfc'])): ?>
                    <p><strong>RFC:</strong> <?php echo htmlspecialchars($contribuyente['rfc']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($contribuyente['razonsocial'])): ?>
                    <p><strong>Razón Social:</strong> <?php echo htmlspecialchars($contribuyente['razonsocial']); ?></p>
                    <?php endif; ?>
```

---

# Resumen de Cambios

Los cambios en `generar_comprobante.php` permiten:

1. **Extraer datos adicionales**: Lee teléfono, email, RFC y razón social del campo `description` del invoice.

2. **Mostrar solo campos no vacíos**: Usa condicionales `<?php if (!empty(...))` para mostrar solo los campos que tienen datos.

3. **Mantener compatibilidad**: Si los campos adicionales no existen en el JSON, simplemente no se muestran.

4. **Mejorar el comprobante**: El comprobante ahora incluye información fiscal y de contacto cuando está disponible.

## Ejemplo de comprobante mejorado:

**Antes:**
- Contribuyente: Juan Pérez
- Dirección: Calle Principal 123

**Después (con datos completos):**
- Contribuyente: Juan Pérez
- Dirección: Calle Principal 123
- Teléfono: 2331234567
- Email: juan@example.com
- RFC: PERJ850101ABC
- Razón Social: Juan Pérez Comerciante

**Después (Publico General):**
- Contribuyente: Publico General
- Dirección: N/A
(No se muestran campos adicionales porque están vacíos)
