# Autor: ASIXcB G5 - Alberto Trujillo, Rehan Farooq, Aleix Tomas, Ian Frias Reyes
# ------------------------------------------------------------------------------
<?php
// /docker/public/db_conn.php

// 1. Buffer de sortida
ob_start();

// 2. CONFIGURACIÓ DE REDIS AMB AUTENTICACIÓ (Hardening)
// La sintaxi correcta per a phpredis amb password és: tcp://host:port?auth=password
$redis_pass = 'Redis_Pass_2026!'; 
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', "tcp://s8_redis:6379?auth=$redis_pass");

// 3. Inici de Sessió Controlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 4. Conexió a MySQL AMB CREDENCIALS ACTUALITZADES
$servername = "s7_mysql";
$username   = "extagram_admin";
$password   = "User_Secure_Pass_99!"; // La nova clau que hem posat al Compose
$dbname     = "extagram_db";

try {
    $db = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Hardening extra: No mostris l'error real a l'usuari final per evitar leak de rutes
    error_log("DB Error: " . $e->getMessage());
    die("Error crític de connexió. Contacta amb l'administrador.");
}
?>
