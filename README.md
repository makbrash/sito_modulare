# 🏃‍♂️ Bologna Marathon · Sistema Modulare

Piattaforma SSR modulare per bolognamarathon.run. Il progetto è pensato per funzionare su hosting PHP/MySQL tradizionali (nessun runtime Node richiesto in produzione) e mette a disposizione un sistema di moduli riutilizzabili affiancato da un page builder drag&drop.

## 📦 Stack & Requisiti

| Ambito            | Tecnologie |
|-------------------|------------|
| Runtime           | PHP 8.1+, MySQL 5.7+
| Front-end         | CSS Variables + Vanilla JS
| Tooling locale    | Node 18+, Gulp 4 (solo per build CSS/JS)
| Compatibilità     | Hosting condivisi senza Node.js

Per lo sviluppo locale è consigliato un ambiente tipo XAMPP o Valet.

## 🚀 Avvio rapido

```bash
# 1. Clona il repository
git clone <repository-url>
cd sito_modulare

# 2. Installa le dipendenze front-end (per sviluppo)
npm install

# 3. Avvia il backend (Apache/MySQL) e importa il database
mysql -u root -p < database/schema.sql

# 4. Popola dati di esempio (opzionale)
mysql -u root -p < database/test_data.sql

# 5. Avvia il watcher front-end
npm run dev

# 6. Visita
# Sito    → http://localhost/BM_layout/sito_modulare/index.php
# Admin   → http://localhost/BM_layout/sito_modulare/admin/admin.php
```

### Build per il cloud

```bash
npm run release
```

Genera la cartella `build/` con asset minificati e `index.php` già ottimizzato per l'upload su hosting senza Node.

## 🧠 Architettura

```
sito_modulare/
├─ admin/                 # Control center e page builder
│  ├─ assets/             # CSS e JS dedicati all'admin
│  ├─ includes/           # Helpers riutilizzabili (bootstrap, sync, util)
│  ├─ admin.php           # Dashboard modulare
│  ├─ page-builder.php    # Drag&drop per le istanze modulo
│  └─ sync-modules.php    # Report visivo per la sincronizzazione moduli
├─ core/ModuleRenderer.php# Motore SSR dei moduli
├─ modules/               # Moduli riutilizzabili (hero, menu, button...)
├─ assets/                # CSS/JS core del sito pubblico
├─ database/              # Schema SQL e dati di esempio
├─ build/                 # Output produzione (generato)
├─ index.php              # Entry point principale (dev)
└─ index-prod.php         # Template produzione
```

### Registro moduli

- Ogni modulo vive in `modules/<slug>/` e include:
  - `module.json` → manifest con slug, path componente, config di default, alias.
  - `<slug>.php` → vista SSR invocata dal `ModuleRenderer`.
  - Asset opzionali (CSS/JS) caricati manualmente.
- Il database mantiene un registro (`modules_registry`) sincronizzato con il filesystem.
- I moduli possono essere instanziati più volte e annidati tramite il page builder.

#### Moduli pubblici pronti all'uso

| Slug | Descrizione | Punti chiave |
|------|-------------|--------------|
| `hero` | Hero principale con overlay configurabile, CTA modulari e statistiche. | CTA annidate tramite modulo `button`, documentazione in `modules/hero/README.md`. |
| `highlights` | Griglia dei punti di forza dell'evento. | Card responsive con icone Font Awesome e CTA finale. |
| `event-schedule` | Timeline dei tre giorni di gara. | Supporta giornate multiple, location e call-to-action finale. |
| `race-cards` | Cards gare collegate al database `races`. | Colori tematici per maratona, 30km e run tune. |
| `results` | Tabella risultati collegata a `race_results`. | Supporta limiti configurabili e formati tempo dal renderer. |
| `button`, `text`, `select`, ... | Componenti atomici riutilizzabili. | Pensati per essere annidati in moduli compositi. |

## 🛠️ Admin Control Center

