<?php
session_start();
include 'db_conn.php';

// Protecció: si no hi ha sessió, no deixem pujar res
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit("Accés denegat");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $post_text = trim($_POST["post"] ?? "");
    $photoid = "";

    // 1. Processar la imatge si existeix
    if (!empty($_FILES['photo']['name'])) {
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed_exts)) {
            // Generar nom únic per evitar sobreescriure
            $photoid = "post_" . uniqid() . "." . $ext;
            $target_path = '/var/www/extagram/uploads/' . $photoid;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                error_log("Falla move_uploaded_file a: " . $target_path);
                $photoid = ""; // Resetejem si falla la pujada física
            }
        }
    }

    // 2. Insertar a la BBDD (només si hi ha text o foto)
    if (!empty($post_text) || !empty($photoid)) {
        try {
            $stmt = $db->prepare("INSERT INTO posts (post, photourl, user_id, likes_count) VALUES (?, ?, ?, 0)");
            $stmt->execute([$post_text, $photoid, $user_id]);
        } catch (PDOException $e) {
            error_log("Error PDO: " . $e->getMessage());
        }
    }
}

// 3. Tornar al feed principal (URL directa per evitar descàrregues)
header("Location: extagram.php");
exit();
?>
