#!/bin/bash
# --- SCRIPT DE DESPLIEGUE EXTAGRAM (SPRINT 1) ---

echo "--- 1. Actualizando sistema e instalando LEMP Stack ---"
sudo apt update
sudo apt install -y nginx mysql-server php-fpm php-mysql

# Detectar versión de PHP instalada para configurar Nginx
PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "Versión PHP detectada: $PHP_VER"

echo "--- 2. Creando estructura de directorios ---"
# Estructura según PDF: carpeta uploads, static, y raíz
sudo mkdir -p /var/www/extagram/{public,static,uploads}
# Permisos: El usuario web (www-data) debe ser dueño
sudo chown -R www-data:www-data /var/www/extagram
sudo chmod -R 755 /var/www/extagram
# Permisos escritura para uploads
sudo chmod -R 777 /var/www/extagram/uploads

echo "--- 3. Configurando Base de Datos (Source: PDF Pág 7/9) ---"
# Creamos la BBDD, el usuario extagram_admin y la tabla posts
sudo mysql -e "CREATE DATABASE IF NOT EXISTS extagram_db;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'extagram_admin'@'localhost' IDENTIFIED BY 'pass123';"
sudo mysql -e "GRANT ALL PRIVILEGES ON extagram_db.* TO 'extagram_admin'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
sudo mysql -e "USE extagram_db; CREATE TABLE IF NOT EXISTS posts (post TEXT, photourl TEXT);"

echo "--- 4. Configurando NGINX ---"
# Configuración que separa lógicamente los servicios en un solo server
sudo bash -c "cat << 'EOF' > /etc/nginx/sites-available/extagram
server {
    listen 80;
    server_name _;
    root /var/www/extagram/public;
    index index.php extagram.php;

    # Gestión de PHP (Backend)
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php$PHP_VER-fpm.sock;
    }

    # Servidor de Imágenes (Simulado)
    location /uploads/ {
        alias /var/www/extagram/uploads/;
    }

    # Servidor Estático (CSS/SVG)
    location ~ \.(css|svg)$ {
        root /var/www/extagram/static;
    }

    # Redirección al index
    location / {
        try_files \$uri \$uri/ /extagram.php;
    }
}
EOF"

# Activar sitio y reiniciar Nginx
sudo ln -sfn /etc/nginx/sites-available/extagram /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo systemctl restart nginx

echo "--- 5. Generando Código Fuente ---"

