<?php
require_once '../config/database.php';

$conn = conectarLycaidosPOS();

// Get a sample from invoice table (most recent paid order)
$sql = "SELECT items FROM invoice ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    echo "=== ITEMS JSON STRUCTURE ===\n\n";
    echo "Raw JSON:\n";
    echo $row['items'] . "\n\n";
    
    echo "Parsed:\n";
    $items = json_decode($row['items'], true);
    print_r($items);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "\nJSON Error: " . json_last_error_msg();
    }
} else {
    echo "No data found";
}

$conn->close();
?>
