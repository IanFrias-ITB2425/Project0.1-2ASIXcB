<?php
/**
 * EXTAGRAM DASHBOARD - LIGHT THEME (Extagram Feed Style)
 * Validació de SSL directa, control de contenidors, UX Millorada i Monitorització Avançada
 */

require_once 'db_conn.php';

// 1. VERIFICACIÓ DE SEGURETAT
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

// 2. OBTENIR DADES DE L'USUARI
try {
    global $pdo; 
    $db_conn = isset($pdo) ? $pdo : (isset($db) ? $db : null);

    if ($db_conn) {
        $stmt = $db_conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        throw new Exception("Connexió a BD no trobada al tauler.");
    }

    if (!$currentUser) {
        session_destroy();
        header("Location: /login.php");
        exit();
    }

    $username = htmlspecialchars($currentUser['username']);
    $role = 'Admin System'; 
} catch (Exception $e) {
    die("Error crític recuperant el perfil: " . $e->getMessage());
}

// 3. LLEGIR CERTIFICAT SSL
$sslMsg = "Sense certificat";
$sslColorClass = "bg-slate-100 text-slate-500 border-slate-200";

$certPaths = [
    '/docker/files/etc/letsencrypt/live/g5asixc2bc.com/fullchain.pem',
    '/etc/letsencrypt/live/g5asixc2bc.com/fullchain.pem'
];

foreach ($certPaths as $path) {
    if (file_exists($path)) {
        $certData = openssl_x509_parse(file_get_contents($path));
        if ($certData && isset($certData['validTo_time_t'])) {
            $daysLeft = floor(($certData['validTo_time_t'] - time()) / 86400);
            if ($daysLeft > 30) {
                $sslMsg = "Vàlid ($daysLeft dies)";
                $sslColorClass = "bg-emerald-50 text-emerald-700 border-emerald-200";
            } elseif ($daysLeft > 0) {
                $sslMsg = "Caduca aviat ($daysLeft dies)";
                $sslColorClass = "bg-amber-50 text-amber-700 border-amber-200";
            } else {
                $sslMsg = "Caducat!";
                $sslColorClass = "bg-rose-50 text-rose-700 border-rose-200";
            }
            break;
        }
    }
}

