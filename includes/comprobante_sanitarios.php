<?php
// comprobante_sanitarios.php
require_once 'config/database.php';

$id = $_GET['id'] ?? 0;

// Obtener datos de la orden
$conn = conectarLycaidosPOS();
$stmt = $conn->prepare("SELECT * FROM sanitarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$orden = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$orden) {
    die('Orden no encontrada');
}

// Formatear fecha
$fecha_formateada = date('d/m/Y H:i:s', strtotime($orden['fecha_hora']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante Oficial - Sanitarios</title>
    <style>
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .comprobante { box-shadow: none !important; border: 1px solid #000 !important; }
        }
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px;
            background-color: #f5f5f5;
        }
        .comprobante {
            width: 8.5in;
            min-height: 11in;
            background: white;
            margin: 0 auto;
            padding: 0.5in;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 10px;
        }
        .titulo {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
        }
        .info {
            margin: 15px 0;
        }
        .info-line {
            margin: 5px 0;
        }
        .detalles {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .detalles th, .detalles td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .detalles th {
            background-color: #f0f0f0;
        }
        .firmas {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        .firma {
            text-align: center;
            width: 45%;
        }
        .linea-firma {
            border-top: 1px solid #000;
            margin-top: 60px;
            padding-top: 5px;
        }
        .qr-code {
            text-align: center;
            margin-top: 20px;
        }
        .qr-placeholder {
            display: inline-block;
            width: 120px;
            height: 120px;
            border: 1px dashed #000;
            line-height: 120px;
            text-align: center;
            background-color: #f9f9f9;
        }
        .btn-print {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 20px auto;
            display: block;
        }
    </style>
</head>
<body>
    <button class="btn-print no-print" onclick="window.print()">🖨️ Imprimir Comprobante</button>
    
    <div class="comprobante">
        <div class="header">
            <!-- Logo del ayuntamiento (reemplazar con la ruta real del logo) -->
            <div class="logo-placeholder" style="text-align: center; margin-bottom: 10px;">
                [LOGO DEL AYUNTAMIENTO]
            </div>
            
            <div class="titulo">COMPROBANTE OFICIAL</div>
            
            <div class="info">
                <div class="info-line"><strong>RFC: MZP850101340</strong></div>
                <div class="info-line">MUNICIPIO DE ZARAGOZA, PUEBLA TESORERÍA MUNICIPAL</div>
                <div class="info-line">C.12 DE OCTUBRE S/N COL. CENTRO ZARAGOZA, PUEBLA C.P 73700</div>
                <div class="info-line">TEL. 2333115039</div>
            </div>
        </div>
        
        <div class="info">
            <div class="info-line"><strong>Folio:</strong> <?php echo htmlspecialchars($orden['folio']); ?></div>
            <div class="info-line"><strong>Nombre:</strong> <?php echo htmlspecialchars($orden['nombre_cliente']); ?></div>
            <div class="info-line"><strong>Fecha y Hora:</strong> <?php echo $fecha_formateada; ?></div>
        </div>
        
        <table class="detalles">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th style="width: 20%; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($orden['descripcion']); ?></td>
                    <td style="text-align: right;">$<?php echo number_format($orden['cantidad_total'], 2); ?></td>
                </tr>
            </tbody>
        </table>
        
        <div style="text-align: right; margin-top: 10px; font-weight: bold;">
            Total: $<?php echo number_format($orden['cantidad_total'], 2); ?>
        </div>
        
        <div class="firmas">
            <div class="firma">
                <div>Elaboró</div>
                <div class="linea-firma"></div>
                <div>Nombre y Firma</div>
            </div>
            
            <div class="firma">
                <div>Autorizó</div>
                <div class="linea-firma"></div>
                <div>Nombre y Firma</div>
            </div>
        </div>
        
        <div class="qr-code">
            <div>QR Único</div>
            <div class="qr-placeholder">
                Código QR<br><?php echo substr($orden['qr_code'], 0, 20); ?>...
            </div>
            <div style="font-size: 10px; margin-top: 5px;">
                Folio: <?php echo htmlspecialchars($orden['folio']); ?>
            </div>
        </div>
    </div>

    <script>
        // Imprimir automáticamente al cargar la página
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>