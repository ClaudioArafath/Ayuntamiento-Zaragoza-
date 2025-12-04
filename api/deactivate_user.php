<?php
session_start();
header('Content-Type: application/json');

// Verificar que el usuario esté autenticado y sea administrador
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'Administrador') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

include '../config/database.php';

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de usuario inválido']);
    exit();
}

try {
    $conn = conectarAyuntamiento();
    
    // Verificar que el usuario no se esté dando de baja a sí mismo
    $check_sql = "SELECT username FROM usuarios WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        exit();
    }
    
    $user_data = $check_result->fetch_assoc();
    
    if ($user_data['username'] === $_SESSION['username']) {
        echo json_encode(['success' => false, 'error' => 'No puede darse de baja a sí mismo']);
        exit();
    }
    
    // Eliminar el usuario de la base de datos
    $delete_sql = "DELETE FROM usuarios WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $user_id);
    
    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Usuario dado de baja exitosamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al dar de baja el usuario']);
    }
    
    $delete_stmt->close();
    $check_stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
