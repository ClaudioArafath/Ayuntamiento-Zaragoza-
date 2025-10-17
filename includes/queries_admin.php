<?php
// Consultas específicas para Administradores y Presidente

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

$periodos = [];
$ingresos = [];
$etiquetas = [];

if ($result_ingresos && $result_ingresos->num_rows > 0) {
    while ($row = $result_ingresos->fetch_assoc()) {
        $periodos[] = $row['periodo'];
        $ingresos[] = $row['ingresos'];
        // Usar etiqueta personalizada si existe, de lo contrario usar periodo
        $etiquetas[] = isset($row['etiqueta']) ? $row['etiqueta'] : $row['periodo'];
    }
}

// === CONSULTA 2: Cobros por departamento para el mes seleccionado ===
$sql_pie = "
    SELECT c.name as categoria, SUM(t.total) as ingresos
    FROM topseller t
    INNER JOIN categorias c ON t.categoryid = c.id
    WHERE DATE_FORMAT(t.date, '%Y-%m') = '$mes_seleccionado'
    GROUP BY c.name
    ORDER BY ingresos DESC
";
$result_pie = $conn_lycaios->query($sql_pie);

$categorias = [];
$ingresos_cat = [];
$total_ingresos_mes = 0;

if ($result_pie && $result_pie->num_rows > 0) {
    while ($row = $result_pie->fetch_assoc()) {
        $categorias[] = $row['categoria'];
        $ingresos_cat[] = $row['ingresos'];
        $total_ingresos_mes += $row['ingresos'];
    }
}

// Calcular porcentajes para cada categoría
$porcentajes = [];
if ($total_ingresos_mes > 0) {
    foreach ($ingresos_cat as $ingreso) {
        $porcentajes[] = round(($ingreso / $total_ingresos_mes) * 100, 2);
    }
}

// === CONSULTA 3: Obtener meses disponibles para el selector ===
$sql_meses = "
    SELECT DISTINCT DATE_FORMAT(date, '%Y-%m') as periodo, 
           CONCAT(ELT(MONTH(date), 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'), ' ', YEAR(date)) as nombre_mes
    FROM invoice 
    ORDER BY periodo DESC
";
$result_meses = $conn_lycaios->query($sql_meses);

$meses_disponibles = [];
if ($result_meses && $result_meses->num_rows > 0) {
    while ($row = $result_meses->fetch_assoc()) {
        $meses_disponibles[$row['periodo']] = $row['nombre_mes'];
    }
}

// === CONSULTA 4: Total de facturas del mes ===
$sql_total_facturas = "
    SELECT COUNT(*) as total_facturas 
    FROM invoice 
    WHERE DATE_FORMAT(date, '%Y-%m') = '$mes_seleccionado'
";
$result_total_facturas = $conn_lycaios->query($sql_total_facturas);
$total_facturas = 0;
if ($result_total_facturas && $result_total_facturas->num_rows > 0) {
    $row = $result_total_facturas->fetch_assoc();
    $total_facturas = $row['total_facturas'];
}

// === CONSULTA 5: Total de condonaciones (descuentos) del mes ===
$sql_condonaciones = "
    SELECT COALESCE(SUM(descuento), 0) as total_condonaciones 
    FROM invoice 
    WHERE DATE_FORMAT(date, '%Y-%m') = '$mes_seleccionado'
";
$result_condonaciones = $conn_lycaios->query($sql_condonaciones);
$total_condonaciones = 0;
if ($result_condonaciones && $result_condonaciones->num_rows > 0) {
    $row = $result_condonaciones->fetch_assoc();
    $total_condonaciones = $row['total_condonaciones'];
}
?>