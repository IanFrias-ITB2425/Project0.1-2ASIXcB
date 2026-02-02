<?php
// Evitar que cualquier salida previa rompa las cabeceras
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    // Configuración de REDIS antes de iniciar la sesión
    ini_set('session.save_handler', 'redis');
    ini_set('session.save_path', 'tcp://s8_redis:6379');
    session_start();
}

$servername = "s7_mysql";
$username = "extagram_admin";
$password = "pass123";
$dbname = "extagram_db";

try {
    $db = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
