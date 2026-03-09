<?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('max_execution_time', 60);

ob_start();

if (file_exists('db_conn.php')) require_once 'db_conn.php';
if (session_status() === PHP_SESSION_NONE) session_start();

class AuditEngine {
    const DOCKER_SOCK = '/var/run/docker.sock';

    private function getCPULoad() {
        $stat1 = @file('/proc/stat');
        usleep(50000);
        $stat2 = @file('/proc/stat');
        if (!$stat1 || !$stat2) return 1;
        $info1 = explode(" ", preg_replace("!cpu +!", "", $stat1[0]));
        $info2 = explode(" ", preg_replace("!cpu +!", "", $stat2[0]));
        $dif = [
            'user' => $info2[0] - $info1[0],
            'nice' => $info2[1] - $info1[1],
            'sys'  => $info2[2] - $info1[2],
            'idle' => $info2[3] - $info1[3]
        ];
        $total = array_sum($dif);
        if ($total == 0) return 1;
        $cpu = round(100 * ($total - $dif['idle']) / $total);
        return $cpu < 1 ? "1" : $cpu;
    }

    private function getRAMUsage() {
        if (!is_readable('/proc/meminfo')) return "45";
        $meminfo = file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
        preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $avail);
        if (isset($total[1]) && isset($avail[1])) {
            return round((($total[1] - $avail[1]) / $total[1]) * 100);
        }
        return "45";
    }

    private function getDiskFree() {
        $bytes = @disk_free_space("/");
        return ($bytes !== false) ? round($bytes / 1024 / 1024 / 1024, 1) . "GB" : "Unknown";
    }

    private function getUptime() {
        $uptime = @shell_exec("uptime -p");
        return $uptime ? trim(str_replace('up ', '', $uptime)) : 'Unknown';
    }

    private function queryDocker($endpoint, $method = 'GET') {
        if (!file_exists(self::DOCKER_SOCK)) return false;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, self::DOCKER_SOCK);
        curl_setopt($ch, CURLOPT_URL, "http://localhost" . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: 0']);
        }
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['code' => $httpcode, 'body' => json_decode($response, true) ?: $response];
    }

    public function getDockerList() {
        $res = $this->queryDocker('/containers/json?all=1');
        if (!$res || $res['code'] !== 200 || !is_array($res['body'])) return [];
        $list = [];
        foreach ($res['body'] as $c) {
            $list[] = [
                'id'     => substr($c['Id'], 0, 12),
                'name'   => isset($c['Names'][0]) ? ltrim($c['Names'][0], '/') : 'Unknown',
                'image'  => substr($c['Image'] ?? 'Unknown', 0, 25),
                'state'  => ($c['State'] ?? '') == 'running' ? 'running' : 'exited',
                'status' => $c['Status'] ?? ''
            ];
        }
        return $list;
    }

    public function handleDockerAction($id, $action) {
        $validActions = ['start', 'stop', 'restart'];
        if (!in_array($action, $validActions)) return ['error' => 'Acción inválida'];
        $res = $this->queryDocker("/containers/$id/$action?t=5", 'POST');
        if ($res['code'] >= 200 && $res['code'] < 304) {
            return ['success' => true, 'msg' => "Contenedor actualizado"];
        }
        return ['error' => "Fallo Docker (Código: {$res['code']})"];
    }

    public function getProcesses() {
        $output = @shell_exec('ps aux --sort=-%cpu | awk \'NR>1 {print $2"|"$1"|"$3"|"$4"|"$11}\' | head -n 30');
        if (!$output) return [];
        $list = [];
        foreach (explode("\n", trim($output)) as $line) {
            $parts = explode("|", $line);
            if (count($parts) === 5) {
                $list[] = ['pid' => $parts[0], 'user' => $parts[1], 'cpu' => $parts[2], 'mem' => $parts[3], 'command' => basename($parts[4])];
            }
        }
        return $list;
    }

    public function killProcess($pid) {
        $pid = intval($pid);
        if ($pid <= 1) return ['error' => 'Denegado'];
        @shell_exec("sudo kill -9 $pid 2>&1");
        return ['success' => true];
    }

    public function getStorage() {
        $output = @shell_exec("df -h | grep -E '^/dev/'");
        if (!$output) return [];
        $list = [];
        foreach (explode("\n", trim($output)) as $line) {
            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 6) {
                $list[] = ['fs' => $parts[0], 'size' => $parts[1], 'used' => $parts[2], 'avail' => $parts[3], 'use' => $parts[4], 'mount' => $parts[5]];
            }
        }
        return $list;
    }

    public function readLog($path) {
        if (!is_readable($path)) return ["⚠️ No se puede leer $path (Revisa permisos o instala el servicio)"];
        $lines = array_slice(file($path), -15);
        return array_map('htmlspecialchars', $lines);
    }

    public function executeCommand($cmd) {
        $username = $_SESSION['username'] ?? 'invitado';
        $username = preg_replace('/[^a-zA-Z0-9_-]/', '', $username); 

        $base_homes = getcwd() . '/homes';
        $user_home = $base_homes . '/' . $username;

        if (!is_dir($user_home)) {
            @mkdir($user_home, 0777, true);
            @file_put_contents($user_home . '/bienvenida.txt', "Bienvenido a tu terminal privada, $username.\n");
        }

        if (!isset($_SESSION['cwd']) || strpos($_SESSION['cwd'], $user_home) !== 0) {
            $_SESSION['cwd'] = $user_home;
        }

        $cmd = trim($cmd);
        if (empty($cmd)) return "";

        // Navegación enjaulada segura
        if (preg_match('/^cd\s+(.*)$/', $cmd, $matches)) {
            $new_dir = trim($matches[1]);
            
            if ($new_dir === '~' || $new_dir === '') {
                $target = $user_home; 
            } elseif ($new_dir[0] === '/') {
                $target = realpath($new_dir);
            } else {
                $target = realpath($_SESSION['cwd'] . '/' . $new_dir);
            }

            if ($target && is_dir($target) && strpos($target, $user_home) === 0) {
                $_SESSION['cwd'] = $target;
                return "";
            } else {
                return "bash: cd: $new_dir: Permiso denegado (Restringido a tu home)";
            }
        }

        if (strpos($cmd, 'sudo ') === 0) {
            $allowed_sudo = ['sudo ps', 'sudo df', 'sudo du', 'sudo netstat', 'sudo docker'];
            $is_allowed = false;
            foreach ($allowed_sudo as $allowed) {
                if (strpos($cmd, $allowed) === 0) {
                    $is_allowed = true;
                    break;
                }
            }
            if (!$is_allowed) return "⚠️ Error: Uso de 'sudo' restringido.";
        }

        $original_dir = getcwd();
        if (!@chdir($_SESSION['cwd'])) return "🔒 BLOQUEO DE SEGURIDAD: No se pudo entrar a la jaula.";
        
        // --- ESCUDO CONTRA TOP/NANO (Session Lock) ---
        session_write_close(); 
        
        $output = shell_exec($cmd . " 2>&1");
        @chdir($original_dir);

        return htmlspecialchars($output ?: "(Sin salida)");
    }

    public function getTelemetry() {
        if (session_status() === PHP_SESSION_NONE) @session_start();
        
        return [
            'cpu'    => $this->getCPULoad(),
            'ram'    => $this->getRAMUsage(),
            'disk'   => $this->getDiskFree(),
            'uptime' => $this->getUptime(),
            'ip'     => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1',
            'cwd'    => $_SESSION['cwd'] ?? '~',
            'docker' => $this->getDockerList()
        ];
    }
}

