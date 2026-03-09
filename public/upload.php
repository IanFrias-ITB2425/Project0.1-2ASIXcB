<?php
// ==========================================
// 1. MODO DEBUG (Para cazar errores rápido)
// ==========================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ==========================================
// 2. CONFIGURACIÓN DE REDIS
// ==========================================
// Conectamos al contenedor 's8_redis' con la contraseña correcta del docker-compose
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://s8_redis:6379?auth=Redis_Pass_2026!');

// ==========================================
// 3. INICIO DE SESIÓN Y BASE DE DATOS
// ==========================================
session_start();
include 'db_conn.php';

// Protecció: si no hi ha sessió, no deixem pujar res
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit("Accés denegat");
}

// ==========================================
// 4. PROCESAMIENTO DEL FORMULARIO
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $post_text = trim($_POST["post"] ?? "");
    $photoid = "";

    // 4A. Processar la imatge si existeix
    if (isset($_FILES['photo']) && $_FILES['photo']['name'] != "") {
        
        // Comprovem si PHP ha bloquejat l'arxiu (ex. per ser massa pesat al php.ini)
        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            die("<h2>Error de PHP al pujar. Codi d'error: " . $_FILES['photo']['error'] . "</h2>");
        }

        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed_exts)) {
            // Generar nom únic per evitar sobreescriure fotos
            $photoid = "post_" . uniqid() . "." . $ext;
            
            // RUTA DINÀMICA: Troba la carpeta basant-se en on és l'upload.php
            $upload_dir = __DIR__ . '/uploads/';
            
            // Si la carpeta no existeix, la creem de forma segura
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0777, true);
            }
            
            $target_path = $upload_dir . $photoid;

            // Movem l'arxiu temporal a la carpeta final
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                die("<h2>Error Fatal: No es pot escriure a la carpeta $upload_dir. Revisa els permisos a Docker!</h2>");
            }
        } else {
            die("<h2>Error: Extensió d'arxiu no permesa ($ext)</h2>");
        }
    }

    // 4B. Insertar a la BBDD (només si hi ha text o foto validada)
    if (!empty($post_text) || !empty($photoid)) {
        try {
            // Preparem la consulta (evita injeccions SQL)
            $stmt = $db->prepare("INSERT INTO posts (post, photourl, user_id, likes_count) VALUES (?, ?, ?, 0)");
            $stmt->execute([$post_text, $photoid, $user_id]);
        } catch (PDOException $e) {
            die("<h2>Error Fatal de BBDD: " . $e->getMessage() . "</h2>");
        }
    }
}

// ==========================================
// 5. REDIRECCIÓN FINAL
// ==========================================
// Tornar al feed de la xarxa social
header("Location: extagram.php");
exit();
?>
