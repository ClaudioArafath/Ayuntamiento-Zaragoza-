<?php
require_once 'config/database.php';

$conn = conectarLycaidosPOS();

$result = $conn->query("DESCRIBE invoice");

echo "Invoice Table Schema:\n";
echo str_repeat("-", 80) . "\n";
while ($row = $result->fetch_assoc()) {
    echo sprintf("%-20s %-15s %-10s %-10s %-15s\n", 
        $row['Field'], 
        $row['Type'], 
        $row['Null'], 
        $row['Key'], 
        $row['Default']
    );
}

$conn->close();
?>
