<?php
include 'db_conn.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// FORZAR LECTURA DESDE LA BASE DE DATOS (No de la sesión)
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Si por algún motivo no existe el usuario, fuera
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

function getAvatar($photo, $username) {
    // 1. Si es una URL completa (Google)
    if (!empty($photo) && filter_var($photo, FILTER_VALIDATE_URL)) {
        return $photo;
    }
    // 2. Si es una foto subida localmente
    $path = "uploads/" . $photo;
    if (!empty($photo) && file_exists(__DIR__ . "/" . $path)) {
        return $path;
    }
    // 3. Fallback: Avatar con iniciales
    return "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=random&color=fff";
}

$current_avatar = getAvatar($user['avatar_url'], $user['username']);
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <title>Configuració - Extagram</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function previewAvatar(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('avatar_preview');
                output.src = reader.result;
                document.getElementById('save_avatar_btn').classList.remove('hidden');
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</head>
<body class="bg-[#fafafa]">

    <nav class="bg-white border-b border-slate-200 p-4 mb-8">
        <div class="max-w-2xl mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Configuració</h1>
            <a href="extagram.php" class="text-sm font-semibold text-blue-500">Tornar a l'inici</a>
        </div>
    </nav>

    <main class="max-w-xl mx-auto px-4 pb-20">
        
        <div class="bg-white border border-slate-200 rounded-xl p-8 mb-6 shadow-sm text-center">
            <img id="avatar_preview" src="<?= $current_avatar ?>" class="w-32 h-32 rounded-full mx-auto mb-6 object-cover border-4 border-white shadow-md">
            
            <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_avatar">
                <label class="cursor-pointer bg-blue-500 text-white px-6 py-2 rounded-full text-xs font-bold hover:bg-blue-600">
                    CANVIAR FOTO
                    <input type="file" name="new_avatar" class="hidden" accept="image/*" onchange="previewAvatar(event)">
                </label>
                <button id="save_avatar_btn" type="submit" class="hidden mt-6 block w-full text-blue-600 font-bold animate-pulse">
                    ✓ GUARDAR NOVA FOTO
                </button>
            </form>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-8 shadow-sm">
            <h2 class="text-xs font-black text-slate-400 uppercase mb-6 tracking-widest">Dades del compte</h2>
            <form action="update_profile.php" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="update_info">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-2 ml-1">NOM D'USUARI</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required
                           class="w-full bg-slate-50 border border-slate-200 p-4 rounded-xl text-sm focus:ring-2 focus:ring-blue-400 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-2 ml-1">NOVA CONTRASENYA</label>
                    <input type="password" name="new_password" placeholder="Buit per mantenir l'actual"
                           class="w-full bg-slate-50 border border-slate-200 p-4 rounded-xl text-sm focus:ring-2 focus:ring-blue-400 outline-none">
                </div>

                <button type="submit" class="w-full bg-slate-900 text-white py-4 rounded-xl font-bold hover:bg-black transition-all shadow-lg">
                    Actualitzar Informació
                </button>
            </form>
        </div>

        <div class="mt-8 text-center text-[10px] text-slate-400 uppercase tracking-widest">
            Sessió: <?= session_id() ?> | Nodo: <?= gethostname() ?>
        </div>

    </main>
</body>
</html>
