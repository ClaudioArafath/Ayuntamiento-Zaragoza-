<?php
session_start();

// Validar sesión
if (!isset($_SESSION['username'])) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(["error" => "No autorizado"]);
    exit();
}

// Conexión a la base de datos
$host = "localhost";
$port = 3311;
$user = "root";
$password = "";
$database = "lycaios_pos";

$conn_lycaios = new mysqli($host, $user, $password, $database, $port);
if ($conn_lycaios->connect_error) {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(["error" => "Error de conexión: " . $conn_lycaios->connect_error]);
    exit();
}

// Obtener el filtro seleccionado
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'mes';

// Obtener el mes seleccionado
$mes_seleccionado = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');

// Preparar respuesta
$response = [];

// === CONSULTA 1: Ingresos según filtro (desde ordenes_backup) ===
$sql_ingresos = "";
switch($filtro) {
    case 'dia':
        $sql_ingresos = "
            SELECT DATE_FORMAT(date, '%Y-%m-%d') as periodo, SUM(total) as ingresos
            FROM ordenes_backup
            WHERE date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY periodo
            ORDER BY periodo ASC
        ";
        break;
    case 'semana':
        $sql_ingresos = "
            SELECT YEARWEEK(date) as periodo, CONCAT('Sem ', YEARWEEK(date)) as etiqueta, SUM(total) as ingresos
            FROM ordenes_backup
            WHERE date >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
            GROUP BY YEARWEEK(date)
            ORDER BY periodo ASC
        ";
        break;
    case 'mes':
    default:
        $sql_ingresos = "
            SELECT DATE_FORMAT(date, '%Y-%m') as periodo, DATE_FORMAT(date, '%b %Y') as etiqueta, SUM(total) as ingresos
            FROM ordenes_backup
            GROUP BY periodo
            ORDER BY periodo ASC
        ";
        break;
}

$result_ingresos = $conn_lycaios->query($sql_ingresos);

$ingresos_labels = [];
$ingresos_data = [];

if ($result_ingresos && $result_ingresos->num_rows > 0) {
    while ($row = $result_ingresos->fetch_assoc()) {
        $ingresos_labels[] = isset($row['etiqueta']) ? $row['etiqueta'] : $row['periodo'];
        $ingresos_data[] = (float)$row['ingresos'];
    }
}

$response['ingresos'] = [
    'labels' => $ingresos_labels,
    'data' => $ingresos_data
];

// === CONSULTA 2: Cobros por departamento para el mes seleccionado ===
// Simplificada - usa directamente la columna employee como departamento
$sql_pie = "
    SELECT 
        employee as categoria,
        SUM(total) as ingresos
    FROM ordenes_backup 
    WHERE DATE_FORMAT(date, '%Y-%m') = '$mes_seleccionado'
    GROUP BY employee
    ORDER BY ingresos DESC
";

$result_pie = $conn_lycaios->query($sql_pie);

$departamentos_labels = [];
$departamentos_data = [];
$total_ingresos_mes = 0;

if ($result_pie && $result_pie->num_rows > 0) {
    while ($row = $result_pie->fetch_assoc()) {
        $departamentos_labels[] = $row['categoria'];
        $departamentos_data[] = (float)$row['ingresos'];
        $total_ingresos_mes += (float)$row['ingresos'];
    }
}

// Calcular porcentajes
$porcentajes = [];
if ($total_ingresos_mes > 0) {
    foreach ($departamentos_data as $ingreso) {
        $porcentajes[] = round(($ingreso / $total_ingresos_mes) * 100, 2);
    }
}

$response['departamentos'] = [
    'labels' => $departamentos_labels,
    'data' => $departamentos_data
];

$response['porcentajes'] = $porcentajes;

// === CONSULTA 3: Total de facturas del mes ===
$sql_total_facturas = "
    SELECT COUNT(*) as total_facturas 
    FROM ordenes_backup 
    WHERE DATE_FORMAT(date, '%Y-%m') = '$mes_seleccionado'
";
$result_total_facturas = $conn_lycaios->query($sql_total_facturas);
$total_facturas = 0;
if ($result_total_facturas && $result_total_facturas->num_rows > 0) {
    $row = $result_total_facturas->fetch_assoc();
    $total_facturas = (int)$row['total_facturas'];
}

