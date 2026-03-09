<?php
// ==========================================
// 1. CONFIGURACIÓN DE SEGURIDAD PARA CHROME
// ==========================================
// Esto es VITAL para que Chrome no rechace la sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax'); 

// Configuración de Redis (La que definimos en tu docker-compose)
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://s8_redis:6379?auth=Redis_Pass_2026!');

// ==========================================
// 2. INICIO DE SESIÓN
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_conn.php';

if (isset($_SESSION['user_id'])) {
    $current_session_id = session_id();
    $user_id = $_SESSION['user_id'];
    
    // Obtenim IP i Navegador
    $ip = $_SERVER['REMOTE_ADDR'];
    $agent = $_SERVER['HTTP_USER_AGENT'];

    // 1. Mirem si aquesta sessió està registrada a la BBDD
    $stmt = $db->prepare("SELECT id FROM active_sessions WHERE session_id = ?");
    $stmt->execute([$current_session_id]);
    
    if ($stmt->fetch()) {
        // Si existeix, actualitzem la "última activitat"
        $update = $db->prepare("UPDATE active_sessions SET last_activity = NOW(), ip_address = ? WHERE session_id = ?");
        $update->execute([$ip, $current_session_id]);
    } else {
        // Si no existeix (acabes de fer login), la creem
        $insert = $db->prepare("INSERT INTO active_sessions (session_id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $insert->execute([$current_session_id, $user_id, $ip, $agent]);
    }
}
?>