// 4. TOKEN CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extagram | Tauler d'Administració</title>
    <link rel="icon" type="image/svg+xml" href="/preview.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], mono: ['JetBrains Mono', 'monospace'] },
                    animation: {
                        'slide-up': 'slideUp 0.3s ease-out forwards',
                    },
                    keyframes: {
                        slideUp: {
                            '0%': { transform: 'translateY(100%)', opacity: 0 },
                            '100%': { transform: 'translateY(0)', opacity: 1 },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .terminal-scroll::-webkit-scrollbar-track { background: #0f172a; }
        .terminal-scroll::-webkit-scrollbar-thumb { background: #334155; }

        .tab-content { display: none; }
        .tab-content.active { display: flex; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        .tab-btn { position: relative; }
        .tab-btn.active { color: #2563eb; background-color: #ffffff; font-weight: 600; }
        .tab-btn.active::after {
            content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 2px; background: #3b82f6;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 font-sans selection:bg-blue-200 selection:text-blue-900 p-4 sm:p-6 lg:p-8">

    <div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <div class="max-w-7xl mx-auto space-y-6">
        
        <header class="glass-card flex flex-col sm:flex-row justify-between items-center p-4 rounded-2xl shadow-sm border border-slate-200 gap-4">
            <div class="flex items-center space-x-3">
                <div class="bg-blue-50 p-2.5 rounded-xl border border-blue-100">
                    <i data-lucide="layers" class="w-6 h-6 text-blue-600"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-800">
                        Extagram <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-500">Admin</span>
                    </h1>
                </div>
            </div>
            
            <nav class="flex items-center space-x-3">
                <a href="/" target="_blank" class="flex items-center space-x-2 bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-sm hover:bg-slate-50 hover:border-slate-300 transition-all text-sm font-semibold text-slate-700">
                    <i data-lucide="external-link" class="w-4 h-4 text-slate-500"></i>
                    <span class="hidden xs:inline">Veure Web</span>
                </a>
                
                <div class="flex items-center space-x-3 bg-white pr-4 rounded-full border border-slate-200 shadow-sm p-1">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-sm font-bold text-white shadow-inner">
                        <?= strtoupper(substr($username, 0, 1)) ?>
                    </div>
                    <div class="leading-tight py-1">
                        <p class="text-sm font-bold text-slate-800">@<?= $username ?></p>
                        <p class="text-[10px] text-blue-600 font-semibold uppercase tracking-wider"><?= $role ?></p>
                    </div>
                </div>
                
                <a href="/logout.php" class="p-2.5 bg-white rounded-xl border border-slate-200 shadow-sm text-slate-400 hover:text-rose-500 hover:bg-rose-50 hover:border-rose-200 transition-all" title="Tancar Sessió">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                </a>
            </nav>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="absolute -right-6 -top-6 bg-blue-50 w-24 h-24 rounded-full opacity-50 group-hover:scale-110 transition-transform"></div>
                <div class="flex justify-between items-start mb-2 relative">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Càrrega CPU</span>
                    <i data-lucide="cpu" class="w-5 h-5 text-blue-500"></i>
                </div>
                <div class="flex items-end gap-2 mt-2 relative">
                    <span id="cpu-val" class="text-3xl font-black text-slate-800 tracking-tight">--%</span>
                </div>
                <div class="w-full bg-slate-100 h-2 mt-4 rounded-full overflow-hidden relative">
                    <div id="cpu-bar" class="h-full bg-gradient-to-r from-blue-400 to-blue-600 transition-all duration-500 rounded-full" style="width: 0%"></div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="absolute -right-6 -top-6 bg-purple-50 w-24 h-24 rounded-full opacity-50 group-hover:scale-110 transition-transform"></div>
                <div class="flex justify-between items-start mb-2 relative">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Ús de RAM</span>
                    <i data-lucide="zap" class="w-5 h-5 text-purple-500"></i>
                </div>
                <div class="flex items-end gap-2 mt-2 relative">
                    <span id="ram-val" class="text-3xl font-black text-slate-800 tracking-tight">--%</span>
                </div>
                <div class="w-full bg-slate-100 h-2 mt-4 rounded-full overflow-hidden relative">
                    <div id="ram-bar" class="h-full bg-gradient-to-r from-purple-400 to-purple-600 transition-all duration-500 rounded-full" style="width: 0%"></div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="absolute -right-6 -top-6 bg-emerald-50 w-24 h-24 rounded-full opacity-50 group-hover:scale-110 transition-transform"></div>
                <div class="flex justify-between items-start mb-2 relative">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Espai Lliure</span>
                    <i data-lucide="hard-drive" class="w-5 h-5 text-emerald-500"></i>
                </div>
                <div class="flex items-end gap-2 mt-2 relative">
                    <span id="disk-val" class="text-3xl font-black text-slate-800 tracking-tight">--</span>
                </div>
                <div class="w-full bg-slate-100 h-2 mt-4 rounded-full overflow-hidden relative">
                    <div id="disk-bar" class="h-full bg-gradient-to-r from-emerald-400 to-emerald-600 transition-all duration-500 rounded-full" style="width: 20%"></div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 relative overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="absolute -right-6 -top-6 bg-indigo-50 w-24 h-24 rounded-full opacity-50"></div>
                <div class="flex justify-between items-start mb-2 relative">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Xarxa i SSL</span>
                    <i data-lucide="globe" class="w-5 h-5 text-indigo-500"></i>
                </div>
                <div class="relative z-10">
                    <span id="net-ip" class="block text-xl font-bold text-slate-800 font-mono tracking-tight mb-2">---.---.---.---</span>
                    <div class="text-[11px] font-bold px-2.5 py-1 rounded-lg inline-flex items-center gap-1.5 border <?= $sslColorClass ?>">
                        <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                        <?= $sslMsg ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-[600px]">
            
            <div class="lg:col-span-2 flex flex-col bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="flex border-b border-slate-200 bg-slate-50/80 overflow-x-auto select-none px-2 pt-2 gap-1 backdrop-blur-sm">
                    <button onclick="switchTab('terminal')" id="btn-terminal" class="tab-btn active px-4 py-3 text-[13px] font-medium text-slate-600 hover:text-slate-900 transition-colors rounded-t-xl whitespace-nowrap flex items-center gap-2">
                        <i data-lucide="terminal" class="w-4 h-4"></i> Terminal
                    </button>
                    <button onclick="switchTab('processes')" id="btn-processes" class="tab-btn px-4 py-3 text-[13px] font-medium text-slate-600 hover:text-slate-900 transition-colors rounded-t-xl whitespace-nowrap flex items-center gap-2">
                        <i data-lucide="activity" class="w-4 h-4 text-purple-500"></i> Processos
                    </button>
                    <button onclick="switchTab('storage')" id="btn-storage" class="tab-btn px-4 py-3 text-[13px] font-medium text-slate-600 hover:text-slate-900 transition-colors rounded-t-xl whitespace-nowrap flex items-center gap-2">
                        <i data-lucide="database" class="w-4 h-4 text-blue-500"></i> Discos
                    </button>
                    <button onclick="switchTab('fail2ban')" id="btn-fail2ban" class="tab-btn px-4 py-3 text-[13px] font-medium text-slate-600 hover:text-slate-900 transition-colors rounded-t-xl whitespace-nowrap flex items-center gap-2">
                        <i data-lucide="shield-alert" class="w-4 h-4 text-rose-500"></i> Fail2Ban
                    </button>
                    <button onclick="switchTab('ufw')" id="btn-ufw" class="tab-btn px-4 py-3 text-[13px] font-medium text-slate-600 hover:text-slate-900 transition-colors rounded-t-xl whitespace-nowrap flex items-center gap-2">
                        <i data-lucide="lock" class="w-4 h-4 text-amber-500"></i> UFW
                    </button>
                </div>

                <div id="tab-terminal" class="tab-content active flex-col flex-1 overflow-hidden bg-[#0f172a] terminal-scroll relative">
                    <div class="h-8 bg-slate-800/50 flex items-center px-4 gap-2 w-full border-b border-slate-800">
                        <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                        <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        <div class="mx-auto text-[10px] text-slate-400 font-mono tracking-wider">bash - extagram-admin</div>
                    </div>
                    
                    <div id="term-output" class="flex-1 p-5 overflow-y-auto text-slate-300 space-y-1.5 text-[13px] font-mono leading-relaxed">
                        <div class="text-slate-400 mb-4 bg-slate-800/50 p-3 rounded-lg border border-slate-700/50">
                            > Sistema preparat. Mode GUI interactiu establert.<br>
                            > Sessió segura iniciada com a: <span class="text-emerald-400 font-bold">@<?= $username ?></span>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-900/90 border-t border-slate-800 flex gap-3 items-center backdrop-blur-md">
                        <span id="prompt-cwd" class="text-blue-400 font-bold font-mono text-sm">➜ ~</span>
                        <input type="text" id="cmd-input" class="w-full bg-transparent border-none outline-none text-slate-100 focus:ring-0 text-[13px] font-mono placeholder-slate-600" placeholder="Escriu una comanda (ex: ls -la)..." autocomplete="off" spellcheck="false">
                    </div>
                </div>

                <div id="tab-processes" class="tab-content flex-col flex-1 p-0 overflow-hidden bg-white">
                    <div class="p-3 bg-slate-50 border-b border-slate-200 flex justify-between items-center text-xs font-bold text-slate-500 uppercase">
                        <span class="flex items-center gap-2"><i data-lucide="server" class="w-4 h-4"></i> Gestor de Processos</span>
                        <span id="uptime-display" class="normal-case text-indigo-600 font-mono bg-indigo-50 px-2 py-1 rounded border border-indigo-100">Uptime: Calculant...</span>
                    </div>
                    <div class="flex-1 overflow-y-auto p-0 terminal-scroll">
                        <table class="w-full text-left text-[13px] font-mono">
                            <thead class="bg-slate-100 sticky top-0 text-slate-600 shadow-sm z-10">
                                <tr>
                                    <th class="p-3">PID</th>
                                    <th class="p-3">USUARI</th>
                                    <th class="p-3">CPU%</th>
                                    <th class="p-3">RAM%</th>
                                    <th class="p-3">COMANDA</th>
                                    <th class="p-3 text-right">ACCIÓ</th>
                                </tr>
                            </thead>
                            <tbody id="processes-list" class="divide-y divide-slate-100 text-slate-700">
                                <tr><td colspan="6" class="p-4 text-center text-slate-400 italic">Carregant processos...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="tab-storage" class="tab-content flex-col flex-1 p-0 overflow-hidden bg-white">
                    <div class="p-3 bg-slate-50 border-b border-slate-200 flex items-center gap-2 text-xs font-bold text-slate-500 uppercase">
                        <i data-lucide="hard-drive" class="w-4 h-4"></i> Sistema de Fitxers
                    </div>
                    <div class="flex-1 overflow-y-auto p-0 terminal-scroll">
                        <table class="w-full text-left text-[13px] font-mono">
                            <thead class="bg-slate-100 sticky top-0 text-slate-600 shadow-sm z-10">
                                <tr>
                                    <th class="p-3">SISTEMA</th>
                                    <th class="p-3">MIDA</th>
                                    <th class="p-3">USAT</th>
                                    <th class="p-3">LLIURE</th>
                                    <th class="p-3">ÚS%</th>
                                    <th class="p-3">MUNTAT A</th>
                                </tr>
                            </thead>
                            <tbody id="storage-list" class="divide-y divide-slate-100 text-slate-700">
                                <tr><td colspan="6" class="p-4 text-center text-slate-400 italic">Carregant discos...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="tab-fail2ban" class="tab-content flex-col flex-1 p-5 overflow-y-auto text-[13px] space-y-2 bg-slate-50 font-mono text-slate-700">
                    <div class="flex items-center gap-2 text-slate-400 italic"><i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Carregant registres de Fail2Ban...</div>
                </div>

                <div id="tab-ufw" class="tab-content flex-col flex-1 p-5 overflow-y-auto text-[13px] space-y-2 bg-slate-50 font-mono text-slate-700">
                    <div class="flex items-center gap-2 text-slate-400 italic"><i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Carregant registres del Tallafocs...</div>
                </div>
            </div>

            <div class="flex flex-col bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-4 bg-slate-50/80 border-b border-slate-200 flex justify-between items-center backdrop-blur-sm">
                    <div class="flex items-center gap-2.5">
                        <div class="bg-blue-100 p-1.5 rounded-lg">
                            <i data-lucide="box" class="w-4 h-4 text-blue-600"></i>
                        </div>
                        <span class="text-[13px] font-bold text-slate-800 uppercase tracking-widest">Motor Docker</span>
                    </div>
                    <button onclick="fetchStats(true)" class="bg-white p-2 rounded-xl border border-slate-200 text-slate-500 hover:text-blue-600 hover:border-blue-300 shadow-sm transition-all active:scale-95 group" title="Força actualització">
                        <i data-lucide="refresh-cw" class="w-4 h-4 group-hover:rotate-180 transition-transform duration-500"></i>
                    </button>
                </div>
                <div id="docker-list" class="flex-1 overflow-y-auto p-4 space-y-3 bg-white">
                    <div class="text-center text-sm text-slate-400 mt-12 flex flex-col items-center">
                        <i data-lucide="loader" class="w-8 h-8 animate-spin mb-3 text-blue-500"></i>
                        Connectant amb el motor...
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        lucide.createIcons();
        const CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";
        let currentTab = 'terminal';
        let isFetchingDocker = false; 
        let lastDockerStateStr = ''; 

        // LLISTA BLANCA DE PROCESSOS INTOCABLES (Anti-Suïcidi)
        const CRITICAL_PROCESSES = ['nginx', 'php', 'docker', 'mysql', 'mysqld', 'sshd', 'systemd', 'fail2ban', 'bash'];

        // --- SISTEMA DE TOASTS ---
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            let bgClass = 'bg-slate-800';
            let icon = 'info';
            
            if (type === 'success') { bgClass = 'bg-emerald-600'; icon = 'check-circle'; }
            if (type === 'error') { bgClass = 'bg-rose-600'; icon = 'alert-circle'; }
            if (type === 'warning') { bgClass = 'bg-amber-500'; icon = 'alert-triangle'; }

            toast.className = `${bgClass} text-white px-4 py-3 rounded-xl shadow-lg flex items-center gap-3 text-sm font-medium animate-slide-up pointer-events-auto`;
            toast.innerHTML = `<i data-lucide="${icon}" class="w-5 h-5"></i> <span>${message}</span>`;
            
            container.appendChild(toast);
            lucide.createIcons({ root: toast });

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(100%)';
                toast.style.transition = 'all 0.3s ease-out';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // --- TELEMETRIA I ACTUALITZACIONS ---
        async function fetchStats(forceRender = false) {
            try {
                const res = await fetch('AuditEngine.php?action=telemetry');
                if(!res.ok) return; 
                const data = await res.json();

                // Dades Generals
                document.getElementById('cpu-val').innerText = data.cpu + '%';
                document.getElementById('ram-val').innerText = data.ram + '%';
                document.getElementById('disk-val').innerText = data.disk;
                document.getElementById('net-ip').innerText = data.ip || 'Local';
                
                if(data.uptime) document.getElementById('uptime-display').innerText = `Uptime: ${data.uptime}`;

                updateBar('cpu', data.cpu);
                updateBar('ram', data.ram);
                document.getElementById('prompt-cwd').innerText = `➜ ${data.cwd || '~'}`;

                // Docker 
                const currentDockerStateStr = JSON.stringify(data.docker);
                if (forceRender || currentDockerStateStr !== lastDockerStateStr) {
                    renderDocker(data.docker);
                    lastDockerStateStr = currentDockerStateStr;
                }
                
                // Actualitzar contingut de pestanyes
                if (currentTab !== 'terminal' && forceRender) loadTabContent(currentTab);

            } catch (e) { 
                console.log("Error de fons en telemetria:", e); 
            }
        }

        function updateBar(id, valString) {
            const num = parseFloat(valString); 
            if(!isNaN(num)) document.getElementById(`${id}-bar`).style.width = `${num}%`;
        }

        // --- RENDERITZAT DE DOCKER GUI ---
        function renderDocker(list) {
            const el = document.getElementById('docker-list');
            if(!list || list.length === 0) {
                el.innerHTML = '<div class="text-center text-sm text-slate-400 py-10 bg-slate-50 rounded-xl border border-slate-100">No s\'han trobat contenidors actius</div>';
                return;
            }

            let html = '';
            list.forEach(d => {
                const isUp = (d.state === 'running'); 
                const indicatorClass = isUp ? 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.6)]' : 'bg-rose-400';
                const statusColor = isUp ? 'text-emerald-700 bg-emerald-50 border-emerald-100' : 'text-rose-700 bg-rose-50 border-rose-100';

                html += `
                    <div class="bg-white border border-slate-200 p-4 rounded-xl shadow-sm hover:shadow-md hover:border-blue-300 transition-all group relative overflow-hidden">
                        ${isUp ? '<div class="absolute top-0 left-0 w-1 h-full bg-emerald-400"></div>' : '<div class="absolute top-0 left-0 w-1 h-full bg-rose-400"></div>'}
                        <div class="flex justify-between items-center mb-1.5 pl-2">
                            <span class="text-[13px] font-bold text-slate-800 truncate pr-2" title="${d.name}">${d.name}</span>
                            <span class="w-2.5 h-2.5 rounded-full ${indicatorClass} flex-shrink-0"></span>
                        </div>
                        <div class="text-[11px] text-slate-500 truncate mb-3 pl-2 font-mono bg-slate-50 py-1 px-2 rounded" title="${d.image}">${d.image}</div>
                        <div class="flex items-center justify-between mb-4 pl-2">
                            <span class="text-[10px] font-bold px-2.5 py-1 rounded-lg border ${statusColor}">${d.status}</span>
                        </div>
                        <div class="flex gap-2 pt-3 border-t border-slate-100 ml-2">
                            <button onclick="dockerCmd(this, '${d.id}', 'start')" class="flex-1 flex justify-center items-center py-2 bg-slate-50 hover:bg-emerald-50 text-slate-600 hover:text-emerald-600 rounded-lg border border-slate-200 hover:border-emerald-200 transition-colors disabled:opacity-50" title="Inicia"><i data-lucide="play" class="w-4 h-4"></i></button>
                            <button onclick="dockerCmd(this, '${d.id}', 'stop')" class="flex-1 flex justify-center items-center py-2 bg-slate-50 hover:bg-rose-50 text-slate-600 hover:text-rose-600 rounded-lg border border-slate-200 hover:border-rose-200 transition-colors disabled:opacity-50" title="Atura"><i data-lucide="square" class="w-4 h-4"></i></button>
                            <button onclick="dockerCmd(this, '${d.id}', 'restart')" class="flex-1 flex justify-center items-center py-2 bg-slate-50 hover:bg-blue-50 text-slate-600 hover:text-blue-600 rounded-lg border border-slate-200 hover:border-blue-200 transition-colors disabled:opacity-50" title="Reinicia"><i data-lucide="rotate-cw" class="w-4 h-4"></i></button>
                        </div>
                    </div>`;
            });
            el.innerHTML = html;
            lucide.createIcons(); 
        }

        async function dockerCmd(btnElement, id, action) {
            let actCat = action === 'start' ? 'iniciar' : (action === 'stop' ? 'aturar' : 'reiniciar');
            const originalIcon = btnElement.innerHTML;
            btnElement.disabled = true;
            btnElement.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i>';
            lucide.createIcons({ root: btnElement });
            
            const fd = new FormData();
            fd.append('container_id', id);
            fd.append('docker_action', action);
            fd.append('csrf_token', CSRF_TOKEN);

            try {
                const res = await fetch('AuditEngine.php', { method: 'POST', body: fd });
                const json = await res.json();
                if(json.success) {
                    showToast(json.msg || `Contenidor ${actCat} correctament.`, 'success');
                    fetchStats(true);
                } else {
                    showToast(json.error || `Error a l'intentar ${actCat} el contenidor.`, 'error');
                    btnElement.disabled = false;
                    btnElement.innerHTML = originalIcon;
                }
            } catch(e) { 
                showToast("Error de connexió amb el servidor.", 'error');
                btnElement.disabled = false;
                btnElement.innerHTML = originalIcon;
            }
        }

        // --- MATAR PROCESSOS (AMB SEGURETAT) ---
        async function killProcess(pid, command) {
            // Verificar si és un procés crític
            const isCritical = CRITICAL_PROCESSES.some(safeWord => command.toLowerCase().includes(safeWord));
            
            if (isCritical) {
                showToast(`Acció bloquejada per seguretat: No pots aturar processos relacionats amb '${command}'.`, 'warning');
                return;
            }

            if (!confirm(`Estàs totalment segur que vols aturar el procés PID ${pid} (${command})?`)) return;

            const fd = new FormData();
            fd.append('action', 'kill_process');
            fd.append('pid', pid);
            fd.append('csrf_token', CSRF_TOKEN);

            try {
                const res = await fetch('AuditEngine.php', { method: 'POST', body: fd });
                const json = await res.json();
                
                if (json.success) {
                    showToast(`Procés ${pid} aturat amb èxit.`, 'success');
                    loadTabContent('processes'); // Recarregar taula
                } else {
                    showToast(json.error || "No s'ha pogut aturar el procés.", 'error');
                }
            } catch(e) {
                showToast("Error de connexió matant el procés.", 'error');
            }
        }

        // --- SISTEMA DE PESTANYES I REGISTRES ---
        function switchTab(tabName) {
            currentTab = tabName;
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            document.getElementById(`btn-${tabName}`).classList.add('active');
            document.getElementById(`tab-${tabName}`).classList.add('active');

            if (tabName !== 'terminal') loadTabContent(tabName);
        }

        async function loadTabContent(tabName) {
            try {
                if (tabName === 'processes') {
                    const res = await fetch(`AuditEngine.php?action=processes`);
                    const data = await res.json();
                    let html = '';
                    if(data.error) html = `<tr><td colspan="6" class="p-4 text-rose-500">${data.error}</td></tr>`;
                    else {
                        data.forEach(p => {
                            // Bloquejar botó visualment si és crític
                            const isCritical = CRITICAL_PROCESSES.some(sw => p.command.toLowerCase().includes(sw));
                            const btnHtml = isCritical 
                                ? `<button disabled class="p-1.5 bg-slate-100 text-slate-400 rounded cursor-not-allowed" title="Protegit pel sistema"><i data-lucide="shield" class="w-4 h-4"></i></button>`
                                : `<button onclick="killProcess('${p.pid}', '${p.command}')" class="p-1.5 bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white rounded transition-colors" title="Matar (Kill)"><i data-lucide="x-circle" class="w-4 h-4"></i></button>`;

                            html += `
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="p-3 font-bold text-slate-800">${p.pid}</td>
                                    <td class="p-3">${p.user}</td>
                                    <td class="p-3"><span class="${parseFloat(p.cpu) > 50 ? 'text-rose-500 font-bold' : ''}">${p.cpu}%</span></td>
                                    <td class="p-3">${p.mem}%</td>
                                    <td class="p-3 text-slate-500 truncate max-w-xs" title="${p.command}">${p.command}</td>
                                    <td class="p-3 text-right">${btnHtml}</td>
                                </tr>
                            `;
                        });
                    }
                    document.getElementById('processes-list').innerHTML = html;
                    lucide.createIcons();
                    return;
                }

                if (tabName === 'storage') {
                    const res = await fetch(`AuditEngine.php?action=storage`);
                    const data = await res.json();
                    let html = '';
                    if(data.error) html = `<tr><td colspan="6" class="p-4 text-rose-500">${data.error}</td></tr>`;
                    else {
                        data.forEach(d => {
                            const useNum = parseInt(d.use.replace('%',''));
                            const colorClass = useNum > 85 ? 'text-rose-500 font-bold' : (useNum > 70 ? 'text-amber-500 font-bold' : 'text-emerald-600');
                            html += `
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="p-3 font-bold text-slate-700">${d.fs}</td>
                                    <td class="p-3">${d.size}</td>
                                    <td class="p-3">${d.used}</td>
                                    <td class="p-3">${d.avail}</td>
                                    <td class="p-3 ${colorClass}">${d.use}</td>
                                    <td class="p-3 text-slate-500">${d.mount}</td>
                                </tr>
                            `;
                        });
                    }
                    document.getElementById('storage-list').innerHTML = html;
                    return;
                }

                // Els teus antics logs (Fail2ban, UFW...)
                const endpointMap = { 'fail2ban': 'fail2ban_logs', 'ufw': 'ufw_logs' };
                if(!endpointMap[tabName]) return;
                const container = document.getElementById(`tab-${tabName}`);
                
                const res = await fetch(`AuditEngine.php?action=${endpointMap[tabName]}`);
                const data = await res.json();
                if (data.error) {
                    container.innerHTML = `<div class="text-rose-600 bg-rose-50 border border-rose-200 p-4 rounded-xl flex items-center gap-2"><i data-lucide="alert-triangle" class="w-5 h-5"></i> ${data.error}</div>`;
                    lucide.createIcons({ root: container });
                    return;
                }
                if (data.length === 0) {
                    container.innerHTML = `<div class="text-slate-400 italic p-4 text-center">No hi ha entrades recents.</div>`;
                    return;
                }
                container.innerHTML = data.map(line => `<div class="border-b border-slate-200/60 pb-2 mb-2 hover:bg-slate-100/50 p-1 rounded transition-colors">${line}</div>`).join('');
            } catch(e) { 
                console.error(e);
            }
        }

        // --- LÒGICA DE LA TERMINAL ---
        const termOut = document.getElementById('term-output');
        const cmdInput = document.getElementById('cmd-input');

        cmdInput.addEventListener('keydown', async (e) => {
            if (e.key === 'Enter') {
                const cmd = cmdInput.value.trim();
                cmdInput.value = '';
                if (!cmd) return;

                if (cmd.toLowerCase() === 'clear') { 
                    termOut.innerHTML = ''; 
                    return; 
                }

                const prompt = document.getElementById('prompt-cwd').innerText;
                termOut.innerHTML += `<div class="mt-4 flex gap-2"><span class="text-blue-400 font-bold shrink-0">${prompt}</span> <span class="text-slate-100 font-medium">${cmd}</span></div>`;
                termOut.scrollTop = termOut.scrollHeight;

                const fd = new FormData();
                fd.append('cmd', cmd);
                fd.append('csrf_token', CSRF_TOKEN);

                try {
                    const res = await fetch('AuditEngine.php', { method: 'POST', body: fd });
                    const json = await res.json();
                    if(json.output) {
                        const outputHtml = json.output.replace(/\n/g, '<br>');
                        termOut.innerHTML += `<div class="text-slate-300 pl-4 border-l-2 border-slate-700/50 mt-2 py-1 text-[13px] leading-relaxed overflow-x-auto whitespace-pre-wrap">${outputHtml}</div>`;
                    } else if (json.error) {
                        termOut.innerHTML += `<div class="text-rose-400 text-[13px] mt-2 pl-4 border-l-2 border-rose-900/50">${json.error}</div>`;
                    }
                } catch(e) {
                    termOut.innerHTML += `<div class="text-rose-400 text-[13px] mt-2 pl-4 border-l-2 border-rose-900/50">Error d'execució.</div>`;
                }
                termOut.scrollTop = termOut.scrollHeight;
            }
        });

        document.getElementById('tab-terminal').addEventListener('click', () => { cmdInput.focus(); });

        fetchStats(true);
        setInterval(() => fetchStats(false), 3000);
    </script>
</body>
</html>
