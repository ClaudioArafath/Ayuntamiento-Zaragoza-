<?php
// Consultas comunes para todos los roles

// === CONSULTA: Últimas ordenes en tiempo real ===
$sql_facturas = "SELECT id, code, date, total, items, employee, estatus FROM ordenes_backup ORDER BY date DESC LIMIT 10";
$result_facturas = $conn_lycaios->query($sql_facturas);

// Procesar los resultados para extraer descripciones y calcular subtotal real
$cobros_con_categoria = [];
if ($result_facturas && $result_facturas->num_rows > 0) {
    while ($row = $result_facturas->fetch_assoc()) {
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
                
                // Limitar las descripciones a las primeras 2 para mostrar
                $descripciones_mostrar = array_slice($descripciones_articulos, 0, 2);
                $descripcion_texto = implode(', ', $descripciones_mostrar);
                if (count($descripciones_articulos) > 2) {
                    $descripcion_texto .= '... (+' . (count($descripciones_articulos) - 2) . ' más)';
                }
            }
        } else {
            $descripcion_texto = 'Sin artículos';
            $subtotal_real = $row['total'];
        }

        $cobros_con_categoria[] = [
            'id' => $row['id'],
            'code' => $row['code'],
            'date' => $row['date'],
            'total' => $row['total'],
            'employee' => $row['employee'],
            'estatus' => $estado,
            'estatus_num' => $row['estatus'],
            'descripcion_articulos' => $descripcion_texto,
            'subtotal_real' => $subtotal_real,
            'cantidad_articulos' => $cantidad_articulos,
            'descripciones_completas' => $descripciones_articulos
        ];
    }
}
?>