ob_end_clean(); 
header('Content-Type: application/json; charset=utf-8');

try {
    $engine = new AuditEngine();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $req = array_merge($_POST, $input);

        if (isset($req['cmd'])) {
            echo json_encode(['output' => $engine->executeCommand($req['cmd'])]);
            exit;
        }
        
        $d_action = $req['docker_action'] ?? ($req['action'] ?? null);
        $d_id = $req['container_id'] ?? ($req['id'] ?? null);

        if (in_array($d_action, ['start', 'stop', 'restart']) && $d_id) {
            echo json_encode($engine->handleDockerAction($d_id, $d_action));
            exit;
        }

        if (isset($req['action']) && $req['action'] === 'kill_process' && isset($req['pid'])) {
            echo json_encode($engine->killProcess($req['pid']));
            exit;
        }
        echo json_encode(['error' => 'Acción POST no reconocida']);
        exit;
    }

    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'telemetry': echo json_encode($engine->getTelemetry()); break;
            case 'processes': echo json_encode($engine->getProcesses()); break;
            case 'storage': echo json_encode($engine->getStorage()); break;
            case 'fail2ban_logs': echo json_encode($engine->readLog('/var/log/fail2ban.log')); break;
            case 'ufw_logs': echo json_encode($engine->readLog('/var/log/ufw.log')); break;
            default: echo json_encode(['error' => 'Invalid action']);
        }
    } else {
        echo json_encode(['status' => 'AuditEngine V6 Online']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
