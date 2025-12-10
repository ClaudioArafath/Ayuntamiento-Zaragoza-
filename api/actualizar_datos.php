<?php
session_start();

// Validar sesión
if (!isset($_SESSION['username'])) {
    header("HTTP/1.1 401 Unauthorized");
    header('Content-Type: application/json');
    echo json_encode(["error" => "No autorizado"]);
    exit();
}

// Conexión a la base de datos
require_once __DIR__ . '/../config/database.php';
$conn_lycaios = conectarLycaidosPOS();      // Para invoice
$conn_ayuntamiento = conectarAyuntamiento(); // Para ordenes_backup

// Verificar conexión
if (!$conn_lycaios || !$conn_ayuntamiento) {
    header('Content-Type: application/json');
    echo json_encode(["error" => "Error de conexión a la base de datos"]);
    exit();
}

// Obtener el filtro seleccionado
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'mes';

// Obtener el mes seleccionado
$mes_seleccionado = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');

// Preparar respuesta
$response = [];

// === CONSULTA 1: Ingresos totales según filtro (desde invoice) ===
$sql_ingresos = "";
switch($filtro) {
    case 'dia':
        $sql_ingresos = "
            SELECT DATE_FORMAT(date, '%Y-%m-%d') as periodo, SUM(total) as ingresos
            FROM invoice
            WHERE date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY periodo
            ORDER BY periodo ASC
        ";
        break;
    case 'semana':
        $sql_ingresos = "
            SELECT YEARWEEK(date) as periodo, CONCAT('Sem ', YEARWEEK(date)) as etiqueta, SUM(total) as ingresos
            FROM invoice
            WHERE date >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
            GROUP BY YEARWEEK(date)
            ORDER BY periodo ASC
        ";
        break;
    case 'mes':
    default:
        $sql_ingresos = "
            SELECT DATE_FORMAT(date, '%Y-%m') as periodo, DATE_FORMAT(date, '%b %Y') as etiqueta, SUM(total) as ingresos
            FROM invoice
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
$sql_pie = "
    SELECT 
        employee,
        SUM(total) as ingresos
    FROM invoice 
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
        $departamentos_labels[] = $row['employee'];
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

// === CONSULTA 2B: Total de ingresos del mes (desde invoice) ===
$sql_ingresos_mes = "
    SELECT COALESCE(SUM(total), 0) as total_ingresos 
    FROM invoice 
    WHERE DATE_FORMAT(date, '%Y-%m') = '$mes_seleccionado'
";
$result_ingresos_mes = $conn_lycaios->query($sql_ingresos_mes);
$total_ingresos_mes = 0;
if ($result_ingresos_mes && $result_ingresos_mes->num_rows > 0) {
    $row = $result_ingresos_mes->fetch_assoc();
    $total_ingresos_mes = (float)$row['total_ingresos'];
}

// === CONSULTA 3: Total de facturas del mes ===
$sql_total_facturas = "
    SELECT COUNT(*) as total_facturas 
    FROM invoice
    WHERE DATE_FORMAT(date, '%Y-%m') = '$mes_seleccionado'
";
$result_total_facturas = $conn_lycaios->query($sql_total_facturas);
$total_facturas = 0;
if ($result_total_facturas && $result_total_facturas->num_rows > 0) {
    $row = $result_total_facturas->fetch_assoc();
    $total_facturas = (int)$row['total_facturas'];
}

// === CONSULTA 4: Total de condonaciones (descuentos) del mes ===
$sql_facturas_descuento = "
    SELECT items
    FROM invoice 
    WHERE DATE_FORMAT(date, '%Y-%m') = '$mes_seleccionado'
    AND items IS NOT NULL
";
$result_facturas_descuento = $conn_lycaios->query($sql_facturas_descuento);
$total_condonaciones = 0;

if ($result_facturas_descuento && $result_facturas_descuento->num_rows > 0) {
    while ($factura = $result_facturas_descuento->fetch_assoc()) {
        $items_data = json_decode($factura['items'], true);
        
        if (is_array($items_data)) {
            foreach ($items_data as $item) {
                $descuento_item = isset($item['Descuento']) ? floatval($item['Descuento']) : 0;
                $total_condonaciones += $descuento_item;
            }
        }
    }
}

// === CONSULTA 5: Últimos ordenes en tiempo real (desde ayuntamiento.ordenes_backup) ===
$sql_facturas = "
    SELECT 
        id, 
        code, 
        date, 
        total, 
        items, 
        employee, 
        estatus,
        clientid,
        (SELECT COUNT(*) FROM ordenes_backup WHERE estatus = 0) as pendientes_count
    FROM ordenes_backup
    ORDER BY date DESC 
    LIMIT 10
";

$result_facturas = $conn_ayuntamiento->query($sql_facturas);

$facturas = [];

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
        
        // Obtener nombre del cliente
        $client_name = 'Cliente no registrado';
        if (!empty($row['clientid']) && isset($client_names[$row['clientid']])) {
            $client_name = $client_names[$row['clientid']];
        }

        if (!empty($row['items'])) {
            $items_data = json_decode($row['items'], true);
            
            if (is_array($items_data) && count($items_data) > 0) {
                $cantidad_articulos = count($items_data);
                
                foreach ($items_data as $item) {
                    $descripcion = isset($item['Description']) ? $item['Description'] : 'Sin descripción';
                    $descripciones_articulos[] = $descripcion;
                    
                    $precio = isset($item['Price']) ? floatval($item['Price']) : 0;
                    $unidades = isset($item['Units']) ? floatval($item['Units']) : 1;
                    $precio_real = isset($item['Real_Price']) ? floatval($item['Real_Price']) : $precio;
                    $descuento = isset($item['Descuento']) ? floatval($item['Descuento']) : 0;
                    
                    if ($descuento > 0) {
                        $subtotal_articulo = $precio_real * $unidades;
                    } else {
                        $subtotal_articulo = $precio_real * $unidades;
                    }                  
                    $subtotal_real += $subtotal_articulo;
                } 
                
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
            'employee' => $row['employee'],
            'estatus' => (int)$row['estatus'],
            'estatus_num' => $row['estatus'],
            'estatus_texto' => ($row['estatus'] == 1) ? 'Pagado' : 'Pendiente',
            'descripcion_articulos' => $descripcion_texto,
            'subtotal_real' => $subtotal_real,
            'cantidad_articulos' => $cantidad_articulos,
            'client_name' => $client_name
        ];
    }
}

// Preparar respuesta con los datos de resumen
$response['resumen'] = [
    'ingresos_mes' => (float)$total_ingresos_mes,
    'total_facturas' => (int)$total_facturas,
    'total_condonaciones' => (float)$total_condonaciones
];

$response['facturas'] = $facturas;

$conn_lycaios->close();
$conn_ayuntamiento->close();

// Devolver respuesta en formato JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
?>