L'admin è stato ripensato con un'interfaccia coerente con il design del sito (variabili CSS esistenti) e prevede:

- **Dashboard** con statistiche e attività recenti.
- **Gestione risultati** collegata alla tabella `races` (niente ID hard-coded).
- **Gestione contenuti dinamici** con metadati JSON e flag `featured`.
- **Gestione pagine** con modifica rapida di titolo, description e CSS variables.
- **Gestione moduli** con toggle attiva/disattiva, manifest in linea e sincronizzazione diretta.
- **Page Builder** link diretto per il drag&drop delle istanze.

Il sync dei moduli ora utilizza `admin/includes/module_sync.php` e può essere lanciato sia dalla UI sia visitando `admin/sync-modules.php` (che restituisce un report leggibile).

## 🧩 Creare o aggiornare moduli

1. Duplica una cartella esistente in `modules/` o creane una nuova.
2. Aggiorna `module.json` con:
   ```json
   {
     "slug": "my-module",
     "component_path": "my-module/my-module.php",
     "css_class": "my-module",
     "default_config": {
       "title": "Titolo",
       "layout": "full"
     },
     "aliases": ["alias-opzionale"]
   }
   ```
3. Scrivi la vista PHP leggendo `$config` fornito dal renderer. Evita hardcoding di asset o testo.
4. Sincronizza dal pannello admin o con `admin/sync-modules.php`.
5. (Facoltativo) Aggiungi documentazione del modulo (`README.md` nella cartella) con schema dei campi → utile per automazioni LLM future.

### Linee guida moduli

- Usa sempre le variabili CSS già definite in `assets/css/core/variables.css`.
- Non modificare il modulo `menu` esistente; puoi crearne varianti alternative in nuove cartelle.
- Mantieni le funzioni idempotenti e prepara i dati nel controller PHP, non nelle viste.
- Per form/select riutilizza librerie affidabili (es. moduli già testati) invece di reinventare componenti.

## 🔄 Page Builder

Il builder usa `module_instances` per salvare la composizione delle pagine e supporta il drag&drop con SortableJS. Ogni modulo può essere annidato richiamando `$renderer->renderModule()` dall'interno di un altro modulo. La UI consente di:

- Creare nuove istanze con nome univoco.
- Aggiornare configurazioni partendo dal manifest (`default_config`).
- Ordinare e rimuovere moduli senza side-effects.

## 📚 Utility & Script

| Comando             | Descrizione |
|---------------------|-------------|
| `npm run dev`       | Watch + BrowserSync (configurabile via `BROWSERSYNC_PROXY`).
| `npm run css:build` | Build solo CSS.
| `npm run js:build`  | Bundle JS vanilla.
| `npm run release`   | Prepara la cartella `build/` per il deploy.

## 🧪 Quality check

- PHP: rispettare PSR-12 (usa `php -l` per lint veloce).
- JS: ES2018+ senza optional chaining lato produzione pubblica (ammesso nell'admin se necessario).
- CSS: niente nesting tipo `&` (solo CSS standard + variabili).

## 🤝 Workflow consigliato

1. Crea un branch `feature/<nome>`.
2. Implementa il modulo/feature seguendo queste linee guida.
3. Aggiorna la documentazione del modulo (manifest + README dedicato se serve).
4. Esegui build/test localmente.
5. Apri una PR descrivendo moduli coinvolti e impatto sull'admin.

## 🧭 Note per automazioni future

- Mantieni i manifest aggiornati: un LLM potrà leggere slug, campi e dipendenze per generare pagine da prompt.
- Documenta eventuali nuovi componenti in `modules/<slug>/README.md` e inserisci esempi di configurazione JSON.
- Evita logiche lato client invasive: il rendering deve rimanere SSR per garantire SEO e semplicità d'uso.

---

**Bologna Marathon – Sistema modulare** · progettato per essere mantenibile, modulare e pronto all'integrazione con sistemi di generazione automatica di contenuti.
