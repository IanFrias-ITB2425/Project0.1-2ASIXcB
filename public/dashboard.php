<?php
/**
 * EXTAGRAM DASHBOARD - LIGHT THEME (Extagram Feed Style)
 * Validació de SSL directa i control de contenidors
 */

require_once 'db_conn.php';

// 1. VERIFICACIÓ DE SEGURETAT
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

// 3. LLEGIR CERTIFICAT SSL DIRECTAMENT DES DE L'ARXIU
$sslMsg = "No s'ha trobat el certificat";
$sslColorClass = "bg-slate-100 text-slate-500 border-slate-200";

// Comprovem la ruta del host o la del contenidor
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
                $sslColorClass = "bg-green-100 text-green-700 border-green-200";
            } elseif ($daysLeft > 0) {
                $sslMsg = "Caduca aviat ($daysLeft dies)";
                $sslColorClass = "bg-orange-100 text-orange-700 border-orange-200";
            } else {
                $sslMsg = "Caducat!";
                $sslColorClass = "bg-red-100 text-red-700 border-red-200";
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
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], mono: ['JetBrains Mono', 'monospace'] }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Terminal Scrollbar */
        .terminal-scroll::-webkit-scrollbar-track { background: #0f172a; }
        .terminal-scroll::-webkit-scrollbar-thumb { background: #334155; }

        .tab-content { display: none; }
        .tab-content.active { display: flex; }
        .tab-btn.active { color: #2563eb; border-bottom: 2px solid #3b82f6; background-color: #f8fafc; font-weight: bold; }
        .instagram-card { max-width: 1200px; width: 100%; margin: 0 auto; }
        .btn-icon { transition: all 0.2s; }
        .btn-icon:hover { transform: scale(1.1); color: #3b82f6; }
    </style>
</head>
<body class="bg-[#fafafa] min-h-screen text-slate-900 font-sans selection:bg-blue-200 p-4 sm:p-8">

    <header class="instagram-card flex flex-col sm:flex-row justify-between items-center mb-8 gap-4 px-2">
        <div class="flex items-center space-x-3">
            <div class="bg-white p-2 rounded-xl shadow-sm border border-slate-200">
                <i data-lucide="layers" class="w-6 h-6 text-blue-600"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-800">
                    Extagram <span class="text-blue-500 font-black">Admin</span>
                </h1>
            </div>
        </div>
        
        <nav class="flex items-center space-x-4">
            <a href="/" target="_blank" class="flex items-center space-x-2 bg-white px-4 py-2 rounded-xl border border-slate-200 shadow-sm hover:bg-slate-50 transition text-sm font-bold text-slate-700">
                <i data-lucide="external-link" class="w-4 h-4"></i>
                <span class="hidden xs:inline">Veure Web</span>
            </a>
            
            <div class="flex items-center space-x-2 bg-white pr-4 rounded-full border border-slate-200 shadow-sm">
                <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-500 flex items-center justify-center text-xs font-bold text-white shadow-inner">
                    <?= strtoupper(substr($username, 0, 1)) ?>
                </div>
                <div class="leading-none py-1">
                    <p class="text-sm font-bold text-slate-700">@<?= $username ?></p>
                    <p class="text-[10px] text-slate-500 uppercase tracking-widest"><?= $role ?></p>
                </div>
            </div>
            
            <a href="/logout.php" class="p-2 bg-white rounded-xl border border-slate-200 shadow-sm text-slate-400 hover:text-red-500 hover:border-red-200 transition-all">
                <i data-lucide="log-out" class="w-5 h-5"></i>
            </a>
        </nav>
    </header>

    <div class="instagram-card space-y-6">
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 relative overflow-hidden group">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Càrrega CPU</span>
                    <i data-lucide="cpu" class="w-5 h-5 text-blue-500"></i>
                </div>
                <div class="flex items-end gap-2 mt-2">
                    <span id="cpu-val" class="text-3xl font-black text-slate-800 tracking-tight">...</span>
                </div>
                <div class="w-full bg-slate-100 h-1.5 mt-4 rounded-full overflow-hidden">
                    <div id="cpu-bar" class="h-full bg-blue-500 transition-all duration-500 rounded-full" style="width: 0%"></div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 relative overflow-hidden group">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Ús de RAM</span>
                    <i data-lucide="zap" class="w-5 h-5 text-purple-500"></i>
                </div>
                <div class="flex items-end gap-2 mt-2">
                    <span id="ram-val" class="text-3xl font-black text-slate-800 tracking-tight">...</span>
                </div>
                <div class="w-full bg-slate-100 h-1.5 mt-4 rounded-full overflow-hidden">
                    <div id="ram-bar" class="h-full bg-purple-500 transition-all duration-500 rounded-full" style="width: 0%"></div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 relative overflow-hidden group">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Espai Lliure</span>
                    <i data-lucide="hard-drive" class="w-5 h-5 text-emerald-500"></i>
                </div>
                <div class="flex items-end gap-2 mt-2">
                    <span id="disk-val" class="text-3xl font-black text-slate-800 tracking-tight">...</span>
                </div>
                <div class="w-full bg-slate-100 h-1.5 mt-4 rounded-full overflow-hidden">
                    <div id="disk-bar" class="h-full bg-emerald-500 transition-all duration-500 rounded-full" style="width: 20%"></div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 relative overflow-hidden flex flex-col justify-between">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Xarxa i SSL</span>
                    <i data-lucide="globe" class="w-5 h-5 text-indigo-500"></i>
                </div>
                <div>
                    <span id="net-ip" class="block text-xl font-bold text-slate-800 font-mono tracking-tight mb-2">Carregant...</span>
                    <div class="text-[10px] font-bold px-2.5 py-1 rounded-md inline-flex items-center gap-1 border <?= $sslColorClass ?>">
                        <i data-lucide="lock" class="w-3 h-3"></i>
                        <?= $sslMsg ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-[550px]">
            
            <div class="lg:col-span-2 flex flex-col bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="flex border-b border-slate-200 bg-slate-50 overflow-x-auto select-none px-2 pt-2 gap-1">
                    <button onclick="switchTab('terminal')" id="btn-terminal" class="tab-btn active px-4 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition rounded-t-lg whitespace-nowrap">
                        <i data-lucide="terminal" class="inline w-3.5 h-3.5 mr-1 mb-0.5"></i> Terminal
                    </button>
                    <button onclick="switchTab('fail2ban')" id="btn-fail2ban" class="tab-btn px-4 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition rounded-t-lg whitespace-nowrap">
                        <i data-lucide="shield-alert" class="inline w-3.5 h-3.5 mr-1 mb-0.5 text-red-400"></i> Fail2Ban
                    </button>
                    <button onclick="switchTab('ufw')" id="btn-ufw" class="tab-btn px-4 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition rounded-t-lg whitespace-nowrap">
                        <i data-lucide="lock" class="inline w-3.5 h-3.5 mr-1 mb-0.5 text-orange-400"></i> UFW Logs
                    </button>
                    <button onclick="switchTab('ssh')" id="btn-ssh" class="tab-btn px-4 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition rounded-t-lg whitespace-nowrap">
                        <i data-lucide="key" class="inline w-3.5 h-3.5 mr-1 mb-0.5 text-green-500"></i> SSH Auth
                    </button>
                </div>

                <div id="tab-terminal" class="tab-content active flex-col flex-1 overflow-hidden bg-slate-900 terminal-scroll">
                    <div id="term-output" class="flex-1 p-5 overflow-y-auto text-slate-300 space-y-1 text-sm font-mono">
                        <div class="text-slate-500 mb-4">
                            [Sistema preparat. Mode GUI activat]<br>
                            Connectat com a: <span class="text-white font-bold">@<?= $username ?></span>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-950 border-t border-slate-800 flex gap-3 items-center">
                        <span id="prompt-cwd" class="text-blue-500 font-bold font-mono text-sm">➜ ~</span>
                        <input type="text" id="cmd-input" class="w-full bg-transparent border-none outline-none text-white focus:ring-0 text-sm font-mono" placeholder="Escriu una comanda..." autocomplete="off">
                    </div>
                </div>

                <div id="tab-fail2ban" class="tab-content flex-col flex-1 p-5 overflow-y-auto text-xs space-y-2 bg-slate-50 font-mono text-slate-700">
                    <div class="text-slate-400 italic">Carregant registres de Fail2Ban...</div>
                </div>

                <div id="tab-ufw" class="tab-content flex-col flex-1 p-5 overflow-y-auto text-xs space-y-2 bg-slate-50 font-mono text-slate-700">
                    <div class="text-slate-400 italic">Carregant registres del Tallafocs...</div>
                </div>

                <div id="tab-ssh" class="tab-content flex-col flex-1 p-5 overflow-y-auto text-xs space-y-2 bg-slate-50 font-mono text-slate-700">
                    <div class="text-slate-400 italic">Carregant registres d'SSH...</div>
                </div>
            </div>

            <div class="flex flex-col bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <i data-lucide="box" class="w-4 h-4 text-blue-500"></i>
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-widest">Motor Docker</span>
                    </div>
                    <button onclick="fetchStats()" class="bg-white p-1.5 rounded-lg border border-slate-200 text-slate-400 hover:text-blue-500 hover:border-blue-200 shadow-sm transition active:scale-95" title="Actualitza dades">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
                <div id="docker-list" class="flex-1 overflow-y-auto p-4 space-y-3 bg-white">
                    <div class="text-center text-sm text-slate-400 mt-10 flex flex-col items-center">
                        <i data-lucide="loader" class="w-6 h-6 animate-spin mb-2"></i>
                        Esperant l'estat del motor...
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        lucide.createIcons();
        const CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";
        let currentTab = 'terminal';

        // --- 1. CORE TELEMETRY & TABS ---
        async function fetchStats() {
            try {
                const res = await fetch('AuditEngine.php?action=telemetry');
                if(!res.ok) throw new Error("Error de xarxa"); 
                const data = await res.json();

                document.getElementById('cpu-val').innerText = data.cpu;
                document.getElementById('ram-val').innerText = data.ram;
                document.getElementById('disk-val').innerText = data.disk;
                document.getElementById('net-ip').innerText = data.ip;

                updateBar('cpu', data.cpu);
                updateBar('ram', data.ram);

                // NOTA: El bloc SSL l'hem tret d'aquí perquè ara el genera PHP correctament en carregar la pàgina.

                document.getElementById('prompt-cwd').innerText = `➜ ${data.cwd}`;

                renderDocker(data.docker);
                
                if (currentTab !== 'terminal') loadTabContent(currentTab);

            } catch (e) { console.log("Esperant telemetria:", e); }
        }

        function updateBar(id, valString) {
            const num = parseFloat(valString); 
            if(!isNaN(num)) document.getElementById(`${id}-bar`).style.width = `${num}%`;
        }

        // --- 2. DOCKER GUI RENDER & ACTIONS ---
        function renderDocker(list) {
            const el = document.getElementById('docker-list');
            
            if(!list || list.length === 0) {
                el.innerHTML = '<div class="text-center text-sm text-slate-400 py-6">No s\'han trobat contenidors actius</div>';
                return;
            }

            let html = '';
            list.forEach(d => {
                const isUp = (d.state === 'running'); 
                const colorClass = isUp ? 'bg-green-400 shadow-[0_0_8px_rgba(74,222,128,0.5)]' : 'bg-red-400';
                const statusColor = isUp ? 'text-green-600 bg-green-50' : 'text-red-500 bg-red-50';

                html += `
                    <div class="bg-white border border-slate-200 p-4 rounded-xl shadow-sm hover:border-blue-300 transition-all group">
                        <div class="flex justify-between items-center mb-1.5">
                            <span class="text-sm font-bold text-slate-800 truncate w-40" title="${d.name}">${d.name}</span>
                            <span class="w-2.5 h-2.5 rounded-full ${colorClass}"></span>
                        </div>
                        <div class="text-[11px] text-slate-500 truncate mb-3" title="${d.image}">${d.image}</div>
                        
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md border border-slate-100 ${statusColor}">${d.status}</span>
                        </div>
                        
                        <div class="flex gap-2 pt-3 border-t border-slate-100">
                            <button onclick="dockerCmd('${d.id}', 'start')" class="flex-1 flex justify-center items-center py-1.5 bg-slate-50 hover:bg-green-50 text-slate-600 hover:text-green-600 rounded-lg border border-slate-200 hover:border-green-200 transition" title="Inicia">
                                <i data-lucide="play" class="w-3.5 h-3.5"></i>
                            </button>
                            <button onclick="dockerCmd('${d.id}', 'stop')" class="flex-1 flex justify-center items-center py-1.5 bg-slate-50 hover:bg-red-50 text-slate-600 hover:text-red-600 rounded-lg border border-slate-200 hover:border-red-200 transition" title="Atura">
                                <i data-lucide="square" class="w-3.5 h-3.5"></i>
                            </button>
                            <button onclick="dockerCmd('${d.id}', 'restart')" class="flex-1 flex justify-center items-center py-1.5 bg-slate-50 hover:bg-blue-50 text-slate-600 hover:text-blue-600 rounded-lg border border-slate-200 hover:border-blue-200 transition" title="Reinicia">
                                <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>`;
            });
            el.innerHTML = html;
            lucide.createIcons(); 
        }

        async function dockerCmd(id, action) {
            let actCat = action === 'start' ? 'iniciar' : (action === 'stop' ? 'aturar' : 'reiniciar');
            if(!confirm(`Vols ${actCat} el contenidor?`)) return;
            
            const fd = new FormData();
            fd.append('container_id', id);
            fd.append('docker_action', action);
            fd.append('csrf_token', CSRF_TOKEN);

            try {
                const res = await fetch('AuditEngine.php', { method: 'POST', body: fd });
                const json = await res.json();
                
                if(json.success) {
                    alert(json.msg);
                    fetchStats(); 
                } else {
                    alert(json.error || "Error en executar l'acció");
                }
            } catch(e) { alert("Error de connexió."); }
        }

        // --- 3. TAB SYSTEM & LOGS FETCHING ---
        function switchTab(tabName) {
            currentTab = tabName;
            
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            document.getElementById(`btn-${tabName}`).classList.add('active');
            document.getElementById(`tab-${tabName}`).classList.add('active');

            if (tabName !== 'terminal') loadTabContent(tabName);
        }

        async function loadTabContent(tabName) {
            const endpointMap = {
                'fail2ban': 'fail2ban_logs',
                'ufw': 'ufw_logs',
                'ssh': 'ssh_logs'
            };
            
            if(!endpointMap[tabName]) return;

            try {
                const res = await fetch(`AuditEngine.php?action=${endpointMap[tabName]}`);
                const data = await res.json();
                const container = document.getElementById(`tab-${tabName}`);
                
                if (data.error) {
                    container.innerHTML = `<div class="text-red-500 bg-red-50 border border-red-200 p-3 rounded-lg">${data.error}</div>`;
                    return;
                }

                container.innerHTML = data.map(line => `<div class="border-b border-slate-200/60 pb-1.5 mb-1.5">${line}</div>`).join('');
            } catch(e) { console.error("Error carregant els registres", e); }
        }

        // --- 4. TERMINAL LOGIC ---
        const termOut = document.getElementById('term-output');
        const cmdInput = document.getElementById('cmd-input');

        cmdInput.addEventListener('keydown', async (e) => {
            if (e.key === 'Enter') {
                const cmd = cmdInput.value.trim();
                cmdInput.value = '';
                if (!cmd) return;

                if (cmd === 'clear') { termOut.innerHTML = ''; return; }

                const prompt = document.getElementById('prompt-cwd').innerText;
                termOut.innerHTML += `<div class="mt-3"><span class="text-blue-400 font-bold">${prompt}</span> <span class="text-white">${cmd}</span></div>`;

                const fd = new FormData();
                fd.append('cmd', cmd);
                fd.append('csrf_token', CSRF_TOKEN);

                try {
                    const res = await fetch('AuditEngine.php', { method: 'POST', body: fd });
                    const json = await res.json();
                    
                    if(json.output) {
                        const outputHtml = json.output.replace(/\n/g, '<br>');
                        termOut.innerHTML += `<div class="text-slate-300 pl-3 border-l-2 border-slate-700 mt-2 text-[13px] leading-relaxed overflow-x-auto">${outputHtml}</div>`;
                    }
                } catch(e) {
                    termOut.innerHTML += `<div class="text-red-400 text-xs mt-2">Connexió fallida o error al servidor.</div>`;
                }
                
                termOut.scrollTop = termOut.scrollHeight;
            }
        });

        // Inici automàtic
        fetchStats();
        setInterval(fetchStats, 2000); 
    </script>
</body>
</html>
