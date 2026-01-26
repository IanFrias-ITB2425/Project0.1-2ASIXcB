<?php
include 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $post_id = $_POST['post_id'];

    if (isset($_POST['like'])) {
        $db->prepare("UPDATE posts SET likes_count = likes_count + 1 WHERE id = ?")->execute([$post_id]);
    }

    // Aquí verificamos que el texto no esté vacío antes de insertar
    if (isset($_POST['comment_text']) && !empty(trim($_POST['comment_text']))) {
        $comment = trim($_POST['comment_text']);
        $stmt = $db->prepare("INSERT INTO comments (post_id, comment) VALUES (?, ?)");
        $stmt->execute([$post_id, $comment]);
    }
}

header("Location: extagram.php");
exit();
