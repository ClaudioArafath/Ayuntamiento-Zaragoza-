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

// === CONSULTA 1: Ingresos según filtro ===
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

//  === CONSULTA 2: Cobros por departamento para el mes seleccionado ===
$sql_pie = "
    SELECT c.name as categoria, SUM(t.total) as ingresos
    FROM topseller t
    INNER JOIN categorias c ON t.categoryid = c.id
    WHERE DATE_FORMAT(t.date, '%Y-%m') = '$mes_seleccionado'
    GROUP BY c.name
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
$sql_condonaciones = "
    SELECT COALESCE(SUM(descuento), 0) as total_condonaciones 
    FROM invoice 
    WHERE DATE_FORMAT(date, '%Y-%m') = '$mes_seleccionado'
";
$result_condonaciones = $conn_lycaios->query($sql_condonaciones);
$total_condonaciones = 0;
if ($result_condonaciones && $result_condonaciones->num_rows > 0) {
    $row = $result_condonaciones->fetch_assoc();
    $total_condonaciones = (float)$row['total_condonaciones'];
}

// Preparar respuesta con los datos de resumen
$response['resumen'] = [
    'ingresos_mes' => (float)$total_ingresos_mes,
    'total_facturas' => (int)$total_facturas,
    'total_condonaciones' => (float)$total_condonaciones
];

// === CONSULTA 5: Últimos cobros en tiempo real ===
// Modificada para extraer la categoría desde el JSON en items
$sql_facturas = "SELECT id, invoicecode, date, total, items FROM invoice ORDER BY date DESC LIMIT 8";
$result_facturas = $conn_lycaios->query($sql_facturas);

$facturas = [];

if ($result_facturas && $result_facturas->num_rows > 0) {
    while ($row = $result_facturas->fetch_assoc()) {
        $categoria = 'N/A';
        $folio = $row['invoicecode'];

        // Intentar obtener el departamento
        $departamento = obtenerDepartamentoPorFolio($folio, $conn_lycaios);
        
        if ($departamento !== 'N/A') {
            $categoria = $departamento;
        } else {
        
        // Intentar extraer la categoría del JSON
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
        
        $facturas[] = [
            'id' => (int)$row['id'],
            'invoicecode' => $row['invoicecode'],
            'date' => $row['date'],
            'total' => (float)$row['total'],
            'categoria' => $categoria
        ];
    }
}

function obtenerDepartamentoPorFolio($folio, $conn) {
    // Intentar desde la tabla ordenes
    $sql_ordenes = "SELECT employee FROM ordenes WHERE id = '$folio' LIMIT 1";
    $result = $conn->query($sql_ordenes);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['employee'];
    }
            return 'N/A';
}

// Función para obtener el nombre de la categoría
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

$response['facturas'] = $facturas;

$conn_lycaios->close();

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
?>