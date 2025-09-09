<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

$host = "localhost";
$port = 3311;
$user = "root";
$password = "";
$database = "lycaios_pos";

$conn_lycaios = new mysqli($host, $user, $password, $database, $port);
if ($conn_lycaios->connect_error) {
    die("Error de conexión: " . $conn_lycaios->connect_error);
}
//Función para obtener el nombre de la categoría por su ID
$sql_facturas = "SELECT id, invoicecode, date, total, items FROM invoice ORDER BY date DESC LIMIT 3";
$result_facturas = $conn_lycaios->query($sql_facturas);

if ($result_facturas && $result_facturas->num_rows > 0) {
    while ($row = $result_facturas->fetch_assoc()) {
        $folio = $row['invoicecode'];
            // Extraer la categoría del JSON
        $categoryFromJson = obtenerCategoryDesdeItems($row['items']);
        
        if ($categoryFromJson == 0) {
            // Si Category es 0, buscar en la tabla ordenes
            $categoria = obtenerDepartamentoDesdeOrdenes($folio, $conn_lycaios);
        } else {
            // Si Category es diferente de 0, usar el mapeo normal
            $categoria = obtenerNombreCategoria($categoryFromJson);
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

        // Función para extraer el valor de Category desde el JSON
function obtenerCategoryDesdeItems($items_json) {
    if (empty($items_json)) {
        return 0;
    }
    
    $items_data = json_decode($items_json, true);
    if (is_array($items_data) && count($items_data) > 0) {
        $primer_item = $items_data[0];
        if (isset($primer_item['Category'])) {
            return (int)$primer_item['Category'];
        }
    }
    
    return 0;
}
// Función para extraer el valor de Description desde el JSON
function obtenerDescriptionDesdeItems($items_json) {
    if (empty($items_json)) {
        return 'Valor';
    }
    
    $items_data = json_decode($items_json, true);
    if (is_array($items_data) && count($items_data) > 0) {
        $primer_item = $items_data[0];
        if (isset($primer_item['Description'])) {
            return $primer_item['Description'];
        }
    }
    log("Items JSON vacío o inválido: " . $items_json);
    return 'N/A';
}

// Función para obtener departamento desde la tabla ordenes
function obtenerDepartamentoDesdeOrdenes($folio, $conn) {
    // Primero verificar si la tabla ordenes existe
    $tabla_existe = $conn->query("SHOW TABLES LIKE 'ordenes'");
    if ($tabla_existe && $tabla_existe->num_rows > 0) {
        // Buscar el employee en la tabla ordenes
        $sql_ordenes = "SELECT employee FROM ordenes WHERE id = '" . $conn->real_escape_string($folio) . "' LIMIT 1";
        $result = $conn->query($sql_ordenes);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return !empty($row['employee']) ? $row['employee'] : 'N/A';
        }
    }
    
    return 'N/A';
}

// Función para obtener el nombre de la categoría según el número
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=<, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <main><!-- Tabla de facturas -->
<div class="data-card">
    <h2 class="text-xl font-semibold mb-4">Últimos cobros</h2>
    <div class="overflow-x-auto">
        <table id="tabla-facturas" class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-200 text-left">
                    <th class="px-4 py-2 border">Folio</th>
                    <th class="px-4 py-2 border">Fecha y hora</th>
                    <th class="px-4 py-2 border">Total</th>
                    <th class="px-4 py-2 border">Departamento</th>
                    <th class="px-4 py-2 border">Comprobante</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($cobros_con_categoria)): ?>
                    <?php foreach ($cobros_con_categoria as $cobro): ?>
                        <tr class="hover:bg-gray-100">
                            <td class="px-4 py-2 border"><?php echo $cobro['invoicecode']; ?></td>
                            <td class="px-4 py-2 border"><?php echo $cobro['date']; ?></td>
                            <td class="px-4 py-2 border">$<?php echo number_format($cobro['total'], 2); ?></td>
                            <td class="px-4 py-2 border"><?php echo htmlspecialchars($cobro['categoria']); ?></td>
                            <td class="px-4 py-2 border text-center">
                                <button onclick="imprimirComprobante(<?php echo $cobro['id']; ?>)" 
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                    🖨️ Imprimir
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center p-4">No hay facturas registradas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
</body>
</html>
