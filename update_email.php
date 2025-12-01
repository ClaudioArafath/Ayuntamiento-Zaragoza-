<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/config/database.php';
$conn = conectarAyuntamiento();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    
    // Validaciones básicas
    if (empty($username) || empty($password) || empty($email)) {
        echo "<script>alert('❌ Todos los campos son obligatorios'); window.history.back();</script>";
        exit;
    }
    
    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('❌ El formato del email no es válido'); window.history.back();</script>";
        exit;
    }
    
    // Buscar usuario en la BD por username
    $sql = "SELECT * FROM usuarios WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $user = $resultado->fetch_assoc();
        $dbPassword = $user['password'];
        
        // Verificar contraseña (soporta texto plano y hash)
        $passwordValid = false;
        
        if ($password === $dbPassword) {
            $passwordValid = true;
        } elseif (password_verify($password, $dbPassword)) {
            $passwordValid = true;
        }
        
        if ($passwordValid) {
            // Verificar que el email no esté en uso por otro usuario
            $checkEmailSql = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
            $checkEmailStmt = $conn->prepare($checkEmailSql);
            $checkEmailStmt->bind_param("si", $email, $user['id']);
            $checkEmailStmt->execute();
            $checkEmailResult = $checkEmailStmt->get_result();
            
            if ($checkEmailResult->num_rows > 0) {
                echo "<script>alert('❌ Este email ya está en uso por otro usuario'); window.history.back();</script>";
                exit;
            }
            
            // Actualizar email
            $updateSql = "UPDATE usuarios SET email = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("si", $email, $user['id']);
            
            if ($updateStmt->execute()) {
                echo "<!DOCTYPE html>
                <html lang='es'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Email Actualizado</title>
                    <script src='https://cdn.tailwindcss.com'></script>
                    <link rel='stylesheet' href='../DB_lycaios/assets/css/styles.css'>
                </head>
                <body class='bg-gray-100 flex items-center justify-center min-h-screen font-sans p-4'>
                    <div class='w-full max-w-md bg-white p-8 rounded-3xl shadow-2xl text-center'>
                        <div class='mb-6'>
                            <img src='media/Ayuntamiento.png' alt='Logo' class='h-24 w-auto mx-auto mb-4'>
                            <div class='text-6xl mb-4'>✅</div>
                            <h1 class='text-2xl font-bold text-green-600 mb-2'>¡Email Actualizado!</h1>
                            <p class='text-gray-600'>
                                Tu email ha sido actualizado correctamente a:<br>
                                <strong>" . htmlspecialchars($email) . "</strong>
                            </p>
                        </div>
                        
                        <div class='bg-green-50 border-l-4 border-green-500 p-4 mb-6 text-left'>
                            <p class='text-sm text-gray-700'>
                                <strong>🔐 Ahora puedes:</strong> Recuperar tu contraseña usando este email si la olvidas.
                            </p>
                        </div>
                        
                        <a href='views/login.html' 
                            class='block w-full py-3 bg-blue-600 text-white font-semibold rounded-lg shadow hover:bg-blue-700 transition'>
                            Ir al Login
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
                echo "<script>alert('❌ Error al actualizar el email: " . $conn->error . "'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('❌ Contraseña incorrecta'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('❌ Usuario no encontrado'); window.history.back();</script>";
    }
    
    $conn->close();
}
?>
