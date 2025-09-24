<?php
session_start();

// Validar sesión
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

// Obtener información del usuario en la base de datos Ayuntamiento
function obtenerUsuario($username) {
    $conn_ayuntamiento = conectarAyuntamiento();
    
    $sql = "SELECT rol FROM usuarios WHERE username = ?";
    $stmt = $conn_ayuntamiento->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        $stmt->close();
        $conn_ayuntamiento->close();
        return $usuario;
    }
    
    $stmt->close();
    $conn_ayuntamiento->close();
    return null;
}
?>