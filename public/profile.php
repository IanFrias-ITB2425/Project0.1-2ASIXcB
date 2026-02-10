<?php
// Assegurem que la sessió i la BD estiguin carregades
require_once 'db_conn.php';

// Verificació manual de sessió si no s'utilitza auth_session.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. OBTENIR DADES DE L'USUARI
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// 2. OBTENIR SESSIONS ACTIVES
$s_stmt = $db->prepare("SELECT * FROM active_sessions WHERE user_id = ? ORDER BY last_activity DESC");
$s_stmt->execute([$_SESSION['user_id']]);
$sessions = $s_stmt->fetchAll(PDO::FETCH_ASSOC);

// Helpers per detectar dispositius
function getDeviceName($ua) {
    $os = "Desconegut";
    if (strpos($ua, 'Windows') !== false) $os = "Windows PC";
    elseif (strpos($ua, 'Mac') !== false) $os = "Mac";
    elseif (strpos($ua, 'Android') !== false) $os = "Android";
    elseif (strpos($ua, 'iPhone') !== false) $os = "iPhone";
    elseif (strpos($ua, 'Linux') !== false) $os = "Linux";
    
    $browser = "Navegador";
    if (strpos($ua, 'Chrome') !== false) $browser = "Chrome";
    elseif (strpos($ua, 'Firefox') !== false) $browser = "Firefox";
    elseif (strpos($ua, 'Safari') !== false) $browser = "Safari";
    elseif (strpos($ua, 'Edge') !== false) $browser = "Edge";
    
    return "$os ($browser)";
}

function getAvatar($photo, $username) {
    if (!empty($photo) && filter_var($photo, FILTER_VALIDATE_URL)) {
        return $photo;
    }
    $path = "uploads/" . $photo;
    if (!empty($photo) && file_exists(__DIR__ . "/" . $path)) {
        return $path;
    }
    return "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=random&color=fff&size=128";
}

