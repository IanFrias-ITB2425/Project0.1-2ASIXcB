<?php
// Incloem la connexió a la base de dades
include 'db_conn.php';

// Verifiquem que la petició provingui d'un formulari POST i tingui l'ID del comentari
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_id'])) {
    $comment_id = $_POST['comment_id'];

    try {
        // Preparem la sentència per esborrar el comentari específic
        $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
        
        // Executem la sentència
        $stmt->execute([$comment_id]);
        
    } catch (PDOException $e) {
        // En cas d'error, el registrem al log del servidor
        error_log("Error al esborrar el comentari: " . $e->getMessage());
    }
}

// Redirecció final al mur principal amb l'extensió .php
header("Location: extagram.php");
exit();
?>
