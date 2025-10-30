<?php
// comprobante_sanitarios.php
require_once 'config/database.php';
require_once 'config/session.php'; // Agregar esta línea para tener acceso a la sesión

$id = $_GET['id'] ?? 0;

if (!$id) {
    die('ID de orden no proporcionado');
}

// Obtener datos de la orden
$conn = conectarLycaidosPOS();
$stmt = $conn->prepare("SELECT * FROM sanitarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$orden = $result->fetch_assoc();
$stmt->close();

// Obtener el nombre completo del usuario en sesión
$nombre_completo_elaboro = "No disponible";
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    
    // Conectar a la base de datos de usuarios (ayuntamiento)
    $conn_ayuntamiento = conectarAyuntamiento();
    
    // Consultar la base de datos de usuarios para obtener el nombre completo
    $stmt_user = $conn_ayuntamiento->prepare("SELECT nombre_completo FROM usuarios WHERE username = ?");
    $stmt_user->bind_param("s", $username);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    
    if ($user_data = $result_user->fetch_assoc()) {
        $nombre_completo_elaboro = htmlspecialchars($user_data['nombre_completo']);
    }
    $stmt_user->close();
    $conn_ayuntamiento->close();
}

if (!$orden) {
    die('Orden no encontrada');
}

// Formatear fecha desde fecha_creacion
$fecha_formateada = date('d/m/Y H:i:s', strtotime($orden['fecha_creacion']));
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
            .comprobante { box-shadow: none !important; border: 1px solid #000 !important; margin: 0 !important; }
            @page { margin: 0.5in; }
        }
        
        body { 
            font-family: 'Arial', sans-serif; 
            margin: 0;
            padding: 20px;
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
            border: 1px solid #ddd;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .logo-placeholder {
            height: 80px;
            border: 1px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }
        
        .titulo {
            font-size: 20px;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
        }
        
        .info {
            margin: 15px 0;
            line-height: 1.4;
        }
        
        .info-line {
            margin: 3px 0;
        }
        
        .detalles {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            border: 1px solid #000;
        }
        
        .detalles th, .detalles td {
            border: 1px solid #000;
            padding: 10px;
            text-align: left;
        }
        
        .detalles th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .total {
            text-align: right;
            font-weight: bold;
            font-size: 16px;
            margin: 15px 0;
        }
        
        .firmas {
            margin-top: 50px;
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
            padding-top: 20px;
            border-top: 1px dashed #ccc;
        }
        
        .qr-placeholder {
            display: inline-block;
            width: 120px;
            height: 120px;
            border: 1px dashed #000;
            line-height: 120px;
            text-align: center;
            background-color: #f9f9f9;
            margin-bottom: 10px;
        }
        
        .btn-print {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            margin: 20px auto;
            display: block;
            font-size: 16px;
        }
        
        .btn-print:hover {
            background: #0056b3;
        }
        
        .no-print {
            text-align: center;
        }
        
        .nombre-elaboro {
            font-weight: bold;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Imprimir Comprobante</button>
        <p>Si la impresión no inicia automáticamente, use el botón arriba o Ctrl+P</p>
    </div>
    
    <div class="comprobante">
        <div class="header">
            <img src="media/Ayuntamiento.png" alt="Fondo" class="fondo" width="150px" height="100px">
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
                    <th style="width: 50%;">Descripción</th>
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
        
        <div class="total">
            Total: $<?php echo number_format($orden['cantidad_total'], 2); ?>
        </div>
        
        <div class="firmas">
            <div class="firma">
                <div>Elaboró</div>
                <div class="linea-firma"></div>
                <div class="nombre-elaboro">C. <?php echo $nombre_completo_elaboro; ?></div> // Nombre desde sesión activa
            </div>
            
            <div class="firma">
                <div>Autorizó</div>
                <div class="linea-firma"></div>
                <div class="nombre-elaboro">C. Esteban Montiel Gonzalez</div>
            </div>
        </div>
        
        <div class="qr-code">
            <div class="qr-placeholder">
                Código QR<br>
                <?php echo substr($orden['qr_code'] ?? 'QR_NOT_GENERATED', 0, 20); ?>...
            </div>
        </div>
    </div>

    <script>
            // Imprimir automáticamente al cargar la página
        window.onload = function() {
            // Pequeño delay para asegurar que todo esté cargado
        setTimeout(function() {
            window.print();
            }, 500);
        };
    </script>
</body>
</html>