# A. Conexión DB
sudo bash -c "cat << 'EOF' > /var/www/extagram/public/db_conn.php
<?php
\$servername = \"localhost\";
\$username = \"extagram_admin\";
\$password = \"pass123\";
\$dbname = \"extagram_db\";
\$db = new mysqli(\$servername, \$username, \$password, \$dbname);
if (\$db->connect_error) { die(\"Connection failed: \" . \$db->connect_error); }
?>
EOF"

# B. extagram.php (Frontend)
sudo bash -c "cat << 'EOF' > /var/www/extagram/public/extagram.php
<?php include 'db_conn.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <title>Extagram</title>
    <link rel=\"stylesheet\" href=\"/style.css\">
</head>
<body>
    <form method=\"POST\" enctype=\"multipart/form-data\" action=\"upload.php\">
        <input type=\"text\" name=\"post\" placeholder=\"Write something...\" required>
        <label for=\"file\">
            <img id=\"preview\" src=\"/preview.svg\">
        </label>
        <input id=\"file\" type=\"file\" name=\"photo\" accept=\"image/*\"
               onchange=\"document.getElementById('preview').src=window.URL.createObjectURL(event.target.files[0])\">
        <input type=\"submit\" value=\"Publish\">
    </form>

    <?php
    \$result = \$db->query(\"SELECT * FROM posts\");
    if (\$result) {
        // Mostrar posts más recientes primero (opcional, mejora UX)
        // \$result = \$db->query(\"SELECT * FROM posts ORDER BY 1 DESC\"); 
        foreach (\$result as \$fila) {
            echo \"<div class='post'>\";
            echo \"<p>\" . htmlspecialchars(\$fila['post']) . \"</p>\";
            if (!empty(\$fila['photourl'])) {
                echo \"<img src='/uploads/\" . \$fila['photourl'] . \"'>\";
            }
            echo \"</div>\";
        }
    }
    ?>
</body>
</html>
EOF"

# C. upload.php (Backend Upload)
sudo bash -c "cat << 'EOF' > /var/www/extagram/public/upload.php
<?php
include 'db_conn.php';

if (\$_SERVER['REQUEST_METHOD'] == 'POST') {
    \$photoid = \"\";
    
    // Procesar imagen
    if (!empty(\$_FILES['photo']['name'])) {
        \$ext = pathinfo(\$_FILES['photo']['name'], PATHINFO_EXTENSION);
        // Generar ID único para evitar sobreescritura
        \$photoid = uniqid() . \".\" . \$ext;
        // Mover a la carpeta física de uploads
        move_uploaded_file(\$_FILES['photo']['tmp_name'], '/var/www/extagram/uploads/' . \$photoid);
    }

    // Insertar en BBDD
    if (!empty(\$_POST[\"post\"]) || !empty(\$photoid)) {
        \$stmt = \$db->prepare(\"INSERT INTO posts (post, photourl) VALUES (?, ?)\");
        \$stmt->bind_param(\"ss\", \$_POST[\"post\"], \$photoid);
        \$stmt->execute();
        \$stmt->close();
    }
}
header(\"Location: /\");
exit();
?>
EOF"

# D. style.css (Estilos corregidos del PDF)
sudo bash -c "cat << 'EOF' > /var/www/extagram/static/style.css
body { background: #fafafa; font-family: sans-serif; margin: 0; }
form { display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 1em; background: white; border-bottom: 1px solid #dbdbdb; padding: 8px; }
input[type=text] { border: 1px solid #dbdbdb; padding: 8px; width: 300px; }
input[type=submit] { background: #0096f7; color: white; border: 0; border-radius: 3px; width: 300px; padding: 8px; cursor: pointer; }
#file { display: none; }
#preview { max-width: 300px; cursor: pointer; }
.post { max-width: 600px; margin: 0 auto; background: white; display: flex; flex-direction: column; border: 1px solid #dbdbdb; border-radius: 3px; margin-bottom: 24px; margin-top: 20px;}
.post img { max-width: 600px; }
.post p { padding: 16px; }
EOF"

# E. preview.svg (Imagen vector corregida)
sudo bash -c "cat << 'EOF' > /var/www/extagram/static/preview.svg
<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\" width=\"300\" height=\"300\">
<g>
    <rect width=\"100\" height=\"100\" fill=\"#cecece\"/>
    <path fill=\"#ffffff\" transform=\"translate(25, 25) scale(0.5)\" d=\"M48.1,26.3c0,4.3,0,7.2-0.1,8.8c-0.2,3.9-1.3,6.9-3.5,9s-5.1,3.3-9,3.5c-1.6,0.1-4.6,0.1-8.8,0.1c-4.3,0-7.2,0-8.8-0.1c-3.9-0.2-6.9-1.3-9-3.5c-2.1-2.1-3.3-5.1-3.5-9c-0.1-1.6-0.1-4.6-0.1-8.8s0-7.2,0.1-8.8c0.2-3.9,1.3-6.9,3.5-9c2.1-2.1,5.1-3.3,9-3.5c1.6-0.1,4.6-0.1,8.8-0.1c4.3,0,7.2,0,8.8,0.1c3.9,0.2,6.9,1.3,9,3.5s3.3,5.1,3.5,9C48,19.1,48.1,22,48.1,26.3z M34.4,18.5c2.1,0.2,4.7,3.2,4.7,7.8s-1.1,5.6-3.2,7.8c-2.1,2.1-4.7,3.2-7.8,3.2c-3.1,0-5.6-1.1-7.8-3.2c-2.1-2.1-3.2-4.7-3.2-7.8s1.1-5.6,3.2-7.8c2.1-2.1,4.7-3.2,7.8-3.2C29.7,15.3,32.3,16.3,34.4,18.5z\"/>
</g>
</svg>
EOF"

echo "--- ¡DESPLIEGUE FINALIZADO! ---"
echo "Abre tu navegador y entra en: http://$(curl -s ifconfig.me)"
