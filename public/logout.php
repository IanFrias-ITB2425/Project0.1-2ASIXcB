<?php
// 1. Incluimos la conexión para que cargue la configuración de Redis
include 'db_conn.php';

// 2. Si por algún motivo session_start no se ejecutó en db_conn, lo forzamos
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Limpiamos las variables del array $_SESSION
$_SESSION = array();

// 4. IMPORTANTE: Borramos la cookie de sesión del navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 5. Destruimos la sesión en el servidor (Redis eliminará la clave)
session_destroy();

// 6. Redirigimos al login
header("Location: login.php");
exit();
