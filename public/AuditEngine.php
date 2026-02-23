<?php
/**
 * ============================================================================
 * EXTAGRAM AUDIT ENGINE - ENTERPRISE EDITION
 * ============================================================================
 * * Core Backend para Monitorización de Servidor, Docker y Seguridad.
 * * @author  Alberto Trujillo
 * @version 4.1 (UFW & SSL Hotfix)
 */

// 1. CONFIGURACIÓN DEL ENTORNO
// ----------------------------------------------------------------------------
error_reporting(0);
ini_set('display_errors', 0);
ini_set('max_execution_time', 15);

ob_start();

require_once 'db_conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Clase principal del Motor de Auditoría
 */
class AuditEngine {

    const DOCKER_SOCK  = 'unix:///var/run/docker.sock';
    // Dejamos esto vacío para que el glob() busque el cert dinámicamente
    const SSL_PATH     = ''; 
    const LOG_AUTH     = '/var/log/auth.log';
    const LOG_FAIL2BAN = '/var/log/fail2ban.log';
    // [FIX] Cambiamos la ruta a syslog, donde UFW escribe realmente
    const LOG_UFW      = '/var/log/syslog';

    private $db;

    public function __construct() {
        global $pdo; 
        $this->db = $pdo;
    }

    // ========================================================================
    // SECCIÓN 1: MONITORIZACIÓN DEL SISTEMA
    // ========================================================================

    private function getCPULoad() {
        $load = 0;
        if (is_readable('/proc/loadavg')) {
            $content = @file_get_contents('/proc/loadavg');
            $parts = explode(' ', $content);
            if (isset($parts[0])) $load = floatval($parts[0]);
        } elseif (function_exists('sys_getloadavg')) {
            $cpu = sys_getloadavg();
            if ($cpu) $load = $cpu[0];
        }

        if ($load < 0.01) return "1%";
        return round($load * 100) . '%';
    }

    private function getRAMUsage() {
        if (!is_readable('/proc/meminfo')) return "45%";
        $meminfo = file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
        preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $avail);

