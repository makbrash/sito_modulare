# 🏃‍♀️ Sito Modulare – Bologna Marathon

Sistema SSR modulare per il sito ufficiale della Bologna Marathon. Il progetto unisce PHP, MySQL e componenti riutilizzabili per generare pagine dinamiche senza dipendenze Node in produzione.

## ✨ Caratteristiche principali

- **Rendering modulare**: ogni sezione del sito è un modulo PHP indipendente con manifest JSON, asset dedicati e configurazione riutilizzabile.
- **Page Builder drag & drop**: nuova interfaccia amministrativa con canvas a moduli annidabili, ispettore dinamico e anteprime server-side.
- **API amministrative**: endpoint REST-like per pagine, contenuti, risultati e istanze modulo (`admin/api/index.php`).
- **Configurazione dichiarativa**: i moduli espongono `module.json` con metadati, default config e hook SQL opzionali.
- **Build cloud friendly**: deployment PHP puro; Node è usato solo in locale per la toolchain Gulp.

## 🗂️ Struttura del progetto

```
sito_modulare/
├── admin/
│   ├── api/                 # Endpoint JSON per l'admin
│   ├── assets/              # JS e CSS dell'interfaccia
│   ├── includes/            # Servizi condivisi (PDO, repository)
│   └── index.php            # Nuova interfaccia amministrativa
├── assets/
│   └── css/core/            # Reset, typography e variabili globali
├── config/
│   └── database.php         # Gestione connessione PDO parametrica
├── core/
│   └── ModuleRenderer.php   # Renderer SSR con supporto annidamento
├── database/                # Schema SQL e dati di test
├── modules/                 # Moduli riutilizzabili (hero, menu, button…)
├── index.php                # Entrypoint pubblico (ambiente dev)
├── index-prod.php           # Entrypoint pronto per deploy cloud
├── package.json / gulpfile  # Tooling di sviluppo (solo locale)
└── README.md                # Questo file
```

## 🚀 Setup locale

1. **Dipendenze**
   - PHP 8.1+
   - MySQL/MariaDB
   - Node.js 18+ (solo per task di build opzionali)

2. **Database**
   ```bash
   mysql -u root -p < database/schema.sql
   mysql -u root -p < database/test_data.sql # opzionale
   ```

   Parametri connessione configurabili via variabili ambiente (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`) o passando un array al costruttore `Database`.

3. **Avvio sito**
   - Pubblica la cartella `sito_modulare` su Apache / nginx con PHP attivo.
   - Accedi al builder: `http://localhost/sito_modulare/admin/`.

4. **Tooling opzionale**
   ```bash
   npm install
   npm run dev     # live reload con BrowserSync (necessita PHP locale)
   npm run release # genera la cartella build/ pronta per deploy cloud
   ```

## 🧩 Moduli

Ogni modulo vive in `modules/<slug>/` ed espone:

```
modules/button/
├── button.php      # markup server-side
├── button.css      # stile specifico
├── button.js       # interazioni opzionali
├── install.sql     # hook per inizializzazione DB (opzionale)
└── module.json     # manifest del modulo
```

`module.json` definisce metadati, configurazione di default e dipendenze. Esempio:

```json
{
  "name": "Button",
  "slug": "button",
  "component_path": "button/button.php",
  "css_class": "btn",
  "default_config": {
    "text": "Clicca qui",
    "variant": "primary",
    "size": "medium",
    "href": "#"
  },
  "aliases": [],
  "assets": {
    "css": ["modules/button/button.css"],
    "js": ["modules/button/button.js"]
  }
}
```

### Annidamento dei moduli

Il `ModuleRenderer` supporta ora slot annidati tramite la chiave `children` della configurazione. Nei template PHP puoi renderizzare gli slot con:

```php
<div class="hero__actions">
    <?= $renderer->renderChildren($config, 'default'); ?>
</div>
```

Nel builder l’utente può trascinare un modulo dentro un altro; la configurazione viene serializzata nel JSON dell’istanza genitore.

## 🛠️ Interfaccia amministrativa

- **Dashboard**: statistiche rapide su pagine, moduli, contenuti e risultati.
- **Page Builder**: canvas drag & drop con catalogo moduli, supporto annidamento, ispettore in tempo reale e anteprime lato server.
- **Registro Moduli**: attiva/disattiva moduli del filesystem con un click.
- **Contenuti dinamici**: gestione tabellare dell’entità `dynamic_content`.
- **Risultati**: inserimento rapido dei record `race_results`.

Gli endpoint JSON sono documentati nel codice (`admin/api/index.php`) e restituiscono payload consistenti: `GET admin/api/index.php?resource=pages`, `POST admin/api/index.php?resource=module-instances`, ecc.

## 🧱 Linee guida per nuovi moduli

1. **Manifest completo**: includi `slug`, `version`, `default_config`, eventuali `aliases` e asset.
2. **Readme di modulo**: aggiungi un file `README.md` dentro la cartella per descrivere campi, slot e esempi di utilizzo (utile per futuri agenti/LLM).
3. **Configurazioni tipizzate**: prediligi strutture semplici (stringhe, numeri, boolean) o JSON validi quando necessario.
4. **Slot annidati**: prevedi zone dichiarate con `renderChildren` per permettere l’iniezione di sottocomponenti (es. CTA in un hero).
5. **Riutilizzo**: evita hardcoding di testi/immagini; usa parametri o riferimenti a contenuti nel DB.

## 📦 Build & deploy

- `npm run release` genera la cartella `build/` con asset minificati e `index.php` pronto per ambienti cloud PHP-only.
- In produzione **non** sono richieste dipendenze Node.js: è sufficiente deployare i file PHP/asset generati.
- Configura web server per puntare a `index.php` (dev) o `build/index.php` (release) e assicurati che `config/database.php` legga i parametri dal sistema.

## 🧰 Strumenti per la manutenzione

- `admin/sync-modules.php`: sincronizza i moduli presenti su filesystem con il registro DB.
- `admin/test-setup.php`: controlla rapidamente la presenza del database e consente la creazione tabelle in locale.
- `admin/fix-css-variables.php`: script di migrazione per variabili CSS legacy.

## 🤝 Contributi

- Mantieni i CSS modulari evitando tecniche di annidamento non supportate (es. `&` in plain CSS).
- Non modificare i file core (`assets/css/core/*`) né il modulo `menu` salvo reali bug.
- Usa il file `.cursor/rules` per linee guida dedicate agli editor/AI.

Buon lavoro! 🧡
