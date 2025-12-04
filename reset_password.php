<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/config/database.php';
$conn = conectarAyuntamiento();
include 'includes/password_recovery.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = trim($_POST['token']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);
    
    // Validaciones básicas
    if (empty($token) || empty($password) || empty($confirmPassword)) {
        echo "<script>alert('❌ Todos los campos son obligatorios'); window.history.back();</script>";
        exit;
    }
    
    if ($password !== $confirmPassword) {
        echo "<script>alert('❌ Las contraseñas no coinciden'); window.history.back();</script>";
        exit;
    }
    
    if (strlen($password) < 6) {
        echo "<script>alert('❌ La contraseña debe tener al menos 6 caracteres'); window.history.back();</script>";
        exit;
    }
    
    // Validar token
    $userId = validateResetToken($token, $conn);
    
    if (!$userId) {
        echo "<script>
            alert('❌ El enlace de recuperación es inválido o ha expirado. Por favor solicita uno nuevo.');
            window.location.href = 'forgot_password.html';
        </script>";
        exit;
    }
    
    // Encriptar nueva contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Actualizar contraseña del usuario
    $updateSql = "UPDATE usuarios SET password = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $hashedPassword, $userId);
    
    if ($updateStmt->execute()) {
        // Marcar token como usado
        markTokenAsUsed($token, $conn);
        
        // Obtener información del usuario para mostrar mensaje personalizado
        $userSql = "SELECT nombre_completo FROM usuarios WHERE id = ?";
        $userStmt = $conn->prepare($userSql);
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $user = $userResult->fetch_assoc();
        
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Contraseña Restablecida</title>
            <script src='https://cdn.tailwindcss.com'></script>
            <link rel='stylesheet' href='../DB_lycaios/assets/css/styles.css'>
        </head>
        <body class='bg-gray-100 flex items-center justify-center min-h-screen font-sans p-4'>
            <div class='w-full max-w-md bg-white p-8 rounded-3xl shadow-2xl text-center'>
                <div class='mb-6'>
                    <img src='media/Ayuntamiento.png' alt='Logo' class='h-24 w-auto mx-auto mb-4'>
                    <div class='text-6xl mb-4'>✅</div>
                    <h1 class='text-2xl font-bold text-green-600 mb-2'>¡Contraseña Restablecida!</h1>
                    <p class='text-gray-600'>
                        Hola <strong>" . htmlspecialchars($user['nombre_completo']) . "</strong>,<br>
                        tu contraseña ha sido actualizada correctamente.
                    </p>
                </div>
                
                <div class='bg-green-50 border-l-4 border-green-500 p-4 mb-6 text-left'>
                    <p class='text-sm text-gray-700'>
                        <strong>🔐 Seguridad:</strong> Tu contraseña ha sido encriptada de forma segura en nuestra base de datos.
                    </p>
                </div>
                
                <a href='views/login.html' 
                    class='block w-full py-3 bg-blue-600 text-white font-semibold rounded-lg shadow hover:bg-blue-700 transition'>
                    Iniciar Sesión
                </a>
                
                <p class='text-xs text-gray-500 mt-4'>
                    Serás redirigido automáticamente en <span id='countdown'>5</span> segundos...
                </p>
            </div>
            
            <script>
                let seconds = 5;
                const countdownEl = document.getElementById('countdown');
                
                const interval = setInterval(() => {
                    seconds--;
                    countdownEl.textContent = seconds;
                    
                    if (seconds <= 0) {
                        clearInterval(interval);
                        window.location.href = 'views/login.html';
                    }
                }, 1000);
            </script>
        </body>
        </html>";
        
    } else {
        echo "<script>
            alert('❌ Error al actualizar la contraseña: " . $conn->error . "');
            window.history.back();
        </script>";
    }
    
    $conn->close();
}
?>
