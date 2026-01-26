<?php
$servername = "s7_mysql";
$username = "extagram_admin";
$password = "pass123";
$dbname = "extagram_db";

try {
    // Creamos la conexión usando PDO
    $db = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    
    // Configuramos para que PHP nos avise si hay errores de SQL
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
