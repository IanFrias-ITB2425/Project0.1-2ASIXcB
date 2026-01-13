# Planificació Inicial P0.1 ASIXcB G5

## Enllaços
- Tasques (ProofHub): https://itecbcn.proofhub.com/bapplite/#app/todos/project-9429814374/list-270271277074
- Web: https://g5asixc2bc.com/

---

## Arquitectura Inicial
<img align="right" width="320" alt="Captura Arquitectura" src="https://github.com/user-attachments/assets/e1185035-66a8-4d61-a5f0-bc0377b435d9" />

- S1: NGINX (proxy invers i balanceig) cap a S2/S3/S4/S5/S6.
- S2 i S3: PHP-FPM executant `extragram.php` (part dinàmica).
- S4: PHP-FPM amb `upload.php` (pujada d’imatges a `uploads/` i registre a BD).
- S5: NGINX servint imatges des d’`uploads/` (estàtic).
- S6: NGINX servint CSS/JS/SVG (estàtic).
- S7: MySQL (taula `posts`), dades persistents a `dbdata/`.

Directoris/volums:
- `uploads/` (imatges pujades)
- `dbdata/` (dades MySQL)

<br clear="right" />

---

## Infraestructura (Sprint 1)
- AWS EC2 t3.medium (2 vCPU, 4 GB RAM), Ubuntu.
- Arrencada ràpida en una instància; arquitectura preparada per separar rols.

---

## Accés al servidor
```bash
ssh -i Baixades/Grupo5.pem ubuntu@54.161.47.236
chmod 400 Baixades/Grupo5.pem
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

Fichers PHP principals (publicats a `public/`):

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

## Canvis realitzats en el codi inicial

- **Backend i BD**:
  - Migració a PDO a [`public/db_conn.php`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/public/db_conn.php) i als scripts principals (`extagram.php`, `upload.php`, etc.).
  - Esborrat en cascada a [`public/delete_post.php`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/public/delete_post.php) (elimina imatge, comentaris i post).
  - Noms d’arxiu únics amb `uniqid()` a [`public/upload.php`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/public/upload.php).
  - CRUD de comentaris amb [`public/delete_comment.php`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/public/delete_comment.php) i la lògica relacionada a [`public/interact.php`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/public/interact.php).

- **Interfície i UX (Tailwind)**:
  - Disseny responsive i maquetació basada en Tailwind al frontend (HTML/PHP dels fitxers de `public/`).
  - Ús d’icones (Heroicons) i components moderns a les vistes principals.
  - Previsualització d’imatge en temps real i microinteraccions (hover i animacions en botons) definides a [`public/static/style.css`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/public/static/style.css).

- **Servidor i configuració**:
  - Nginx amb blocs per HTTPS, certificats SSL Let’s Encrypt i rutes d’estàtics (`/static`) i pujades (`/uploads`) definits a [`files/nginx/extagram`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/files/nginx/extagram).
  - Scripts per automatitzar la configuració d’HTTPS i posada en marxa del servei:
    - [`files/scripts/Script-HTTPS.sh`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/files/scripts/Script-HTTPS.sh)
    - [`files/scripts/Script_Funcional.sh`](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/files/scripts/Script_Funcional.sh)

---
