#!/bin/bash
echo "--- 1. Generando Certificados SSL Autofirmados ---"
# Creamos carpeta para certificados
sudo mkdir -p /etc/nginx/ssl
# Generamos clave y certificado válidos por 365 días
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/nginx/ssl/nginx.key \
    -out /etc/nginx/ssl/nginx.crt \
    -subj "/C=ES/ST=Barcelona/L=Barcelona/O=Extagram/OU=IT/CN=$(curl -s ifconfig.me)"

echo "--- 2. Reconfigurando NGINX para HTTPS ---"
# Detectar versión PHP
PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")

sudo bash -c "cat << 'EOF' > /etc/nginx/sites-available/extagram
server {
    listen 80;
    server_name _;
    # Redirigir todo el tráfico HTTP a HTTPS
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl;
    server_name _;
    root /var/www/extagram/public;
    index index.php extagram.php;

    # Certificados SSL
    ssl_certificate /etc/nginx/ssl/nginx.crt;
    ssl_certificate_key /etc/nginx/ssl/nginx.key;

    # Backend PHP
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php$PHP_VER-fpm.sock;
    }

    # Imágenes
    location /uploads/ {
        alias /var/www/extagram/uploads/;
    }

    # Estáticos (CSS/SVG)
    location ~ \.(css|svg)$ {
        root /var/www/extagram/static;
    }

    # Router
    location / {
        try_files \$uri \$uri/ /extagram.php;
    }
}
EOF"

echo "--- 3. Aplicando Diseño 'Premium' (Nuevo CSS) ---"
sudo bash -c "cat << 'EOF' > /var/www/extagram/static/style.css
/* Importamos fuente moderna 'Inter' o similar del sistema */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

body {
    background: #f0f2f5;
    font-family: 'Poppins', sans-serif;
    margin: 0;
    color: #333;
    padding-bottom: 50px;
}

/* Header simulado */
body::before {
    content: 'Extagram';
    display: block;
    background: white;
    text-align: center;
    padding: 15px;
    font-weight: 600;
    font-size: 1.5rem;
    color: #405de6; /* Color marca */
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 30px;
}

/* Formulario de subida */
form {
    background: white;
    max-width: 500px;
    margin: 0 auto 40px auto;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.5em;
    transition: transform 0.2s;
}

form:hover {
    transform: translateY(-2px);
}

input[type=text] {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #eee;
    border-radius: 8px;
    font-family: inherit;
    font-size: 1rem;
    outline: none;
    transition: border-color 0.3s;
    box-sizing: border-box;
}

input[type=text]:focus {
    border-color: #405de6;
}

/* Botón con gradiente */
input[type=submit] {
    background: linear-gradient(45deg, #405de6, #5851db, #833ab4);
    color: white;
    border: 0;
    border-radius: 8px;
    width: 100%;
    padding: 12px;
    font-weight: 600;
    cursor: pointer;
    font-size: 1rem;
    transition: opacity 0.3s;
}

input[type=submit]:hover {
    opacity: 0.9;
}

#file { display: none; }

/* Preview de imagen con estilo */
label[for='file'] {
    cursor: pointer;
    border-radius: 50%;
    padding: 5px;
    border: 2px dashed #ccc;
    transition: border-color 0.3s;
}
label[for='file']:hover {
    border-color: #405de6;
}

#preview {
    max-width: 150px;
    max-height: 150px;
    border-radius: 50%;
    object-fit: cover;
    display: block;
}

/* Tarjetas de Posts */
.post {
    background: white;
    max-width: 500px;
    margin: 0 auto 30px auto;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.03);
    border: 1px solid #eee;
}

.post p {
    padding: 20px;
    margin: 0;
    font-size: 0.95rem;
    line-height: 1.5;
}

.post img {
    width: 100%;
    display: block;
    object-fit: cover;
}

/* Footer del post simulado */
.post::after {
    content: '❤️ Like  💬 Comment';
    display: block;
    padding: 15px 20px;
    border-top: 1px solid #f0f0f0;
    color: #666;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
}
EOF"

echo "--- 4. Reiniciando Servidor ---"
sudo systemctl restart nginx
echo "¡Hecho! Accede ahora usando HTTPS: https://$(curl -s ifconfig.me)"
