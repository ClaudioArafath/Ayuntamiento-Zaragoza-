<?php
require_once __DIR__ . '/../config/database.php';

$conn = conectarLycaidosPOS();

// Obtener todos los campos de la tabla invoice
echo "=== TODOS LOS CAMPOS DE LA TABLA INVOICE ===\n\n";
$sql = "DESCRIBE invoice";
$result = $conn->query($sql);

$required_fields = [];
$optional_fields = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $field_info = sprintf(
            "%-25s %-20s %-8s %-15s %-10s\n",
            $row['Field'],
            $row['Type'],
            $row['Null'],
            $row['Default'] === null ? 'NULL' : $row['Default'],
            $row['Key']
        );
        
        echo $field_info;
        
        // Identificar campos requeridos sin default
        if ($row['Null'] === 'NO' && $row['Default'] === null && $row['Extra'] !== 'auto_increment') {
            $required_fields[] = $row['Field'];
        } else {
            $optional_fields[] = $row['Field'];
        }
    }
}

echo "\n=== CAMPOS REQUERIDOS (NO NULL, SIN DEFAULT) ===\n";
foreach ($required_fields as $field) {
    echo "- $field\n";
}

echo "\n=== CAMPOS OPCIONALES O CON DEFAULT ===\n";
foreach ($optional_fields as $field) {
    echo "- $field\n";
}

$conn->close();
?>
