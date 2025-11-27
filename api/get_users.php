<?php
session_start();
header('Content-Type: application/json');

// Verificar que el usuario esté autenticado y sea administrador
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'Administrador') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

include '../config/database.php';

try {
    $conn = conectarAyuntamiento();
    
    // Obtener todos los usuarios activos excepto el usuario actual
    $current_user = $_SESSION['username'];
    $sql = "SELECT id, username, nombre_completo, rol FROM usuarios WHERE username != ? ORDER BY nombre_completo ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_user);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => (int)$row['id'],
            'username' => $row['username'],
            'nombre_completo' => $row['nombre_completo'],
            'rol' => $row['rol']
        ];
    }
    
    echo json_encode(['success' => true, 'users' => $users]);
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
