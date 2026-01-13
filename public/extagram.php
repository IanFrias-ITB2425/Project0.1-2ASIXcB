<?php 
// Report d'errors per a desenvolupament
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_conn.php'; 
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extagram</title>
    
    <link rel="icon" type="image/svg+xml" href="/preview.svg">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        /* Suavitzar el scroll dels comentaris */
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #ddd; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col items-center py-10 px-4">

    <header class="flex flex-col items-center mb-10">
        <div class="flex items-center space-x-3">
            <img src="/preview.svg" alt="Logo" class="w-10 h-10">
            <h1 class="text-4xl font-extrabold text-indigo-600 tracking-tight">Extagram</h1>
        </div>
    </header>

    <section class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-12">
        <form method="POST" enctype="multipart/form-data" action="upload.php" class="space-y-4">
            <input type="text" name="post" placeholder="Què vols compartir?" required 
                   class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white outline-none transition-all">
            
            <label class="group flex flex-col items-center justify-center border-2 border-dashed border-slate-300 rounded-2xl p-6 hover:bg-slate-50 hover:border-indigo-400 cursor-pointer transition-all">
                <img id="preview_img" src="/preview.svg" class="w-14 h-14 object-cover opacity-30 group-hover:opacity-60 mb-2 transition-opacity">
                <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Pujar fotografia</span>
                <input type="file" name="photo" accept="image/*" class="hidden"
                       onchange="document.getElementById('preview_img').src=window.URL.createObjectURL(event.target.files[0]); document.getElementById('preview_img').classList.remove('opacity-30')">
            </label>

            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-indigo-100 transition-all active:scale-[0.98]">
                Publicar
            </button>
        </form>
    </section>

    <main class="w-full max-w-md space-y-10">
        <?php
        try {
            $query = $db->query("SELECT * FROM posts ORDER BY id DESC");
            $posts = $query->fetchAll(PDO::FETCH_ASSOC);

            if (count($posts) > 0):
                foreach ($posts as $fila):
                    $post_id = $fila['id'];
        ?>
            <article class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden relative group transition-shadow hover:shadow-md">
                
                <form method="POST" action="delete_post.php" onsubmit="return confirm('Vols eliminar definitivament aquesta publicació?')" 
                      class="absolute top-4 right-4 z-20 opacity-0 group-hover:opacity-100 transition-opacity">
                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                    <button type="submit" class="bg-white/90 backdrop-blur-sm hover:bg-red-50 text-red-500 w-10 h-10 rounded-full flex items-center justify-center shadow-sm border border-slate-100 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </form>

                <div class="p-5 flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xs">U</div>
                    <p class="text-sm text-slate-800">
                        <span class="font-bold text-slate-900">@usuari_itb</span> <?php echo htmlspecialchars($fila['post']); ?>
                    </p>
                </div>

                <?php if (!empty($fila['photourl'])): ?>
                    <div class="bg-slate-100 flex justify-center items-center border-y border-slate-50">
                        <img src="/uploads/<?php echo $fila['photourl']; ?>" 
                             class="max-w-full h-auto max-h-[550px] block"
                             alt="Imatge del post">
                    </div>
                <?php endif; ?>

                <div class="p-5 space-y-4">
                    
                    <form method="POST" action="interact.php" class="flex items-center">
                        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                        <button type="submit" name="like" class="flex items-center space-x-2 bg-indigo-50 hover:bg-indigo-100 px-5 py-2 rounded-full transition-all active:scale-90 group/like">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600 group-hover/like:rotate-12 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                              <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 10.333z" />
                            </svg>
                            <span class="text-sm font-bold text-indigo-700"><?php echo $fila['likes_count'] ?? 0; ?></span>
                        </button>
                    </form>

                    <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar pr-2">
                        <?php
                        $stmt = $db->prepare("SELECT id, comment FROM comments WHERE post_id = ? ORDER BY id ASC");
                        $stmt->execute([$post_id]);
                        while ($com = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                            <div class="group/comm flex justify-between items-start text-xs bg-slate-50 p-3 rounded-2xl border border-slate-100">
                                <div class="flex-1">
                                    <span class="font-bold text-slate-900 block mb-1">Anònim</span> 
                                    <span class="text-slate-600 leading-relaxed"><?php echo htmlspecialchars($com['comment']); ?></span>
                                </div>
                                
                                <form method="POST" action="delete_comment.php" onsubmit="return confirm('Esborrar comentari?')" class="opacity-0 group-hover/comm:opacity-100 transition-opacity ml-2">
                                    <input type="hidden" name="comment_id" value="<?php echo $com['id']; ?>">
                                    <button type="submit" class="text-slate-300 hover:text-red-500 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <form method="POST" action="interact.php" class="flex items-center gap-3 mt-4 pt-4 border-t border-slate-100">
                        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                        <input type="text" name="comment_text" placeholder="Afegeix un comentari..." required
                               class="flex-1 text-sm outline-none bg-transparent py-1">
                        <button type="submit" class="text-indigo-600 font-bold text-sm hover:text-indigo-800 transition-colors">Publicar</button>
                    </form>

                </div>
            </article>
        <?php 
                endforeach;
            else:
        ?>
            <div class="text-center py-20 bg-white rounded-3xl border border-dashed border-slate-300">
                <p class="text-slate-400 font-medium">No hi ha publicacions disponibles.</p>
            </div>
        <?php 
            endif;
        } catch (Exception $e) {
            echo "<div class='bg-red-50 p-4 rounded-xl text-red-600 text-sm'>Error: " . $e->getMessage() . "</div>";
        }
        ?>
    </main>

    <footer class="mt-20 pb-10 text-slate-400 text-xs text-center">
        &copy; <?php echo date('Y'); ?> Extagram
    </footer>

</body>
</html>
