<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../includes/functions.php';

// Obtener el código de factura de la URL
$invoice_code = $_GET['invoice_code'] ?? '';

if (empty($invoice_code)) {
    die('Error: No se proporcionó un código de factura válido');
}

try {
    $conn = conectarLycaidosPOS();
    
    // Consultar la factura
    $sql = "SELECT i.invoicecode, i.date, i.total, i.employee, i.description, i.items, 
                   i.paymoney, i.`change`
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
        throw new Exception('Factura no encontrada');
    }
    
    $factura_data = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
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
                    $conceptos[] = sprintf("%s (x%d) - $%.2f", $nombre, $cantidad, $precio * $cantidad);
                }
            }
        }
    }
    
    // Si no hay conceptos, agregar uno genérico
    if (empty($conceptos)) {
        $conceptos[] = 'Pago de servicios municipales';
    }
    
    // Preparar datos para el comprobante
    $factura = [
        'invoicecode' => $factura_data['invoicecode'],
        'date' => $factura_data['date'],
        'nombre' => $contribuyente['nombre'],
        'direccion' => $contribuyente['direccion'],
        'categoria' => '', // No tenemos categoría en este sistema
        'conceptos' => $conceptos,
        'total' => floatval($factura_data['total']),
        'atendio' => $factura_data['employee'] ?? 'Sistema',
        'autorizo' => 'Tesorería Municipal' // Valor fijo
    ];
    
} catch (Exception $e) {
    die('Error al generar comprobante: ' . $e->getMessage());
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
            @page {
                size: letter portrait;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
            body {
                margin: 0;
                padding: 0;
                background: #fff !important;
            }
            .container {
                margin: 0;
                padding: 0;
                width: 100%;
                max-width: 100%;
            }
            .comprobante {
                width: 100%;
                max-width: 100%;
                margin: 0;
                padding: 20mm; /* margen interno solo del contenido */
                box-shadow: none;
                border: none;
            }
        }

        .comprobante {
            position: relative;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 24px;
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            background: #fff;
            overflow: hidden;
        }

        /* Fondo ocupa todo el espacio de la hoja */
        .comprobante .fondo {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.05; /* 5% de opacidad */
            z-index: 0;
        }

        /* Contenido encima del fondo */
        .comprobante .contenido {
            position: relative;
            z-index: 1;
        }

        .logo {
            text-align: center;
            margin-bottom: 10px;
        }
        .qr-code {
            text-align: center;
            margin: 20px 0;
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
    <script>
        // Auto-abrir diálogo de impresión después de cargar
        window.addEventListener('load', function() {
            setTimeout(function() {
                // window.print(); // Descomentado si quieres que se abra automáticamente
            }, 500);
        });
    </script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-4">
        <!-- Botones de acción -->
        <div class="text-center mb-4 no-print">
            <button onclick="window.print()" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg mr-2">
                📄 Imprimir Comprobante
            </button>
            <button onclick="window.close()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                ← Cerrar
            </button>
        </div>

        <!-- Comprobante -->
        <div class="comprobante bg-white shadow-lg mx-auto">
            <!-- Imagen de fondo -->
            <img src="../media/Background.png" alt="Fondo" class="fondo">

            <!-- Contenido del comprobante -->
            <div class="contenido">
                <!-- Encabezado con logo y datos del ayuntamiento -->
                <div class="logo">
                    <img src="../media/Ayuntamiento.png" alt="Logo Ayuntamiento" class="mx-auto mb-2" style="height: 150px;">
                    <h1 class="text-xl font-bold">AYUNTAMIENTO DE ZARAGOZA</h1>
                    <h2 class="text-md font-bold">RFC: MZP850101340</h2>
                    <h3 class="text-md font-bold">Calle 12 de Octubre S/N, Col. Centro, Zaragoza, Puebla</h3>
                </div>

                <hr class="my-2">

                <!-- Datos del comprobante -->
                <div class="datos">
                    <p><strong>Folio:</strong> <?php echo htmlspecialchars($factura['invoicecode']); ?></p>
                    <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($factura['date'])); ?></p>
                    <p><strong>Contribuyente:</strong> <?php echo htmlspecialchars($factura['nombre']); ?></p>
                    <p><strong>Dirección:</strong> <?php echo htmlspecialchars($factura['direccion']); ?></p>
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
                    
                    <div class="responsables" style="display: flex; justify-content: space-between; margin-top: 20px;">
                        <div style="text-align: left; width: 48%;">
                            <p class="text-center"><strong>Atendió</strong></p>
                            <div style="height: 30px; border-bottom: 1px solid #888; margin-bottom: 2px;"></div>
                            <p class="text-xs"><?php echo htmlspecialchars($factura['atendio']); ?></p>
                        </div>
                        <div style="text-align: right; width: 48%;">
                            <p class="text-center"><strong>Autorizó</strong></p>
                            <div style="height: 30px; border-bottom: 1px solid #888; margin-bottom: 2px;"></div>
                            <p class="text-xs"><?php echo htmlspecialchars($factura['autorizo']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Código QR -->
                <div class="qr-code">
                    <div style="width: 100px; height: 100px; background-color: #f0f0f0; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                        QR Code<br>(<?php echo substr($factura['invoicecode'], -6); ?>)
                    </div>
                    <p class="text-xs">Escanee para verificar autenticidad</p>
                </div>

                <!-- Leyenda y información legal -->
                <div class="leyenda">
                    <p>COMPROBANTE OFICIAL</p>
                    <p>Este documento es válido como comprobante de pago</p>
                    <p>Conserve este comprobante para cualquier aclaración</p>
                    <p>Tel: (233) 115-50-39 | www.zaragoza.gob.mx</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
