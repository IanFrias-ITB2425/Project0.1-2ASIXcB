<?php
class SecurityModule {
    private $admin_pin = "1234"; 
    private $log_file = __DIR__ . "/sudo_audit.log";

    public function handlePrivilegedCommand($input) {
        if (preg_match('/^sudo\s+(\d+)\s+(.+)$/', $input, $matches)) {
            $pin = $matches[1];
            $cmd = $matches[2];
            
            if ($pin !== $this->admin_pin) {
                $this->logAction("FALLIDO", $cmd);
                return "❌ PIN Incorrecto.";
            }

            $output = shell_exec("sudo " . $cmd . " 2>&1");
            $this->logAction("OK", $cmd);
            return "🔐 [ROOT]:\n" . htmlspecialchars($output);
        }
        return null;
    }

    private function logAction($status, $cmd) {
        $date = date("Y-m-d H:i:s");
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $entry = "[$date] IP: $ip | $status | CMD: $cmd\n";
        @file_put_contents($this->log_file, $entry, FILE_APPEND);
    }

    public function getHistory() {
        if (!file_exists($this->log_file)) return "Aún no hay historial de comandos sudo.";
        // Usamos cat en lugar de tail para evitar bloqueos si el archivo es pequeño
        $content = shell_exec("cat " . escapeshellarg($this->log_file));
        return "📜 HISTORIAL DE SEGURIDAD:\n" . htmlspecialchars($content);
    }
}
