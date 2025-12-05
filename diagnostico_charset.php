<?php
/**
 * Script de Diagnóstico de Codificación UTF-8
 * 
 * Este script verifica:
 * 1. La configuración de charset de las tablas
 * 2. Detecta registros con caracteres problemáticos
 * 3. Muestra ejemplos de datos que pueden causar errores
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>\n";
echo "<html lang='es'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <title>Diagnóstico de Codificación</title>\n";
echo "    <style>\n";
echo "        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }\n";
echo "        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }\n";
echo "        h1 { color: #333; border-bottom: 3px solid #e74c3c; padding-bottom: 10px; }\n";
echo "        h2 { color: #e74c3c; margin-top: 30px; }\n";
echo "        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0; }\n";
echo "        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 10px; border-radius: 4px; margin: 10px 0; }\n";
echo "        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0; }\n";
echo "        table { width: 100%; border-collapse: collapse; margin: 15px 0; }\n";
echo "        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }\n";
echo "        th { background: #e74c3c; color: white; }\n";
echo "        tr:nth-child(even) { background: #f9f9f9; }\n";
echo "        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }\n";
echo "        .info-box { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 15px 0; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";
echo "<div class='container'>\n";
echo "<h1>🔍 Diagnóstico de Codificación UTF-8</h1>\n";

$conn = conectarLycaidosPOS();

if (!$conn) {
    echo "<div class='error'>❌ Error: No se pudo conectar a la base de datos</div>";
    exit;
}

// =====================================================
// 1. VERIFICAR CHARSET DE LA BASE DE DATOS
// =====================================================
echo "<h2>1. Configuración de la Base de Datos</h2>\n";

$db_name = 'lycaidospos';
$sql = "SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME 
        FROM information_schema.SCHEMATA 
        WHERE SCHEMA_NAME = '$db_name'";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    $charset = $row['DEFAULT_CHARACTER_SET_NAME'];
    $collation = $row['DEFAULT_COLLATION_NAME'];
    
    if ($charset === 'utf8mb4') {
        echo "<div class='success'>✅ Base de datos: <code>$charset</code> (Correcto)</div>";
    } else {
        echo "<div class='error'>❌ Base de datos: <code>$charset</code> (Debería ser utf8mb4)</div>";
    }
    echo "<div class='info-box'>Collation: <code>$collation</code></div>";
}

// =====================================================
// 2. VERIFICAR CHARSET DE LAS TABLAS
// =====================================================
echo "<h2>2. Configuración de las Tablas</h2>\n";

$tables = ['invoice', 'ordenes_backup'];

echo "<table>\n";
echo "<tr><th>Tabla</th><th>Charset</th><th>Collation</th><th>Estado</th></tr>\n";

foreach ($tables as $table) {
    $sql = "SELECT TABLE_NAME, TABLE_COLLATION 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = '$db_name' AND TABLE_NAME = '$table'";
    $result = $conn->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
        $collation = $row['TABLE_COLLATION'];
        $charset = explode('_', $collation)[0];
        
        $status = ($charset === 'utf8mb4') 
            ? "<span style='color: green;'>✅ Correcto</span>" 
            : "<span style='color: red;'>❌ Necesita conversión</span>";
        
        echo "<tr>";
        echo "<td><strong>$table</strong></td>";
        echo "<td><code>$charset</code></td>";
        echo "<td><code>$collation</code></td>";
        echo "<td>$status</td>";
        echo "</tr>\n";
    }
}

echo "</table>\n";

// =====================================================
// 3. VERIFICAR COLUMNAS DE TEXTO
// =====================================================
echo "<h2>3. Configuración de Columnas de Texto</h2>\n";

echo "<table>\n";
echo "<tr><th>Tabla</th><th>Columna</th><th>Tipo</th><th>Charset</th><th>Estado</th></tr>\n";

foreach ($tables as $table) {
    $sql = "SELECT COLUMN_NAME, COLUMN_TYPE, CHARACTER_SET_NAME, COLLATION_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = '$db_name' 
            AND TABLE_NAME = '$table'
            AND DATA_TYPE IN ('varchar', 'text', 'char', 'mediumtext', 'longtext')";
    
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $charset = $row['CHARACTER_SET_NAME'];
            $status = ($charset === 'utf8mb4' || $charset === null) 
                ? "<span style='color: green;'>✅</span>" 
                : "<span style='color: red;'>❌</span>";
            
            echo "<tr>";
            echo "<td>$table</td>";
            echo "<td><code>{$row['COLUMN_NAME']}</code></td>";
            echo "<td>{$row['COLUMN_TYPE']}</td>";
            echo "<td><code>" . ($charset ?? 'N/A') . "</code></td>";
            echo "<td>$status</td>";
            echo "</tr>\n";
        }
    }
}

echo "</table>\n";

// =====================================================
// 4. BUSCAR REGISTROS CON CARACTERES PROBLEMÁTICOS
// =====================================================
echo "<h2>4. Detección de Caracteres Problemáticos</h2>\n";

// Buscar en invoice
$sql = "SELECT id, employee, date,
        LENGTH(employee) as len_bytes,
        CHAR_LENGTH(employee) as len_chars
        FROM invoice 
        WHERE employee REGEXP '[áéíóúñÁÉÍÓÚÑüÜ¿¡]'
        LIMIT 10";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<h3>Tabla: invoice</h3>\n";
    echo "<table>\n";
    echo "<tr><th>ID</th><th>Date</th><th>Employee</th><th>Bytes</th><th>Chars</th><th>Problema</th></tr>\n";
    
    while ($row = $result->fetch_assoc()) {
        $problema = ($row['len_bytes'] !== $row['len_chars']) 
            ? "<span style='color: orange;'>⚠️ Multibyte</span>" 
            : "<span style='color: green;'>✅ OK</span>";
        
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['date']}</td>";
        echo "<td>{$row['employee']}</td>";
        echo "<td>{$row['len_bytes']}</td>";
        echo "<td>{$row['len_chars']}</td>";
        echo "<td>$problema</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
} else {
    echo "<div class='success'>✅ No se encontraron caracteres especiales en invoice.employee</div>\n";
}

// Buscar en ordenes_backup
$sql = "SELECT id, code, employee,
        LENGTH(employee) as len_bytes,
        CHAR_LENGTH(employee) as len_chars
        FROM ordenes_backup 
        WHERE employee REGEXP '[áéíóúñÁÉÍÓÚÑüÜ¿¡]'
        LIMIT 10";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<h3>Tabla: ordenes_backup</h3>\n";
    echo "<table>\n";
    echo "<tr><th>ID</th><th>Code</th><th>Employee</th><th>Bytes</th><th>Chars</th><th>Problema</th></tr>\n";
    
    while ($row = $result->fetch_assoc()) {
        $problema = ($row['len_bytes'] !== $row['len_chars']) 
            ? "<span style='color: orange;'>⚠️ Multibyte</span>" 
            : "<span style='color: green;'>✅ OK</span>";
        
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['code']}</td>";
        echo "<td>{$row['employee']}</td>";
        echo "<td>{$row['len_bytes']}</td>";
        echo "<td>{$row['len_chars']}</td>";
        echo "<td>$problema</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
} else {
    echo "<div class='success'>✅ No se encontraron caracteres especiales en ordenes_backup.employee</div>\n";
}

// =====================================================
// 5. TEST DE JSON ENCODING
// =====================================================
echo "<h2>5. Test de JSON Encoding</h2>\n";

$sql = "SELECT id, employee FROM invoice LIMIT 5";
$result = $conn->query($sql);

if ($result) {
    echo "<table>\n";
    echo "<tr><th>ID</th><th>Employee</th><th>JSON (sin flags)</th><th>JSON (con flags)</th><th>Estado</th></tr>\n";
    
    while ($row = $result->fetch_assoc()) {
        $employee = $row['employee'];
        $id = $row['id'];
        
        // Sin flags
        $json_sin_flags = json_encode($employee);
        $error_sin_flags = json_last_error();
        
        // Con flags
        $json_con_flags = json_encode($employee, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        $error_con_flags = json_last_error();
        
        $status = ($error_con_flags === JSON_ERROR_NONE) 
            ? "<span style='color: green;'>✅ OK</span>" 
            : "<span style='color: red;'>❌ Error</span>";
        
        echo "<tr>";
        echo "<td>$id</td>";
        echo "<td>$employee</td>";
        echo "<td><code>" . htmlspecialchars($json_sin_flags) . "</code></td>";
        echo "<td><code>" . htmlspecialchars($json_con_flags) . "</code></td>";
        echo "<td>$status</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
}


// =====================================================
// 6. RECOMENDACIONES
// =====================================================
echo "<h2>6. Recomendaciones</h2>\n";

echo "<div class='info-box'>\n";
echo "<h3>📋 Pasos a Seguir:</h3>\n";
echo "<ol>\n";
echo "<li><strong>Hacer backup</strong> de la base de datos antes de cualquier cambio</li>\n";
echo "<li><strong>Ejecutar el script SQL</strong> <code>convertir_charset_utf8mb4.sql</code></li>\n";
echo "<li><strong>Verificar</strong> que las tablas ahora usen utf8mb4</li>\n";
echo "<li><strong>Probar</strong> el sistema en ambos entornos (desarrollo y producción)</li>\n";
echo "<li><strong>Monitorear</strong> la consola del navegador para errores</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<div class='warning'>\n";
echo "<strong>⚠️ Importante:</strong> Este script debe ejecutarse en AMBOS servidores:\n";
echo "<ul>\n";
echo "<li>Tu laptop de desarrollo</li>\n";
echo "<li>El servidor del ayuntamiento</li>\n";
echo "</ul>\n";
echo "</div>\n";

$conn->close();

echo "</div>\n";
echo "</body>\n";
echo "</html>\n";
?>
