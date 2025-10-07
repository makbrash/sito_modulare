# 🚀 Installazione Modulo Newsletter

## 📦 File Creati

```
modules/newsletter/
├── newsletter.php          ✅ Template principale
├── newsletter.css          ✅ Stili (BEM + CSS Variables)
├── newsletter.js           ✅ JavaScript interattivo
├── module.json             ✅ Manifest completo
├── install.sql             ✅ Script database
├── register-module.php     ✅ Script registrazione
├── README.md              ✅ Documentazione completa
└── INSTALLAZIONE.md       ✅ Questo file

api/
└── newsletter-subscribe.php ✅ API endpoint

test-newsletter.html        ✅ Pagina di test
```

## 🎯 Installazione Rapida

### 1️⃣ Registra il Modulo nel Database

```bash
# Apri nel browser
http://localhost/sito_modulare/modules/newsletter/register-module.php
```

**Oppure esegui manualmente:**

```bash
php modules/newsletter/register-module.php
```

### 2️⃣ Testa il Modulo

```bash
# Apri la pagina di test
http://localhost/sito_modulare/test-newsletter.html
```

### 3️⃣ Usa nel Page Builder

1. Vai su: `http://localhost/sito_modulare/admin/page-builder.php`
2. Seleziona una pagina
3. Aggiungi modulo "Newsletter"
4. Configura il tipo di registrazione
5. Salva

## 🎨 Tre Modalità di Utilizzo

### 📧 Newsletter Classica
```json
{
  "type": "classic",
  "title": "Newsletter",
  "subtitle": "Resta aggiornato",
  "variant": "primary"
}
```

**Caratteristiche:**
- Form con nome + email
- Checkbox privacy obbligatorio
- Validazione real-time
- API endpoint per salvataggio

### 💬 WhatsApp Messaggio
```json
{
  "type": "whatsapp",
  "title": "Iscriviti via WhatsApp",
  "whatsapp_number": "393123456789",
  "whatsapp_message": "Ciao! Voglio registrarmi",
  "variant": "secondary"
}
```

**Caratteristiche:**
- Pulsante con link WhatsApp
- Messaggio precompilato
- Apertura diretta dell'app
- Design con icona WhatsApp verde

### 📢 Canale WhatsApp
```json
{
  "type": "channel",
  "title": "Seguici su WhatsApp",
  "channel_url": "https://whatsapp.com/channel/0029Vb2BgN0GOj9uUN5Zjp3Z",
  "variant": "dark"
}
```

**Caratteristiche:**
- Link diretto al canale
- Features visualizzate
- Contatore membri
- Call-to-action efficace

## 🎯 Configurazione Avanzata

### Background Personalizzato

```json
{
  "background_image": "/assets/images/bg-newsletter.jpg",
  "background_color": "linear-gradient(135deg, #667eea, #764ba2)"
}
```

### Colori Personalizzati

Modifica `assets/css/core/colors.css`:

```css
.newsletter--custom {
  --primary: #your-color;
  --secondary: #your-color-2;
}
```

## 🔧 Configurazione API (Newsletter Classica)

### Verifica Endpoint API

Il file `api/newsletter-subscribe.php` è già pronto. Verifica che:

1. **Database configurato** (`config/database.php`)
2. **Tabella creata** (eseguita da `register-module.php`)
3. **CORS abilitato** (già configurato)

### Test API Manuale

```bash
curl -X POST http://localhost/sito_modulare/api/newsletter-subscribe.php \
  -d "name=Mario Rossi" \
  -d "email=mario@example.com" \
  -d "privacy=1"
```

**Risposta attesa:**
```json
{
  "success": true,
  "message": "Iscrizione completata con successo!",
  "data": {
    "name": "Mario Rossi",
    "email": "mario@example.com"
  }
}
```

## 📊 Database

### Tabella `newsletter_subscribers`

Creata automaticamente dallo script di installazione con:

