<?php
/**
 * EXTAGRAM DASHBOARD - TITANIUM EDITION (Fixed v2)
 * Conectado a MySQL (s7_mysql) y Redis (s8_redis)
 */

// 1. INCLUIR CONEXIÓN
require_once 'db_conn.php';

// 2. VERIFICACIÓN DE SEGURIDAD
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

// 3. OBTENER DATOS REALES DEL USUARIO
try {
    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser) {
        session_destroy();
        header("Location: /login.php");
        exit();
    }

    $username = htmlspecialchars($currentUser['username']);
    $role = 'Admin System'; 
    
} catch (PDOException $e) {
    die("Error crítico recuperando perfil: " . $e->getMessage());
}

// 4. TOKEN CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extagram | Command Center</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], mono: ['JetBrains Mono', 'monospace'] },
                    colors: { 
                        gray: { 850: '#1f2937', 900: '#111827', 950: '#030712' },
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
        .glow-text { text-shadow: 0 0 10px rgba(59, 130, 246, 0.5); }
    </style>
</head>
<body class="bg-gray-950 text-gray-300 h-screen flex overflow-hidden font-sans selection:bg-blue-500/30">

    <aside class="w-64 bg-gray-900 border-r border-gray-800 flex flex-col z-20">
        <div class="h-16 flex items-center px-6 border-b border-gray-800 gap-3">
            <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center shadow-[0_0_15px_rgba(37,99,235,0.5)]">
                <i data-lucide="layers" class="text-white w-5 h-5"></i>
            </div>
            <div>
                <h1 class="font-bold text-white tracking-tight">EXTAGRAM</h1>
                <p class="text-[10px] text-blue-400 font-mono tracking-wider">PRODUCTION</p>
            </div>
        </div>

        <div class="p-6 space-y-6 flex-1 overflow-y-auto">
            <div class="bg-gray-800/40 rounded-xl p-4 border border-gray-700/50">
                <h3 class="text-xs font-bold text-gray-400 uppercase mb-3 flex items-center gap-2">
                    <i data-lucide="database" class="w-3 h-3"></i> Realtime DB
                </h3>
                <div class="space-y-2">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-500">MySQL Host</span>
                        <span class="font-mono text-green-400">s7_mysql</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-500">Session</span>
                        <span class="font-mono text-orange-400">s8_redis</span>
                    </div>
                </div>
            </div>

            <nav class="space-y-1">
                <a href="/dashboard.php" class="flex items-center gap-3 px-3 py-2 text-sm font-medium bg-blue-600/10 text-blue-400 border border-blue-600/20 rounded-lg">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
                </a>
                <a href="/" target="_blank" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition">
                    <i data-lucide="external-link" class="w-4 h-4"></i> Ver Web Pública
                </a>
            </nav>
        </div>

        <div class="p-4 border-t border-gray-800 bg-gray-900/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center text-xs font-bold text-white shadow-lg">
                        <?= strtoupper(substr($username, 0, 1)) ?>
                    </div>
                    <div class="leading-none">
                        <p class="text-xs font-bold text-white"><?= $username ?></p>
                        <p class="text-[10px] text-gray-500"><?= $role ?></p>
                    </div>
                </div>
                <a href="/logout.php" class="text-gray-500 hover:text-red-400 transition" title="Cerrar Sesión">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </aside>

    <main class="flex-1 flex flex-col min-w-0 bg-black/20 relative">
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-6 border-b border-gray-800 bg-gray-900/20 backdrop-blur-sm">
            <div class="bg-gray-800/40 border border-gray-700/50 rounded-lg p-4 relative overflow-hidden">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-[10px] font-bold text-gray-400 uppercase">CPU Load</span>
                    <i data-lucide="cpu" class="w-4 h-4 text-blue-500"></i>
                </div>
                <div class="flex items-end gap-2">
                    <span id="cpu-val" class="text-2xl font-bold text-white glow-text">...</span>
                </div>
                <div class="w-full bg-gray-700 h-1 mt-3 rounded-full overflow-hidden">
                    <div id="cpu-bar" class="h-full bg-blue-500 transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>

            <div class="bg-gray-800/40 border border-gray-700/50 rounded-lg p-4 relative overflow-hidden">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-[10px] font-bold text-gray-400 uppercase">RAM Usage</span>
                    <i data-lucide="zap" class="w-4 h-4 text-purple-500"></i>
                </div>
                <div class="flex items-end gap-2">
                    <span id="ram-val" class="text-2xl font-bold text-white glow-text">...</span>
                </div>
                <div class="w-full bg-gray-700 h-1 mt-3 rounded-full overflow-hidden">
                    <div id="ram-bar" class="h-full bg-purple-500 transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>

            <div class="bg-gray-800/40 border border-gray-700/50 rounded-lg p-4 relative overflow-hidden">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-[10px] font-bold text-gray-400 uppercase">Disk Free</span>
                    <i data-lucide="hard-drive" class="w-4 h-4 text-emerald-500"></i>
                </div>
                <div class="flex items-end gap-2">
                    <span id="disk-val" class="text-2xl font-bold text-white glow-text">...</span>
                </div>
                <div class="w-full bg-gray-700 h-1 mt-3 rounded-full overflow-hidden">
                    <div id="disk-bar" class="h-full bg-emerald-500 transition-all duration-500" style="width: 20%"></div>
                </div>
            </div>

            <div class="bg-gray-800/40 border border-gray-700/50 rounded-lg p-4 relative overflow-hidden">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-[10px] font-bold text-gray-400 uppercase">Node IP</span>
                    <i data-lucide="globe" class="w-4 h-4 text-orange-500"></i>
                </div>
                <div class="flex items-end gap-2">
                    <span id="net-ip" class="text-xl font-bold text-white font-mono">Loading...</span>
                </div>
                <div class="mt-2 text-[10px] text-gray-500" id="ssl-status">Checking SSL...</div>
            </div>
        </div>

        <div class="flex-1 flex overflow-hidden">
            
            <div class="flex-1 flex flex-col bg-black border-r border-gray-800 font-mono text-sm">
                <div class="h-8 bg-gray-900 border-b border-gray-800 flex items-center px-3 justify-between">
                    <span class="text-xs text-gray-400 flex items-center gap-2">
                        <i data-lucide="terminal-square" class="w-3 h-3"></i> <?= strtolower($username) ?>@extagram:~
                    </span>
                    <span class="text-[10px] text-green-500 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> CONNECTED
                    </span>
                </div>
                
                <div id="term-output" class="flex-1 p-4 overflow-y-auto text-gray-300 space-y-1">
                    <div class="text-gray-500 mb-4">
                        System ready.<br>
                        DB Connection: <span class="text-green-400">ESTABLISHED</span><br>
                        Logged in as: <span class="text-white"><?= $username ?></span> (<?= $role ?>)
                    </div>
                </div>

                <div class="p-3 bg-gray-900 border-t border-gray-800 flex gap-2">
                    <span id="prompt-cwd" class="text-blue-500 font-bold">➜ ~</span>
                    <input type="text" id="cmd-input" class="w-full bg-transparent border-none outline-none text-white focus:ring-0" placeholder="..." autocomplete="off" autofocus>
                </div>
            </div>

            <div class="w-80 bg-gray-950 flex flex-col border-l border-gray-800">
                <div class="p-2 bg-gray-900 border-b border-gray-800 text-xs font-bold text-gray-400 uppercase text-center">
                    Docker Containers
                </div>
                <div id="docker-list" class="flex-1 overflow-y-auto p-2 space-y-2">
                    <div class="text-center text-xs text-gray-600 mt-10">Waiting for telemetry...</div>
                </div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
        const CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";
        const termOut = document.getElementById('term-output');
        const cmdInput = document.getElementById('cmd-input');

        // --- 1. CORE TELEMETRY ---
        async function fetchStats() {
            try {
                const res = await fetch('AuditEngine.php?action=telemetry');
                if(!res.ok) throw new Error("Network response was not ok"); 
                
                const data = await res.json();

                // 1. UPDATE TEXT VALUES (Mapeo directo al nuevo AuditEngine)
                document.getElementById('cpu-val').innerText = data.cpu; // Antes: data.resources.cpu.load
                document.getElementById('ram-val').innerText = data.ram;
                document.getElementById('disk-val').innerText = data.disk;
                document.getElementById('net-ip').innerText = data.ip;

                // 2. UPDATE BARS (Parseamos porcentajes)
                updateBar('cpu', data.cpu);
                updateBar('ram', data.ram);
                // Para el disco es un valor fijo porque viene en GB, no porcentaje
                document.getElementById('disk-bar').style.width = '30%'; 
                
                // 3. UPDATE DOCKER
                renderDocker(data.docker);
                
                // 4. UPDATE TERMINAL PATH
                document.getElementById('prompt-cwd').innerText = `➜ ${data.cwd}`;

            } catch (e) { 
                console.log("Telemetry waiting:", e); 
            }
        }

        function updateBar(id, valString) {
            const num = parseFloat(valString); 
            if(!isNaN(num)) {
                document.getElementById(`${id}-bar`).style.width = `${num}%`;
            }
        }

        function renderDocker(list) {
            const el = document.getElementById('docker-list');
            el.innerHTML = "";
            
            if(!list || list.length === 0) {
                el.innerHTML = '<div class="text-center text-xs text-gray-500 py-4">No containers</div>';
                return;
            }

            list.forEach(d => {
                // Usamos el estado que viene del PHP ('running' vs 'exited')
                const isUp = (d.state === 'running'); 
                const colorClass = isUp ? 'bg-green-500 shadow-[0_0_5px_lime]' : 'bg-red-500';

                el.innerHTML += `
                    <div class="bg-gray-900 border border-gray-800 p-2 rounded hover:border-gray-600 transition cursor-pointer">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-bold text-gray-300 truncate w-32" title="${d.name}">${d.name}</span>
                            <span class="w-2 h-2 rounded-full ${colorClass}"></span>
                        </div>
                        <div class="text-[10px] text-gray-500 truncate" title="${d.image}">${d.image}</div>
                        <div class="text-[9px] text-gray-600 mt-1">${d.status}</div>
                    </div>`;
            });
        }

        // --- 2. TERMINAL LOGIC ---
        cmdInput.addEventListener('keydown', async (e) => {
            if (e.key === 'Enter') {
                const cmd = cmdInput.value.trim();
                cmdInput.value = '';
                if (!cmd) return;

                if (cmd === 'clear') { termOut.innerHTML = ''; return; }

                const prompt = document.getElementById('prompt-cwd').innerText;
                termOut.innerHTML += `<div class="mt-1"><span class="text-blue-500 font-bold">${prompt}</span> <span class="text-white">${cmd}</span></div>`;

                const fd = new FormData();
                fd.append('cmd', cmd);
                // Enviamos token aunque el engine actual no lo valide estricto (buena práctica)
                fd.append('csrf_token', CSRF_TOKEN);

                try {
                    const res = await fetch('AuditEngine.php', { method: 'POST', body: fd });
                    const json = await res.json();
                    
                    if(json.output) {
                        // Formatear salida (reemplazar saltos de linea)
                        const outputHtml = json.output.replace(/\n/g, '<br>');
                        termOut.innerHTML += `<div class="text-gray-400 pl-2 border-l border-gray-700 mt-1 font-mono text-xs leading-relaxed">${outputHtml}</div>`;
                    }
                } catch(e) {
                    termOut.innerHTML += `<div class="text-red-500">Connection Failed or JSON Error.</div>`;
                }
                
                // Auto-scroll al fondo
                termOut.scrollTop = termOut.scrollHeight;
            }
        });

        // Loop de actualización (cada 2 segundos)
        fetchStats();
        setInterval(fetchStats, 2000);
        
        // Foco siempre en input
        document.querySelector('main').addEventListener('click', (e) => {
             if(e.target.tagName !== 'INPUT' && e.target.tagName !== 'A') cmdInput.focus();
        });
    </script>
</body>
</html>
