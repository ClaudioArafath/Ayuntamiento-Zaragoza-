import re

# Leer el archivo
with open('assets/js/main.js', 'r', encoding='utf-8') as f:
    content = f.read()

# Patrón para encontrar y eliminar las funciones duplicadas
# Desde "// Función para abrir el modal de orden personalizada" hasta justo antes de "// Inicializar componentes"
pattern = r'// Función para abrir el modal de orden personalizada.*?(?=// Inicializar componentes)'

# Eliminar el bloque
new_content = re.sub(pattern, '', content, flags=re.DOTALL)

# Guardar el archivo
with open('assets/js/main.js', 'w', encoding='utf-8') as f:
    f.write(new_content)

print("✅ Funciones duplicadas eliminadas de main.js")