// === CONSULTA 4: Total de órdenes pendientes vs pagadas ===
$sql_estatus = "
    SELECT 
        estatus,
        COUNT(*) as cantidad,
        SUM(total) as total
    FROM ordenes_backup 
    WHERE DATE_FORMAT(date, '%Y-%m') = '$mes_seleccionado'
    GROUP BY estatus
";
$result_estatus = $conn_lycaios->query($sql_estatus);
$ordenes_pendientes = 0;
$ordenes_pagadas = 0;

if ($result_estatus && $result_estatus->num_rows > 0) {
    while ($row = $result_estatus->fetch_assoc()) {
        if ($row['estatus'] == 0) {
            $ordenes_pendientes = (int)$row['cantidad'];
        } else if ($row['estatus'] == 1) {
            $ordenes_pagadas = (int)$row['cantidad'];
        }
    }
}

// === CONSULTA 5: Últimos ordenes en tiempo real (desde ordenes_backup) ===
$sql_facturas = "
    SELECT 
        id, 
        code, 
        date, 
        total, 
        items, 
        employee, 
        estatus,
        (SELECT COUNT(*) FROM ordenes_backup WHERE estatus = 0) as pendientes_count
    FROM ordenes_backup 
    ORDER BY date DESC 
    LIMIT 10
";

$result_facturas = $conn_lycaios->query($sql_facturas);

$facturas = [];
$total_pendientes = 0;

if ($result_facturas && $result_facturas->num_rows > 0) {
    while ($row = $result_facturas->fetch_assoc()) {
        $descripciones_articulos = [];
        $subtotal_real = 0;
        $cantidad_articulos = 0;

        // Procesar el JSON para extraer información de artículos
        if (!empty($row['items'])) {
            $items_data = json_decode($row['items'], true);
            
            if (is_array($items_data) && count($items_data) > 0) {
                $cantidad_articulos = count($items_data);
                
                foreach ($items_data as $item) {
                    // Extraer descripción del artículo
                    $descripcion = isset($item['Description']) ? $item['Description'] : 'Sin descripción';
                    $descripciones_articulos[] = $descripcion;
                    
                    // Calcular subtotal real
                    $precio = isset($item['Price']) ? floatval($item['Price']) : 0;
                    $unidades = isset($item['Units']) ? floatval($item['Units']) : 1;
                    $precio_real = isset($item['Real_Price']) ? floatval($item['Real_Price']) : $precio;
                    $descuento = isset($item['Descuento']) ? floatval($item['Descuento']) : 0;
                    
                    if ($descuento > 0) {
                        $subtotal_articulo = $precio_real * $unidades; // Precio ya con descuento aplicado
                    } else {
                        $subtotal_articulo = $precio_real * $unidades; // Sin descuento
                    }                  
                    $subtotal_real += $subtotal_articulo; // Sumar al subtotal total
                } 
                
                // Limitar las descripciones para mostrar
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
        
        $facturas[] = [
            'id' => (int)$row['id'],
            'code' => $row['code'],
            'date' => $row['date'],
            'total' => (float)$row['total'],
            'employee' => $row['employee'], // Usamos employee como categoría/departamento
            'estatus' => (int)$row['estatus'],
            'estatus_num' => $row['estatus'],
            'estatus_texto' => ($row['estatus'] == 1) ? 'Pagado' : 'Pendiente',
            'descripcion_articulos' => $descripcion_texto,
            'subtotal_real' => $subtotal_real,
            'cantidad_articulos' => $cantidad_articulos
        ];
    }
    
    // Obtener el total de pendientes del primer registro
    if (isset($row['pendientes_count'])) {
        $total_pendientes = (int)$row['pendientes_count'];
    }
}

// Preparar respuesta con los datos de resumen
$response['resumen'] = [
    'ingresos_mes' => (float)$total_ingresos_mes,
    'total_facturas' => (int)$total_facturas,
    'ordenes_pendientes' => $ordenes_pendientes,
    'ordenes_pagadas' => $ordenes_pagadas,
    'total_pendientes' => $total_pendientes
];

$response['facturas'] = $facturas;

$conn_lycaios->close();

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
?>