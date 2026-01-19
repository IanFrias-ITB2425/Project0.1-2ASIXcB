<?php
// 1. Iniciem la sessió per poder tancar-la
session_start();

// 2. Eliminem totes les variables de sessió (user_id, username, etc.)
$_SESSION = array();

// 3. Destruïm la sessió al servidor
session_destroy();

// 4. Redirigim l'usuari a la pàgina principal
header("Location: extagram.php");
exit();
?>
