#!/bin/bash
###############################################################################
# EXTAGRAM - SCRIPT DE CONFIGURACIÓN AUTOMÁTICA
# -----------------------------------------------------------------------------
# Este script realiza las siguientes tareas:
# 1. Genera certificados SSL autofirmados
# 2. Configura NGINX para forzar HTTPS
# 3. Detecta automáticamente la versión de PHP instalada
# 4. Aplica un diseño CSS moderno tipo red social
# 5. Reinicia NGINX para aplicar los cambios
###############################################################################


###############################################################################
# 1. GENERACIÓN DE CERTIFICADOS SSL AUTOFIRMADOS
###############################################################################

echo "--- 1. Generando Certificados SSL Autofirmados ---"
# Mensaje informativo para el usuario

sudo mkdir -p /etc/nginx/ssl
# Crea el directorio donde se almacenarán los certificados SSL
# El flag -p evita errores si el directorio ya existe

sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
# Genera un certificado SSL autofirmado:
# -x509     → Certificado autofirmado
# -nodes    → Clave privada sin contraseña
# -days     → Validez de 365 días
# rsa:2048  → Clave RSA de 2048 bits
    -keyout /etc/nginx/ssl/nginx.key \
    # Ruta de la clave privada
    -out /etc/nginx/ssl/nginx.crt \
    # Ruta del certificado público
    -subj "/C=ES/ST=Barcelona/L=Barcelona/O=Extagram/OU=IT/CN=$(curl -s ifconfig.me)"
    # Datos del certificado:
    # C  → País
    # ST → Provincia
    # L  → Ciudad
    # O  → Organización
    # OU → Unidad organizativa
    # CN → IP pública del servidor


###############################################################################
# 2. CONFIGURACIÓN DE NGINX PARA HTTPS
###############################################################################

echo "--- 2. Reconfigurando NGINX para HTTPS ---"
# Indica el inicio de la configuración de NGINX

PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
# Detecta automáticamente la versión de PHP instalada (ej: 8.1)

sudo bash -c "cat << 'EOF' > /etc/nginx/sites-available/extagram
###############################################################################
# NGINX - VIRTUAL HOST EXTAGRAM
###############################################################################

# Servidor HTTP (puerto 80)
server {
    listen 80;                               # Escucha tráfico HTTP
    server_name _;                          # Acepta cualquier dominio/IP
    return 301 https://\$host\$request_uri; # Redirección permanente a HTTPS
}

# Servidor HTTPS (puerto 443)
server {
    listen 443 ssl;                         # Escucha en HTTPS
    server_name _;                          # Acepta cualquier dominio/IP

    root /var/www/extagram/public;          # Directorio raíz del proyecto
    index index.php extagram.php;           # Archivos índice

    # Certificados SSL
    ssl_certificate /etc/nginx/ssl/nginx.crt;
    ssl_certificate_key /etc/nginx/ssl/nginx.key;

    # Manejo de archivos PHP con PHP-FPM
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php$PHP_VER-fpm.sock;
    }

    # Archivos subidos por los usuarios
    location /uploads/ {
        alias /var/www/extagram/uploads/;
    }

    # Archivos estáticos (CSS, SVG)
    location ~ \.(css|svg)$ {
        root /var/www/extagram/static;
    }

    # Router frontal
    location / {
        try_files \$uri \$uri/ /extagram.php;
    }
}
EOF"


###############################################################################
# 3. APLICACIÓN DEL DISEÑO VISUAL (CSS)
###############################################################################

echo "--- 3. Aplicando Diseño 'Premium' (Nuevo CSS) ---"
# Indica que se va a aplicar el nuevo diseño visual

sudo bash -c "cat << 'EOF' > /var/www/extagram/static/style.css
/* ============================================================================
   EXTAGRAM - HOJA DE ESTILOS PRINCIPAL
   Diseño moderno inspirado en redes sociales
   ========================================================================== */

/* Fuente moderna */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

/* Estilos generales */
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
    color: #405de6;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 30px;
}

/* Formulario */
form {
    background: white;
    max-width: 500px;
    margin: 0 auto 40px auto;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
}

/* Campos de texto */
input[type=text] {
    width: 100%;
    padding: 12px;
    border: 2px solid #eee;
    border-radius: 8px;
}

/* Botón principal */
input[type=submit] {
    background: linear-gradient(45deg, #405de6, #5851db, #833ab4);
    color: white;
    border: none;
    padding: 12px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

/* Publicaciones */
.post {
    background: white;
    max-width: 500px;
    margin: 0 auto 30px auto;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.03);
}

.post img {
    width: 100%;
    display: block;
}

.post p {
    padding: 20px;
}

/* Footer simulado */
.post::after {
    content: '❤️ Like   💬 Comment';
    display: block;
    padding: 15px;
    border-top: 1px solid #f0f0f0;
    color: #666;
    font-size: 0.85rem;
}
EOF"


###############################################################################
# 4. REINICIO DEL SERVIDOR
###############################################################################

echo "--- 4. Reiniciando Servidor ---"
# Indica que se aplicarán los cambios

sudo systemctl restart nginx
# Reinicia NGINX para cargar la nueva configuración

echo "¡Hecho! Accede ahora usando HTTPS: https://$(curl -s ifconfig.me)"
# Muestra la URL final con HTTPS activo
###############################################################################

