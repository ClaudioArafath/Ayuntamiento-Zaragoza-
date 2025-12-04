with open('comprobante_sanitarios.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

# Buscar y reemplazar la sección del QR (líneas 258-263 aproximadamente)
new_lines = []
skip_until = -1

for i, line in enumerate(lines):
    # Si estamos en modo skip, saltar hasta la línea indicada
    if i < skip_until:
        continue
    
    # Buscar el inicio de la sección QR placeholder
    if '<div class="qr-code">' in line and i > 200:  # Asegurar que es la sección correcta
        # Agregar la línea del div
        new_lines.append(line)
        # Agregar el nuevo código QR
        new_lines.append('            <?php\r\n')
        new_lines.append('            // Generar QR code usando la URL almacenada en la base de datos\r\n')
        new_lines.append('            $qr_size = "150";\r\n')
        new_lines.append('            $qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=" . $qr_size . "x" . $qr_size . "&data=" . urlencode($orden[\'qr_code\']);\r\n')
        new_lines.append('            ?>\r\n')
        new_lines.append('            <img src="<?php echo htmlspecialchars($qr_image_url); ?>" \r\n')
        new_lines.append('                 alt="Código QR de verificación" \r\n')
        new_lines.append('                 style="width: 150px; height: 150px; margin: 0 auto; display: block; border: 2px solid #ddd; padding: 5px; background: white;">\r\n')
        new_lines.append('            <p style="font-size: 12px; margin-top: 10px;">Escanee para verificar autenticidad</p>\r\n')
        new_lines.append('            <p style="font-size: 10px; color: #666;">Folio: <?php echo htmlspecialchars($orden[\'folio\']); ?></p>\r\n')
        new_lines.append('        </div>\r\n')
        # Saltar las líneas del placeholder viejo (hasta </div>)
        skip_until = i + 6  # Saltar las próximas 5 líneas del placeholder
        continue
    
    new_lines.append(line)

# Aplicar ajustes de márgenes
content = ''.join(new_lines)
content = content.replace('padding: 0.5in;', 'padding: 0.35in;')
content = content.replace('@page { margin: 0.5in; }', '@page { margin: 0.3in; size: letter; }')
content = content.replace('padding-bottom: 15px;', 'padding-bottom: 10px;')
content = content.replace('margin-bottom: 20px;', 'margin-bottom: 12px;')
content = content.replace('font-size: 20px;', 'font-size: 18px;')
content = content.replace('margin: 10px 0;', 'margin: 6px 0;', 1)  # Solo el primero (titulo)
content = content.replace('margin: 15px 0;', 'margin: 10px 0;', 1)  # Solo el primero (info)
content = content.replace('line-height: 1.4;', 'line-height: 1.2;')
content = content.replace('margin: 25px 0;', 'margin: 15px 0;')
content = content.replace('padding: 10px;', 'padding: 7px;')
content = content.replace('font-size: 16px;', 'font-size: 15px;')
content = content.replace('margin-top: 50px;', 'margin-top: 30px;')
content = content.replace('margin-top: 60px;', 'margin-top: 45px;')

with open('comprobante_sanitarios.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ QR code agregado y márgenes ajustados")
