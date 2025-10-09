# 🔄 Migrazioni Database - Guida Completa

## 🎯 Panoramica
Questa guida documenta tutte le migrazioni database applicate al sistema modulare Bologna Marathon, con istruzioni per applicarle e rollback.

## 📋 Migrazioni Applicate

### 1. Sistema Template Globali
**File**: `database/add_module_templates.sql`
**Data**: 2025-01-XX
**Descrizione**: Aggiunta supporto per modelli globali

#### Modifiche Schema
```sql
-- Aggiunta colonne per template system
ALTER TABLE module_instances ADD COLUMN is_template BOOLEAN DEFAULT FALSE;
ALTER TABLE module_instances ADD COLUMN template_name VARCHAR(100);
ALTER TABLE module_instances ADD COLUMN template_instance_id INT DEFAULT NULL;

-- Modifica page_id per permettere NULL (template master)
ALTER TABLE module_instances MODIFY COLUMN page_id INT DEFAULT NULL;

-- Indici per performance
ALTER TABLE module_instances ADD INDEX idx_is_template (is_template);
ALTER TABLE module_instances ADD INDEX idx_template_instance (template_instance_id);

-- Foreign key per template_instance_id
ALTER TABLE module_instances ADD CONSTRAINT fk_template_instance 
FOREIGN KEY (template_instance_id) REFERENCES module_instances(id) ON DELETE SET NULL;
```

#### Applicazione
```bash
# Metodo 1: Script automatico
mysql -u username -p database_name < database/add_module_templates.sql

# Metodo 2: phpMyAdmin
# 1. Apri phpMyAdmin
# 2. Seleziona database
# 3. Vai su SQL
# 4. Incolla contenuto del file
# 5. Esegui
```

#### Rollback
```sql
-- Rimuovi foreign key
ALTER TABLE module_instances DROP FOREIGN KEY fk_template_instance;

-- Rimuovi indici
ALTER TABLE module_instances DROP INDEX idx_is_template;
ALTER TABLE module_instances DROP INDEX idx_template_instance;

-- Rimuovi colonne
ALTER TABLE module_instances DROP COLUMN is_template;
ALTER TABLE module_instances DROP COLUMN template_name;
ALTER TABLE module_instances DROP COLUMN template_instance_id;

-- Ripristina page_id NOT NULL
ALTER TABLE module_instances MODIFY COLUMN page_id INT NOT NULL;
```

### 2. Colonna Theme per Pagine
**File**: `database/add_theme_column.sql`
**Data**: 2025-01-XX
**Descrizione**: Aggiunta supporto temi dinamici per pagine

#### Modifiche Schema
```sql
-- Aggiungi colonna theme se non esiste
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'pages'
    AND COLUMN_NAME = 'theme'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE pages ADD COLUMN theme VARCHAR(50) DEFAULT "race-marathon" AFTER status',
    'SELECT "Colonna theme già esistente" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Aggiungi indice per performance
ALTER TABLE pages ADD INDEX idx_theme (theme);
```

#### Applicazione
```bash
mysql -u username -p database_name < database/add_theme_column.sql
```

#### Rollback
```sql
-- Rimuovi indice
ALTER TABLE pages DROP INDEX idx_theme;

-- Rimuovi colonna
ALTER TABLE pages DROP COLUMN theme;
```

### 3. Fix Module Instances Page ID
**File**: `database/fix_module_instances_page_id.sql`
**Data**: 2025-01-XX
**Descrizione**: Fix per permettere page_id NULL per template master

#### Modifiche Schema
```sql
-- Verifica se page_id è già nullable
SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'module_instances'
AND COLUMN_NAME = 'page_id';

-- Se IS_NULLABLE = 'NO', modifica
ALTER TABLE module_instances MODIFY COLUMN page_id INT DEFAULT NULL;
```

#### Applicazione
```bash
mysql -u username -p database_name < database/fix_module_instances_page_id.sql
```

## 🔧 Strumenti Migrazione

### Script di Verifica
**File**: `database/verify_migrations.sql`

```sql
-- Verifica migrazioni applicate
SELECT 'Template System' as migration,
       CASE 
           WHEN EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_NAME = 'module_instances' AND COLUMN_NAME = 'is_template')
           THEN 'APPLICATA'
           ELSE 'NON APPLICATA'
       END as status;

SELECT 'Theme Column' as migration,
       CASE 
           WHEN EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_NAME = 'pages' AND COLUMN_NAME = 'theme')
           THEN 'APPLICATA'
           ELSE 'NON APPLICATA'
       END as status;

-- Verifica indici
SELECT INDEX_NAME, COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'module_instances'
AND INDEX_NAME IN ('idx_is_template', 'idx_template_instance');

-- Verifica foreign keys
SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'module_instances'
AND CONSTRAINT_NAME = 'fk_template_instance';
```

### Script di Backup
**File**: `database/backup_before_migration.sql`

