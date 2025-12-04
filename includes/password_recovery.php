<?php
/**
 * Funciones auxiliares para recuperación de contraseña
 */

/**
 * Genera un token seguro aleatorio de 64 caracteres
 * @return string Token hexadecimal de 64 caracteres
 */
function generateSecureToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Crea un token de recuperación de contraseña para un usuario
 * @param int $userId ID del usuario
 * @param mysqli $conn Conexión a la base de datos
 * @return string|false Token generado o false en caso de error
 */
function createPasswordResetToken($userId, $conn) {
    // Invalidar tokens anteriores del mismo usuario
    $invalidateSql = "UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0";
    $invalidateStmt = $conn->prepare($invalidateSql);
    $invalidateStmt->bind_param("i", $userId);
    $invalidateStmt->execute();
    
    // Generar nuevo token
    $token = generateSecureToken();
    
    // Calcular fecha de expiración (1 hora desde ahora)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Insertar token en la base de datos
    $insertSql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param("iss", $userId, $token, $expiresAt);
    
    if ($insertStmt->execute()) {
        return $token;
    }
    
    return false;
}

/**
 * Valida un token de recuperación y retorna el ID del usuario
 * @param string $token Token a validar
 * @param mysqli $conn Conexión a la base de datos
 * @return int|false ID del usuario si el token es válido, false en caso contrario
 */
function validateResetToken($token, $conn) {
    $sql = "SELECT user_id FROM password_resets 
            WHERE token = ? 
            AND used = 0 
            AND expires_at > NOW()";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['user_id'];
    }
    
    return false;
}

/**
 * Marca un token como usado
 * @param string $token Token a marcar
 * @param mysqli $conn Conexión a la base de datos
 * @return bool True si se marcó correctamente, false en caso contrario
 */
function markTokenAsUsed($token, $conn) {
    $sql = "UPDATE password_resets SET used = 1 WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    return $stmt->execute();
}

/**
 * Busca un usuario por username o email
 * @param string $identifier Username o email del usuario
 * @param mysqli $conn Conexión a la base de datos
 * @return array|false Datos del usuario si se encuentra, false en caso contrario
 */
function findUserByIdentifier($identifier, $conn) {
    $sql = "SELECT id, username, email, nombre_completo 
            FROM usuarios 
            WHERE username = ? OR email = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}

/**
 * Limpia tokens expirados de la base de datos (mantenimiento)
 * @param mysqli $conn Conexión a la base de datos
 * @return int Número de tokens eliminados
 */
function cleanExpiredTokens($conn) {
    $sql = "DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1";
    $conn->query($sql);
    return $conn->affected_rows;
}
?>
