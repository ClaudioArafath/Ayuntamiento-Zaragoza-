-- ============================================
-- Script de Migración: Módulo de Recuperación de Contraseña
-- Base de datos: ayuntamiento
-- ============================================

USE ayuntamiento;

-- 1. Agregar columna email a la tabla usuarios (si no existe)
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS email VARCHAR(255) UNIQUE DEFAULT NULL 
COMMENT 'Email del usuario para recuperación de contraseña';

-- 2. Crear índice en email para búsquedas rápidas
CREATE INDEX IF NOT EXISTS idx_usuarios_email ON usuarios(email);

-- 3. Crear tabla para tokens de recuperación de contraseña
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tokens de recuperación de contraseña';

-- 4. Limpiar tokens expirados (opcional, para mantenimiento)
-- DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1;

SELECT 'Migración completada exitosamente' AS status;
