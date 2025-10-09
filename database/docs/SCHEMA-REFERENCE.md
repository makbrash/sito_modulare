# 🗄️ Schema Database - Riferimento Completo

## 🎯 Panoramica
Questo documento fornisce un riferimento completo dello schema database del sistema modulare Bologna Marathon, con esempi pratici e best practices.

## 📊 Tabelle Principali

### pages
**Scopo**: Pagine del sito con configurazioni e metadati

```sql
CREATE TABLE pages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    template VARCHAR(50) DEFAULT 'default',
    layout_config JSON,
    css_variables JSON,
    meta_data JSON,
    status ENUM('draft', 'published') DEFAULT 'draft',
    theme VARCHAR(50) DEFAULT 'race-marathon',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_theme (theme)
);
```

#### Colonne
| Colonna | Tipo | Nullable | Default | Descrizione |
|---------|------|----------|---------|-------------|
| `id` | INT | NO | AUTO_INCREMENT | ID univoco pagina |
| `slug` | VARCHAR(100) | NO | - | URL della pagina |
| `title` | VARCHAR(200) | NO | - | Titolo pagina |
| `description` | TEXT | YES | NULL | Meta description |
| `template` | VARCHAR(50) | YES | 'default' | Template layout |
| `layout_config` | JSON | YES | NULL | Configurazione layout |
| `css_variables` | JSON | YES | NULL | CSS custom per pagina |
| `meta_data` | JSON | YES | NULL | Meta tags SEO |
| `status` | ENUM | YES | 'draft' | Stato pubblicazione |
| `theme` | VARCHAR(50) | YES | 'race-marathon' | Tema pagina |
| `created_at` | TIMESTAMP | YES | CURRENT_TIMESTAMP | Data creazione |
| `updated_at` | TIMESTAMP | YES | CURRENT_TIMESTAMP | Data modifica |

### module_instances
**Scopo**: Istanze moduli per ogni pagina con configurazioni specifiche

```sql
CREATE TABLE module_instances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_id INT DEFAULT NULL,
    module_name VARCHAR(100) NOT NULL,
    instance_name VARCHAR(100) NOT NULL,
    config JSON,
    order_index INT DEFAULT 0,
    is_template BOOLEAN DEFAULT FALSE,
    template_name VARCHAR(100),
    template_instance_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_page_id (page_id),
    INDEX idx_module_name (module_name),
    INDEX idx_is_template (is_template),
    INDEX idx_template_instance (template_instance_id),
    INDEX idx_order (order_index),
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);
```

#### Colonne
| Colonna | Tipo | Nullable | Default | Descrizione |
|---------|------|----------|---------|-------------|
| `id` | INT | NO | AUTO_INCREMENT | ID univoco istanza |
| `page_id` | INT | YES | NULL | ID pagina (NULL per template) |
| `module_name` | VARCHAR(100) | NO | - | Nome del modulo |
| `instance_name` | VARCHAR(100) | NO | - | Nome istanza |
| `config` | JSON | YES | NULL | Configurazione modulo |
| `order_index` | INT | YES | 0 | Ordinamento nella pagina |
| `is_template` | BOOLEAN | YES | FALSE | Se è template globale |
| `template_name` | VARCHAR(100) | YES | NULL | Nome template globale |
| `template_instance_id` | INT | YES | NULL | ID template master |
| `created_at` | TIMESTAMP | YES | CURRENT_TIMESTAMP | Data creazione |
| `updated_at` | TIMESTAMP | YES | CURRENT_TIMESTAMP | Data modifica |

### modules_registry
**Scopo**: Registro moduli disponibili nel sistema

```sql
CREATE TABLE modules_registry (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    component_path VARCHAR(200) NOT NULL,
    css_class VARCHAR(100),
    default_config JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_is_active (is_active)
);
```

#### Colonne
| Colonna | Tipo | Nullable | Default | Descrizione |
|---------|------|----------|---------|-------------|
| `id` | INT | NO | AUTO_INCREMENT | ID univoco |
| `name` | VARCHAR(100) | NO | UNIQUE | Nome modulo |
| `component_path` | VARCHAR(200) | NO | - | Percorso file PHP |
| `css_class` | VARCHAR(100) | YES | NULL | Classe CSS principale |
| `default_config` | JSON | YES | NULL | Configurazione default |
| `is_active` | BOOLEAN | YES | TRUE | Se modulo è attivo |
| `created_at` | TIMESTAMP | YES | CURRENT_TIMESTAMP | Data registrazione |

## 🎯 Tabelle Specifiche

### race_results
**Scopo**: Risultati delle gare

```sql
CREATE TABLE race_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    race_id INT NOT NULL,
    position INT NOT NULL,
    bib_number VARCHAR(10),
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    time VARCHAR(20),
    category VARCHAR(50),
    gender ENUM('M', 'F'),
    nationality VARCHAR(3),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_race_id (race_id),
    INDEX idx_position (position),
    INDEX idx_category (category),
    FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE CASCADE
);
```

### races
**Scopo**: Gare disponibili

```sql
CREATE TABLE races (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    distance VARCHAR(50),
    date DATE,
    status ENUM('upcoming', 'ongoing', 'completed') DEFAULT 'upcoming',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_date (date),
    INDEX idx_status (status)
);
```

