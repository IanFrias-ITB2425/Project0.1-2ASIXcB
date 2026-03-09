<?php
session_start();
require_once 'db_conn.php';

$message = "";
$type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "Les contrasenyes no coincideixen.";
        $type = "error";
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->execute([$hashed, $user['id']]);
            
            // Redirigir al login con mensaje de éxito
            header("Location: login.php?msg=password_updated");
            exit();
        } else {
            $message = "L'usuari no existeix al sistema.";
            $type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Recuperar Contrasenya - Extagram</title>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4">

    <div class="bg-white p-8 rounded-xl shadow-lg border border-slate-200 w-full max-w-sm">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold italic text-slate-800">Extagram</h1>
            <p class="text-slate-500 text-sm font-semibold mt-4">Tens problemes per entrar?</p>
            <p class="text-slate-400 text-xs mt-2">Introdueix el teu usuari i definirem una nova contrasenya per al teu compte.</p>
        </div>

        <?php if($message): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-lg text-center mb-4 text-sm">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <input type="text" name="username" placeholder="Nom d'usuari" required 
                       class="w-full bg-slate-50 border border-slate-200 p-3 rounded-lg outline-none focus:bg-white focus:border-blue-400 text-sm transition-colors">
            </div>
            
            <div class="relative flex py-2 items-center">
                <div class="flex-grow border-t border-slate-100"></div>
            </div>

            <div>
                <input type="password" name="new_password" placeholder="Nova contrasenya" required 
                       class="w-full bg-slate-50 border border-slate-200 p-3 rounded-lg outline-none focus:bg-white focus:border-blue-400 text-sm transition-colors">
            </div>
            <div>
                <input type="password" name="confirm_password" placeholder="Confirma la nova contrasenya" required 
                       class="w-full bg-slate-50 border border-slate-200 p-3 rounded-lg outline-none focus:bg-white focus:border-blue-400 text-sm transition-colors">
            </div>

            <button type="submit" class="w-full bg-[#0095f6] hover:bg-[#1877f2] text-white py-2.5 rounded-lg font-bold transition-all shadow-md active:scale-95 mt-2">
                Actualitzar Contrasenya
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-100 text-center">
            <a href="login.php" class="text-slate-800 text-sm font-bold hover:text-slate-500 transition-colors">
                Tornar al Login
            </a>
        </div>
    </div>

</body>
</html>
