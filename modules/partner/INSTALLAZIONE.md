# Installazione Modulo Partner

## 🚀 Setup Completo

### 1. Esegui Installazione Database
```sql
-- Esegui il file install.sql
source modules/partner/install.sql;
```

### 2. Verifica Installazione
```sql
-- Controlla che la tabella sia stata creata
DESCRIBE sponsors;

-- Verifica che tutti gli sponsor siano stati inseriti
SELECT COUNT(*) as total_sponsors FROM sponsors;

-- Controlla sponsor per gruppo
SELECT group_type, COUNT(*) as count 
FROM sponsors 
GROUP BY group_type;
```

### 3. Test Modulo
1. Vai su `http://localhost/sito_modulare/admin/test-setup.php`
2. Verifica che il modulo "partner" sia registrato
3. Aggiungi il modulo a una pagina tramite page builder
4. Testa la visualizzazione

## 📊 Struttura Database

### Tabella `sponsors`
```sql
CREATE TABLE sponsors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    category VARCHAR(100) NOT NULL,
    group_type ENUM('main', 'official', 'technical', 'sponsor') NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    website_url VARCHAR(500),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Gruppi Sponsor
- **main**: Sponsor principali (Pellicon)
- **official**: Sponsor ufficiali (Hyundai, Culligan, Birra Poretti)
- **technical**: Sponsor tecnici (Joma + Sponsor 2)
- **sponsor**: Sponsor generici (Sponsor)

## 🎯 Utilizzo

### Nel Page Builder
1. Aggiungi il modulo "Partner"
2. Configura i gruppi da mostrare
3. Personalizza il titolo se necessario

### Nel Codice
```php
// Configurazione personalizzata
$config = [
    'title' => 'I Nostri Partner',
    'show_group1' => true,  // Sponsor Principali
    'show_group2' => true,  // Sponsor
    'show_group3' => true   // Sponsor Tecnici
];
```

## 🔧 Gestione Sponsor

### Aggiungere Nuovo Sponsor
```sql
INSERT INTO sponsors (name, category, group_type, image_path, sort_order) 
VALUES ('Nuovo Sponsor', 'categoria', 'main', 'path/immagine.png', 100);
```

### Disattivare Sponsor
```sql
UPDATE sponsors SET is_active = 0 WHERE name = 'Nome Sponsor';
```

### Modificare Ordine
```sql
UPDATE sponsors SET sort_order = 50 WHERE name = 'Nome Sponsor';
```

## 🎨 Personalizzazione

### Modificare CSS
Il file `partner.css` è già ottimizzato e non richiede modifiche.

### Aggiungere Nuovi Gruppi
1. Modifica `partner.php` per aggiungere nuovo gruppo
2. Aggiorna `partner.js` per gestire nuovo gruppo
3. Aggiungi configurazione in `module.json`

## 🐛 Troubleshooting

### Modulo non appare
- Verifica che `modules_registry` contenga il modulo
- Controlla che `is_active = 1`
- Verifica path del componente

### Sponsor non si caricano
- Controlla che la tabella `sponsors` esista
- Verifica che gli sponsor abbiano `is_active = 1`
- Controlla errori PHP nel log

### Swiper non funziona
- Verifica che Swiper.js sia caricato
- Controlla errori JavaScript in console
- Verifica configurazione breakpoints

## 📈 Performance

### Ottimizzazioni
- Le immagini sono lazy-loaded
- Swiper è inizializzato solo quando necessario
- Autoplay si ferma quando la pagina non è visibile

### Monitoraggio
- Controlla console per errori JavaScript
- Verifica log PHP per errori server
- Monitora performance con DevTools

## 🔄 Aggiornamenti

### Aggiungere Nuovi Sponsor
1. Aggiungi immagini nella cartella appropriata
2. Inserisci record nel database
3. Il modulo si aggiorna automaticamente

### Modificare Gruppi
1. Modifica `group_type` nel database
2. Aggiorna `sort_order` se necessario
3. Il modulo riflette le modifiche automaticamente
