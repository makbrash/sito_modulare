# 🏷️ Installazione Sponsor Credits

Guida rapida per aggiungere i loghi credits al database.

## 📋 Prerequisiti

- ✅ Database `sito_modulare` esistente
- ✅ Tabella `sponsors` già creata
- ✅ Loghi presenti in `assets/images/sponsor/credits/`

## 🚀 Installazione Rapida

### Opzione 1: Via MySQL Command Line

```bash
# Connetti al database
mysql -u root -p sito_modulare

# Esegui lo script
source modules/partner/add_credits_sponsors.sql

# Oppure
\. modules/partner/add_credits_sponsors.sql
```

### Opzione 2: Via phpMyAdmin

1. Apri phpMyAdmin: `http://localhost/phpmyadmin`
2. Seleziona database `sito_modulare`
3. Vai su "SQL"
4. Copia e incolla il contenuto di `add_credits_sponsors.sql`
5. Clicca "Esegui"

### Opzione 3: Via Script PHP

```php
<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$sql = file_get_contents('modules/partner/add_credits_sponsors.sql');
$db->exec($sql);

echo "✅ Credits sponsor installati con successo!";
?>
```

## 📊 Loghi Credits Inclusi

| # | Nome | File | Tipo |
|---|------|------|------|
| 1 | FIDAL | LogoFidal.jpg | Ente |
| 2 | CONI Emilia Romagna | CONI_EMILIA_ROMAGNA.png | Ente |
| 3 | Comune di Bologna | 06-comune-di-bologna-bn-rgb (8).png | Ente |
| 4 | Sport Valley ER | SPORT-VALLEY-ER-COLOR.png | Ente |
| 5 | Bologna per lo Sport | BO-per-lo-sport_LOGO_yellow.png | Ente |
| 6 | CSI Bologna | Logo CSI Bologna.png | Organizzatore |
| 7 | Run Tune Up | Logo RTU-01.png | Organizzatore |
| 8 | BM Termal | Logo BM-Termal.png | Organizzatore |
| 9 | 30km Portici | 30km.png | Gara |
| 10 | 5km City Run | 5km_Bologna City Run.png | Gara |

## ✅ Verifica Installazione

```sql
-- Conta credits inseriti
SELECT COUNT(*) as 'Totale Credits' 
FROM sponsors 
WHERE group_type = 'credits';

-- Mostra tutti i credits
SELECT id, name, image_path, is_active 
FROM sponsors 
WHERE group_type = 'credits' 
ORDER BY sort_order ASC;
```

**Risultato atteso**: 10 record con `group_type = 'credits'`

## 🔧 Troubleshooting

### Errore: "Unknown column 'credits'"

**Problema**: L'ENUM non include 'credits'

**Soluzione**: Lo script include già l'ALTER TABLE. Verifica che sia stato eseguito:

```sql
SHOW COLUMNS FROM sponsors LIKE 'group_type';
```

Dovresti vedere: `enum('main','official','technical','sponsor','credits')`

### Errore: "Duplicate entry"

**Problema**: Alcuni loghi sono già presenti

**Soluzione**: Lo script usa `ON DUPLICATE KEY UPDATE`, quindi aggiorna semplicemente. Nessun problema.

### Immagini non si vedono

**Problema**: Path delle immagini errato

**Soluzione**: Verifica che i file siano in `assets/images/sponsor/credits/`

```bash
# Controlla che i file esistano
ls -la assets/images/sponsor/credits/
```

## 🎨 Visualizzazione nel Modulo

Dopo l'installazione, i loghi appariranno automaticamente nel modulo Partner se hai:

```php
$config = [
    'show_group4' => true  // Mostra Credits
];
```

Nel Page Builder:
- ☑ **Mostra Credits** (checkbox)

## 📝 Note

- **Sort Order**: I loghi sono ordinati 101-110 per non sovrapporsi con gli altri sponsor
- **Category**: Tutti hanno `category = 'credits'` per facile identificazione
- **is_active**: Tutti attivi di default (`is_active = 1`)
- **Design**: I loghi credits sono automaticamente più piccoli (CSS già impostato)

## 🔄 Aggiornamento

Per modificare i loghi credits:

```sql
-- Aggiorna path immagine
UPDATE sponsors 
SET image_path = 'nuovo/path/logo.png' 
WHERE name = 'Nome Sponsor' AND group_type = 'credits';

-- Disattiva un logo
UPDATE sponsors 
SET is_active = 0 
WHERE name = 'Nome Sponsor' AND group_type = 'credits';

-- Modifica ordine
UPDATE sponsors 
SET sort_order = 105 
WHERE name = 'Nome Sponsor' AND group_type = 'credits';
```

## 🗑️ Rimozione

Per rimuovere i credits:

```sql
-- Disattiva tutti i credits
UPDATE sponsors SET is_active = 0 WHERE group_type = 'credits';

-- Oppure elimina definitivamente
DELETE FROM sponsors WHERE group_type = 'credits';
```

---

**Credits Sponsor System - Bologna Marathon** 🏷️

*Versione 1.0.0 - Gennaio 2025*

