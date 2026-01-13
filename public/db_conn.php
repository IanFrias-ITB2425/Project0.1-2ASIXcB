<?php
$servername = "localhost";
$username = "extagram_admin";
$password = "pass123";
$dbname = "extagram_db";

try {
    // Creem la conexio utilitzant PDO
    $db = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    
    // Configurem que PHP ens avisi si n'hi ha errors de SQ
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
