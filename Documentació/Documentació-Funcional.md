# Extagram - Documentació Desplegament Automatitzat

Amb el script (`Script_Funcional.sh`) està dissenyat per automatitzar el desplegament de l'aplicació web **Extagram**. El script configura un servidor complet (LEMP Stack) i genera el codi font de l'aplicació des de zero.

## Índex
- [Descripció del Projecte](#descripció-del-projecte)
- [Requisits Previs](#requisits-previs)
- [Instal·lació i Ús](#installació-i-ús)
- [Arquitectura del Sistema](#arquitectura-del-sistema)
- [Detalls de Configuració](#detalls-de-configuració)
- [Autor](#autor)

---

## Descripció del Projecte

El script realitza les següents tasques de forma seqüencial:
1.  **Actualització del sistema:** `apt update`.
2.  **Instal·lació del LEMP Stack:** Nginx, MySQL, PHP (FPM) i mòduls necessaris.
3.  **Configuració de permisos:** Crea l'estructura de directoris a `/var/www/` i assigna permisos al grup `www-data`.
4.  **Base de Dades:** Crea la BBDD, l'usuari administrador i les taules necessàries automàticament.
5.  **Configuració Nginx:** Crea un *Virtual Host* personalitzat per servir fitxers PHP i rutes estàtiques.
6.  **Generació de Codi:** Crea dinàmicament els fitxers `.php`, `.css` i `.svg` necessaris per a l'aplicació.

## Requisits Previs

* Un servidor o màquina virtual amb **Ubuntu** (Recomanat 20.04 o 22.04).
* Accés a internet per descarregar paquets.
* Permisos de superusuari (`sudo`).
* No tenir altres serveis ocupant el port 80 (Apache/Nginx previs) o el script els sobreescriurà/aturarà.

## Instal·lació i Ús

1.  **Descarregar el script** al servidor:
    ```bash
    git clone [https://github.com/EL_TEU_USUARI/EL_TEU_REPO.git](https://github.com/EL_TEU_USUARI/EL_TEU_REPO.git)
    cd EL_TEU_REPO
    ```

2.  **Donar permisos d'execució:**
    ```bash
    chmod +x Script_Funcional.sh
    ```

3.  **Executar el script:**
    ```bash
    sudo ./Script_Funcional.sh
    ```

4.  **Verificar el desplegament:**
    Al final de l'execució, el script mostrarà la IP pública. Obre el navegador i ves a:
    `http://LA_TEVA_IP`

## Arquitectura del Sistema

El script organitza l'aplicació en la següent estructura de directoris dins de `/var/www/extagram`:

```text
/var/www/extagram/
├── public/           # (Root de Nginx)
│   ├── extagram.php  # Pàgina principal (Llistat de posts i formulari)
│   ├── upload.php    # Script PHP que processa la pujada d'imatges
│   └── db_conn.php   # Credencials de connexió a MySQL
├── static/           # (Actius estàtics)
│   ├── style.css     # Fulls d'estil
│   └── preview.svg   # Imatge per defecte per a la previsualització
└── uploads/          # (Emmagatzematge)
    └── [imatges.jpg] # Les fotos que pugen els usuaris