$current_avatar = getAvatar($user['avatar_url'], $user['username']);
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>El meu Perfil - Extagram</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
<body class="bg-[#fafafa] text-slate-800">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50 backdrop-blur-md bg-opacity-90">
        <div class="max-w-2xl mx-auto px-4 py-3 flex justify-between items-center">
            <a href="extagram.php" class="flex items-center text-slate-600 hover:text-blue-600 transition-colors font-medium">
                <i class="bi bi-arrow-left text-lg mr-2"></i> Tornar al Feed
            </a>
            <span class="font-bold text-lg tracking-tight">Configuració</span>
            <a href="logout.php" class="text-sm font-semibold text-red-500 hover:text-red-700 bg-red-50 px-3 py-1 rounded-full">
                Sortir
            </a>
        </div>
    </nav>

    <main class="max-w-xl mx-auto px-4 py-8 pb-24 space-y-6">

        <?php if(isset($_GET['success'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center shadow-sm">
                <i class="bi bi-check-circle-fill mr-2"></i> <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        <?php if(isset($_GET['error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center shadow-sm">
                <i class="bi bi-exclamation-triangle-fill mr-2"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm text-center relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-blue-400 to-purple-500"></div>
            
            <img id="avatar_preview" src="<?= $current_avatar ?>" class="w-32 h-32 rounded-full mx-auto mb-6 object-cover border-4 border-slate-50 shadow-lg ring-1 ring-slate-200">
            
            <h2 class="text-xl font-bold mb-1"><?= htmlspecialchars($user['username']) ?></h2>
            <p class="text-slate-400 text-sm mb-6"><?= htmlspecialchars($user['email']) ?></p>

            <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_avatar">
                <label class="cursor-pointer inline-flex items-center px-6 py-2 bg-blue-50 text-blue-600 rounded-full text-sm font-bold hover:bg-blue-100 transition-colors">
                    <i class="bi bi-camera mr-2"></i> Canviar Foto
                    <input type="file" name="new_avatar" class="hidden" accept="image/*" onchange="previewAvatar(event)">
                </label>
                <button id="save_avatar_btn" type="submit" class="hidden mt-4 w-full bg-blue-600 text-white py-2 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-md animate-pulse">
                    Desar Nova Foto
                </button>
            </form>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
            <h3 class="text-xs font-black text-slate-400 uppercase mb-6 tracking-widest border-b pb-2">Informació Personal</h3>
            <form action="update_profile.php" method="POST" class="space-y-5">
                <input type="hidden" name="action" value="update_info">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 ml-1">NOM D'USUARI</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required
                           class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl text-sm focus:ring-2 focus:ring-blue-400 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 ml-1">CONTRASENYA (OPCIONAL)</label>
                    <input type="password" name="new_password" placeholder="••••••••"
                           class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl text-sm focus:ring-2 focus:ring-blue-400 outline-none transition-all">
                </div>

                <button type="submit" class="w-full bg-slate-800 text-white py-3 rounded-xl font-bold hover:bg-black transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    Actualitzar Perfil
                </button>
            </form>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
            <div class="bg-amber-50 p-4 border-b border-amber-100 flex items-center justify-between">
                <h3 class="text-amber-800 font-bold text-sm flex items-center">
                    <i class="bi bi-shield-check mr-2"></i> Dispositius Connectats
                </h3>
            </div>
            
            <div class="divide-y divide-slate-100">
                <?php foreach($sessions as $sess): 
                    $is_me = ($sess['session_id'] === session_id());
                ?>
                <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 mr-3">
                            <i class="bi bi-laptop"></i> 
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-700">
                                <?php echo getDeviceName($sess['user_agent']); ?>
                                <?php if($is_me) echo '<span class="ml-2 text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full uppercase tracking-wide">Aquest</span>'; ?>
                            </p>
                            <p class="text-xs text-slate-400 font-mono mt-0.5">
                                <?= $sess['ip_address'] ?> • <?= date('d M H:i', strtotime($sess['last_activity'])); ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if(!$is_me): ?>
                        <form action="update_profile.php" method="POST">
                            <input type="hidden" name="action" value="kill_session">
                            <input type="hidden" name="session_id_to_kill" value="<?php echo $sess['session_id']; ?>">
                            <button class="text-red-500 hover:text-red-700 bg-white border border-red-100 hover:bg-red-50 p-2 rounded-lg text-xs font-bold transition-all" title="Tancar sessió en aquest dispositiu">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-100 text-right">
                <form action="update_profile.php" method="POST" onsubmit="return confirm('N\'estàs segur? Es tancarà la sessió a la resta de dispositius.');">
                    <input type="hidden" name="action" value="logout_all">
                    <button class="text-xs font-bold text-amber-600 hover:text-amber-800 uppercase tracking-wider">
                        Tancar totes les altres sessions
                    </button>
                </form>
            </div>
        </div>

        <div class="border border-red-200 rounded-2xl p-6 bg-red-50/50">
            <h3 class="text-red-600 font-bold text-sm mb-2 flex items-center">
                <i class="bi bi-exclamation-octagon mr-2"></i> Zona de Perill
            </h3>
            <p class="text-xs text-red-400 mb-4">Aquesta acció no es pot desfer. S'esborraran totes les teves fotos i dades.</p>
            <form action="update_profile.php" method="POST" onsubmit="return confirm('ADVERTÈNCIA!\n\nAquesta acció és IRREVERSIBLE.\nEstàs segur que vols eliminar el teu compte?');">
                <input type="hidden" name="action" value="delete_account">
                <button class="w-full bg-white border border-red-200 text-red-500 py-3 rounded-xl text-sm font-bold hover:bg-red-500 hover:text-white transition-all">
                    Eliminar Compte Permanentment
                </button>
            </form>
        </div>

        <div class="text-center text-[10px] text-slate-300 uppercase tracking-widest pt-8">
            Extagram ID: <?= session_id() ?>
        </div>

    </main>
</body>
</html>
