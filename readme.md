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
---

## Rutes del servei
- GET `/`            → S2/S3: llista posts (DB).
- POST `/upload`     → S4: desa imatge a `uploads/` + fila a DB.
- GET `/images/...`  → S5: serveix imatges.
- GET `/static/...`  → S6: serveix CSS/JS/SVG.

---

## NGINX
- Fitxer de configuració al repositori: [Config NGINX (extagram)](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXcB/blob/main/files/nginx/extagram)
---

## Canvis realitzats en el codi inicial

- Backend i BD: Migració a PDO; esborrat en cascada a `delete_post.php` (elimina imatge, comentaris i post); noms d’arxiu únics amb `uniqid()` a `upload.php`; CRUD de comentaris amb `delete_comment.php`.
- Interfície i UX (Tailwind): Disseny responsive; icones Heroicons; previsualització d’imatge en temps real; microinteraccions (hover i animacions en botons).
- Servidor i configuració: Nginx amb blocs per HTTPS, certificats SSL i rutes d’estàtics (`/static`) i pujades (`/uploads`).

---
