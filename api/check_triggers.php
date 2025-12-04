<?php
require_once __DIR__ . '/../config/database.php';

$conn = conectarLycaidosPOS();

// Check for triggers on invoice table
$sql = "SHOW TRIGGERS FROM lycaios_pos WHERE `Table` = 'invoice'";
$result = $conn->query($sql);

echo "=== TRIGGERS ON INVOICE TABLE ===\n";
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        print_r($row);
        echo "\n";
    }
} else {
    echo "No triggers found on invoice table\n";
}

// Check invoice table structure
echo "\n=== INVOICE TABLE STRUCTURE ===\n";
$sql = "DESCRIBE invoice";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Null'] . " - " . $row['Default'] . "\n";
    }
}

// Check for related tables that might have priceunit field
echo "\n=== TABLES WITH PRICEUNIT FIELD ===\n";
$sql = "SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = 'lycaios_pos' 
        AND COLUMN_NAME = 'priceunit'";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        print_r($row);
        echo "\n";
    }
} else {
    echo "No tables with priceunit field found\n";
}

$conn->close();
?>
