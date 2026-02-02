
<?php
/**
 * SISTEMA DE GESTIÓ DE PERFIL - EXTAGRAM (CORREGIDO)
 */

// AFEGIT: Iniciar sessió abans de res
session_start(); 

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_conn.php';

// Verificació de seguretat
if (!isset($_SESSION['user_id'])) {
    // Debug: si falla, sabrem si és per falta de ID
    header("Location: login.php?error=no_session_id_" . session_id());
    exit();
}

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? '';

function redirectWith($msg, $type = 'success') {
    header("Location: profile.php?$type=" . urlencode($msg));
    exit();
}

// --- ACCIÓ: ACTUALITZAR AVATAR ---
if ($action === 'update_avatar') {
    if (!isset($_FILES['new_avatar']) || $_FILES['new_avatar']['error'] === UPLOAD_ERR_NO_FILE) {
        redirectWith("No has seleccionat cap fitxer.", "error");
    }

    $file = $_FILES['new_avatar'];
    $upload_dir = __DIR__ . "/uploads/";

    // Validacions de tipus
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowed_exts)) {
        redirectWith("Format no permès.", "error");
    }

    // Nom únic per evitar cache del navegador
    $new_filename = "avatar_" . $user_id . "_" . time() . "." . $extension;
    $final_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $final_path)) {
        // Esborrar antic i fer UPDATE
        $stmt = $db->prepare("SELECT avatar_url FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $old_avatar = $stmt->fetchColumn();

        if ($old_avatar && !filter_var($old_avatar, FILTER_VALIDATE_URL) && $old_avatar !== 'default_avatar.png') {
            @unlink($upload_dir . $old_avatar);
        }

        $db->prepare("UPDATE users SET avatar_url = ? WHERE id = ?")->execute([$new_filename, $user_id]);
        
        // ACTUALITZAR SESSIÓ PERQUÈ ES VEGI AL MOMENT
        $_SESSION['avatar_url'] = $new_filename; 
        
        redirectWith("Foto de perfil actualitzada!");
    }
}

// --- ACCIÓ: ACTUALITZAR INFORMACIÓ (Nom i Pass) ---
if ($action === 'update_info') {
    $new_username = trim($_POST['username'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    if (empty($new_username)) {
        redirectWith("El nom d'usuari no pot estar buit.", "error");
    }

    try {
        // Comprovar duplicats
        $check = $db->prepare("SELECT id FROM users WHERE username = ? AND id <> ?");
        $check->execute([$new_username, $user_id]);
        if ($check->fetch()) {
            redirectWith("Aquest nom d'usuari ja està agafat.", "error");
        }

        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = ?, password = ? WHERE id = ?";
            $params = [$new_username, $hashed, $user_id];
        } else {
            $sql = "UPDATE users SET username = ? WHERE id = ?";
            $params = [$new_username, $user_id];
        }

        $stmt = $db->prepare($sql);
        $res = $stmt->execute($params);

        if ($res) {
            // CRUCIAL: Actualitzar la sessió amb el nou nom
            $_SESSION['username'] = $new_username;
            redirectWith("Dades actualitzades a la BD i sessió.");
        } else {
            redirectWith("Error en l'execució de la consulta.", "error");
        }

    } catch (PDOException $e) {
        redirectWith("Error DB: " . $e->getMessage(), "error");
    }
}

// Les altres accions (delete_data, delete_account) estan bé estructuralment.
header("Location: profile.php");
exit();
?>
