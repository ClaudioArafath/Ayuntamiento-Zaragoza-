<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Buscar usuario en la BD
    $sql = "SELECT * FROM usuarios WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $user = $resultado->fetch_assoc();

        // Verificar contraseña encriptada
        //Falta encriptar la contraseña en la base de datos
        //falta usar password_hash() al crear usuarios
        if ($password === $user['password']) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['rol'] = $user['rol'];
            header("Location: dashboard.php"); // Redirige si es correcto
            exit;
        } else {
            echo "<script>alert('❌ Contraseña incorrecta'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('❌ Usuario no encontrado'); window.history.back();</script>";
    }
}
?>