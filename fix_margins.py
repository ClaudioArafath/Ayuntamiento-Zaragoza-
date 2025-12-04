import re

# Leer el archivo
with open('comprobante_sanitarios.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Ajustes para que quepa en una página
replacements = {
    'padding: 0.5in;': 'padding: 0.35in;',
    'margin: 0.5in;': 'margin: 0.3in; size: letter;',
    'padding-bottom: 15px;': 'padding-bottom: 10px;',
    'margin-bottom: 20px;': 'margin-bottom: 12px;',
    'font-size: 20px;': 'font-size: 18px;',
    'margin: 10px 0;': 'margin: 6px 0;',
    'margin: 15px 0;': 'margin: 10px 0;',
    'line-height: 1.4;': 'line-height: 1.2;',
    'margin: 25px 0;': 'margin: 12px 0;',
    'padding: 10px;': 'padding: 6px;',
    'font-size: 16px;': 'font-size: 14px;',
    'margin-top: 50px;': 'margin-top: 25px;',
    'margin-top: 60px;': 'margin-top: 40px;',
    'margin-top: 20px;': 'margin-top: 12px;',
    'padding-top: 20px;': 'padding-top: 12px;',
}

for old, new in replacements.items():
    content = content.replace(old, new)

# Guardar
with open('comprobante_sanitarios.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Márgenes ajustados para una sola página")
