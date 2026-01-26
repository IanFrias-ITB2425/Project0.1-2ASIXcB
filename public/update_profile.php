k<?php
/**
 * SISTEMA DE GESTIÓ DE PERFIL - EXTAGRAM
 * Aquest fitxer processa: Avatar, Dades d'usuari, Password i Eliminació de compte.
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. CONFIGURACIÓ I CONNEXIÓ
// -------------------------------------------------------------------------
require_once 'db_conn.php';

// Verificació de seguretat: Només usuaris loguejats
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=no_session");
    exit();
}

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? '';
$errors  = [];

// 2. FUNCIÓ AUXILIAR PER RECONDUIR AMB MISSATGES
// -------------------------------------------------------------------------
function redirectWith($msg, $type = 'success') {
    header("Location: profile.php?$type=" . urlencode($msg));
    exit();
}

// -------------------------------------------------------------------------
// ACCIÓ: ACTUALITZAR AVATAR (FOTO)
// -------------------------------------------------------------------------
if ($action === 'update_avatar') {
    if (!isset($_FILES['new_avatar']) || $_FILES['new_avatar']['error'] === UPLOAD_ERR_NO_FILE) {
        redirectWith("No has seleccionat cap fitxer.", "error");
    }

    $file = $_FILES['new_avatar'];
    
    // Validació d'errors de PHP en la pujada
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $php_errors = [
            1 => "El fitxer és massa gran (limit PHP).",
            2 => "El fitxer és massa gran (limit HTML).",
            3 => "Pujada parcial.",
            4 => "No s'ha pujat cap fitxer.",
            6 => "Falta la carpeta temporal.",
            7 => "Error en escriure al disc.",
            8 => "Una extensió de PHP va aturar la pujada."
        ];
        redirectWith($php_errors[$file['error']] ?? "Error desconegut en la pujada.", "error");
    }

    // Validació de tipus MIME i Extensió
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $file_info    = pathinfo($file['name']);
    $extension    = strtolower($file_info['extension']);
    
    if (!in_array($extension, $allowed_exts)) {
        redirectWith("Format no permès. Fes servir JPG, PNG, GIF o WebP.", "error");
    }

    // Validació de mida manual (Exemple: 3MB)
    if ($file['size'] > 3 * 1024 * 1024) {
        redirectWith("La imatge és massa gran. El límit són 3MB.", "error");
    }

    // Gestió del Directori de destí
    $upload_dir = __DIR__ . "/uploads/";
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            redirectWith("No s'ha pogut crear la carpeta 'uploads'. Revisa permisos del servidor.", "error");
        }
    }

    // Generar nom únic per evitar sobreescriure o problemes de cache
    $new_filename = "avatar_" . $user_id . "_" . bin2hex(random_bytes(4)) . "." . $extension;
    $final_path   = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $final_path)) {
        try {
            // Obtenir l'avatar antic per esborrar-lo i no omplir el disc de brossa
            $stmt = $db->prepare("SELECT avatar_url FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $old_avatar = $stmt->fetchColumn();

            if ($old_avatar && $old_avatar !== 'default_avatar.png') {
                $old_file_path = $upload_dir . $old_avatar;
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
            }

            // Actualitzar la DB
            $update = $db->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
            $update->execute([$new_filename, $user_id]);

            redirectWith("Foto de perfil actualitzada!");
        } catch (PDOException $e) {
            redirectWith("Error DB: " . $e->getMessage(), "error");
        }
    } else {
        redirectWith("Error en moure el fitxer. Comprova el CHMOD de 'uploads'.", "error");
    }
}

// -------------------------------------------------------------------------
// ACCIÓ: ACTUALITZAR INFORMACIÓ (NOM D'USUARI I PASSWORD)
// -------------------------------------------------------------------------
if ($action === 'update_info') {
    $new_username = trim($_POST['username'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    if (empty($new_username)) {
        redirectWith("El nom d'usuari no pot estar buit.", "error");
    }

    try {
        // Comprovar si el nom d'usuari ja existeix (i no és el nostre)
        $check = $db->prepare("SELECT id FROM users WHERE username = ? AND id <> ?");
        $check->execute([$new_username, $user_id]);
        if ($check->fetch()) {
            redirectWith("Aquest nom d'usuari ja està agafat.", "error");
        }

        // Preparar la query dinàmicament
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = ?, password = ? WHERE id = ?";
            $params = [$new_username, $hashed, $user_id];
        } else {
            $sql = "UPDATE users SET username = ? WHERE id = ?";
            $params = [$new_username, $user_id];
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $_SESSION['username'] = $new_username; // Actualitzar sessió
        redirectWith("Dades actualitzades correctament.");

    } catch (PDOException $e) {
        redirectWith("Error en l'actualització: " . $e->getMessage(), "error");
    }
}

// -------------------------------------------------------------------------
// ACCIÓ: ELIMINAR TOTES LES DADES (POSTS I COMENTARIS)
// -------------------------------------------------------------------------
if ($action === 'delete_data') {
    try {
        $db->beginTransaction();

        // 1. Esborrar fitxers físics dels posts
        $stmt = $db->prepare("SELECT photourl FROM posts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        while ($row = $stmt->fetch()) {
            $file_path = __DIR__ . "/uploads/" . $row['photourl'];
            if (!empty($row['photourl']) && file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // 2. Esborrar de la base de dades
        $del_comments = $db->prepare("DELETE FROM comments WHERE user_id = ?");
        $del_comments->execute([$user_id]);

        $del_posts = $db->prepare("DELETE FROM posts WHERE user_id = ?");
        $del_posts->execute([$user_id]);

        $db->commit();
        redirectWith("Tots els teus posts i comentaris han estat eliminats.");

    } catch (Exception $e) {
        $db->rollBack();
        redirectWith("Error al netejar dades: " . $e->getMessage(), "error");
    }
}

// -------------------------------------------------------------------------
// ACCIÓ: ELIMINAR COMPTE DEFINITIVAMENT
// -------------------------------------------------------------------------
if ($action === 'delete_account') {
    try {
        $db->beginTransaction();

        // Esborrar avatar físic
        $stmt = $db->prepare("SELECT avatar_url FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $avatar = $stmt->fetchColumn();
        if ($avatar && $avatar !== 'default_avatar.png') {
            @unlink(__DIR__ . "/uploads/" . $avatar);
        }

        // Esborrar posts (fitxers i registres)
        $stmt_p = $db->prepare("SELECT photourl FROM posts WHERE user_id = ?");
        $stmt_p->execute([$user_id]);
        while ($p = $stmt_p->fetch()) {
            @unlink(__DIR__ . "/uploads/" . $p['photourl']);
        }

        // Eliminar de les taules (L'ordre importa si no hi ha ON DELETE CASCADE)
        $db->prepare("DELETE FROM comments WHERE user_id = ?")->execute([$user_id]);
        $db->prepare("DELETE FROM posts WHERE user_id = ?")->execute([$user_id]);
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);

        $db->commit();
        
        session_destroy();
        header("Location: login.php?msg=compte_eliminat");
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        redirectWith("Error al tancar el compte: " . $e->getMessage(), "error");
    }
}

// -------------------------------------------------------------------------
// FINALITZACIÓ DEL SCRIPT
// -------------------------------------------------------------------------
// Si s'arriba aquí per algun motiu estrany, tornem al perfil
header("Location: profile.php");
exit();
?>
