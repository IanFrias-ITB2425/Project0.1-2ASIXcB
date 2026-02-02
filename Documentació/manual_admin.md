# Manual d’Administració — Extagram

Document operatiu per a infraestructura, desplegament i manteniment del servei. Conté arquitectura, configuracions de Nginx/TLS, backend/BD, DNS/Cloudflare i el desplegament amb Docker. Per al desplegament clàssic (no Docker), utilitzeu la guia funcional:
- Guia de desplegament: https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/Documentaci%C3%B3/Documentaci%C3%B3-Funcional.md

## 1. Arquitectura i rols
- Nginx com a proxy invers i balancejador (entrada HTTP/HTTPS) cap a nodes PHP-FPM (dinàmica i pujades) i servidors d’estàtics.
- PHP-FPM 8.3 per a la lògica (`extagram.php`) i per al flux de pujades (`upload.php`).
- MySQL com a persistència (taules `posts` i `comments`).
- Estàtics i imatges servits per Nginx (`/static`, `/uploads`).

Rutes del servei:
- GET `/` → llista de posts (DB)
- POST `/upload` → desa imatge a `uploads/` + inserció a DB
- GET `/images/...` → imatges (uploads)
- GET `/static/...` → CSS/JS/SVG

Infraestructura recomanada (Sprint 1):
- AWS EC2 t3.medium (Ubuntu)

Accés a la instància:
```bash
ssh -i Baixades/Grupo5.pem ubuntu@54.161.47.236
chmod 400 Baixades/Grupo5.pem
```

## 2. Estructura del repositori i del servidor
- Codi i recursos web: https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/tree/main/public
- Configuracions i scripts: https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/tree/main/files
- Documentació: https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/tree/main/Documentaci%C3%B3

Organització destacada (segons les captures proporcionades):
- Public:
  - `static/` (CSS i SVG), `uploads/` (imatges)
  - Fitxers PHP principals (connexió, lògica, CRUD, login/logout)
- Files:
  - `docker/` (Dockerfile i docker-compose)
  - `etc/` (Let’s Encrypt, `php.ini`)
  - `nginx/` (config `extagram`)
  - `scripts/` (automatització HTTPS i funcional)
  - `sql/` (esquema)

## 3. Nginx i TLS (Let’s Encrypt)
- Configuració Nginx (host): https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/files/nginx/extagram
- Certificats al repositori:
  - Full chain: https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/files/etc/letsencrypt/live/g5asixc2bc.com/fullchain.pem
  - Clau privada: https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/files/etc/letsencrypt/live/g5asixc2bc.com/privkey.pem
- Documentació i scripts:
  - Guia HTTPS: https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/Documentaci%C3%B3/Documentacio-HTTPS.md
  - Scripts: https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/tree/main/files/scripts

Objectius de configuració:
- Proxy FastCGI cap a PHP-FPM per a dinàmica (`extagram.php`) i pujades (`upload.php`).
- Rutes `/static` i `/uploads` servides directament per Nginx.
- Blocs HTTPS amb integració Let’s Encrypt (validacions i renovació).

## 4. Backend i base de dades
- Scripts PHP (directori): https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/tree/main/public
- Esquema SQL (taules principals): https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/files/sql/schema.sql

Canvis aplicats (segons codi existent):
- Migració a PDO amb consultes preparades per seguretat i mantenibilitat.
- Esborrat en cascada de posts (imatge del sistema, comentaris associats i registre a la BD).
- Noms d’arxiu únics amb `uniqid()` per evitar col·lisions en pujades.
- CRUD de comentaris integrat a les rutes dinàmiques.

## 5. DNS i Cloudflare
- Gestió de DNS, proxy i SSL/TLS via Cloudflare.
- Referència visual de configuració DNS: https://github.com/user-attachments/assets/e4abb767-afc0-46d0-be3e-c7e9e616e8a4

## 6. Desplegament amb Docker
Arxius de configuració:
- Dockerfile: https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/files/docker/Dockerfile
- docker-compose.yml: https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/files/docker/docker-compose.yml

Topologia operativa (serveis):
- `s1_nginx` — proxy invers i balanceig; publica 80/443; muntatge de config Nginx i certificats.
- `s2_extagram` i `s3_extagram` — PHP-FPM per lògica general; Redis per sessions; PDO per MySQL; codi via `public/`.
- `s4_upload` — PHP-FPM dedicat a pujades; límits/temps ajustables via `php.ini` i Nginx.
- `s5_nginx` i `s6_nginx` — Nginx per estàtics (`static/`) i imatges (`uploads/`).
- `s7_mysql` — MySQL 8.0 amb esquema inicial de `files/sql/schema.sql`; persistència a `mysql_data/`.
- `s8_redis` — Redis per sessions compartides entre nodes PHP.

Balanceig resumit (Nginx):
- Dinàmica: `s2_extagram:9000` i `s3_extagram:9000` amb `least_conn`.
- Pujades: `s4_upload:9000`.
- Estàtics: `s5_nginx:80` i `s6_nginx:80`.

Operació bàsica (host amb Compose v2):
```bash
# Desplegar (build + arrencada)
docker compose -f files/docker/docker-compose.yml up -d --build

# Estat i diagnòstic
docker compose -f files/docker/docker-compose.yml ps
docker compose -f files/docker/docker-compose.yml logs -f s1_nginx
```

Bones pràctiques:
- Muntar codi des de `public/` i configuració Nginx des de `files/etc/nginx`.
- Persistir dades en `mysql_data` i contingut en `public/uploads`.
- Ajustar límits de pujada i timeouts a `php.ini` i Nginx.
- Utilitzar `least_conn` per balanceig en upstreams PHP-FPM.

## 7. Operació i manteniment
- Scripts d’automatització i HTTPS: https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/tree/main/files/scripts
- Logs habituals (host o contenidors): Nginx, PHP-FPM, MySQL, Redis.
- Seguretat: capçaleres de Nginx, OPcache actiu, secrets fora del codi (variables d’entorn), WAF/Rate limiting a Cloudflare.

Desplegament clàssic sense Docker i posada en marxa:
- Guia funcional: https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/Documentaci%C3%B3/Documentaci%C3%B3-Funcional.md
