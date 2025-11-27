<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/config/database.php';
$conn = conectarAyuntamiento();
include 'includes/password_recovery.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifier = trim($_POST['identifier']);
    
    // Validación básica
    if (empty($identifier)) {
        echo "<script>alert('❌ Por favor ingresa tu usuario o email'); window.history.back();</script>";
        exit;
    }
    
    // Buscar usuario por username o email
    $user = findUserByIdentifier($identifier, $conn);
    
    if (!$user) {
        // Por seguridad, no revelar si el usuario existe o no
        echo "<script>
            alert('✅ Si el usuario existe, recibirás un enlace de recuperación.');
            window.location.href = 'login.html';
        </script>";
        exit;
    }
    
    // Verificar que el usuario tenga email registrado
    if (empty($user['email'])) {
        echo "<script>
            alert('❌ Este usuario no tiene un email registrado. Por favor contacta al administrador para actualizar tu perfil.');
            window.history.back();
        </script>";
        exit;
    }
    
    // Generar token de recuperación
    $token = createPasswordResetToken($user['id'], $conn);
    
    if ($token) {
        // Construir URL de recuperación
        $resetUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.html?token=" . $token;
        
        // OPCIÓN 1: Mostrar el enlace en pantalla (para desarrollo/sin servidor de email)
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Enlace de Recuperación</title>
            <script src='https://cdn.tailwindcss.com'></script>
            <link rel='stylesheet' href='../DB_lycaios/assets/css/styles.css'>
        </head>
        <body class='bg-gray-100 flex items-center justify-center min-h-screen font-sans p-4'>
            <div class='w-full max-w-2xl bg-white p-8 rounded-3xl shadow-2xl'>
                <div class='text-center mb-6'>
                    <img src='media/Ayuntamiento.png' alt='Logo' class='h-24 w-auto mx-auto mb-4'>
                    <h1 class='text-2xl font-bold text-green-600 mb-2'>✅ Enlace de Recuperación Generado</h1>
                    <p class='text-gray-600'>Hola <strong>" . htmlspecialchars($user['nombre_completo']) . "</strong></p>
                </div>
                
                <div class='bg-blue-50 border-l-4 border-blue-500 p-4 mb-6'>
                    <p class='text-sm text-gray-700 mb-3'>
                        <strong>📧 Nota:</strong> En un entorno de producción, este enlace se enviaría por email. 
                        Por ahora, copia el siguiente enlace para restablecer tu contraseña:
                    </p>
                    <div class='bg-white p-3 rounded border border-gray-300 break-all'>
                        <code class='text-sm text-blue-600'>" . $resetUrl . "</code>
                    </div>
                    <button onclick=\"copyToClipboard()\" 
                        class='mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition w-full'>
                        📋 Copiar Enlace
                    </button>
                </div>
                
                <div class='bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6'>
                    <p class='text-sm text-gray-700'>
                        <strong>⏰ Importante:</strong> Este enlace expirará en <strong>1 hora</strong>.
                    </p>
                </div>
                
                <div class='flex gap-3'>
                    <a href='" . $resetUrl . "' 
                        class='flex-1 py-3 bg-green-600 text-white font-semibold rounded-lg shadow hover:bg-green-700 transition text-center'>
                        🔑 Ir a Restablecer Contraseña
                    </a>
                    <a href='login.html' 
                        class='flex-1 py-3 bg-gray-600 text-white font-semibold rounded-lg shadow hover:bg-gray-700 transition text-center'>
                        ← Volver al Login
                    </a>
                </div>
            </div>
            
            <script>
                function copyToClipboard() {
                    const url = '" . $resetUrl . "';
                    navigator.clipboard.writeText(url).then(() => {
                        alert('✅ Enlace copiado al portapapeles');
                    }).catch(() => {
                        alert('❌ Error al copiar. Por favor copia manualmente.');
                    });
                }
            </script>
        </body>
        </html>";
        
        // OPCIÓN 2: Enviar por email (descomentar cuando se configure SMTP)
        /*
        $to = $user['email'];
        $subject = "Recuperación de Contraseña - Ayuntamiento de Zaragoza";
        $message = "Hola " . $user['nombre_completo'] . ",\n\n";
        $message .= "Has solicitado restablecer tu contraseña.\n\n";
        $message .= "Haz clic en el siguiente enlace para continuar:\n";
        $message .= $resetUrl . "\n\n";
        $message .= "Este enlace expirará en 1 hora.\n\n";
        $message .= "Si no solicitaste este cambio, ignora este mensaje.\n\n";
        $message .= "Saludos,\nAyuntamiento de Zaragoza";
        
        $headers = "From: noreply@ayuntamiento-zaragoza.es\r\n";
        $headers .= "Reply-To: soporte@ayuntamiento-zaragoza.es\r\n";
        
        if (mail($to, $subject, $message, $headers)) {
            echo "<script>
                alert('✅ Se ha enviado un enlace de recuperación a tu email.');
                window.location.href = 'login.html';
            </script>";
        } else {
            echo "<script>
                alert('❌ Error al enviar el email. Por favor intenta más tarde.');
                window.history.back();
            </script>";
        }
        */
        
    } else {
        echo "<script>
            alert('❌ Error al generar el enlace de recuperación. Por favor intenta más tarde.');
            window.history.back();
        </script>";
    }
    
    $conn->close();
}
?>