### theme_identities
**Scopo**: Identità tematiche per personalizzazione

```sql
CREATE TABLE theme_identities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    css_variables JSON,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_is_active (is_active)
);
```

## 🔗 Relazioni

### Relazioni Principali
```
pages (1) -----> (N) module_instances
pages (1) -----> (N) race_results [tramite races]
races (1) -----> (N) race_results
module_instances (1) -----> (1) module_instances [template system]
```

### Template System
```sql
-- Template master (is_template = TRUE, page_id = NULL)
INSERT INTO module_instances (module_name, config, is_template, template_name, page_id) 
VALUES ('hero', '{"title":"Default"}', TRUE, 'Hero Standard', NULL);

-- Istanza collegata (template_instance_id punta al master)
INSERT INTO module_instances (page_id, module_name, config, template_instance_id) 
VALUES (1, 'hero', '{"title":"Default"}', 1);
```

## 📝 Esempi Query

### Pagine Pubblicate
```sql
SELECT id, slug, title, status, theme 
FROM pages 
WHERE status = 'published' 
ORDER BY updated_at DESC;
```

### Moduli per Pagina
```sql
SELECT mi.*, mr.component_path 
FROM module_instances mi
JOIN modules_registry mr ON mi.module_name = mr.name
WHERE mi.page_id = ? AND mr.is_active = TRUE
ORDER BY mi.order_index;
```

### Template Globali
```sql
SELECT id, module_name, template_name, config
FROM module_instances 
WHERE is_template = TRUE 
ORDER BY template_name;
```

### Istanze con Template
```sql
SELECT mi.id, mi.instance_name, t.template_name, t.config as template_config
FROM module_instances mi
JOIN module_instances t ON mi.template_instance_id = t.id
WHERE mi.page_id = ?;
```

## 🔧 Best Practices

### Indici
```sql
-- Indici per performance
CREATE INDEX idx_pages_status_theme ON pages(status, theme);
CREATE INDEX idx_instances_page_module ON module_instances(page_id, module_name);
CREATE INDEX idx_instances_template ON module_instances(template_instance_id, is_template);
```

### Prepared Statements
```php
// ✅ CORRETTO
$stmt = $db->prepare("SELECT * FROM pages WHERE slug = ? AND status = ?");
$stmt->execute([$slug, 'published']);
$page = $stmt->fetch();

// ❌ SBAGLIATO
$query = "SELECT * FROM pages WHERE slug = '$slug'";
$result = $db->query($query);
```

### JSON Columns
```php
// Salvataggio JSON
$config = json_encode($moduleConfig);
$stmt = $db->prepare("INSERT INTO module_instances (config) VALUES (?)");
$stmt->execute([$config]);

// Lettura JSON
$stmt = $db->prepare("SELECT config FROM module_instances WHERE id = ?");
$stmt->execute([$instanceId]);
$instance = $stmt->fetch();
$config = json_decode($instance['config'], true);
```

## 🚨 Troubleshooting

### Problemi Comuni

#### Foreign Key Violations
```sql
-- Verifica orfani
SELECT mi.* FROM module_instances mi
LEFT JOIN pages p ON mi.page_id = p.id
WHERE mi.page_id IS NOT NULL AND p.id IS NULL;
```

#### Template Inconsistencies
```sql
-- Verifica template non collegati
SELECT * FROM module_instances 
WHERE is_template = TRUE 
AND template_name IS NULL;
```

#### Duplicate Slugs
```sql
-- Trova slug duplicati
SELECT slug, COUNT(*) as count 
FROM pages 
GROUP BY slug 
HAVING count > 1;
```

## 📊 Performance

### Query Optimization
```sql
-- Usa EXPLAIN per analizzare query
EXPLAIN SELECT mi.*, t.config as template_config
FROM module_instances mi
LEFT JOIN module_instances t ON mi.template_instance_id = t.id
WHERE mi.page_id = 1
ORDER BY mi.order_index;
```

### Monitoring
```sql
-- Query lente da monitorare
SHOW PROCESSLIST;
SHOW FULL PROCESSLIST;

-- Analisi tabelle
ANALYZE TABLE pages;
ANALYZE TABLE module_instances;
```

## 🔄 Migrazioni

### Aggiunta Colonna
```sql
-- Esempio: aggiunta colonna theme
ALTER TABLE pages ADD COLUMN theme VARCHAR(50) DEFAULT 'race-marathon' AFTER status;
```

### Modifica Tipo Colonna
```sql
-- Esempio: modifica page_id per template system
ALTER TABLE module_instances MODIFY COLUMN page_id INT DEFAULT NULL;
```

### Backup e Restore
```sql
-- Backup
mysqldump -u username -p database_name > backup.sql

-- Restore
mysql -u username -p database_name < backup.sql
```

## 📚 Riferimenti

### File Schema
- `database/schema.sql` - Schema completo
- `database/test_data.sql` - Dati di test
- `database/add_module_templates.sql` - Migrazione template system

### Guide Correlate
- `../admin/docs/PAGE-BUILDER.md` - Page Builder
- `../modules/docs/TEMPLATES-SYSTEM.md` - Sistema modelli
- `../admin/docs/FIXES.md` - Fix database

---

**Schema Database - Sistema Modulare Bologna Marathon** 🗄️

*Riferimento completo per sviluppatori e AI models*
