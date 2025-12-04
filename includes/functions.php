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