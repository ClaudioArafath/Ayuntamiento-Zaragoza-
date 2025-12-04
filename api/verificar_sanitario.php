<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

// Obtener el código de folio de la URL
$folio_code = $_GET['code'] ?? '';

$error_message = '';
$comprobante = null;

if (empty($folio_code)) {
    $error_message = 'No se proporcionó un código de comprobante válido';
} else {
    try {
        $conn = conectarLycaidosPOS();
        
        // Consultar el comprobante de sanitarios
        $sql = "SELECT id, folio, nombre_cliente, cantidad_total, descripcion, fecha_creacion
                FROM sanitarios
                WHERE folio = ?
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Error preparando consulta: ' . $conn->error);
        }
        
        $stmt->bind_param("s", $folio_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error_message = 'Comprobante no encontrado';
        } else {
            $comprobante_data = $result->fetch_assoc();
            
            // Preparar datos para mostrar
            $comprobante = [
                'folio' => $comprobante_data['folio'],
                'fecha' => $comprobante_data['fecha_creacion'],
                'nombre' => $comprobante_data['nombre_cliente'],
                'descripcion' => $comprobante_data['descripcion'],
                'total' => floatval($comprobante_data['cantidad_total']),
                'estatus' => 'PAGADO' // Los comprobantes de sanitarios siempre están pagados
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
    <title>Verificación de Comprobante - Sanitarios</title>
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
        .status-invalido {
            background: #ef4444;
            color: white;
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
            <p class="text-purple-200">Cobro de Sanitarios - Ayuntamiento de Zaragoza</p>
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
                    <span class="status-badge status-pagado">
                        ✓ <?php echo $comprobante['estatus']; ?>
                    </span>
                </div>

                <!-- Transaction Details -->
                <div class="space-y-4">
                    <!-- Folio -->
                    <div class="border-b pb-3">
                        <p class="text-sm text-gray-500 mb-1">Folio del Comprobante</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($comprobante['folio']); ?></p>
                    </div>

                    <!-- Fecha y Hora -->
                    <div class="border-b pb-3">
                        <p class="text-sm text-gray-500 mb-1">Fecha y Hora</p>
                        <p class="text-lg font-semibold text-gray-800">
                            <?php echo date('d/m/Y', strtotime($comprobante['fecha'])); ?> 
                            <span class="text-gray-500">a las</span> 
                            <?php echo date('H:i', strtotime($comprobante['fecha'])); ?>
                        </p>
                    </div>

                    <!-- Cliente -->
                    <div class="border-b pb-3">
                        <p class="text-sm text-gray-500 mb-1">Cliente</p>
                        <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($comprobante['nombre']); ?></p>
                    </div>

                    <!-- Descripción -->
                    <div class="border-b pb-3">
                        <p class="text-sm text-gray-500 mb-1">Descripción del Servicio</p>
                        <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($comprobante['descripcion']); ?></p>
                    </div>

                    <!-- Monto Total -->
                    <div class="bg-purple-50 rounded-lg p-4 border-b pb-3">
                        <p class="text-sm text-gray-500 mb-1">Monto Total Pagado</p>
                        <p class="text-3xl font-bold text-purple-600">$<?php echo number_format($comprobante['total'], 2); ?></p>
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
                        <a href="../index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition duration-200 inline-block">
                            ← Volver
                        </a>
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
