<?php
// 1. Connexió a la base de dades i sessió
include 'db_conn.php'; 
session_start();

// Report d'errors per depurar
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- LÒGICA DE REGISTRE ---
    if (isset($_POST['register'])) {
        $user = $_POST['username'] ?? '';
        $pass_raw = $_POST['password'] ?? '';
        
        if (!empty($user) && !empty($pass_raw)) {
            $pass = password_hash($pass_raw, PASSWORD_DEFAULT);
            $avatar_id = 'default_avatar.png'; // Valor per defecte

            // Processar la foto de perfil (Avatar)
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array($ext, $allowed)) {
                    $avatar_id = "avatar_" . uniqid() . "." . $ext;
                    $target_path = '/var/www/extagram/uploads/' . $avatar_id;
                    
                    if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $target_path)) {
                        error_log("Error movent l'avatar a: " . $target_path);
                        $avatar_id = 'default_avatar.png';
                    }
                }
            }

            try {
                $stmt = $db->prepare("INSERT INTO users (username, password, avatar_url) VALUES (?, ?, ?)");
                $stmt->execute([$user, $pass, $avatar_id]);
                $msg = "Registre correcte! Ja pots entrar.";
            } catch (PDOException $e) {
                $error = "L'usuari ja existeix o error a la BD.";
            }
        }
    }

    // --- LÒGICA DE LOGIN ---
    if (isset($_POST['login'])) {
        $user = $_POST['username'] ?? '';
        $pass_raw = $_POST['password'] ?? '';

        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$user]);
        $found_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($found_user && password_verify($pass_raw, $found_user['password'])) {
            $_SESSION['user_id'] = $found_user['id'];
            $_SESSION['username'] = $found_user['username'];
            $_SESSION['avatar'] = $found_user['avatar_url'];
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
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Accés - Extagram</title>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4">

    <div class="bg-white p-8 rounded-xl shadow-lg border border-slate-200 w-full max-w-sm">
        <h1 class="text-3xl font-bold text-center mb-8 italic">Extagram</h1>

        <?php if(isset($msg)) echo "<p class='text-green-600 text-center mb-4'>$msg</p>"; ?>
        <?php if(isset($error)) echo "<p class='text-red-600 text-center mb-4'>$error</p>"; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <input type="text" name="username" placeholder="Usuari" required 
                       class="w-full border border-slate-300 p-2 rounded-lg outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <input type="password" name="password" placeholder="Contrasenya" required 
                       class="w-full border border-slate-300 p-2 rounded-lg outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div class="pt-2">
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Foto de perfil (només per registre)</label>
                <input type="file" name="avatar" accept="image/*" class="text-xs text-slate-500">
            </div>

            <div class="flex flex-col gap-2 pt-4">
                <button type="submit" name="login" class="w-full bg-[#0096f7] text-white py-2 rounded-lg font-bold hover:bg-[#0081d6]">
                    Entrar
                </button>
                <button type="submit" name="register" class="w-full bg-white border border-slate-300 text-slate-700 py-2 rounded-lg font-bold hover:bg-slate-50">
                    Registrar-se
                </button>
            </div>
        </form>
    </div>

</body>
</html>
