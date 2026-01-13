#!/bin/bash
################################################################################
# Script de configuración HTTPS + NGINX + CSS para el proyecto "Extagram"
# ------------------------------------------------------------------------------
# Este script realiza las siguientes tareas:
# 1. Genera certificados SSL autofirmados
# 2. Configura NGINX para forzar HTTPS
# 3. Detecta automáticamente la versión de PHP instalada
# 4. Aplica un diseño CSS moderno estilo “premium”
# 5. Reinicia NGINX para aplicar los cambios
#
# Pensado para sistemas GNU/Linux (Ubuntu / Debian)
################################################################################


################################################################################
# 1. GENERACIÓN DE CERTIFICADOS SSL AUTOFIRMADOS
################################################################################

echo "--- 1. Generando Certificados SSL Autofirmados ---"
# Mensaje informativo en consola

sudo mkdir -p /etc/nginx/ssl
# Crea el directorio donde se almacenarán los certificados SSL
# El flag -p evita errores si el directorio ya existe

sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
# Genera un certificado SSL autofirmado:
# -x509      → Certificado autofirmado
# -nodes     → Clave privada sin contraseña
# -days 365  → Validez de 1 año
# -newkey    → Genera una nueva clave RSA de 2048 bits
    -keyout /etc/nginx/ssl/nginx.key \
    # Ruta donde se guarda la clave privada
    -out /etc/nginx/ssl/nginx.crt \
    # Ruta donde se guarda el certificado público
    -subj "/C=ES/ST=Barcelona/L=Barcelona/O=Extagram/OU=IT/CN=$(curl -s ifconfig.me)"
    # Información del certificado:
    # C  → País
    # ST → Provincia
    # L  → Ciudad
    # O  → Organización
    # OU → Unidad organizativa
    # CN → Common Name (IP pública del servidor)


################################################################################
# 2. CONFIGURACIÓN DE NGINX PARA HTTPS
################################################################################

echo "--- 2. Reconfigurando NGINX para HTTPS ---"
# Mensaje informativo

PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
# Detecta automáticamente la versión de PHP instalada (ej: 8.1)
# Se usará para apuntar correctamente al socket PHP-FPM

sudo bash -c "cat << 'EOF' > /etc/nginx/sites-available/extagram
################################################################################
# CONFIGURACIÓN DEL VIRTUAL HOST DE NGINX PARA EXTAGRAM
################################################################################

# SERVIDOR HTTP (PUERTO 80)
server {
    listen 80;                               # Escucha tráfico HTTP
    server_name _;                          # Acepta cualquier dominio/IP
    return 301 https://\$host\$request_uri; # Redirige todo a HTTPS
}

# SERVIDOR HTTPS (PUERTO 443)
server {
    listen 443 ssl;                         # Escucha en HTTPS
    server_name _;                          # Acepta cualquier dominio/IP

    root /var/www/extagram/public;          # Directorio raíz del proyecto
    index index.php extagram.php;           # Archivos índice

    # CERTIFICADOS SSL
    ssl_certificate /etc/nginx/ssl/nginx.crt;
    ssl_certificate_key /etc/nginx/ssl/nginx.key;

    # MANEJO DE ARCHIVOS PHP CON PHP-FPM
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;  # Configuración FastCGI estándar
        fastcgi_pass unix:/run/php/php$PHP_VER-fpm.sock;
        # Usa el socket PHP-FPM según la versión detectada
    }

    # DIRECTORIO DE IMÁGENES SUBIDAS
    location /uploads/ {
        alias /var/www/extagram/uploads/;   # Ruta real del sistema
    }

    # ARCHIVOS ESTÁTICOS (CSS, SVG)
    location ~ \.(css|svg)$ {
        root /var/www/extagram/static;      # Carpeta de recursos estáticos
    }

    # ROUTER FRONTAL
    location / {
        try_files \$uri \$uri/ /extagram.php;
        # Si el archivo no existe, redirige al router principal en PHP
    }
}
EOF"


################################################################################
# 3. APLICACIÓN DEL DISEÑO "PREMIUM" (CSS)
################################################################################

echo "--- 3. Aplicando Diseño 'Premium' (Nuevo CSS) ---"
# Mensaje informativo

sudo bash -c "cat << 'EOF' > /var/www/extagram/static/style.css
/* ============================================================================
   HOJA DE ESTILOS PRINCIPAL - EXTAGRAM
   Diseño moderno inspirado en redes sociales
   ========================================================================== */

/* Importación de fuente moderna desde Google Fonts */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

/* ESTILOS GENERALES */
body {
    background: #f0f2f5;                    /* Fondo claro */
    font-family: 'Poppins', sans-serif;     /* Fuente global */
    margin: 0;                              /* Elimina márgenes */
    color: #333;                            /* Color del texto */
    padding-bottom: 50px;                   /* Espacio inferior */
}

/* HEADER SIMULADO */
body::before {
    content: 'Extagram';                    /* Título del sitio */
    display: block;
    background: white;
    text-align: center;
    padding: 15px;
    font-weight: 600;
    font-size: 1.5rem;
    color: #405de6;                         /* Color corporativo */
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 30px;
}

/* FORMULARIO DE SUBIDA */
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
}

/* CAMPOS DE TEXTO */
input[type=text] {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #eee;
    border-radius: 8px;
    font-size: 1rem;
}

/* BOTÓN PRINCIPAL */
input[type=submit] {
    background: linear-gradient(45deg, #405de6, #5851db, #833ab4);
    color: white;
    border: 0;
    border-radius: 8px;
    width: 100%;
    padding: 12px;
    font-weight: 600;
    cursor: pointer;
}

/* TARJETAS DE PUBLICACIONES */
.post {
    background: white;
    max-width: 500px;
    margin: 0 auto 30px auto;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.03);
}

/* TEXTO DE LA PUBLICACIÓN */
.post p {
    padding: 20px;
    margin: 0;
}

/* IMAGEN DEL POST */
.post img {
    width: 100%;
    display: block;
}

/* FOOTER SIMULADO */
.post::after {
    content: '❤️ Like   💬 Comment';
    display: block;
    padding: 15px 20px;
    border-top: 1px solid #f0f0f0;
    color: #666;
    font-size: 0.85rem;
    font-weight: 600;
}
EOF"


################################################################################
# 4. REINICIO DEL SERVIDOR
################################################################################

echo "--- 4. Reiniciando Servidor ---"
# Mensaje informativo

sudo systemctl restart nginx
# Reinicia NGINX para aplicar la nueva configuración

echo "¡Hecho! Accede ahora usando HTTPS: https://$(curl -s ifconfig.me)"
# Muestra la URL final del servidor con HTTPS activo
################################################################################

