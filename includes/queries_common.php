<?php
// Consultas comunes para todos los roles

// === CONSULTA: Últimos cobros en tiempo real ===
$sql_facturas = "SELECT id, invoicecode, date, total, items FROM invoice ORDER BY date DESC LIMIT 8";
$result_facturas = $conn_lycaios->query($sql_facturas);

// Procesar los resultados para extraer la categoría
$cobros_con_categoria = [];
if ($result_facturas && $result_facturas->num_rows > 0) {
    while ($row = $result_facturas->fetch_assoc()) {
        $categoria = 'N/A';
        $folio = $row['invoicecode'];

        // Extraer la categoría del JSON
        $categoryFromJson = obtenerCategoryDesdeItems($row['items']);
        
        if ($categoryFromJson == 0) {
            // Si Category es 0, buscar en la tabla ordenes
            $categoria = obtenerDepartamentoDesdeOrdenes($folio, $conn_lycaios);
        } else {
            // Extraer la categoría del JSON
            if (!empty($row['items'])) {
                $items_data = json_decode($row['items'], true);
                if (is_array($items_data) && count($items_data) > 0) {
                    // Tomar la categoría del primer item
                    $primer_item = $items_data[0];
                    if (isset($primer_item['Category']) && $primer_item['Category'] != 0) {
                        $categoria = obtenerNombreCategoria($primer_item['Category']);
                    }
                }
            }
        }
        
        $cobros_con_categoria[] = [
            'id' => $row['id'],
            'invoicecode' => $row['invoicecode'],
            'date' => $row['date'],
            'total' => $row['total'],
            'categoria' => $categoria
        ];
    }
}
?>