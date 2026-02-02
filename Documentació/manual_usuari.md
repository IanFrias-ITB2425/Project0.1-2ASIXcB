# Manual d'Usuari - Extagram

Benvingut a **Extagram**, la plataforma de gestió de fotos del Grup 5. Aquesta guia t'explicarà com utilitzar les funcions del nostre servei des del punt d'entrada de l'usuari.

---

## Començant
Per accedir a l'aplicació, només cal que visitis el domini oficial:
**[g5asixc2bc.com](https://g5asixc2bc.com/)**

L'aplicació és totalment **responsive**, el que significa que pots fer-la servir tant des del teu ordinador com des del teu dispositiu mòbil.

---

## Funcions de la Plataforma

### 1. Visualització del Mur (Feed)
A la pàgina principal (`/`), podràs veure totes les publicacions fetes pels usuaris en ordre cronològic. 
* Les imatges es carreguen de forma optimitzada.
* Pots veure els comentaris associats a cada foto.

### 2. Publicar una nova imatge
Per pujar contingut, utilitza el formulari de pujada:
1.  Selecciona una imatge des del teu dispositiu.
2.  **Previsualització en temps real:** Gràcies al nostre sistema, podràs veure la imatge abans de confirmar la pujada.
3.  Escriu un peu de foto o descripció.
4.  Prem el botó de publicar. L'arxiu rebrà un nom únic per seguretat.

### 3. Interaccions i Comentaris
* **Afegir comentaris:** Pots interactuar amb qualsevol post afegint comentaris mitjançant la interfície dinàmica.
* **Gestió d'Avatars:** No cal que pugis una foto de perfil. El sistema genera automàticament un avatar personalitzat per a tu utilitzant l'API de UI-Avatars.

### 4. Eliminar contingut
Si vols esborrar una publicació:
* En prémer "Eliminar", el sistema realitzarà un **esborrat complet**: s'eliminarà la imatge del servidor, el registre de la base de dades i tots els comentaris que tingui aquell post.

---

## Detalls del Disseny (UX)
Hem posat especial atenció en la teva experiència:
* **Icones intuïtives:** Utilitzem la llibreria Heroicons perquè identifiquis ràpidament cada acció.
* **Velocitat:** Gràcies a la tecnologia PHP 8.3 i el cache d'Nginx, la navegació entre posts és fluida i sense esperes.
* **Estètica moderna:** Utilitzem Tailwind CSS per oferir una interfície neta, amb ombres suaves i micro-animacions en els botons.

---

## Tens problemes?
Si la pàgina no carrega o trobes algun error en la pujada, assegura't que:
1.  La teva connexió a internet és estable.
2.  L'arxiu que intentes pujar és una imatge vàlida (JPG, PNG, SVG).
3.  Estàs accedint mitjançant la connexió segura **HTTPS**.
