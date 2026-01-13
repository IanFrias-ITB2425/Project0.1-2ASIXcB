<?php
include 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $photoid = "";
    $post_text = $_POST["post"] ?? "";

    // 1. Processar la imatge
    if (!empty($_FILES['photo']['name'])) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        // Generar ID únic
        $photoid = uniqid() . "." . $ext;
        
        // Ruta absoluta del servidor
        $target_path = '/var/www/extagram/uploads/' . $photoid;
        
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
            // Opcional: pots registrar si ha fallat la pujada física
            error_log("Error al moure el fitxer a: " . $target_path);
        }
    }

    // 2. Insertar a la BBDD usant PDO
    if (!empty($post_text) || !empty($photoid)) {
        try {
            $stmt = $db->prepare("INSERT INTO posts (post, photourl, likes_count) VALUES (?, ?, 0)");
            // En PDO es fa l'execute amb un array, sense bind_param
            $stmt->execute([$post_text, $photoid]);
        } catch (PDOException $e) {
            error_log("Error a la BBDD: " . $e->getMessage());
        }
    }
}

// 3. Redirecció neta segons la teva nova config de Nginx
header("Location: /extagram");
exit();
?>
