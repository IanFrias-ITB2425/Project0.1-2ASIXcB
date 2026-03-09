# Autor: ASIXcB G5 - Alberto Trujillo, Rehan Farooq, Aleix Tomas, Ian Frias Reyes
# ------------------------------------------------------------------------------
<?php
// /docker/public/google_callback.php

// Al incluir db_conn.php, la sesión se inicia AUTOMÁTICAMENTE.
require_once 'db_conn.php';
require_once 'google_config.php';

if (isset($_GET['code'])) {
    // 1. Intercambiar el código por un Token de Acceso
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'code'          => $_GET['code'],
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URL,
        'grant_type'    => 'authorization_code'
    ]));
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    if (isset($data['access_token'])) {
        // 2. Obtener información del usuario con el Token
        $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $data['access_token']]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $userInfoResponse = curl_exec($ch);
        $user_info = json_decode($userInfoResponse, true);
        curl_close($ch);

        if (isset($user_info['sub'])) {
            // Datos recibidos de Google
            $gid   = $user_info['sub'];
            $email = $user_info['email'];
            $name  = $user_info['name'];
            $pic   = $user_info['picture'];

            // 3. Lógica de Base de Datos (Login o Registro)
            // Verificamos si el usuario existe por ID de Google o Email
            $stmt = $db->prepare("SELECT id FROM users WHERE google_id = ? OR email = ?");
            $stmt->execute([$gid, $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // USUARIO EXISTE: Actualizamos datos (foto y nombre)
                $stmt = $db->prepare("UPDATE users SET username = ?, avatar_url = ?, google_id = ? WHERE id = ?");
                $stmt->execute([$name, $pic, $gid, $user['id']]);
                $userId = $user['id'];
            } else {
                // USUARIO NUEVO: Lo creamos
                $stmt = $db->prepare("INSERT INTO users (username, email, google_id, avatar_url) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $gid, $pic]);
                $userId = $db->lastInsertId();
            }

            // Recuperamos los datos finales del usuario para la sesión
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $finalUser = $stmt->fetch(PDO::FETCH_ASSOC);

            // 4. GUARDAR SESIÓN (¡Sin hacer session_start de nuevo!)
            $_SESSION['user_id']    = $finalUser['id'];
            $_SESSION['username']   = $finalUser['username'];
            $_SESSION['avatar_url'] = $finalUser['avatar_url'];

            // 5. ¡CRÍTICO! Forzar escritura en Redis antes de redirigir
            // Esto evita que la redirección ocurra antes de guardar los datos
            session_write_close();

            header("Location: extagram.php");
            exit();
        }
    }
}

// Si algo falla, volver al login con error
header("Location: login.php?error=auth_failed");
exit();
?>
