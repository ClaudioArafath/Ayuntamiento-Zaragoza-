<?php
// Consultas comunes para todos los roles

// === CONSULTA: Últimas ordenes en tiempo real ===
$sql_facturas = "SELECT id, code, date, total, items, employee, estatus FROM ordenes_backup ORDER BY date DESC LIMIT 10";
$result_facturas = $conn_lycaios->query($sql_facturas);

// Procesar los resultados para extraer la categoría
$cobros_con_categoria = [];
if ($result_facturas && $result_facturas->num_rows > 0) {
    while ($row = $result_facturas->fetch_assoc()) {
        $categoria = 'N/A';
        $folio = $row['code'];

        // Determinar estado (usando el campo estatus)
        $estado = ($row['estatus'] == 1) ? 'Pagado' : 'Pendiente';
        /*
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
        */
        $cobros_con_categoria[] = [
            'id' => $row['id'],
            'code' => $row['code'],
            'date' => $row['date'],
            'total' => $row['total'],
            'employee' => $row['employee'],
            //'categoria' => $categoria,
            'estatus' => $estado, // Estado (Pagado/Pendiente)
            'estatus_num' => $row['estatus'] // Valor numérico para filtros aun que no se usa por el momento.
        ];
    }
}
?>