# 🏁 Bologna Marathon – Sistema Modulare

Sistema SSR modulare per la Bologna Marathon con page builder visuale, moduli riutilizzabili e pipeline di rilascio cloud friendly.

## 🌐 Architettura in breve

| Layer | Descrizione |
| --- | --- |
| **Frontend** | PHP SSR, CSS Variables e JavaScript vanilla. Nessuna dipendenza runtime su Node in produzione. |
| **Admin** | Page builder drag & drop basato su moduli annidabili, API JSON e manifest dei moduli. |
| **Backend** | PHP 8+, MySQL 5.7+. Tutte le query usano prepared statement. |
| **Build** | Gulp 4 per bundling CSS/JS e generazione cartella `build/` deployable. |

## 📁 Struttura principale

```
sito_modulare/
├── admin/                # Interfaccia e API page builder
│   ├── api/              # Endpoint JSON per moduli e pagine
│   └── page-builder.php  # UI amministrativa
├── assets/
│   ├── css/              # CSS core + admin
│   └── js/               # JS core + admin
├── core/ModuleRenderer.php
├── modules/              # Moduli con manifest e assets dedicati
├── database/             # Schema SQL e dati di esempio
├── build/                # Output pronto per il cloud (no Node richiesto)
└── gulpfile.js           # Pipeline di build
```

## ⚙️ Setup rapido (locale)

1. **Prerequisiti**
   - PHP ≥ 8.0
   - MySQL ≥ 5.7
   - Node.js ≥ 16 (solo per build locale)
   - Composer *non* necessario

2. **Installazione**
   ```bash
   git clone <repo>
   cd sito_modulare
   npm install
   ```

3. **Database**
   - Aggiorna le credenziali in `config/database.php`
   - Importa `database/schema.sql` (contiene dati di esempio)
   - Verifica con `admin/test-setup.php`

4. **Sviluppo**
   ```bash
   npm run dev        # Watch mode senza server
   npm run serve      # Watch mode con BrowserSync
   ```

5. **Build e Release**
   ```bash
   npm run build      # Build solo asset
   npm run release    # Build completo per cloud
   npm run rollback   # Rollback all'ultimo backup
   ```

La cartella `build/` contiene tutto il necessario per il deploy (PHP + asset minificati). Nessuna dipendenza Node in produzione.

## 🧩 Page Builder (admin/page-builder.php)

### Funzionalità principali
- Drag & drop con SortableJS (supporto ordinamento dinamico)
- Moduli annidabili basati su manifest JSON (`modules/<slug>/module.json`)
- Configuratore dinamico generato da `ui_schema`
- Anteprima live con rendering server-side
- API RESTful (`admin/api/page_builder.php`) per CRUD istanze moduli

### Workflow
1. **Seleziona una pagina** dal menù a tendina
2. **Aggiungi moduli** dalla libreria (riutilizzabili e annidabili)
3. **Configura il modulo** tramite form generato da `ui_schema`
4. **Salva** per creare/aggiornare l'istanza (`module_instances`)
5. **Trascina** per riordinare (persistenza automatica dell'ordine)
6. **Anteprima** apre rendering lato server in modal oppure pagina pubblica

### UI Schema (estratto)
   ```json
"ui_schema": {
  "title": {
    "type": "text",
    "label": "Titolo",
    "placeholder": "Titolo sezione",
    "help": "Usato nell'hero principale"
  },
  "menu_items": {
    "type": "array",
    "label": "Voci menu",
    "item_schema": {
      "label": { "type": "text", "label": "Etichetta" },
      "url":   { "type": "url",  "label": "URL" }
    }
  }
}
```

Ogni campo supporta `type`, `label`, `placeholder`, `default`, `help`, `options` (per select) e strutture `array` con `item_schema` annidato.

## 🧱 Moduli

### Guida Completa Sviluppo
- **Documentazione**: `MODULE-DEVELOPMENT-GUIDE.md` (guida completa)
- **Regole**: `MODULE-RULES.md` (regole specifiche)
- **Template**: `MODULE-TEMPLATE.md` (template completo)
- **Checklist**: `MODULE-CHECKLIST.md` (checklist validazione)
- **Riepilogo**: `MODULE-SUMMARY.md` (riepilogo rapido)
- **Esempi**: `modules/README.md` (esempi pratici)

### Struttura Modulo
- Ogni modulo vive in `modules/<slug>/`
- File obbligatori: `module.json`, template PHP, CSS/JS opzionali
- `module.json` deve includere almeno:
  ```json
  {
    "name": "Hero",
    "slug": "hero",
    "component_path": "hero/hero.php",
    "default_config": { ... },
    "ui_schema": { ... }
  }
  ```
- I campi `default_config` e `ui_schema` vengono uniti lato server con la configurazione salvata
- Documenta ogni modulo con README o schema per facilitare automazione LLM futura

### Regole CSS CRITICHE
- **MAI** stili annidati (`&:hover`, `&::before`)
- **SOLO** CSS classico esplicito
- **SEMPRE** CSS Variables
- **SEMPRE** BEM methodology
- **SEMPRE** mobile-first responsive

### Consigli
- Riutilizza moduli esistenti quando possibile
- Evita hardcoding di colori: usa `assets/css/core/variables.css`
- Mantieni compatibilità con CSS del menu principale
- Per select/form usa componenti validati dalla community (es. [Shoelace](https://shoelace.style/)) integrandoli via manifest `assets.vendors`

## 🛠️ Maintenance & Quality

- **PHP**: segui PSR-12, niente `try/catch` attorno agli `include`
- **JS**: ES2015+, nessun transpiler necessario
- **CSS**: niente nesting tipo `&`, usa classi esplicite
- **Database**: tutte le tabelle già indicizzate, mantieni `module_instances.instance_name` univoco per pagina
- **Logs**: eventuali errori AJAX restituiscono JSON con messaggi significativi

### Test veloci
- `php -l admin/page-builder.php` (lint)
- `npm run release` (verifica build)
- Controlla anteprima moduli dalla UI admin

## 🚀 Deploy su cloud

1. Esegui `npm run release`
2. Carica il contenuto di `build/` sul server PHP
3. Imposta credenziali DB su `build/config/database.php`
4. (Opzionale) configura cache HTTP e compressione da `.htaccess`

> **Nota:** la produzione non richiede Node.js. Tutti gli asset sono già precompilati.

## 📄 Licenza

MIT License – consulta il file `LICENSE` per i dettagli.

---

Per ulteriori dettagli su singoli moduli consulta `modules/README.md` e mantieni aggiornate le documentazioni per supportare future integrazioni automatizzate.