<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../includes/functions.php';

// Obtener el código de factura de la URL
$invoice_code = $_GET['code'] ?? '';

$error_message = '';
$factura = null;

if (empty($invoice_code)) {
    $error_message = 'No se proporcionó un código de comprobante válido';
} else {
    try {
        $conn = conectarLycaidosPOS();
        
        // Consultar la factura
        $sql = "SELECT i.invoicecode, i.date, i.total, i.employee, i.description, i.items, 
                       i.paymoney, i.`change`, i.paid
                FROM invoice i
                WHERE i.invoicecode = ?
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Error preparando consulta: ' . $conn->error);
        }
        
        $stmt->bind_param("s", $invoice_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error_message = 'Comprobante no encontrado';
        } else {
            $factura_data = $result->fetch_assoc();
            
            // Procesar datos del contribuyente desde el campo description
            $contribuyente = ['nombre' => 'No especificado', 'direccion' => 'No especificada'];
            if (!empty($factura_data['description'])) {
                $desc_decoded = json_decode($factura_data['description'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($desc_decoded)) {
                    $contribuyente['nombre'] = $desc_decoded['nombre'] ?? 'No especificado';
                    $contribuyente['direccion'] = $desc_decoded['direccion'] ?? 'No especificada';
                }
            }
            
            // Procesar items para obtener conceptos
            $conceptos = [];
            if (!empty($factura_data['items'])) {
                $items = json_decode($factura_data['items'], true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($items)) {
                    foreach ($items as $item) {
                        $nombre = $item['name'] ?? $item['nombre'] ?? $item['description'] ?? $item['descripcion'] ?? null;
                        $cantidad = $item['quantity'] ?? $item['cantidad'] ?? 1;
                        $precio = $item['price'] ?? $item['precio'] ?? 0;
                        
                        if ($nombre) {
                            $conceptos[] = [
                                'nombre' => $nombre,
                                'cantidad' => $cantidad,
                                'subtotal' => $precio * $cantidad
                            ];
                        }
                    }
                }
            }
            
            // Si no hay conceptos, agregar uno genérico
            if (empty($conceptos)) {
                $conceptos[] = [
                    'nombre' => 'Pago de servicios municipales',
                    'cantidad' => 1,
                    'subtotal' => floatval($factura_data['total'])
                ];
            }
            
            // Preparar datos para mostrar
            $factura = [
                'invoicecode' => $factura_data['invoicecode'],
                'date' => $factura_data['date'],
                'nombre' => $contribuyente['nombre'],
                'direccion' => $contribuyente['direccion'],
                'conceptos' => $conceptos,
                'total' => floatval($factura_data['total']),
                'employee' => $factura_data['employee'] ?? 'Sistema',
                'estatus' => ($factura_data['paid'] == 1) ? 'PAGADO' : 'PENDIENTE'
            ];
        }
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        $error_message = 'Error al verificar comprobante: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Comprobante - Ayuntamiento de Zaragoza</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Arial', sans-serif;
        }
        .verification-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        .status-pagado {
            background: #10b981;
            color: white;
        }
        .status-pendiente {
            background: #f59e0b;
            color: white;
        }
        .status-invalido {
            background: #ef4444;
            color: white;
        }
        .concept-item {
            border-left: 3px solid #667eea;
            padding-left: 12px;
            margin: 8px 0;
        }
    </style>
</head>
<body class="p-4">
    <div class="max-w-2xl mx-auto py-8">
        <!-- Header -->
        <div class="text-center mb-6">
            <div class="bg-white rounded-full w-24 h-24 mx-auto mb-4 flex items-center justify-center shadow-lg">
                <svg class="w-16 h-16 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Verificación de Comprobante</h1>
            <p class="text-purple-200">Ayuntamiento de Zaragoza, Puebla</p>
        </div>

        <!-- Verification Card -->
        <div class="verification-card p-6 md:p-8">
            <?php if ($error_message): ?>
                <!-- Error State -->
                <div class="text-center py-8">
                    <svg class="w-20 h-20 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Comprobante No Válido</h2>
                    <p class="text-gray-600"><?php echo htmlspecialchars($error_message); ?></p>
                    <div class="mt-6">
                        <a href="../index.php" class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg transition duration-200">
                            Volver al Inicio
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Success State -->
                <div class="text-center mb-6">
                    <span class="status-badge status-<?php echo strtolower($factura['estatus']); ?>">
                        <?php if ($factura['estatus'] === 'PAGADO'): ?>
                            ✓ <?php echo $factura['estatus']; ?>
                        <?php else: ?>
                            ⏳ <?php echo $factura['estatus']; ?>
                        <?php endif; ?>
                    </span>
                </div>

                <!-- Transaction Details -->
                <div class="space-y-4">
                    <!-- Folio -->
                    <div class="border-b pb-3">
                        <p class="text-sm text-gray-500 mb-1">Folio del Comprobante</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($factura['invoicecode']); ?></p>
                    </div>

                    <!-- Fecha y Hora -->
                    <div class="border-b pb-3">
                        <p class="text-sm text-gray-500 mb-1">Fecha y Hora</p>
                        <p class="text-lg font-semibold text-gray-800">
                            <?php echo date('d/m/Y', strtotime($factura['date'])); ?> 
                            <span class="text-gray-500">a las</span> 
                            <?php echo date('H:i', strtotime($factura['date'])); ?>
                        </p>
                    </div>

                    <!-- Contribuyente -->
                    <div class="border-b pb-3">
                        <p class="text-sm text-gray-500 mb-1">Contribuyente</p>
                        <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($factura['nombre']); ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($factura['direccion']); ?></p>
                    </div>

                    <!-- Conceptos -->
                    <div class="border-b pb-3">
                        <p class="text-sm text-gray-500 mb-2">Conceptos de Pago</p>
                        <div class="space-y-2">
                            <?php foreach ($factura['conceptos'] as $concepto): ?>
                                <div class="concept-item">
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($concepto['nombre']); ?></p>
                                    <p class="text-sm text-gray-600">
                                        Cantidad: <?php echo $concepto['cantidad']; ?> 
                                        <span class="ml-3">Subtotal: $<?php echo number_format($concepto['subtotal'], 2); ?></span>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Monto Total -->
                    <div class="bg-purple-50 rounded-lg p-4 border-b pb-3">
                        <p class="text-sm text-gray-500 mb-1">Monto Total Pagado</p>
                        <p class="text-3xl font-bold text-purple-600">$<?php echo number_format($factura['total'], 2); ?></p>
                    </div>

                    <!-- Empleado -->
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Procesado por</p>
                        <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($factura['employee']); ?></p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-8 pt-6 border-t text-center">
                    <p class="text-sm text-gray-500 mb-4">
                        Este comprobante ha sido verificado exitosamente en el sistema del Ayuntamiento de Zaragoza.
                    </p>
                    <div class="flex justify-center space-x-3">
                        <button onclick="window.print()" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition duration-200">
                            📄 Imprimir
                        </button>
                        <button onclick="window.close()" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition duration-200">
                            Cerrar
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info Footer -->
        <div class="text-center mt-6 text-white text-sm">
            <p>Ayuntamiento de Zaragoza, Puebla</p>
            <p>Calle 12 de Octubre S/N, Col. Centro</p>
            <p>Tel: (233) 115-50-39 | www.zaragoza.gob.mx</p>
        </div>
    </div>
</body>
</html>
