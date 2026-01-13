# Docuemntació de Extagram HTTPS

Aquesta documentació conté tot per configurar el servidor web NGINX per a l'aplicació **Extagram**, implementar seguretat SSL i aplicar una nova interfície d'usuari moderna.

## Característiques Principals

1.  **Seguretat SSL (HTTPS):** Genera automàticament certificats SSL autofirmats vàlids utilitzant OpenSSL.
2.  **Configuració NGINX:**
    * Crea un bloc de servidor (`server block`) complet.
    * Força la redirecció automàtica de tràfic HTTP (port 80) a HTTPS (port 443).
    * Configura PHP-FPM detectant la versió de PHP instal·lada automàticament.
3.  **Millora Visual (CSS):** Injecta un full d'estils `style.css` que imita el disseny modern d'Instagram, incloent la font *Poppins*, degradats en els botons i targetes amb ombres suaus.
4.  **Desplegament:** Reinicia el servei NGINX per aplicar els canvis i mostra la URL d'accés final.

## Requisits Previs

Abans d'executar l'script, assegurem que el servidor compleix els següents requisits:

* Sistema operatiu Linux (basat en Debian/Ubuntu recomanat).
* **NGINX** instal·lat.
* **PHP** i **PHP-FPM** instal·lats.
* L'aplicació ha d'estar ubicada a `/var/www/extagram`.
* Permisos de superusuari (`sudo`).

## Instruccions pas a pas

Segueix aquests passos per executar la configuració al teu servidor:

1.  **Descarregar o crear l'script:**
    Crea un fitxer anomenat `Script-HTTPS.sh` i enganxa-hi el codi.

2.  **Donar permisos d'execució:**
    És necessari fer l'script executable abans de córrer-lo.
    ```bash
    chmod +x Script-HTTPS.sh
    ```

3.  **Executar l'script:**
    Executa l'arxiu. No cal posar `sudo` davant si l'usuari ja té permisos, però l'script utilitza comandes `sudo` internament.
    ```bash
    ./Script-HTTPS.sh
    ```

4.  **Verificació:**
    Un cop finalitzat, l'script mostrarà la IP pública del servidor. Obre el navegador i accedeix a:
    `https://<LA_TEVA_IP>`

## Nota sobre els Certificats SSL

Aquest script genera certificats **autofirmats** (`openssl req -x509`).
* **Què significa això?** La connexió serà xifrada i segura, però els navegadors (Chrome, Firefox, etc.) mostraran una advertència de seguretat indicant que el certificat no ha estat emès per una autoritat certificadora coneguda.
* **Com procedir:** Hauràs d'acceptar l'advertència del navegador (normalment a "Configuració avançada" -> "Continuar al lloc web") per veure l'aplicació.

## Estructura de Fitxers Generats

L'script modifica o crea els següents fitxers del sistema:

| Fitxer | Descripció |
| :--- | :--- |
| `/etc/nginx/ssl/nginx.crt` | Certificat SSL públic |
| `/etc/nginx/ssl/nginx.key` | Clau privada del certificat |
| `/etc/nginx/sites-available/extagram` | Configuració del Virtual Host de NGINX |
| `/var/www/extagram/static/style.css` | Nou full d'estils amb disseny "Premium" |

---
