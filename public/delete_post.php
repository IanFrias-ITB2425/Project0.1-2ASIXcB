<?php
// Incluimos la conexión que acabas de actualizar a PDO
include 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];

    try {
        // 1. Obtener el nombre de la imagen antes de borrar el post
        $stmt = $db->prepare("SELECT photourl FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($post) {
            // 2. Borrar el archivo físico del servidor si existe
            $imagePath = "uploads/" . $post['photourl'];
            if (!empty($post['photourl']) && file_exists($imagePath)) {
                unlink($imagePath);
            }

            // 3. Borrar comentarios asociados (Importante para evitar errores de integridad)
            $stmtDelComments = $db->prepare("DELETE FROM comments WHERE post_id = ?");
            $stmtDelComments->execute([$post_id]);

            // 4. Borrar el post de la tabla posts
            $stmtDelPost = $db->prepare("DELETE FROM posts WHERE id = ?");
            $stmtDelPost->execute([$post_id]);
        }
    } catch (PDOException $e) {
        // En caso de error, puedes comentarlo o guardarlo en un log
        // die("Error al eliminar: " . $e->getMessage());
    }
}

// Redirigir siempre de vuelta al muro principal
header("Location: extagram.php");
exit();
