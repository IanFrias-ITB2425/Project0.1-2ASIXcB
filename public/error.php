<?php
// Detectem el codi d'error real enviat per Nginx
$code = $_SERVER['REDIRECT_STATUS'] ?? $_GET['code'] ?? '404';
$message = "S'ha produït un error inesperat.";
$description = "Sembla que alguna cosa ha sortit malament als nostres servidors.";

switch ($code) {
    case '404':
        $message = "Pàgina no trobada";
        $description = "Ho sentim, la pàgina que busques no existeix o ha estat moguda.";
        break;
    case '403':
        $message = "Accés prohibit";
        $description = "No tens permisos per entrar en aquesta zona.";
        break;
    case '500':
        $message = "Error intern";
        $description = "Tenim un problema tècnic als nodes de processament.";
        break;
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $code; ?> - Extagram</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#fafafa] min-h-screen flex flex-col items-center justify-center p-4 text-slate-900">

    <div class="max-w-md w-full text-center space-y-6">
        <div class="flex justify-center mb-4">
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <i data-lucide="frown" class="w-16 h-16 text-slate-300"></i>
            </div>
        </div>

        <h1 class="text-8xl font-black text-slate-100"><?php echo htmlspecialchars($code); ?></h1>
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight"><?php echo htmlspecialchars($message); ?></h2>
        <p class="text-slate-500 text-sm leading-relaxed max-w-[280px] mx-auto">
            <?php echo htmlspecialchars($description); ?>
        </p>

        <div class="pt-6">
            <a href="extagram.php" 
               class="bg-[#0095f6] hover:bg-[#1877f2] text-white font-bold py-2.5 px-8 rounded-xl transition-all inline-block shadow-sm active:scale-95 text-sm">
                Tornar a l'inici
            </a>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