- ✅ Gestione stati (pending, confirmed, unsubscribed)
- ✅ Double opt-in ready
- ✅ Tracking IP e User Agent
- ✅ Stored procedures
- ✅ Viste per statistiche

### Query Utili

```sql
-- Iscritti attivi
SELECT * FROM active_newsletter_subscribers;

-- Statistiche
SELECT * FROM newsletter_stats;

-- Conferma iscrizione
CALL confirm_newsletter_subscription('email@example.com');

-- Disiscrizione
CALL unsubscribe_newsletter('email@example.com');
```

## 🎨 Personalizzazione CSS

### Override Stili

```css
/* Nel tuo CSS custom */
.newsletter {
  --newsletter-bg: your-custom-gradient;
}

.newsletter__title {
  font-size: 4rem;
  color: #your-color;
}
```

### Responsive Custom

```css
@media (max-width: 600px) {
  .newsletter__content {
    padding: var(--space-md);
  }
}
```

## 🧪 Testing

### Checklist Completa

- [ ] **Newsletter Classica**
  - [ ] Form appare correttamente
  - [ ] Validazione nome funziona
  - [ ] Validazione email funziona
  - [ ] Checkbox privacy obbligatorio
  - [ ] Submit invia dati all'API
  - [ ] Messaggio successo appare
  - [ ] Messaggio errore appare
  
- [ ] **WhatsApp Messaggio**
  - [ ] Pulsante WhatsApp appare
  - [ ] Click apre WhatsApp
  - [ ] Messaggio precompilato corretto
  - [ ] Numero destinatario corretto
  
- [ ] **Canale WhatsApp**
  - [ ] Pulsante canale appare
  - [ ] Link canale corretto
  - [ ] Features visualizzate
  - [ ] Click apre canale

- [ ] **Responsive**
  - [ ] Desktop (>1024px)
  - [ ] Tablet (768px-1024px)
  - [ ] Mobile (<768px)

## 🚨 Troubleshooting

### Modulo non appare
```bash
# Verifica registrazione database
php modules/newsletter/register-module.php
```

### Form non invia
- Controlla console browser per errori
- Verifica endpoint API: `/api/newsletter-subscribe.php`
- Controlla configurazione database

### WhatsApp non si apre
- Verifica formato numero (senza +): `393123456789`
- Testa su mobile con WhatsApp installato
- Desktop: verifica WhatsApp Web

### Canale WhatsApp non funziona
- Verifica URL: `https://whatsapp.com/channel/...`
- Aggiorna WhatsApp all'ultima versione
- I canali sono disponibili solo su versioni recenti

## 📱 Integrazione WhatsApp Business

### Setup Consigliato

1. **WhatsApp Business Account**
   - Crea account business
   - Configura profilo aziendale
   - Abilita risposte automatiche

2. **Webhook per Messaggi**
   - Configura webhook API
   - Gestisci messaggi in arrivo
   - Risposte automatiche

3. **Database Integrato**
   - Salva contatti da WhatsApp
   - Sincronizza con newsletter classica
   - Dashboard unificata

## 🎯 Next Steps

### Funzionalità Aggiuntive

1. **Double Opt-In**
   - Email conferma iscrizione
   - Link attivazione unico
   - Protezione anti-spam

2. **Segmentazione**
   - Campi personalizzati
   - Tag e categorie
   - Liste multiple

3. **Dashboard Admin**
   - Pannello gestione iscritti
   - Export CSV
   - Statistiche dettagliate

4. **Automazioni**
   - Email benvenuto
   - Serie email automatiche
   - Retargeting discritti

## 📚 Documentazione

- **Modulo**: `modules/newsletter/README.md`
- **API**: `api/newsletter-subscribe.php` (commenti inline)
- **Database**: `modules/newsletter/install.sql`
- **Test**: `test-newsletter.html`

## 🎉 Fatto!

Il modulo newsletter è pronto all'uso. Buon lavoro! 🏃‍♂️

---

**Bologna Marathon - Sistema Modulare**  
*Versione 1.0.0 - Gennaio 2025*

