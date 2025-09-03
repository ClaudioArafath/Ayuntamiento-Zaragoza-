<?php
session_start();

// Validar sesión
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
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
    die("Error de conexión: " . $conn_lycaios->connect_error);
}

// Obtener ID de la factura desde parámetro GET
$factura_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Consultar datos de la factura - CORREGIDO: variable fuera de comillas
$sql_factura = "
    SELECT i.*, c.name as categoria
    FROM invoice i
    LEFT JOIN topseller t ON i.id = t.invoiceid
    LEFT JOIN categorias c ON t.categoryid = c.id
    WHERE i.id = " . $factura_id . "
    LIMIT 1
";

$result_factura = $conn_lycaios->query($sql_factura);
$factura = null;

if ($result_factura && $result_factura->num_rows > 0) {
    $factura = $result_factura->fetch_assoc();
    
    // Consultar los items de la factura - CORREGIDO: variable fuera de comillas
    $sql_items = "SELECT items FROM invoice WHERE id = " . $factura_id;
    $result_items = $conn_lycaios->query($sql_items);
    $items_data = [];
    
    if ($result_items && $result_items->num_rows > 0) {
        $row_items = $result_items->fetch_assoc();
        if (!empty($row_items['items'])) {
            // Intentar decodificar el JSON de items
            $items_json = $row_items['items'];
            $items_data = json_decode($items_json, true);
            
            // Si json_decode falla, intentar limpiar el JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Limpiar posibles caracteres especiales
                $cleaned_json = preg_replace('/[^\x20-\x7E]/', '', $items_json);
                $items_data = json_decode($cleaned_json, true);
                
                // Si aún falla, mostrar error en log pero continuar
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Error decodificando JSON de items: " . json_last_error_msg());
                    $items_data = [];
                }
            }
        }
    }
    
    // Extraer conceptos de los items
    $conceptos = [];
    $total_items = 0;
    
    if (is_array($items_data) && count($items_data) > 0) {
        foreach ($items_data as $item) {
            if (isset($item['Description'])) {
                $conceptos[] = $item['Description'];
            }
            if (isset($item['Units']) && isset($item['Price'])) {
                $total_items += floatval($item['Units']) * floatval($item['Price']);
            }
        }
    }
    
    $factura['conceptos'] = $conceptos;
    $factura['total_items'] = $total_items;
}

$conn_lycaios->close();

// Si no se encuentra la factura, redirigir
if (!$factura) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Pago</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .comprobante {
                box-shadow: none;
                border: none;
            }
        }
        .comprobante {
            width: 80mm;
            margin: 0 auto;
            padding: 10px;
            font-family: 'Arial', sans-serif;
            font-size: 12px;
        }
        .logo {
            text-align: center;
            margin-bottom: 10px;
        }
        .qr-code {
            text-align: center;
            margin: 10px 0;
        }
        .leyenda {
            font-size: 10px;
            text-align: center;
            margin-top: 15px;
            color: #666;
        }
        .concepto-item {
            margin: 3px 0;
            padding: 2px;
            border-bottom: 1px dotted #eee;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-4">
        <!-- Botones de acción -->
        <div class="text-center mb-4 no-print">
            <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg mr-2">
                📄 Imprimir Comprobante
            </button>
            <button onclick="window.history.back()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                ← Volver
            </button>
        </div>

        <!-- Comprobante -->
        <div class="comprobante bg-white shadow-lg mx-auto">
            <!-- Encabezado con logo y datos del ayuntamiento -->
            <div class="logo">
                <h1 class="text-xl font-bold">AYUNTAMIENTO DE ZARAGOZA</h1>
                <h2 class="text-lg">Sistema de Cobranza</h2>
                <p class="text-sm">Fecha: <?php echo date('d/m/Y H:i:s'); ?></p>
            </div>

            <hr class="my-2">

            <!-- Datos del comprobante -->
            <div class="datos">
                <p><strong>Folio:</strong> <?php echo htmlspecialchars($factura['invoicecode']); ?></p>
                <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($factura['date'])); ?></p>
                
                <?php if (!empty($factura['categoria'])): ?>
                <p><strong>Categoría:</strong> <?php echo htmlspecialchars($factura['categoria']); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($factura['conceptos'])): ?>
                <hr class="my-2">
                <p><strong>Conceptos:</strong></p>
                <div class="conceptos">
                    <?php foreach ($factura['conceptos'] as $concepto): ?>
                        <div class="concepto-item">• <?php echo htmlspecialchars($concepto); ?></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <hr class="my-2">
                
                <p class="text-center"><strong>MONTO PAGADO</strong></p>
                <p class="text-center text-2xl font-bold">$<?php echo number_format($factura['total'], 2); ?></p>
                
                <hr class="my-2">
                
                <p><strong>Descuento:</strong> $<?php echo number_format($factura['descuento'] ?? 0, 2); ?></p>
                <p><strong>Subtotal:</strong> $<?php echo number_format(($factura['total'] + ($factura['descuento'] ?? 0)), 2); ?></p>
                
                <?php if (isset($factura['total_items']) && $factura['total_items'] > 0): ?>
                <p><strong>Total Items:</strong> $<?php echo number_format($factura['total_items'], 2); ?></p>
                <?php endif; ?>
            </div>

            <!-- Código QR -->
            <div class="qr-code">
                <!-- Aquí irá el código QR generado -->
                <div style="width: 100px; height: 100px; background-color: #f0f0f0; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                    QR Code<br>(<?php echo substr($factura['invoicecode'], -6); ?>)
                </div>
                <p class="text-xs">Escanee para verificar autenticidad</p>
            </div>

            <!-- Leyenda y información legal -->
            <div class="leyenda">
                <p>** COMPROBANTE OFICIAL **</p>
                <p>Este documento es válido como comprobante de pago</p>
                <p>Conserve este comprobante para cualquier aclaración</p>
                <p>Tel: (XXX) XXX-XXXX | www.zaragoza.gob.mx</p>
            </div>
        </div>
    </div>
</body>
</html>