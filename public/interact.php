<?php
include 'db_conn.php';

// Verificamos que el usuario esté logueado para interactuar
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id']; // Capturamos el ID real de la sesión

    // GESTIÓN DE LIKES
    if (isset($_POST['like'])) {
        $stmt = $db->prepare("UPDATE posts SET likes_count = likes_count + 1 WHERE id = ?");
        $stmt->execute([$post_id]);
    }

    // GESTIÓN DE COMENTARIOS CON USUARIO REAL
    if (isset($_POST['comment_text']) && !empty(trim($_POST['comment_text']))) {
        $comment = trim($_POST['comment_text']);
        
        // Insertamos incluyendo el user_id para que no sea Anónimo
        $stmt = $db->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $user_id, $comment]);
    }
}

header("Location: extagram.php");
exit();
