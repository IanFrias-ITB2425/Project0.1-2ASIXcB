<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_conn.php'; 

/**
 * Funció per obtenir la imatge: prioritzant la pujada de l'usuari
 * i usant l'API d'inicials com a "fallback".
 */
function getAvatar($photo, $username) {
    if (!empty($photo) && file_exists("uploads/" . $photo)) {
        return "/uploads/" . $photo;
    }
    // API gratuïta: genera inicials amb fons aleatori i lletres blanques
    return "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=random&color=fff&bold=true";
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extagram</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="static/style.css">
    <script>
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('preview_img');
                output.src = reader.result;
                output.classList.remove('opacity-30');
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #ddd; border-radius: 10px; }
        .instagram-card { max-width: 600px; width: 100%; }
    </style>
</head>
<body class="bg-[#fafafa] min-h-screen flex flex-col items-center py-8 px-4 text-slate-900">

    <header class="instagram-card flex justify-between items-center mb-8">
        <div class="flex items-center space-x-3">
            <div class="bg-slate-200 p-2 rounded-lg">
                <img src="/preview.svg" alt="Logo" class="w-6 h-6">
            </div>
            <h1 class="text-2xl font-bold tracking-tight"><a href="extagram.php">Extagram</a></h1>
        </div>
        
        <nav class="text-sm">
            <?php if (isset($_SESSION['user_id'])): 
                // Obtenim les dades actualitzades de l'usuari loguejat per la capçalera
                $st = $db->prepare("SELECT username, avatar_url FROM users WHERE id = ?");
                $st->execute([$_SESSION['user_id']]);
                $me = $st->fetch(PDO::FETCH_ASSOC);
            ?>
                <div class="flex items-center space-x-4">
                    <a href="profile.php" class="flex items-center space-x-2 group">
                        <img src="<?php echo getAvatar($me['avatar_url'], $me['username']); ?>" 
                             class="w-8 h-8 rounded-full object-cover border border-slate-200 shadow-sm">
                        <span class="font-medium text-slate-600 group-hover:text-blue-500 transition-colors">
                            @<?php echo htmlspecialchars($me['username']); ?>
                        </span>
                    </a>
                    <a href="logout.php" class="text-red-500 font-bold hover:underline">Sortir</a>
                </div>
            <?php else: ?>
                <a href="login.php" class="bg-[#0096f7] text-white px-4 py-1.5 rounded-lg font-bold">Entrar</a>
            <?php endif; ?>
        </nav>
    </header>

    <?php if (isset($_SESSION['user_id'])): ?>
    <section class="instagram-card bg-white border border-slate-200 rounded-lg shadow-sm mb-10 overflow-hidden">
        <form method="POST" enctype="multipart/form-data" action="upload.php" class="flex flex-col items-center gap-4 p-8">
            <input type="text" name="post" placeholder="Què estàs pensant, <?php echo $_SESSION['username']; ?>?" required 
                   class="w-full max-w-[400px] px-4 py-2 bg-slate-50 border border-slate-200 rounded-md outline-none text-sm focus:ring-1 focus:ring-blue-400">
            
            <label class="group flex flex-col items-center justify-center border-2 border-dashed border-slate-100 rounded-xl p-6 w-full max-w-[400px] hover:bg-blue-50 cursor-pointer transition-all">
                <img id="preview_img" src="/preview.svg" class="w-10 h-10 opacity-20 mb-2">
                <span class="text-[10px] text-slate-400 font-bold uppercase">Seleccionar imatge</span>
                <input type="file" name="photo" accept="image/*" class="hidden" onchange="previewImage(event)">
            </label>

            <button type="submit" class="w-full max-w-[400px] bg-[#0096f7] hover:bg-[#0081d6] text-white font-bold py-2 rounded-md transition-all active:scale-95">
                Publicar Post
            </button>
        </form>
    </section>
    <?php endif; ?>

    <main class="instagram-card space-y-10">
        <?php
        try {
            $query = $db->query("
                SELECT p.*, u.username, u.avatar_url 
                FROM posts p 
                LEFT JOIN users u ON p.user_id = u.id 
                ORDER BY p.id DESC
            ");
            
            while ($fila = $query->fetch(PDO::FETCH_ASSOC)):
                $post_id = $fila['id'];
                $autor_nom = $fila['username'] ?? "Anònim";
                $autor_avatar_file = $fila['avatar_url'];
        ?>
            <article class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden group">
                <div class="p-4 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <img src="<?php echo getAvatar($autor_avatar_file, $autor_nom); ?>" 
                             class="w-8 h-8 rounded-full object-cover border border-slate-100 shadow-sm">
                        <span class="font-bold text-sm">@<?php echo htmlspecialchars($autor_nom); ?></span>
                    </div>

                    <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $fila['user_id'])): ?>
                    <form method="POST" action="delete_post.php" onsubmit="return confirm('Segur que vols esborrar el post?')">
                        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                        <button type="submit" class="text-slate-300 hover:text-red-500 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>

                <?php if (!empty($fila['photourl'])): ?>
                    <div class="bg-slate-50 border-y border-slate-50">
                        <img src="/uploads/<?php echo $fila['photourl']; ?>" class="w-full h-auto max-h-[600px] block mx-auto" alt="Post">
                    </div>
                <?php endif; ?>

                <div class="p-4 space-y-3">
                    <div class="flex items-center space-x-4">
                        <form method="POST" action="interact.php">
                            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                            <button type="submit" name="like" class="hover:text-red-500 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                            </button>
                        </form>
                    </div>

                    <span class="text-sm font-bold block"><?php echo $fila['likes_count']; ?> likes</span>
                    
                    <p class="text-sm">
                        <span class="font-bold mr-2"><?php echo htmlspecialchars($autor_nom); ?></span>
                        <?php echo htmlspecialchars($fila['post']); ?>
                    </p>

                    <div class="pt-2 space-y-1">
                        <?php
                        $stmt_com = $db->prepare("SELECT c.*, u.username FROM comments c LEFT JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.id ASC");
                        $stmt_com->execute([$post_id]);
                        while ($comentari = $stmt_com->fetch(PDO::FETCH_ASSOC)):
                        ?>
                            <div class="text-sm flex justify-between group/com">
                                <span><b class="mr-1"><?php echo htmlspecialchars($comentari['username'] ?? 'Anònim'); ?></b> <?php echo htmlspecialchars($comentari['comment']); ?></span>
                                
                                <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $comentari['user_id'])): ?>
                                <form method="POST" action="delete_comment.php">
                                    <input type="hidden" name="comment_id" value="<?php echo $comentari['id']; ?>">
                                    <button type="submit" class="text-[10px] text-red-400 opacity-0 group-hover/com:opacity-100 transition-opacity">Eliminar</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="POST" action="interact.php" class="flex items-center gap-2 pt-3 border-t border-slate-100">
                        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                        <input type="text" name="comment_text" placeholder="Afegeix un comentari..." required
                               class="flex-1 text-sm outline-none bg-transparent focus:placeholder-slate-300">
                        <button type="submit" class="text-blue-500 font-bold text-sm hover:text-blue-700">Publicar</button>
                    </form>
                    <?php endif; ?>
                </div>
            </article>
<?php endwhile; } catch (Exception $e) { echo "<div class='text-red-500 font-bold'>Error de base de dades: " . $e->getMessage() . "</div>"; } ?>
    </main>

    <footer class="w-full max-w-[600px] mt-12 pb-8 text-center">
        <div class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 border border-slate-200 shadow-sm">
            <span class="flex h-2 w-2 rounded-full bg-green-500 mr-2 animate-pulse"></span>
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                Atès per el servidor: <?php echo getenv('NODE_NAME') ?: gethostname(); ?>
            </span>
        </div>
    </footer>
</body>
</html>