        if (isset($total[1]) && isset($avail[1])) {
            $used = $total[1] - $avail[1];
            return round(($used / $total[1]) * 100) . "%";
        }
        return "45%";
    }

    private function getDiskFree() {
        $bytes = disk_free_space("/");
        if ($bytes === false) $bytes = disk_free_space(__DIR__);
        return ($bytes !== false) ? round($bytes / 1024 / 1024 / 1024, 1) . "GB" : "Unknown";
    }

    // [FIX] Ajustado para encontrar el certificado automáticamente si la constante está vacía
    private function checkSSL() {
        if (empty(self::SSL_PATH) || !file_exists(self::SSL_PATH)) {
            $wildcard = glob("/etc/letsencrypt/live/*/fullchain.pem");
            if (empty($wildcard)) return ["status" => "error", "msg" => "Cert File Not Found"];
            $cert_path = $wildcard[0];
        } else {
            $cert_path = self::SSL_PATH;
        }

        try {
            $cert_content = file_get_contents($cert_path);
            if (!$cert_content) return ["status" => "error", "msg" => "Read Error"];
            $cert_info = openssl_x509_parse($cert_content);
            
            if ($cert_info && isset($cert_info['validTo_time_t'])) {
                $days_left = floor(($cert_info['validTo_time_t'] - time()) / 86400);
                if ($days_left > 30) return ["status" => "valid", "msg" => "Valid ($days_left days)"];
                if ($days_left > 0) return ["status" => "warning", "msg" => "Expiring ($days_left days)"];
                return ["status" => "error", "msg" => "EXPIRED"];
            }
        } catch (Exception $e) { return ["status" => "error", "msg" => "Parse Error"]; }
        
        return ["status" => "error", "msg" => "Unknown"];
    }

    // ========================================================================
    // SECCIÓN 2: MOTOR DOCKER
    // ========================================================================

    private function queryDocker($endpoint, $method = 'GET') {
        if (!file_exists('/var/run/docker.sock')) return false;

        $fp = @stream_socket_client(self::DOCKER_SOCK, $errno, $errstr, 3);
        if (!$fp) return false;

        $request  = "$method $endpoint HTTP/1.1\r\nHost: localhost\r\n";
        if ($method === 'POST') {
            $request .= "Content-Length: 0\r\nContent-Type: application/json\r\n";
        }
        $request .= "Connection: Close\r\n\r\n";
        fwrite($fp, $request);

        $response = '';
        while (!feof($fp)) $response .= fgets($fp, 8192);
        fclose($fp);

        $parts = explode("\r\n\r\n", $response, 2);
        if (!isset($parts[1])) return true; 

        $json_body = $parts[1];
        if (strpos($response, 'Transfer-Encoding: chunked') !== false) {
            $json_body = $this->decodeChunked($json_body);
        }

        return json_decode($json_body, true);
    }

    private function decodeChunked($str) {
        for ($res = ''; !empty($str); $str = trim($str)) {
            $pos = strpos($str, "\r\n");
            if ($pos === false) return $str;
            $len = hexdec(substr($str, 0, $pos));
            $res .= substr($str, $pos + 2, $len);
            $str = substr($str, $pos + 2 + $len);
        }
        return $res;
    }

    public function getDockerList() {
        $data = $this->queryDocker('/containers/json?all=1');
        if (!$data || !is_array($data)) return [];

        $list = [];
        foreach ($data as $c) {
            $list[] = [
                'id'     => substr($c['Id'], 0, 12),
                'name'   => isset($c['Names'][0]) ? ltrim($c['Names'][0], '/') : 'Unknown',
                'image'  => substr($c['Image'] ?? 'Unknown Image', 0, 25),
                'state'  => ($c['State'] ?? '') == 'running' ? 'running' : 'exited',
                'status' => $c['Status'] ?? ''
            ];
        }
        return $list;
    }

    public function inspectContainer($id) {
        $data = $this->queryDocker("/containers/$id/json");
        if (!$data) return ['error' => 'Container not found'];

        return [
            'id'      => substr($data['Id'], 0, 12),
            'name'    => ltrim($data['Name'], '/'),
            'image'   => $data['Config']['Image'],
            'cmd'     => implode(' ', $data['Config']['Cmd'] ?? []),
            'ip'      => $data['NetworkSettings']['Networks']['extagram_network']['IPAddress'] ?? 'Host/Bridge',
            'ports'   => array_keys($data['NetworkSettings']['Ports'] ?? []),
            'state'   => $data['State']['Status'],
            'created' => substr($data['Created'], 0, 19)
        ];
    }

    public function containerAction($id, $action) {
        $valid_actions = ['start', 'stop', 'restart'];
        if (!in_array($action, $valid_actions)) {
            return ['error' => "Action '$action' is denied."];
        }
        $clean_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
        $this->queryDocker("/containers/$clean_id/$action", 'POST');
        return ['success' => true, 'msg' => "Container $clean_id: $action executed."];
    }

    // ========================================================================
    // SECCIÓN 3: USUARIOS
    // ========================================================================

    public function getActiveSSHUsers() {
        $output = shell_exec("who | awk '{print $1, $5, $3, $4}'");
        if (!$output) return [["user" => "None", "ip" => "-", "time" => "-"]];

        $lines = explode("\n", trim($output));
        $users = [];
        foreach ($lines as $line) {
            if (empty($line)) continue;
            preg_match('/^(\S+)\s+\(([^)]+)\)\s+(.*)$/', $line, $matches);
            if (count($matches) === 4) {
                $users[] = [
                    'user' => htmlspecialchars($matches[1]),
                    'ip'   => htmlspecialchars($matches[2]),
                    'time' => htmlspecialchars($matches[3])
                ];
            }
        }
        return $users;
    }

    public function getWebUsers($type = 'recent') {
        if (!$this->db) return ['error' => 'Database connection not found.'];
        try {
            if ($type === 'recent') {
                $stmt = $this->db->query("SELECT username, created_at FROM users ORDER BY created_at DESC LIMIT 5");
            } else { 
                $stmt = $this->db->query("SELECT username, last_activity FROM users WHERE last_activity > DATE_SUB(NOW(), INTERVAL 15 MINUTE) ORDER BY last_activity DESC");
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => 'Table or columns missing for tracking users.'];
        }
    }

    // ========================================================================
    // SECCIÓN 4: SEGURIDAD, LOGS Y TERMINAL
    // ========================================================================

    private function readLogSafely($path, $lines_count = 20) {
        if (!is_readable($path)) return ["⚠️ Cannot read $path. Ensure correct permissions (e.g. chmod 644)."];
        $output = shell_exec("tail -n $lines_count " . escapeshellarg($path));
        return $output ? explode("\n", trim($output)) : ["No recent activity."];
    }

    public function getFail2BanLogs() {
        $logs = $this->readLogSafely(self::LOG_FAIL2BAN, 25);
        $formatted = [];
        foreach ($logs as $line) {
            if (empty($line)) continue;
            if (strpos($line, 'Ban ') !== false && strpos($line, 'Restore') === false) {
                $formatted[] = "<span style='color:#ef4444; font-weight:bold;'>[BANNED]</span> " . htmlspecialchars($line);
            } elseif (strpos($line, 'Unban ') !== false) {
                $formatted[] = "<span style='color:#22c55e; font-weight:bold;'>[UNBANNED]</span> " . htmlspecialchars($line);
            } else {
                $formatted[] = "<span style='color:#9ca3af;'>[INFO]</span> " . htmlspecialchars($line);
            }
        }
        return array_reverse($formatted);
    }

    // [FIX] Función UFW rescrita para extraer la fecha y la IP de syslog
    public function getUFWLogs() {
        if (!is_readable(self::LOG_UFW)) return ["⚠️ Cannot read " . self::LOG_UFW];
        
        $lines = file(self::LOG_UFW);
        $blocks = [];
        
        // Regex para capturar ambos formatos de fecha y las IPs
        $regex = '/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}|[A-Z][a-z]{2}\s+\d+\s+\d{2}:\d{2}:\d{2}).*?\[UFW BLOCK\] IN=.*? SRC=([\d\.]+) DST=([\d\.]+).*?DPT=([\d]+)/';

        foreach (array_reverse($lines) as $line) {
            if (strpos($line, '[UFW BLOCK]') !== false) {
                if (preg_match($regex, $line, $matches)) {
                    $date = $matches[1];
                    $ip = $matches[2];
                    $port = $matches[4];
                    $blocks[] = "<span style='color:#f97316; font-weight:bold;'>[FIREWALL]</span> $date - Blocked IP <b>$ip</b> targeting port <b>$port</b>";
                }
            }
            if (count($blocks) >= 20) break;
        }
        
        return empty($blocks) ? ["No recent firewall blocks."] : $blocks;
    }

    public function getSSHLogs() {
        $lines = array_slice(file(self::LOG_AUTH), -15);
        $logs = [];
        foreach ($lines as $line) {
            if (strpos($line, 'sshd') !== false) {
                $clean = htmlspecialchars(substr($line, 0, 90));
                if (strpos($line, 'Accepted') !== false) {
                    $logs[] = "<span style='color:#4ade80'>✔ " . $clean . "</span>";
                } elseif (strpos($line, 'Failed') !== false || strpos($line, 'Invalid') !== false) {
                    $logs[] = "<span style='color:#f87171'>✖ " . $clean . "</span>";
                }
            }
        }
        return empty($logs) ? ["No recent SSH activity."] : array_reverse($logs);
    }

    public function executeCommand($cmd_raw) {
        $input = trim($cmd_raw);
        if (empty($input)) return "";

        $parts = explode(' ', $input);
        $cmd = $parts[0];
        $whitelist = ['ls', 'pwd', 'whoami', 'id', 'date', 'uptime', 'cat', 'uname', 'du', 'free'];

        if (!in_array($cmd, $whitelist)) {
            if ($cmd === 'cd') return "Directory change is not persistent (Stateless session).";
            return "Error: Command '$cmd' is restricted by Audit Policy.";
        }
        $output = shell_exec($input . " 2>&1");
        return htmlspecialchars($output ?: " (Empty output)");
    }

    // ========================================================================
    // SECCIÓN 5: SALIDA PRINCIPAL
    // ========================================================================

    public function getTelemetry() {
        return [
            'cpu'    => $this->getCPULoad(),
            'ram'    => $this->getRAMUsage(),
            'disk'   => $this->getDiskFree(),
            'ip'     => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1',
            'cwd'    => '~',
            'ssl'    => $this->checkSSL(),
            'docker' => $this->getDockerList()
        ];
    }
}

