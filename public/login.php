<?php
// 1. Incluimos conexión DB y Configuración de Google
include 'db_conn.php'; 
require_once 'google_config.php'; 

error_reporting(E_ALL);
ini_set('display_errors', 1);

$msg = "";
$error = "";

// Capturar mensajes de redirección (por ejemplo, desde reset_password.php)
if (isset($_GET['msg']) && $_GET['msg'] == 'password_updated') {
    $msg = "Contrasenya actualitzada! Ja pots entrar.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- LÒGICA DE REGISTRE ---
    if (isset($_POST['register'])) {
        $user = trim($_POST['username'] ?? '');
        $pass_raw = $_POST['password'] ?? '';
        
        if (!empty($user) && !empty($pass_raw)) {
            $pass = password_hash($pass_raw, PASSWORD_DEFAULT);
            $avatar_id = 'default_avatar.png'; 

            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array($ext, $allowed)) {
                    $avatar_id = "avatar_" . uniqid() . "." . $ext;
                    $target_path = "uploads/" . $avatar_id;
                    
                    if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $target_path)) {
                        $avatar_id = 'default_avatar.png';
                    }
                }
            }

            try {
                $stmt = $db->prepare("INSERT INTO users (username, password, avatar_url) VALUES (?, ?, ?)");
                $stmt->execute([$user, $pass, $avatar_id]);
                $msg = "Registre correcte! Ja pots entrar.";
            } catch (PDOException $e) {
                $error = "L'usuari ja existeix o error en la base de dades.";
            }
        } else {
            $error = "Falten camps per omplir.";
        }
    }

    // --- LÒGICA DE LOGIN LOCAL ---
    if (isset($_POST['login'])) {
        $user = trim($_POST['username'] ?? '');
        $pass_raw = $_POST['password'] ?? '';

        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$user]);
        $found_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($found_user && $found_user['password'] && password_verify($pass_raw, $found_user['password'])) {
            session_regenerate_id(true); 
            $_SESSION['user_id'] = $found_user['id'];
            $_SESSION['username'] = $found_user['username'];
            $_SESSION['avatar'] = $found_user['avatar_url'];

            session_write_close();
            header("Location: extagram.php");
            exit();
        } else {
            $error = "Usuari o contrasenya incorrectes.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/preview.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Accés - Extagram</title>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4">

    <div class="bg-white p-8 rounded-xl shadow-lg border border-slate-200 w-full max-w-sm">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold italic text-slate-800">Extagram</h1>
            <p class="text-slate-400 text-xs mt-2">Connecta't per veure fotos dels teus amics</p>
        </div>

        <?php if($msg): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 p-3 rounded-lg text-center mb-4 text-sm">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-lg text-center mb-4 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <a href="<?= $google_login_url ?>" class="flex items-center justify-center w-full bg-white border border-slate-300 text-slate-700 font-bold py-2.5 rounded-lg hover:bg-slate-50 transition-all shadow-sm mb-6 gap-3 active:scale-95 group">
            <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-5 h-5 group-hover:scale-110 transition-transform" alt="Google Logo">
            <span>Continuar amb Google</span>
        </a>

        <div class="relative flex py-2 items-center mb-4">
            <div class="flex-grow border-t border-slate-200"></div>
            <span class="flex-shrink-0 mx-4 text-slate-400 text-[10px] font-bold uppercase tracking-widest">o amb usuari</span>
            <div class="flex-grow border-t border-slate-200"></div>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <input type="text" name="username" placeholder="Nom d'usuari" required 
                       class="w-full bg-slate-50 border border-slate-200 p-3 rounded-lg outline-none focus:bg-white focus:border-blue-400 text-sm transition-colors">
            </div>
            <div>
                <input type="password" name="password" placeholder="Contrasenya" required 
                       class="w-full bg-slate-50 border border-slate-200 p-3 rounded-lg outline-none focus:bg-white focus:border-blue-400 text-sm transition-colors">
            </div>

            <div class="pt-2">
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 ml-1">Foto de perfil (opcional registre)</label>
                <input type="file" name="avatar" accept="image/*" 
                       class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100 cursor-pointer">
            </div>

            <div class="flex flex-col gap-3 pt-2">
                <button type="submit" name="login" class="w-full bg-[#0095f6] hover:bg-[#1877f2] text-white py-2.5 rounded-lg font-bold transition-all shadow-md active:scale-95">
                    Entrar
                </button>
                
                <div class="text-center">
                    <a href="reset_password.php" class="text-blue-900 text-[11px] hover:underline">Has oblidat la contrasenya?</a>
                </div>

                <div class="relative flex py-2 items-center">
                    <div class="flex-grow border-t border-slate-100"></div>
                </div>

                <button type="submit" name="register" class="w-full bg-white border border-slate-300 text-slate-700 py-2.5 rounded-lg font-bold hover:bg-slate-50 transition-all active:scale-95">
                    Registrar-se
                </button>
            </div>
        </form>
    </div>
</body>
</html>
