# Autor: ASIXcB G5 - Alberto Trujillo, Rehan Farooq, Aleix Tomas, Ian Frias Reyes
# ------------------------------------------------------------------------------
<?php
/**
 * UPDATE_PROFILE.PHP - VERSIÓN FINAL CON GESTIÓN DE SESIONES
 */

// Usamos el nuevo gestor que arregla lo de Chrome
require_once 'auth_session.php'; 

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ya no hace falta require 'db_conn.php' ni session_start() porque auth_session lo hace.

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? '';

function redirectWith($msg, $type = 'success') {
    header("Location: profile.php?$type=" . urlencode($msg));
    exit();
}

try {
    // =================================================================
    // 1. ACTUALIZAR AVATAR
    // =================================================================
    if ($action === 'update_avatar') {
        if (!isset($_FILES['new_avatar']) || $_FILES['new_avatar']['error'] === UPLOAD_ERR_NO_FILE) {
            redirectWith("No has seleccionado imagen.", "error");
        }

        $file = $_FILES['new_avatar'];
        $upload_dir = '/var/www/html/uploads/'; 
        
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            redirectWith("Formato no válido.", "error");
        }

        $new_filename = "avatar_" . $user_id . "_" . time() . "." . $ext;
        
        if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
            // Limpieza antigua
            $stmt = $db->prepare("SELECT avatar_url FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $old = $stmt->fetchColumn();

            if ($old && !filter_var($old, FILTER_VALIDATE_URL) && $old !== 'default_avatar.png') {
                @unlink($upload_dir . $old);
            }

            $db->prepare("UPDATE users SET avatar_url = ? WHERE id = ?")->execute([$new_filename, $user_id]);
            $_SESSION['avatar_url'] = $new_filename;
            redirectWith("Foto actualizada.");
        }
    }

    // =================================================================
    // 2. ACTUALIZAR INFO (Usuario/Pass)
    // =================================================================
    if ($action === 'update_info') {
        $new_username = trim($_POST['username'] ?? '');
        $new_pass     = $_POST['new_password'] ?? '';

        if (empty($new_username)) redirectWith("Nombre obligatorio.", "error");

        $check = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check->execute([$new_username, $user_id]);
        if ($check->fetch()) redirectWith("Nombre ocupado.", "error");

        if (!empty($new_pass)) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = ?, password = ? WHERE id = ?";
            $params = [$new_username, $hashed, $user_id];
        } else {
            $sql = "UPDATE users SET username = ? WHERE id = ?";
            $params = [$new_username, $user_id];
        }

        $db->prepare($sql)->execute($params);
        $_SESSION['username'] = $new_username;
        redirectWith("Datos actualizados.");
    }

    // =================================================================
    // 3. GESTIÓN DE SESIONES (NUEVO)
    // =================================================================
    
    // ECHAR A UN DISPOSITIVO ESPECÍFICO
    if ($action === 'kill_session') {
        $target_session = $_POST['session_id_to_kill'] ?? '';
        
        // Solo borramos si pertenece a este usuario
        $stmt = $db->prepare("DELETE FROM active_sessions WHERE session_id = ? AND user_id = ?");
        $stmt->execute([$target_session, $user_id]);
        
        redirectWith("Dispositivo desconectado.");
    }

    // CERRAR TODAS LAS SESIONES MENOS ESTA
    if ($action === 'logout_all') {
        $current = session_id();
        $stmt = $db->prepare("DELETE FROM active_sessions WHERE user_id = ? AND session_id != ?");
        $stmt->execute([$user_id, $current]);
        
        redirectWith("Has cerrado sesión en todos los otros dispositivos.");
    }

    // =================================================================
    // 4. ELIMINAR CUENTA
    // =================================================================
    if ($action === 'delete_account') {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
        session_destroy();
        header("Location: login.php?msg=Cuenta eliminada");
        exit();
    }

} catch (Exception $e) {
    redirectWith("Error: " . $e->getMessage(), "error");
}

header("Location: profile.php");
?>
