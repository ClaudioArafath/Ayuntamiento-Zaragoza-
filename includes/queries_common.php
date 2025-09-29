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
        $descripciones_articulos = [];
        $subtotal_real = 0;
        $cantidad_articulos = 0;

        // Determinar estado (usando el campo estatus)
        $estado = ($row['estatus'] == 1) ? 'Pagado' : 'Pendiente';

        // Procesar el JSON para extraer información de artículos
        if (!empty($row['items'])) {
            $items_data = json_decode($row['items'], true);
            
            if (is_array($items_data) && count($items_data) > 0) {
                $cantidad_articulos = count($items_data);
                
                foreach ($items_data as $item) {
                    // Extraer descripción del artículo
                    $descripcion = isset($item['Description']) ? $item['Description'] : 'Sin descripción';
                    $descripciones_articulos[] = $descripcion;
                    
                    // Calcular subtotal real (Price * Units)
                    $precio = isset($item['Price']) ? floatval($item['Price']) : 0;
                    $unidades = isset($item['Units']) ? floatval($item['Units']) : 1;
                    $precio_real = isset($item['Real_Price']) ? floatval($item['Real_Price']) : $precio;
                    $descuento = isset($item['Descuento']) ? floatval($item['Descuento']) : 0;
                    
                    // Calcular el subtotal para este artículo
                    // Si hay descuento, usar Real_Price, de lo contrario usar Price * Units
                    if ($descuento > 0) {
                        $subtotal_articulo = $precio_real * $unidades;
                    } else {
                        $subtotal_articulo = $precio * $unidades;
                    }
                    
                    $subtotal_real += $subtotal_articulo;
                }
                
                // Limitar las descripciones a las primeras 3 para mostrar
                $descripciones_mostrar = array_slice($descripciones_articulos, 0, 3);
                $descripcion_texto = implode(', ', $descripciones_mostrar);
                if (count($descripciones_articulos) > 3) {
                    $descripcion_texto .= '... (+' . (count($descripciones_articulos) - 3) . ' más)';
                }

        $cobros_con_categoria[] = [
            'id' => $row['id'],
            'code' => $row['code'],
            'date' => $row['date'],
            'total' => $row['total'],
            'employee' => $row['employee'],
            'estatus' => $estado, // Estado (Pagado/Pendiente)
            'estatus_num' => $row['estatus'], // Valor numérico para filtros aun que no se usa por el momento.
            'descripcion_articulos' => $descripcion_texto,
            'subtotal' => $subtotal_real,
            'cantidad_articulos' => $cantidad_articulos,
            'descripciones_completas' => $descripciones_articulos // Para uso detallado si se necesita
        ];
            }
        }
    }
}
?>