```sql
-- Backup completo prima di migrazione
-- Esegui: mysqldump -u username -p database_name > backup_YYYYMMDD.sql

-- Backup specifico tabelle critiche
CREATE TABLE pages_backup AS SELECT * FROM pages;
CREATE TABLE module_instances_backup AS SELECT * FROM module_instances;
CREATE TABLE modules_registry_backup AS SELECT * FROM modules_registry;
```

## 🚨 Troubleshooting Migrazioni

### Problemi Comuni

#### "Duplicate column name"
**Causa**: Migrazione già applicata
**Soluzione**: 
```sql
-- Verifica colonna esistente
SHOW COLUMNS FROM table_name LIKE 'column_name';

-- Se esiste, skip migrazione
```

#### "Column 'page_id' cannot be null"
**Causa**: Template master richiede page_id NULL
**Soluzione**:
```sql
-- Applica fix page_id
ALTER TABLE module_instances MODIFY COLUMN page_id INT DEFAULT NULL;
```

#### "Foreign key constraint fails"
**Causa**: Riferimenti orfani
**Soluzione**:
```sql
-- Trova orfani
SELECT * FROM module_instances 
WHERE template_instance_id IS NOT NULL 
AND template_instance_id NOT IN (SELECT id FROM module_instances WHERE is_template = TRUE);

-- Rimuovi orfani
DELETE FROM module_instances 
WHERE template_instance_id IS NOT NULL 
AND template_instance_id NOT IN (SELECT id FROM module_instances WHERE is_template = TRUE);
```

## 📊 Monitoraggio Migrazioni

### Log Migrazioni
```sql
-- Tabella per tracking migrazioni
CREATE TABLE IF NOT EXISTS migration_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    migration_name VARCHAR(100) NOT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rollback_sql TEXT,
    status ENUM('applied', 'rolled_back') DEFAULT 'applied',
    INDEX idx_migration_name (migration_name),
    INDEX idx_applied_at (applied_at)
);

-- Log applicazione migrazione
INSERT INTO migration_log (migration_name, rollback_sql) 
VALUES ('template_system', 'ALTER TABLE module_instances DROP COLUMN is_template;');
```

### Verifica Integrità
```sql
-- Verifica integrità template system
SELECT 
    'Template senza nome' as issue,
    COUNT(*) as count
FROM module_instances 
WHERE is_template = TRUE AND template_name IS NULL

UNION ALL

SELECT 
    'Istanze con template inesistente' as issue,
    COUNT(*) as count
FROM module_instances mi
WHERE mi.template_instance_id IS NOT NULL 
AND mi.template_instance_id NOT IN (
    SELECT id FROM module_instances WHERE is_template = TRUE
);
```

## 🔄 Processo Migrazione Sicuro

### 1. Pre-Migrazione
```bash
# 1. Backup completo
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Verifica spazio disco
df -h

# 3. Test su database di sviluppo
mysql -u username -p test_database < migration.sql
```

### 2. Durante Migrazione
```bash
# 1. Esegui migrazione
mysql -u username -p database_name < migration.sql

# 2. Verifica applicazione
mysql -u username -p database_name < verify_migrations.sql

# 3. Test funzionalità
# - Test Page Builder
# - Test template globali
# - Test rendering pagine
```

### 3. Post-Migrazione
```bash
# 1. Verifica integrità
mysql -u username -p database_name < check_integrity.sql

# 2. Monitora performance
SHOW PROCESSLIST;
SHOW STATUS LIKE 'Slow_queries';

# 3. Log migrazione
INSERT INTO migration_log (migration_name) VALUES ('migration_name');
```

## 📋 Checklist Migrazione

### Pre-Migrazione
- [ ] Backup completo database
- [ ] Test su ambiente di sviluppo
- [ ] Verifica spazio disco disponibile
- [ ] Notifica team su downtime
- [ ] Preparato script rollback

### Durante Migrazione
- [ ] Esegui migrazione
- [ ] Verifica applicazione corretta
- [ ] Test funzionalità critiche
- [ ] Monitora errori database
- [ ] Verifica performance

### Post-Migrazione
- [ ] Test completo sistema
- [ ] Verifica integrità dati
- [ ] Monitora performance
- [ ] Documenta migrazione applicata
- [ ] Notifica team completamento

## 📚 Riferimenti

### File Migrazione
- `database/add_module_templates.sql` - Sistema template
- `database/add_theme_column.sql` - Colonna theme
- `database/fix_module_instances_page_id.sql` - Fix page_id

### Script Utilità
- `database/verify_migrations.sql` - Verifica migrazioni
- `database/backup_before_migration.sql` - Backup pre-migrazione
- `database/check_integrity.sql` - Verifica integrità

### Guide Correlate
- `SCHEMA-REFERENCE.md` - Riferimento schema
- `../admin/docs/FIXES.md` - Fix applicati
- `../admin/docs/TROUBLESHOOTING.md` - Troubleshooting

---

**Migrazioni Database - Sistema Modulare Bologna Marathon** 🔄

*Guida completa per amministratori database e sviluppatori*
