<?php
// Consultas comunes para todos los roles

// Función para limpiar strings y evitar problemas de codificación
function limpiarString($str) {
    if (!is_string($str)) return $str;
    // Convertir a UTF-8 válido, reemplazando caracteres inválidos
    return mb_convert_encoding($str, 'UTF-8', 'UTF-8');
}

// === CONSULTA: Últimas ordenes en tiempo real ===
$sql_facturas = "SELECT id, code, date, total, items, employee, estatus, clientid
                 FROM ordenes_backup
                 ORDER BY date DESC LIMIT 10";
$result_facturas = $conn_ayuntamiento->query($sql_facturas);

// Procesar los resultados para extraer descripciones y calcular subtotal real
$cobros_con_categoria = [];
if ($result_facturas && $result_facturas->num_rows > 0) {
    // Primero, recopilar todos los clientids únicos
    $client_ids = [];
    $ordenes_temp = [];
    
    while ($row = $result_facturas->fetch_assoc()) {
        $ordenes_temp[] = $row;
        if (!empty($row['clientid']) && $row['clientid'] > 1) {
            $client_ids[] = (int)$row['clientid'];
        }
    }
    
    // Obtener nombres de clientes en una sola consulta
    $client_names = [];
    if (!empty($client_ids)) {
        $client_ids_unique = array_unique($client_ids);
        $ids_placeholder = implode(',', $client_ids_unique);
        $sql_clients = "SELECT id, name FROM clients WHERE id IN ($ids_placeholder)";
        $result_clients = $conn_lycaios->query($sql_clients);
        
        if ($result_clients && $result_clients->num_rows > 0) {
            while ($client = $result_clients->fetch_assoc()) {
                $client_names[$client['id']] = $client['name'];
            }
        }
    }
    
    // Procesar cada orden
    foreach ($ordenes_temp as $row) {
        $descripciones_articulos = [];
        $subtotal_real = 0;
        $cantidad_articulos = 0;

        // Determinar estado (usando el campo estatus)
        $estado_texto = ($row['estatus'] == 1) ? 'Pagado' : 'Pendiente';
        
        // Obtener nombre del cliente
        $client_name = 'Cliente no registrado';
        if (!empty($row['clientid']) && isset($client_names[$row['clientid']])) {
            $client_name = $client_names[$row['clientid']];
        }

        // Procesar el JSON para extraer información de artículos
        if (!empty($row['items'])) {
            $items_data = json_decode($row['items'], true);
            
            if (is_array($items_data) && count($items_data) > 0) {
                $cantidad_articulos = count($items_data);
                
                foreach ($items_data as $item) {
                    // Extraer descripción del artículo y limpiarla
                    $descripcion = isset($item['Description']) ? limpiarString($item['Description']) : 'Sin descripción';
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
            'code' => limpiarString($row['code']),
            'date' => $row['date'],
            'total' => (float)$row['total'],
            'employee' => limpiarString($row['employee']),
            'estatus' => (int)$row['estatus'], // CAMBIADO: ahora es número
            'estatus_num' => $row['estatus'],
            'estatus_texto' => $estado_texto, // NUEVO: texto del estatus
            'descripcion_articulos' => $descripcion_texto,
            'subtotal_real' => $subtotal_real,
            'cantidad_articulos' => $cantidad_articulos,
            'descripciones_completas' => $descripciones_articulos,
            'client_name' => limpiarString($client_name) // NUEVO: nombre del cliente
        ];
    }
}
?>