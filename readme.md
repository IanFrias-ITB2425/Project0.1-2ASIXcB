# Planificació Inicial P0.1 ASIXcB G5

## Enllaços
- Tasques (ProofHub): https://itecbcn.proofhub.com/bapplite/#app/todos/project-9429814374/list-270271277074
- Web: https://g5asixc2bc.com/

---

## Arquitectura Inicial
- S1: NGINX (proxy invers i balanceig) cap a S2/S3/S4/S5/S6.
- S2 i S3: PHP-FPM executant `extragram.php` (part dinàmica).
- S4: PHP-FPM amb `upload.php` (pujada d’imatges a `uploads/` i registre a BD).
- S5: NGINX servint imatges des d’`uploads/` (estàtic).
- S6: NGINX servint CSS/JS/SVG (estàtic).
- S7: MySQL (taula `posts`), dades persistents a `dbdata/`.

Diagrama ràpid:
```
Browser → [S1 LB] → { S2 extragram.php | S3 extragram.php | S4 upload.php | S5 images | S6 static } → S7 MySQL
```

Directoris/volums:
- `uploads/` (imatges pujades)
- `dbdata/` (dades MySQL)

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
Notes:
- En prod: contrasenya forta i restringir host (idealment `'localhost'`).
- Backups: `mysqldump` diari + snapshot del volum `dbdata/`.

---

## Rutes del servei
- GET `/`            → S2/S3: llista posts (DB).
- POST `/upload`     → S4: desa imatge a `uploads/` + fila a DB.
- GET `/images/...`  → S5: serveix imatges.
- GET `/static/...`  → S6: serveix CSS/JS/SVG.

---

## NGINX
- Fitxer de configuració al repositori: [Config NGINX (extagram)](https://github.com/IanFrias-ITB2425/Project0.1-2ASIXc2BC/blob/main/files/files/nginx/extagram)
---
