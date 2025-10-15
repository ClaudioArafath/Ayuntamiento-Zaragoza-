<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Buscar usuario en la BD por username
    $sql = "SELECT * FROM usuarios WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $user = $resultado->fetch_assoc();
        $dbPassword = $user['password']; // La contraseña almacenada (actualmente en texto plano)

        /**
         * 1️⃣ Validar contraseña con texto plano (fase de migración)
         * Si coincide → encriptamos y actualizamos en la BD
         */
        if ($password === $dbPassword) {
            // Generar hash seguro con BCRYPT
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Actualizar la contraseña en BD para este usuario
            $updateSql = "UPDATE usuarios SET password = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("si", $hashedPassword, $user['id']);
            $updateStmt->execute();

            // Guardamos sesión
            $_SESSION['username'] = $user['username'];
            $_SESSION['rol'] = $user['rol'];

            header("Location: index.php"); // Redirige si es correcto
            exit;
        }

        /**
         * 2️⃣ Caso en que ya está encriptada (cuando el usuario ya haya migrado)
         */
        if (password_verify($password, $dbPassword)) {
            // Guardamos sesión
            $_SESSION['username'] = $user['username'];
            $_SESSION['rol'] = $user['rol'];

            header("Location: index.php");
            exit;
        }

        /**
         * 3️⃣ Contraseña incorrecta
         */
        echo "<script>alert('❌ Contraseña incorrecta'); window.history.back();</script>";

    } else {
        // Usuario no encontrado
        echo "<script>alert('❌ Usuario no encontrado'); window.history.back();</script>";
    }
}
?>
