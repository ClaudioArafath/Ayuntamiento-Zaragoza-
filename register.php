<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $nombre_completo = trim($_POST['nombre_completo']);
    $email = trim($_POST['email']);

    // Validaciones básicas
    if (empty($username) || empty($password) || empty($confirm_password) || empty($nombre_completo) || empty($email)) {
        echo "<script>alert('❌ Todos los campos obligatorios deben ser completados'); window.history.back();</script>";
        exit;
    }
    
    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('❌ El formato del email no es válido'); window.history.back();</script>";
        exit;
    }

    if ($password !== $confirm_password) {
        echo "<script>alert('❌ Las contraseñas no coinciden'); window.history.back();</script>";
        exit;
    }

    if (strlen($password) < 6) {
        echo "<script>alert('❌ La contraseña debe tener al menos 6 caracteres'); window.history.back();</script>";
        exit;
    }

    // Conectar a la base de datos Ayuntamiento
    $conn = conectarAyuntamiento();

    // Verificar si el usuario o email ya existe
    $check_sql = "SELECT id FROM usuarios WHERE username = ? OR email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $username, $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo "<script>alert('❌ El nombre de usuario o email ya está en uso'); window.history.back();</script>";
        $conn->close();
        exit;
    }

    // Encriptar contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insertar nuevo usuario (rol por defecto: 'empleado')
    $insert_sql = "INSERT INTO usuarios (username, password, nombre_completo, email, rol) VALUES (?, ?, ?, ?, 'empleado')";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ssss", $username, $hashedPassword, $nombre_completo, $email);

    if ($insert_stmt->execute()) {
        echo "<script>alert('✅ Usuario registrado correctamente. Ahora puede iniciar sesión.'); window.location.href = 'views/login.html';</script>";
    } else {
        echo "<script>alert('❌ Error al registrar el usuario: " . $conn->error . "'); window.history.back();</script>";
    }

    $conn->close();
}
?>