import mysql.connector

# Conexión (usa los datos reales de host, usuario, contraseña y base de datos)
conn = mysql.connector.connect(
    host="localhost",
    port=3311,
    user="root",
    password="",
    database="lycaios_pos"
)

cursor = conn.cursor()

# Ver los primeros registros de la tabla tickets
cursor.execute("SELECT * FROM topseller LIMIT 10;")

# Mostrar nombres de columnas
columnas = [desc[0] for desc in cursor.description]
print("Columnas:", columnas)

# Mostrar registros
for fila in cursor.fetchall():
    print(fila)

conn.close()