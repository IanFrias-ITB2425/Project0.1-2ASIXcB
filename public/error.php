<?php
$code = $_GET['code'] ?? 'Error';
$message = "S'ha produït un error inesperat.";
$description = "Sembla que alguna cosa ha sortit malament als nostres servidors.";

// Personalització segons el codi d'error
switch ($code) {
    case '404':
        $message = "Pàgina no trobada";
        $description = "Ho sentim, la pàgina que busques no existeix o ha estat moguda.";
        break;
    case '403':
        $message = "Accés prohibit";
        $description = "No tens permisos per entrar en aquesta zona del servidor.";
        break;
    case '500':
        $message = "Error intern del servidor";
        $description = "Tenim un problema tècnic. El nostre equip (o l'Apache) està treballant-hi.";
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
    <link rel="icon" type="image/svg+xml" href="/preview.svg">
</head>
<body class="bg-[#fafafa] min-h-screen flex flex-col items-center justify-center p-4 text-slate-900">

    <div class="max-w-md w-full text-center space-y-6">
        <div class="flex justify-center">
            <div class="bg-red-50 p-6 rounded-full">
                <img src="/preview.svg" alt="Error" class="w-16 h-16 opacity-50">
            </div>
        </div>

        <h1 class="text-6xl font-black text-slate-200"><?php echo htmlspecialchars($code); ?></h1>
        <h2 class="text-2xl font-bold text-slate-800"><?php echo htmlspecialchars($message); ?></h2>
        <p class="text-slate-500 text-sm leading-relaxed">
            <?php echo htmlspecialchars($description); ?>
        </p>

        <div class="pt-6">
            <a href="extagram.php" 
               class="bg-[#0096f7] hover:bg-[#0081d6] text-white font-bold py-3 px-8 rounded-lg transition-all inline-block shadow-md active:scale-95">
                Tornar a l'inici
            </a>
        </div>

        <footer class="pt-12 text-[10px] text-slate-400 uppercase tracking-widest font-bold">
        </footer>
    </div>

</body>
</html>
