<?php
session_start();
include 'db_conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtenir dades fresques de la BD
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/**
 * Lògica idèntica a extagram.php per mantenir la coherència.
 * Comprova si el fitxer existeix físicament.
 */
function getAvatar($photo, $username) {
    $path_to_file = __DIR__ . "/uploads/" . $photo;
    if (!empty($photo) && file_exists($path_to_file) && is_file($path_to_file)) {
        return "/uploads/" . $photo;
    }
    // API d'inicials si no hi ha foto o el fitxer no existeix
    return "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=random&color=fff&bold=true&size=256";
}

$current_avatar = getAvatar($user['avatar_url'], $user['username']);
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuració - Extagram</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/svg+xml" href="/preview.svg">
    <script>
        // Previsualització en temps real
        function previewAvatar(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('avatar_preview');
                output.src = reader.result;
            }
            reader.readAsDataURL(event.target.files[0]);
            document.getElementById('save_avatar_btn').classList.remove('hidden');
        }
    </script>
</head>
<body class="bg-[#fafafa] text-slate-900 min-h-screen">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-10 p-4 mb-8">
        <div class="max-w-2xl mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold tracking-tight">Configuració</h1>
            <a href="extagram.php" class="text-sm font-semibold text-blue-500 hover:text-slate-900 transition-colors">Tornar a l'inici</a>
        </div>
    </nav>

    <main class="max-w-2xl mx-auto px-4 pb-20">
        
        <div class="bg-white border border-slate-200 rounded-xl p-8 mb-6 shadow-sm text-center">
            <div class="flex flex-col items-center">
                <div class="relative mb-4">
                    <img id="avatar_preview" 
                         src="<?php echo $current_avatar; ?>" 
                         class="w-40 h-40 rounded-full object-cover border-4 border-white shadow-md">
                    
                    <form action="update_profile.php" method="POST" enctype="multipart/form-data" class="mt-6">
                        <label class="cursor-pointer bg-blue-500 text-white px-6 py-2 rounded-full text-xs font-bold shadow-sm hover:bg-blue-600 transition-all block">
                            TRIA UNA NOVA FOTO
                            <input type="file" name="new_avatar" class="hidden" accept="image/*" onchange="previewAvatar(event)">
                        </label>
                        <input type="hidden" name="action" value="update_avatar">
                        <button id="save_avatar_btn" type="submit" class="hidden mt-4 text-sm font-bold text-blue-600 hover:underline animate-bounce">
                            ✓ CLICA AQUÍ PER GUARDAR ELS CANVIS
                        </button>
                    </form>
                </div>
                <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest">Format recomanat: JPG o PNG</p>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-8 mb-6 shadow-sm">
            <h2 class="text-xs font-black text-slate-300 uppercase tracking-[0.2em] mb-8">Dades del compte</h2>
            <form action="update_profile.php" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="update_info">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-2 ml-1">NOM D'USUARI</label>
                    <input type="text" name="username" required
                           value="<?php echo htmlspecialchars($user['username']); ?>" 
                           class="w-full bg-slate-50 border border-slate-200 p-4 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-2 ml-1">NOVA CONTRASENYA</label>
                    <input type="password" name="new_password" placeholder="Deixa-ho en blanc per mantenir l'actual" 
                           class="w-full bg-slate-50 border border-slate-200 p-4 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all">
                </div>

                <button type="submit" class="w-full bg-slate-900 text-white py-4 rounded-xl font-bold hover:bg-black active:scale-[0.98] transition-all shadow-lg">
                    Actualitzar Informació
                </button>
            </form>
        </div>

        <div class="bg-red-50 border border-red-100 rounded-xl p-8 shadow-sm mt-12">
            <h2 class="text-[10px] font-bold text-red-400 uppercase tracking-widest mb-6 text-center">Zona Crítica</h2>
            <div class="flex flex-col sm:flex-row gap-4">
                <form action="update_profile.php" method="POST" class="flex-1" onsubmit="return confirm('Segur? Això esborrarà les teves fotos publicades.')">
                    <input type="hidden" name="action" value="delete_data">
                    <button type="submit" class="w-full bg-white border border-red-100 text-red-400 py-3 rounded-xl text-xs font-bold hover:bg-red-50 transition-all">
                        NETEJAR ELS MEUS POSTS
                    </button>
                </form>

                <form action="update_profile.php" method="POST" class="flex-1" onsubmit="return confirm('ATENCIÓ: Vols esborrar el teu compte per sempre?')">
                    <input type="hidden" name="action" value="delete_account">
                    <button type="submit" class="w-full bg-red-500 text-white py-3 rounded-xl text-xs font-bold hover:bg-red-600 shadow-md transition-all">
                        ELIMINAR COMPTE
                    </button>
                </form>
            </div>
        </div>
    </main>

</body>
</html>
