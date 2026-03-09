# Autor: ASIXcB G5 - Alberto Trujillo, Rehan Farooq, Aleix Tomas, Ian Frias Reyes
# ------------------------------------------------------------------------------
<?php
// VITAL: Sense session_start() no podem saber qui és l'usuari (S2, S3 i S4 ho necessiten)
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
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] == 0) {
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed_exts)) {
            // Generar nom únic
            $photoid = "post_" . uniqid() . "." . $ext;
            
            // CORRECCIÓ DE RUTA: 
            // Nginx espera els fitxers a /var/www/html/uploads/
            // La ruta /var/www/extagram/ NO existeix dins del contenidor per defecte
            $target_path = '/var/www/html/uploads/' . $photoid;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                // Si falla, mira els logs de docker: docker logs s4_upload
                error_log("Falla move_uploaded_file a: " . $target_path);
                $photoid = ""; 
            }
        }
    }

    // 2. Insertar a la BBDD (només si hi ha text o foto)
    if (!empty($post_text) || !empty($photoid)) {
        try {
            // Afegim created_at si la teva taula ho té, si no, deixa-ho com estava
            $stmt = $db->prepare("INSERT INTO posts (post, photourl, user_id, likes_count) VALUES (?, ?, ?, 0)");
            $stmt->execute([$post_text, $photoid, $user_id]);
        } catch (PDOException $e) {
            error_log("Error PDO: " . $e->getMessage());
        }
    }
}

// 3. Tornar al feed
header("Location: extagram.php");
exit();
?>
