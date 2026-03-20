## 🔹 1. Instrucțiuni pentru rularea proiectului

### Cerințe:

* WordPress instalat (local sau online)
* PHP 7.4+
* Acces la panoul de administrare (`/wp-admin`)


### Pași de instalare:

1. Accesează directorul WordPress:

   ```
   /wp-content/plugins/
   ```

2. Creează folderul:

   ```
   usm-notes
   ```

3. Adaugă fișierul:

   ```
   usm-notes.php
   ```

   și inserează codul pluginului.

4. În panoul de administrare WordPress:

   * Mergi la **Plugins → Installed Plugins**
   * Activează **USM Notes**

5. Reîmprospătează permalinks:

   * **Settings → Permalinks → Save Changes**


## 🔹 2. Descrierea lucrării de laborator

Scopul acestui laborator este dezvoltarea unui plugin WordPress personalizat pentru gestionarea notițelor.

Funcționalitățile implementate includ:

* Crearea unui **Custom Post Type (CPT)** pentru notițe
* Definirea unei **taxonomii personalizate** pentru priorități
* Adăugarea unui **metacâmp** pentru data de reamintire
* Implementarea unui **metabox** în editorul WordPress
* Salvarea securizată a datelor folosind **nonce**
* Validarea datelor introduse (ex: interzicerea datelor din trecut)
* Extinderea interfeței admin (coloană custom)
* Crearea unui **shortcode dinamic** pentru afișare în frontend
* Stilizarea listelor de notițe

Acest laborator demonstrează cum WordPress poate fi extins pentru a crea aplicații personalizate.


## 🔹 3. Documentație succintă pentru plugin

### Custom Post Type: `note`

* Reprezintă notițele utilizatorului
* Suportă:

  * titlu
  * conținut
  * autor
  * imagine (thumbnail)


### Taxonomie: `priority`

* Tip: ierarhic (similar categoriilor)
* Valori recomandate:

  * High
  * Medium
  * Low


### Metacâmp: `_usm_reminder_date`

* Tip: dată (`YYYY-MM-DD`)
* Câmp obligatoriu
* Nu acceptă valori din trecut


### Metabox

* Locație: editorul CPT „Notițe”
* Conține input:

  ```
  type="date"
  ```


### Securitate

* Utilizare **nonce** pentru protecție CSRF
* Verificare:

  * permisiuni utilizator
  * tip post
  * autosave


### Shortcode

```
[usm_notes]
```

Atribute disponibile:

* `priority` – filtrare după prioritate
* `before_date` – filtrare după dată


## 🔹 4. Exemple de utilizare

### Afișarea tuturor notițelor:

```
[usm_notes]
```


### Notițe cu prioritate mare:

```
[usm_notes priority="high"]
```


### Notițe până la o anumită dată:

```
[usm_notes before_date="2025-04-30"]
```


### Filtrare combinată:

```
[usm_notes priority="high" before_date="2025-04-30"]
```


### Exemplu pagină „All Notes”:

```
<h2>Toate notițele</h2>
[usm_notes]

<h2>Prioritate mare</h2>
[usm_notes priority="high"]

<h2>Notițe până la 30 aprilie 2025</h2>
[usm_notes before_date="2025-04-30"]
```


## 🔹 5. Observații finale

* Pluginul este extensibil și poate fi adaptat pentru:

  * task management
  * reminder system
  * organizare personală

* Poate fi integrat cu:

  * notificări email
  * filtre dinamice (AJAX)
  * API-uri externe


## Autor

Sorin
