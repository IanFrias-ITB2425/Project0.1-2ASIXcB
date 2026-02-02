<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_conn.php';

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Funció optimitzada per obtenir l'avatar
 * Prioritza URLs externes (Google) i verifica fitxers locals
 */
function getAvatar($photo, $username) {
    // 1. Si és una URL (com les de Google), la retornem directament
    if (!empty($photo) && (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://'))) {
        return $photo;
    }

    // 2. Si no és URL, busquem a la carpeta local d'uploads
    if (!empty($photo)) {
        $path = "uploads/" . $photo;
        if (file_exists(__DIR__ . "/" . $path)) {
            return $path;
        }
    }

    // 3. Fallback: Avatar genèric amb inicials
    return "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=random&color=fff&bold=true";
}

$nodo = getenv('NODE_NAME') ?: gethostname();
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extagram</title>
    <link rel="icon" type="image/svg+xml" href="/preview.svg">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="static/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <script>
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('preview_img');
                output.src = reader.result;
                output.classList.remove('opacity-20');
                output.classList.add('opacity-100');
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        document.addEventListener("DOMContentLoaded", function() {
            lucide.createIcons();
            const scrollPos = localStorage.getItem("extagram_scroll");
            if (scrollPos) {
                window.scrollTo(0, scrollPos);
                localStorage.removeItem("extagram_scroll");
            }
        });

        function saveScroll() {
            localStorage.setItem("extagram_scroll", window.scrollY);
        }
    </script>
    <style>
        .instagram-card { max-width: 600px; width: 100%; }
        .btn-icon {
            background: transparent;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1e293b;
            transition: all 0.2s;
        }
        .btn-icon:hover { transform: scale(1.1); color: #3b82f6; }
        .ping { animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite; }
        @keyframes ping { 75%, 100% { transform: scale(2); opacity: 0; } }
    </style>
</head>
<body class="bg-[#fafafa] min-h-screen flex flex-col items-center py-8 px-4 text-slate-900">

    <header class="instagram-card flex justify-between items-center mb-8">
        <div class="flex items-center space-x-3">
            <div class="bg-white p-2 rounded-xl shadow-sm border border-slate-100">
                <img src="/preview.svg" alt="Logo" class="w-6 h-6">
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800">
                <a href="extagram.php">Extagram</a>
            </h1>
        </div>
        
        <nav class="text-sm">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="flex items-center space-x-4">
                    <a href="profile.php" class="flex items-center space-x-2 bg-white pr-3 rounded-full border border-slate-200 shadow-sm hover:bg-slate-50 transition">
                        <img src="<?= getAvatar($_SESSION['avatar_url'] ?? '', $_SESSION['username']); ?>" class="w-8 h-8 rounded-full object-cover">
                        <span class="font-bold text-slate-700">@<?= htmlspecialchars($_SESSION['username']); ?></span>
                    </a>
                    <a href="logout.php" class="text-slate-400 hover:text-red-500 transition-colors">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </a>
                </div>
            <?php else: ?>
                <a href="login.php" class="bg-[#0096f7] text-white px-5 py-2 rounded-full font-bold shadow-md hover:bg-blue-600 transition">Entrar</a>
            <?php endif; ?>
        </nav>
    </header>

    <?php if (isset($_SESSION['user_id'])): ?>
    <section class="instagram-card bg-white border border-slate-200 rounded-2xl shadow-sm mb-10 overflow-hidden">
        <form method="POST" enctype="multipart/form-data" action="upload.php" class="flex flex-col items-center gap-4 p-8">
            <input type="text" name="post" placeholder="Què vols compartir, <?= htmlspecialchars($_SESSION['username']); ?>?" required 
                   class="w-full max-w-[400px] px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl outline-none text-sm text-black focus:bg-white focus:border-blue-300 transition-all">
            
            <label class="group flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-2xl p-6 w-full max-w-[400px] hover:border-blue-400 hover:bg-blue-50 cursor-pointer transition-all">
                <img id="preview_img" src="/preview.svg" class="w-8 h-8 opacity-20 mb-2 object-cover rounded-lg">
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Pujar imatge</span>
                <input type="file" name="photo" accept="image/*" class="hidden" onchange="previewImage(event)">
            </label>

            <button type="submit" class="w-full max-w-[400px] bg-slate-900 hover:bg-black text-white font-bold py-3 rounded-xl transition-all shadow-lg active:scale-95">
                Publicar Post
            </button>
        </form>
    </section>
    <?php endif; ?>

    <main class="instagram-card space-y-12">
        <?php
        try {
            $sql = "SELECT p.*, u.username, u.avatar_url 
                    FROM posts p 
                    LEFT JOIN users u ON p.user_id = u.id 
                    ORDER BY p.id DESC 
                    LIMIT 50";
            
            $query = $db->query($sql);

            while ($fila = $query->fetch(PDO::FETCH_ASSOC)):
                $post_id = $fila['id'];
                $post_owner_id = $fila['user_id'];
                $autor_nom = $fila['username'] ?? "Anònim";
        ?>
            <article class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden group">
                
                <div class="p-4 flex items-center justify-between border-b border-slate-50">
                    <div class="flex items-center space-x-3">
                        <img src="<?= getAvatar($fila['avatar_url'], $autor_nom); ?>" 
                             class="w-9 h-9 rounded-full object-cover ring-2 ring-slate-50">
                        <span class="font-bold text-sm text-slate-800">@<?= htmlspecialchars($autor_nom); ?></span>
                    </div>

                    <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $post_owner_id)): ?>
                    <form method="POST" action="delete_post.php" onsubmit="return confirm('Eliminar post?')">
                        <input type="hidden" name="post_id" value="<?= $post_id; ?>">
                        <button type="submit" class="text-slate-300 hover:text-red-500 transition-colors p-1">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>

                <?php if (!empty($fila['photourl'])): ?>
                    <div class="bg-slate-50 flex justify-center border-b border-slate-50">
                        <img src="/uploads/<?= htmlspecialchars($fila['photourl']); ?>" 
                             class="w-full h-auto max-h-[600px] object-contain" 
                             alt="Post" loading="lazy">
                    </div>
                <?php endif; ?>

                <div class="p-5 space-y-4">
                    <div class="flex items-center space-x-5">
                        <form method="POST" action="interact.php" onsubmit="saveScroll()">
                            <input type="hidden" name="post_id" value="<?= $post_id; ?>">
                            <button type="submit" name="like" class="btn-icon">
                                <i data-lucide="thumbs-up" class="w-6 h-6"></i>
                            </button>
                        </form>
                        <button class="btn-icon hover:text-blue-500">
                             <i data-lucide="message-circle" class="w-6 h-6"></i>
                        </button>
                    </div>

                    <div class="text-sm font-black text-slate-900"><?= number_format($fila['likes_count'] ?? 0); ?> likes</div>
                    
                    <p class="text-sm leading-relaxed">
                        <span class="font-bold mr-2 text-slate-900">@<?= htmlspecialchars($autor_nom); ?></span>
                        <span class="text-slate-600"><?= htmlspecialchars($fila['post']); ?></span>
                    </p>

                    <div class="space-y-2 pt-2 border-t border-slate-50">
                        <?php
                        $stmt_com = $db->prepare("SELECT c.*, u.username FROM comments c LEFT JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.id ASC LIMIT 5");
                        $stmt_com->execute([$post_id]);
                        while ($com = $stmt_com->fetch(PDO::FETCH_ASSOC)):
                        ?>
                            <div class="flex justify-between group/com items-center py-1">
                                <span class="text-sm">
                                    <b class="text-slate-900 mr-1">@<?= htmlspecialchars($com['username'] ?? 'Anònim'); ?></b> 
                                    <span class="text-slate-600"><?= htmlspecialchars($com['comment']); ?></span>
                                </span>
                                <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $com['user_id'] || $_SESSION['user_id'] == $post_owner_id)): ?>
                                <form method="POST" action="delete_comment.php" onsubmit="saveScroll()">
                                    <input type="hidden" name="comment_id" value="<?= $com['id']; ?>">
                                    <button type="submit" class="text-slate-300 hover:text-red-500 opacity-0 group-hover/com:opacity-100 transition-opacity p-1">
                                        <i data-lucide="x" class="w-3 h-3"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="POST" action="interact.php" onsubmit="saveScroll()" class="flex items-center gap-2 pt-4 border-t border-slate-100 mt-2">
                        <input type="hidden" name="post_id" value="<?= $post_id; ?>">
                        <div class="flex-1 bg-slate-50/50 rounded-lg px-3 py-2 border border-slate-100 focus-within:border-slate-200 focus-within:bg-white transition-all">
                            <input type="text" name="comment_text" placeholder="Afegeix un comentari..." required
                                   class="w-full bg-transparent outline-none text-[13px] text-slate-800 placeholder:text-slate-400">
                        </div>
                        <button type="submit" class="text-blue-500 font-bold text-[11px] uppercase tracking-wider hover:text-blue-700 transition-colors px-1 shrink-0">
                            Publicar
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php 
            endwhile; 
        } catch (Exception $e) { 
            echo "<p class='text-center text-red-500'>Error de connexió: " . $e->getMessage() . "</p>"; 
        } 
        ?>
    </main>

    <footer class="mt-16 mb-8 flex flex-col items-center text-slate-400">
        <div class="flex items-center bg-white px-5 py-2 rounded-full border border-slate-200 shadow-sm space-x-3">
            <span class="relative flex h-2 w-2">
                <span class="ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
            </span>
            <span class="text-[9px] font-mono font-bold tracking-widest uppercase">
                Active Node: <span class="text-slate-800"><?= htmlspecialchars($nodo); ?></span>
            </span>
        </div>
    </footer>
</body>
</html>
