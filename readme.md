# Extagram - Projecte 0.1 ASIXcB G5

![Status](https://img.shields.io/badge/Status-Active-success?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-8.3-blue?style=flat-square)
![NGINX](https://img.shields.io/badge/NGINX-Proxy-green?style=flat-square)
![AWS](https://img.shields.io/badge/AWS-EC2-orange?style=flat-square)

---
## Índex de Continguts
1. [Panell de Control i Documentació](#panell-de-control-i-documentació)
2. [Enllaços Ràpids](#enllaços-ràpids)
3. [Arquitectura del Sistema](#arquitectura-del-sistema)
4. [Infraestructura i Accés](#infraestructura-i-accés-sprint-1)
5. [Base de Dades](#base-de-dades-en-ús)
6. [Rutes del Servei](#rutes-del-servei)
7. [NGINX](#nginx)
8. [Certificats i Let's Encrypt](#certificats-i-lets-encrypt)
9. [PHP / Backend](#php--backend)
10. [Frontend / Estils i Estàtics](#frontend--estils-i-estàtics)
11. [Canvis Realitzats en el Codi Inicial](#canvis-realitzats-en-el-codi-inicial)
12. [Justificació Tecnològica](#justificació-tecnològica)
13. [Enllaços Útils](#enllaços-útils-apis-i-docs)
14. [Configuració DNS](#dns-a-cloudflare)
---

## Panell de Control i Documentació
> **Manual d’Administració: https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/Documentaci%C3%B3/manual_admin.md**
> **Manual d’Usuari: https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/Documentaci%C3%B3/manual_usuari.md**

## Enllaços Ràpids
> **Web: https://g5asixc2bc.com/**
> **Tasques (ProofHub): https://itecbcn.proofhub.com/bapplite/#app/todos/project-9429814374/list-270271277074**
---
## Arquitectura del Sistema

<div align="center">
  <img src="https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/public/uploads/esquema.png" alt="Arquitectura Extagram" width="600" />
</div>

### Components i Serveis
El sistema utilitza NGINX com a proxy invers que balanceja i distribueix la càrrega:

- **S1 (Proxy):** NGINX gestionant el tràfic cap a la resta de serveis.
- **S2 & S3 (App):** PHP-FPM executant `extragram.php` (lògica principal).
- **S4 (Upload):** PHP-FPM dedicat a `upload.php` (pujada d'imatges + registre DB).
- **S5 (Media):** NGINX servint contingut estàtic d'`uploads/`.
- **S6 (Assets):** NGINX servint CSS/JS/SVG.
- **S7 (Dades):** MySQL (Persistència a `dbdata/`).

---

## Infraestructura i Accés (Sprint 1)

**Especificacions:** AWS EC2 `t3.medium` (2 vCPU, 4 GB RAM) sobre Ubuntu.

### Accés SSH
```bash
# Permisos i connexió
chmod 400 Baixades/Grupo5.pem
ssh -i Baixades/Grupo5.pem ubuntu@54.161.47.236
```

---

## Base de dades (en ús)
```sql
CREATE DATABASE extagram_db;
CREATE USER 'extagram_admin'@'%' IDENTIFIED BY 'pass123';
GRANT ALL PRIVILEGES ON extagram_db.* TO 'extagram_admin'@'%';
FLUSH PRIVILEGES;

CREATE TABLE extagram_db.posts (
  post     TEXT,
  photourl TEXT
);
```

> Esquema detallat a: [`files/sql/schema.sql`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/files/sql/schema.sql)

---

## Rutes del servei
- GET `/`            → S2/S3: llista posts (DB).
- POST `/upload`     → S4: desa imatge a `uploads/` + fila a DB.
- GET `/images/...`  → S5: serveix imatges.
- GET `/static/...`  → S6: serveix CSS/JS/SVG.

---

## NGINX

- Fitxer de configuració principal d’Nginx per a Extagram:  
  - [`files/nginx/extagram`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/files/nginx/extagram)
- Configuració pensada per:
  - Proxy invers cap als serveis PHP-FPM (`extragram.php`, `upload.php`, etc.).
  - Rutes d’estàtics (`/static`) servides des de `public/static`.
  - Rutes de pujades (`/uploads`) servides des de `public/uploads`.
  - Blocs per HTTPS integrats amb certificats Let’s Encrypt.

---

## Certificats i Let's Encrypt

Certificats generats amb Let’s Encrypt i emmagatzemats al repositori:

- Certificat complet (cadena):  
  - [`files/etc/letsencrypt/live/g5asixc2bc.com/fullchain.pem`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/files/etc/letsencrypt/live/g5asixc2bc.com/fullchain.pem)
- Clau privada:  
  - [`files/etc/letsencrypt/live/g5asixc2bc.com/privkey.pem`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/files/etc/letsencrypt/live/g5asixc2bc.com/privkey.pem)

Documentació del procés d’HTTPS / Let’s Encrypt i integració amb Nginx:

- Guia/document:  
  - [`Documentació/Script-HTTPS.md`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/Documentaci%C3%B3/Script-HTTPS.md)
- Scripts utilitzats:
  - [`files/scripts/Script-HTTPS.sh`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/files/scripts/Script-HTTPS.sh)
  - [`files/scripts/Script_Funcional.sh`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/files/scripts/Script_Funcional.sh)

---

## PHP / Backend

Fitxers PHP principals (publicats a `public/`):

- Connexió a la base de dades (PDO):  
  - [`public/db_conn.php`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/public/db_conn.php)
- Llistat i interacció de posts (vista principal):  
  - [`public/extagram.php`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/public/extagram.php)
- Interaccions addicionals amb posts/comentaris (AJAX / accions):  
  - [`public/interact.php`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/public/interact.php)
- Pujada d’imatges i inserció a BD:  
  - [`public/upload.php`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/public/upload.php)
- Eliminació de posts (amb llògica d’esborrat d’imatge i registres associats):  
  - [`public/delete_post.php`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/public/delete_post.php)
- Gestió de comentaris (part del CRUD de comentaris):  
  - [`public/delete_comment.php`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/public/delete_comment.php)

---

## Frontend / Estils i estàtics

- Full d’estils principal (Tailwind + customitzacions):  
  - [`public/static/style.css`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/public/static/style.css)
- Recursos estàtics (imatges, SVG, etc.):  
  - [`public/static/preview.svg`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/public/static/preview.svg)
- Directori de pujades (imatges dels usuaris):  
  - [`public/uploads/`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/tree/main/public/uploads) (conté `uploads/.gitkeep` per mantenir el directori en el repo).

---

# Canvis realitzats en el codi inicial

## Backend i Base de Dades
- Migració a PDO a [`public/db_conn.php`](../public/db_conn.php) i als scripts principals ([`public/extagram.php`](../public/extagram.php), [`public/upload.php`](../public/upload.php), etc.).
  - Consultes preparades (protecció contra SQL Injection).
  - Codi més net i mantenible.
- Esborrat en cascada a [`public/delete_post.php`](../public/delete_post.php):
  - Elimina la imatge del sistema de fitxers, els comentaris associats i el post de BD.
- Noms d’arxiu únics amb `uniqid()` a [`public/upload.php`](../public/upload.php) per evitar col·lisions i sobrescriptures.
- CRUD de comentaris:
  - [`public/delete_comment.php`](../public/delete_comment.php) per eliminar comentaris.
  - Lògica a [`public/interact.php`](../public/interact.php) per crear, llistar i eliminar comentaris.

## Interfície i UX (Tailwind + CSS propi)
- Maquetació responsive amb Tailwind a les vistes de [`public/`](../public/).
- Ús d’icones (Heroicons) i components moderns a les vistes principals.
- Millores d’UX a [`public/static/style.css`](../public/static/style.css):
  - Previsualització d’imatge en temps real.
  - Microinteraccions: efectes de hover i petites animacions en botons.

> Carpeta d’estàtics: [`public/static/`](../public/static/) · Carpeta de pujades: `public/uploads/` (generada en execució).

## Servidor i Configuració
- Nginx amb HTTPS (Let’s Encrypt) i rutes:
  - Estàtics: `/static`
  - Pujades: `/uploads`
  - Arxiu de configuració: [`files/nginx/extagram`](../files/nginx/extagram) (ruta del projecte: `Project0.1-2ASIXcB/files/nginx/extagram`)
- Scripts d’automatització:
  - [`files/scripts/Script-HTTPS.sh`](../files/scripts/Script-HTTPS.sh) (configuració HTTPS i certificats).
  - [`files/scripts/Script_Funcional.sh`](../files/scripts/Script_Funcional.sh) (posada en marxa, permisos, estructura de carpetes, reload, etc.).

---

# Justificació tecnològica

## Nginx (Servidor web)
- Arquitectura esdeveniment-driven: rendiment alt i consum de memòria contingut.
- Integració amb PHP-FPM: separa el servidor web del processador PHP per estabilitat i escalar fàcilment.
- Server blocks i rutes dedicades (`/static`, `/uploads`) per servir contingut eficientment.
- HTTP/2, compressió i headers de caché senzills de definir.
- TLS amb Let’s Encrypt i compatibilitat amb Cloudflare com a proxy/frontal.
- Config real del projecte a [`files/nginx/extagram`](../files/nginx/extagram).

## PHP 8.3
- Rendiment superior (JIT) respecte a 7.x.
- OPcache actiu per respostes més ràpides.
- Sintaxi moderna que facilita manteniment i seguretat del codi.

## Cloudflare
- SSL fàcil i gratuït sense complicar el servidor.
- Amaga la IP real de la instància, dificultant atacs directes.
- CDN i caché per servir estàtics des del node més proper a l’usuari.

## UI-Avatars API
- Estalvi d’espai en disc (no calen avatars genèrics al servidor).
- Zero manteniment i bona experiència d’usuari des del primer moment.

## Tailwind CSS
- Disseny ràpid amb utilitats (`rounded-full`, `shadow-md`, `flex`, etc.).
- Manteniment senzill (evitem un únic CSS gegant).
- Consistència visual a tot el frontend.

---

# Enllaços útils (APIs i docs)
- Tailwind CSS: https://tailwindcss.com/docs
- Heroicons: https://heroicons.com/
- UI-Avatars API: https://ui-avatars.com/
- Cloudflare (DNS, Proxy, SSL/TLS): https://dash.cloudflare.com/
- Let’s Encrypt (ACME): https://letsencrypt.org/
- PHP PDO: https://www.php.net/manual/en/book.pdo.php
- PHP OPcache: https://www.php.net/opcache
- Nginx docs: https://nginx.org/en/docs/

> Manuals: [`manual_admin.md`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/Documentaci%C3%B3/manual_admin.md) · [`manual_usuari.md`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/Documentaci%C3%B3/manual_usuari.md) · Punts d’entrada del projecte: [`public/`](/public/) · Config de servidor: [`files/nginx/`](/files/nginx/) · Scripts: [`files/scripts/`](/files/scripts/)

# DNS a Cloudflare

<img width="1261" height="337" alt="Captura de pantalla de 2026-01-19 15-50-41" src="https://github.com/user-attachments/assets/e4abb767-afc0-46d0-be3e-c7e9e616e8a4" />

Entès, té tot el sentit del món. Com que la seguretat és un dels pilars que heu tancat a l'**Sprint 4**, l'ideal és col·locar-ho al final de tot per tancar el document amb la capa de protecció que envolta tot el projecte.

Aquí tens el bloc llest per enganxar al final de la teva documentació de l'**Extagram - Projecte 0.1 ASIXcB G5**:

---

## 🔒 Seguretat i Protecció (Sprint 4)

Com a tancament de la infraestructura, hem implementat una capa de seguretat activa per protegir la instància de producció contra accessos no autoritzats i abusos.

### Firewall (UFW)

Hem configurat el **Uncomplicated Firewall** seguint una política de "Deny by Default". Només permetem el tràfic necessari per al funcionament de l'aplicació i la gestió de l'equip.

* **Política Entrant:** Denegada per defecte.
* **Ports Oberts:**
* `22/TCP` (SSH) per a administració.
* `80/TCP` i `443/TCP` per al servei web d'Extagram.
* `3000/TCP`, `3306/TCP` i `9000/TCP` per a la comunicació entre serveis (Frontend, DB i Backend).

<img width="592" height="344" alt="Captura de pantalla de 2026-02-23 16-08-33" src="https://github.com/user-attachments/assets/864a6380-a9a8-4ea3-9c58-866a6d3f84dd" />

### Prevenció d'Intrusions (Fail2Ban)

Per gestionar els atacs de força bruta, hem desplegat **Fail2Ban**, que monitoritza els logs i actua dinàmicament:

* **Protecció SSH:** Si es detecten 3 intents fallits, la IP atacant es bloqueja durant **1 hora**.
* **Filtre de Bots:** Bloqueig de **24 hores** per a IPs que escanegen rutes vulnerables de NGINX.
* **Recidiva:** Les IPs reincidents són banejades automàticament durant **1 setmana**.
- [`files/fail2ban/discord.conf`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/e252a6bec66f9add45ec8c0e9e0c904477f0df9e/files/fail2ban/discord.conf)
- [`files/fail2ban/fail2ban-discord.sh`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/e252a6bec66f9add45ec8c0e9e0c904477f0df9e/files/fail2ban/fail2ban-discord.sh)
- [`files/fail2ban/jail.local`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/e252a6bec66f9add45ec8c0e9e0c904477f0df9e/files/fail2ban/jail.local)


<img width="1845" height="471" alt="Captura de pantalla de 2026-02-23 16-11-19" src="https://github.com/user-attachments/assets/9738cd02-7261-48f4-abc7-8283153386be" />


### Integració amb Discord

Hem desenvolupat un sistema de notificacions automàtiques. Cada vegada que el sistema detecta i bloqueja una amenaça, l'equip rep una alerta en temps real al canal de **Discord** detallant la IP i el servei atacat.

<img width="592" height="391" alt="Captura de pantalla de 2026-02-23 16-08-58" src="https://github.com/user-attachments/assets/7e8dfb47-6e05-4b3e-af29-08b103d0eba6" />

### Gestió Segura de Secrets

Per garantir la higiene del repositori i la seguretat de l'equip:

* **Aïllament:** La URL del Webhook de Discord i altres claus sensibles es guarden en fitxers ocults (`.discord_secret`) amb permisos restrictius (`600`).
* **Git Hygiene:** Hem actualitzat el `.gitignore` per evitar que dades sensibles o logs del sistema es publiquin al GitHub del projecte.
* **Plantilles:** S'han inclòs fitxers `.example` per facilitar el desplegament en nous entorns sense exposar dades reals.

---
