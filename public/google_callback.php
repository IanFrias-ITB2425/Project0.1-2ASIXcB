<?php
// /docker/public/google_callback.php
include 'db_conn.php';
require_once 'google_config.php';

if (isset($_GET['code'])) {
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
    
    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($data['access_token'])) {
        $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $data['access_token']]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $user_info = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (isset($user_info['sub'])) {
            $gid   = $user_info['sub'];
            $email = $user_info['email'];
            $name  = $user_info['name'];
            $pic   = $user_info['picture']; // Foto actual de Google

            // 1. Buscamos si el usuario ya existe
            $stmt = $db->prepare("SELECT id FROM users WHERE google_id = ? OR email = ?");
            $stmt->execute([$gid, $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // 2. ACTUALIZACIÓN: Si ya existe, actualizamos su foto y nombre de Google
                $stmt = $db->prepare("UPDATE users SET username = ?, avatar_url = ?, google_id = ? WHERE id = ?");
                $stmt->execute([$name, $pic, $gid, $user['id']]);
                $userId = $user['id'];
            } else {
                // 3. REGISTRO: Si no existe, lo creamos
                $stmt = $db->prepare("INSERT INTO users (username, email, google_id, avatar_url) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $gid, $pic]);
                $userId = $db->lastInsertId();
            }

            // Volvemos a leer los datos finales para asegurar consistencia
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $finalUser = $stmt->fetch(PDO::FETCH_ASSOC);

            session_start();
            $_SESSION['user_id']    = $finalUser['id'];
            $_SESSION['username']   = $finalUser['username'];
            $_SESSION['avatar_url'] = $finalUser['avatar_url']; // Coincide con extagram.php
            
            header("Location: extagram.php");
            exit();
        }
    }
}
header("Location: login.php?error=auth");
