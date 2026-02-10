<?php
// /docker/public/db_conn.php

// 1. Buffer de salida para evitar errores de "Headers already sent"
ob_start();

// 2. Configuración de REDIS para sesiones (Siempre antes de session_start)
// Asegúrate de que el host 's8_redis' coincide con tu docker-compose
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://s8_redis:6379');

// 3. Inicio de Sesión Controlado
// Solo iniciamos si no hay una sesión activa previa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 4. Conexión a Base de Datos MySQL
$servername = "s7_mysql";
$username   = "extagram_admin";
$password   = "pass123";
$dbname     = "extagram_db";

try {
    $db = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    // Modo de errores estricto para depuración
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la Base de Datos: " . $e->getMessage());
}
?>
