# Autor: ASIXcB G5 - Alberto Trujillo, Rehan Farooq, Aleix Tomas, Ian Frias Reyes
# ------------------------------------------------------------------------------
<?php
include 'db_conn.php';

// Verifiquem que l'usuari estigui loguejat i hagi enviat un ID
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_id']) && isset($_SESSION['user_id'])) {
    $comment_id = $_POST['comment_id'];
    $current_user_id = $_SESSION['user_id'];

    try {
        // Busquem qui és l'amo del comentari i qui és l'amo del post
        $stmt = $db->prepare("
            SELECT c.user_id AS comment_author, p.user_id AS post_owner 
            FROM comments c 
            JOIN posts p ON c.post_id = p.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$comment_id]);
        $perms = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($perms) {
            // LÒGICA DE PERMISOS:
            // Si l'usuari actual és l'autor del comentari O és l'amo del post
            if ($current_user_id == $perms['comment_author'] || $current_user_id == $perms['post_owner']) {
                $delete = $db->prepare("DELETE FROM comments WHERE id = ?");
                $delete->execute([$comment_id]);
            }
        }
        
    } catch (PDOException $e) {
        error_log("Error al esborrar el comentari: " . $e->getMessage());
    }
}

// Redirecció sempre a extagram.php
header("Location: extagram.php");
exit();
?>
