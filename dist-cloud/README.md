# Bologna Marathon - Sistema Modulare

Sistema modulare per il sito ufficiale della Bologna Marathon, progettato per essere facilmente gestibile e personalizzabile.

## 🚀 Caratteristiche

- **Sistema Modulare**: Componenti riutilizzabili e configurabili
- **CSS Variables**: Personalizzazione colori e stili tramite variabili CSS
- **SSR (Server-Side Rendering)**: Performance ottimali e SEO friendly
- **Responsive Design**: Ottimizzato per desktop, tablet e mobile
- **Database MySQL**: Gestione contenuti dinamici
- **Build System**: Gulp per ottimizzazione assets

## 📁 Struttura Progetto

```
sito_modulare/
├── assets/
│   ├── css/
│   │   ├── core/           # CSS core (variables, reset, typography)
│   │   └── main.css        # Stili principali
│   ├── js/
│   │   └── core/           # JavaScript core
│   └── images/             # Immagini
├── config/
│   └── database.php        # Configurazione database
├── core/
│   └── ModuleRenderer.php  # Sistema rendering moduli
├── modules/                # Moduli riutilizzabili
│   ├── hero/               # Modulo hero
│   ├── results/            # Tabella risultati
│   ├── menu/               # Menu navigazione
│   └── footer/             # Footer sito
├── database/
│   └── schema.sql          # Schema database
└── index.php              # Homepage
```

## 🛠️ Installazione

### 1. Prerequisiti
- PHP 8.0+
- MySQL 5.7+
- Node.js 16+ (per build system)
- XAMPP/WAMP/LAMP

### 2. Setup Database
```sql
-- Crea database
CREATE DATABASE bologna_marathon;

-- Importa schema
mysql -u root -p bologna_marathon < database/schema.sql
```

### 3. Configurazione
Modifica `config/database.php` con le tue credenziali:
```php
private $host = 'localhost';
private $db_name = 'bologna_marathon';
private $username = 'root';
private $password = '';
```

### 4. 🚀 Sistema DEV (Nuovo!)
```bash
# Installa dipendenze
npm install

# Sviluppo con live-reload
npm run dev
# → http://localhost:3000/sito_modulare

# Produzione
npm run build

# Build per Cloud (senza memory leak)
npm run build:cloud
```

**📖 Vedi [README-DEV-SYSTEM.md](README-DEV-SYSTEM.md) per dettagli completi**

## 🎨 Personalizzazione

### CSS Variables
Personalizza colori e stili modificando le variabili in `assets/css/core/variables.css`:



### Override per Pagina
Ogni pagina può avere variabili CSS personalizzate tramite database:

```json
{
  "--primary": "#FF5722",
  "--hero-bg": "linear-gradient(135deg, #667eea 0%, #764ba2 100%)"
}
```

## 📦 Moduli Disponibili

### Action Hero
```php
// Configurazione
{
  "title": "Bologna Marathon 2025",
  "subtitle": "La corsa più bella d'Italia",
  "image": "hero-bg.jpg",
  "layout": "2col"
}
```

### Results Table
```php
// Configurazione
{
  "race_id": 1,
  "limit": 50,
  "show_categories": true,
  "sortable": true
}
```

### Rich Text
```php
// Configurazione
{
  "content": "Contenuto HTML...",
  "wrapper": "article"
}
```

## 🔧 Sviluppo

### Aggiungere un Nuovo Modulo

1. **Crea cartella modulo**:
```
modules/mio-modulo/
├── mio-modulo.php
├── mio-modulo.css
└── mio-modulo.js
```

2. **Registra modulo nel database**:
```sql
INSERT INTO modules_registry (name, component_path, css_class, default_config) 
VALUES ('mioModulo', 'modules/mio-modulo/mio-modulo.php', 'mio-modulo', '{"param": "value"}');
```

3. **Template modulo**:
```php
<?php
// modules/mio-modulo/mio-modulo.php
$moduleData = $renderer->getModuleData('mioModulo', $config);
?>

<div class="mio-modulo">
    <h2><?= htmlspecialchars($moduleData['title']) ?></h2>
    <p><?= htmlspecialchars($moduleData['content']) ?></p>
</div>
```

### Workflow Sviluppo

1. **Modifica CSS**: Modifica file in `assets/css/`
2. **Build**: `npm run build` per ottimizzare
3. **Test**: Verifica su browser
4. **Deploy**: Carica su server

## 📊 Database Schema

### Tabelle Principali
- `pages`: Pagine del sito
- `modules_registry`: Moduli disponibili
- `page_modules`: Moduli assegnati alle pagine
- `race_results`: Risultati gara
- `dynamic_content`: Contenuti dinamici

## 🚀 Deploy

### Produzione
1. Ottimizza assets: `npm run build`
2. Configura database produzione
3. Carica file su server
4. Imposta permessi corretti

### Performance
- CSS e JS minificati
- Immagini ottimizzate
- Cache database
- CDN per assets statici

## 🤖 AI Integration

Il sistema è progettato per integrazione con AI:

```json
{
  "template": "home",
  "cssOverrides": {
    "--primary-color": "#D81E05"
  },
  "modules": [
    {
      "type": "actionHero",
      "props": {
        "title": "Bologna Marathon 2025",
        "layout": "2col"
      }
    }
  ]
}
```

## 📝 Licenza

MIT License - Vedi file LICENSE per dettagli.

## 👥 Supporto

Per supporto e domande:
- Email: dev@bolognamarathon.run
- GitHub Issues: [Repository Issues]

---

**Bologna Marathon Team** 🏃‍♂️🏃‍♀️
