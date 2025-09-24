<?php
// Funciones auxiliares
function obtenerCategoryDesdeItems($items_json) {
    if (empty($items_json)) return 0;
    
    $items_data = json_decode($items_json, true);
    if (is_array($items_data) && count($items_data) > 0) {
        $primer_item = $items_data[0];
        if (isset($primer_item['Category'])) {
            return (int)$primer_item['Category'];
        }
    }
    return 0;
}

function obtenerDepartamentoDesdeOrdenes($folio, $conn) {
    $tabla_existe = $conn->query("SHOW TABLES LIKE 'ordenes'");
    if ($tabla_existe && $tabla_existe->num_rows > 0) {
        $sql = "SELECT employee FROM ordenes WHERE employee = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $folio);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return !empty($row['employee']) ? $row['employee'] : 'N/A';
        }
        $stmt->close();
    }
    return 'N/A';
}

function obtenerNombreCategoria($categoryId) {
    $categorias = [
        2 => 'INDUSTRIA Y COMERCIO',
        3 => 'REGISTRO CIVIL',
        4 => 'SECRETARÍA DEL AYUNTAMIENTO',
        5 => 'PANTEONES, PARQUES Y JARDINES',
        6 => 'VIALIDAD',
        7 => 'JUZGADO',
        8 => 'SINDICATURA',
        10 => 'PROTECCIÓN CIVIL',
        11 => 'RECAUDACIÓN',
        12 => 'PATRIMONIO Y HACIENDA PÚBLICA',
        13 => 'OBRAS PÚBLICAS',
        14 => 'CONTRALORÍA',
        15 => 'DESARROLLO RURAL',    
    ];
    return $categorias[$categoryId] ?? 'CATEGORÍA ' . $categoryId;
}

?>