// ============================================================================
// ROUTER & CONTROLADOR
// ============================================================================

ob_end_clean(); 
header('Content-Type: application/json; charset=utf-8');

try {
    $engine = new AuditEngine();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['docker_action']) && isset($_POST['container_id'])) {
            echo json_encode($engine->containerAction($_POST['container_id'], $_POST['docker_action']));
            exit;
        }
        if (isset($_POST['cmd'])) {
            echo json_encode(['output' => $engine->executeCommand($_POST['cmd'])]);
            exit;
        }
        echo json_encode(['error' => 'Invalid POST request']);
        exit;
    }

    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'telemetry': echo json_encode($engine->getTelemetry()); break;
            case 'docker_details': echo json_encode($engine->inspectContainer($_GET['id'] ?? '')); break;
            case 'ssh_logs': echo json_encode($engine->getSSHLogs()); break;
            case 'fail2ban_logs': echo json_encode($engine->getFail2BanLogs()); break;
            case 'ufw_logs': echo json_encode($engine->getUFWLogs()); break;
            case 'ssh_users': echo json_encode($engine->getActiveSSHUsers()); break;
            case 'web_users_recent': echo json_encode($engine->getWebUsers('recent')); break;
            case 'web_users_active': echo json_encode($engine->getWebUsers('active')); break;
            default: echo json_encode(['error' => 'Invalid action']);
        }
    } else {
        echo json_encode(['status' => 'AuditEngine Online', 'version' => '4.1 GUI Edition']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
