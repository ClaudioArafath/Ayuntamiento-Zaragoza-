<?php
//  Conexiones a base de datos LycaiosPOS
function conectarLycaidosPOS() {
    $host = "localhost";
    $port = 3311;
    $user = "root";
    $password = "";
    $database = "lycaios_pos";

    $conn = new mysqli($host, $user, $password, $database, $port);
    if ($conn->connect_error) {
        die("Error de conexión Lycaios POS: " . $conn->connect_error);
    }
    return $conn;
}

//  Conexiones a base de datos Ayuntamiento en PHPMyAdmin
function conectarAyuntamiento() {
    $host = "localhost";
    $port = 3306;
    $user = "root";
    $password = "";
    $database = "ayuntamiento";

    $conn = new mysqli($host, $user, $password, $database, $port);
    if ($conn->connect_error) {
        die("Error de conexión Ayuntamiento: " . $conn->connect_error);
    }
    return $conn;
}
?>