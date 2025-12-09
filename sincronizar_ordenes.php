<?php
/**
 * Script de Sincronización: Lycaios POS → Ayuntamiento
 * 
 * Este script copia las órdenes nuevas de lycaios_pos.ordenes 
 * a ayuntamiento.ordenes_backup automáticamente.
 * 
 * Reemplaza la funcionalidad del trigger que fue eliminado.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

$log_file = __DIR__ . '/logs/sincronizacion.log';
$timestamp = date('Y-m-d H:i:s');

function log_message($message) {
    global $log_file, $timestamp;
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    log_message("=== INICIANDO SINCRONIZACIÓN ===");
    
    // Conectar a ambas bases de datos
    $connLycaios = conectarLycaidosPOS();
    $connAyuntamiento = conectarAyuntamiento();
    
    log_message("Conexiones establecidas");
    
    // PASO 1: Obtener todas las órdenes de lycaios_pos.ordenes
    $sql_lycaios = "SELECT id, code, userid, clientid, date, items, piezas, employee, description, total 
                    FROM ordenes 
                    ORDER BY id ASC";
    
    $result_lycaios = $connLycaios->query($sql_lycaios);
    
    if (!$result_lycaios) {
        throw new Exception("Error consultando ordenes: " . $connLycaios->error);
    }
    
    $total_lycaios = $result_lycaios->num_rows;
    log_message("Total órdenes en lycaios_pos.ordenes: $total_lycaios");
    
    // PASO 2: Obtener códigos existentes en ayuntamiento.ordenes_backup
    $sql_existentes = "SELECT code FROM ordenes_backup";
    $result_existentes = $connAyuntamiento->query($sql_existentes);
    
    $codigos_existentes = [];
    while ($row = $result_existentes->fetch_assoc()) {
        $codigos_existentes[$row['code']] = true;
    }
    
    log_message("Total órdenes en ayuntamiento.ordenes_backup: " . count($codigos_existentes));
    
    // PASO 3: Insertar órdenes nuevas
    $insertadas = 0;
    $omitidas = 0;
    
    $stmt_insert = $connAyuntamiento->prepare(
        "INSERT INTO ordenes_backup (code, clientid, date, items, employee, total, estatus) 
         VALUES (?, ?, ?, ?, ?, ?, 0)"
    );
    
    if (!$stmt_insert) {
        throw new Exception("Error preparando INSERT: " . $connAyuntamiento->error);
    }
    
    while ($orden = $result_lycaios->fetch_assoc()) {
        // Verificar si ya existe
        if (isset($codigos_existentes[$orden['code']])) {
            $omitidas++;
            continue;
        }
        
        // Insertar nueva orden
        $stmt_insert->bind_param(
            "sisssd",
            $orden['code'],
            $orden['clientid'],
            $orden['date'],
            $orden['items'],
            $orden['employee'],
            $orden['total']
        );
        
        if ($stmt_insert->execute()) {
            $insertadas++;
            log_message("✓ Insertada orden: {$orden['code']}");
        } else {
            log_message("✗ Error insertando orden {$orden['code']}: " . $stmt_insert->error);
        }
    }
    
    $stmt_insert->close();
    
    // PASO 4: Resumen
    log_message("=== SINCRONIZACIÓN COMPLETADA ===");
    log_message("Órdenes insertadas: $insertadas");
    log_message("Órdenes omitidas (ya existían): $omitidas");
    log_message("Total procesadas: " . ($insertadas + $omitidas));
    
    // Cerrar conexiones
    $connLycaios->close();
    $connAyuntamiento->close();
    
    // Respuesta para ejecución manual o AJAX
    $response = [
        'success' => true,
        'insertadas' => $insertadas,
        'omitidas' => $omitidas,
        'total' => $insertadas + $omitidas
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    $error = $e->getMessage();
    log_message("❌ ERROR: $error");
    
    echo json_encode([
        'success' => false,
        'error' => $error
    ], JSON_PRETTY_PRINT);
    
    http_response_code(500);
}
?>
