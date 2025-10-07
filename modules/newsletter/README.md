# 📧 Modulo Newsletter - Bologna Marathon

Modulo elegante e versatile per la registrazione newsletter con tre modalità di iscrizione differenti.

## 🎯 Caratteristiche

### Tre Modalità di Registrazione

#### 1️⃣ **Newsletter Classica**
Form completo con:
- Campo Nome (obbligatorio)
- Campo Email (obbligatorio)
- Checkbox Privacy con link a Privacy Policy
- Pulsante di invio con animazioni
- Validazione real-time
- Messaggi di successo/errore

#### 2️⃣ **WhatsApp Messaggio Diretto**
- Messaggio precompilato personalizzabile
- Pulsante che apre WhatsApp con il messaggio
- Design specifico con icona WhatsApp
- Descrizione e note informative

#### 3️⃣ **Canale WhatsApp**
- Link diretto al canale WhatsApp ufficiale
- Features del canale (aggiornamenti in tempo reale, notizie esclusive)
- Contatore membri (1.2K+)
- Design accattivante con call-to-action efficace

## 🎨 Design

### Caratteristiche Visive
- **Background Glassmorphism**: Effetto vetro sfumato con blur
- **Gradients Dinamici**: Tre varianti di colore (primary, secondary, dark)
- **Animazioni Fluide**: Transizioni su hover e interazioni
- **Responsive Design**: Mobile-first, ottimizzato per tutti i dispositivi
- **Icone SVG**: Icone vettoriali scalabili
- **CSS Variables**: Completamente personalizzabile tramite variabili CSS

### Varianti Disponibili
- `primary`: Gradient principale del brand
- `secondary`: Gradient secondario
- `dark`: Gradient scuro per contrasto

## 📱 Responsive

### Breakpoint
- **Desktop** (>1024px): Layout completo con spaziature generose
- **Tablet** (768px-1024px): Layout ottimizzato con font ridotti
- **Mobile** (<768px): Layout verticale, font e spaziature compatte

## 🔧 Configurazione

### Esempio Base (Newsletter Classica)
```json
{
  "title": "Newsletter",
  "subtitle": "Resta aggiornato sulle ultime novità",
  "type": "classic",
  "variant": "primary"
}
```

### Esempio WhatsApp
```json
{
  "title": "Iscriviti via WhatsApp",
  "subtitle": "Ricevi aggiornamenti direttamente su WhatsApp",
  "type": "whatsapp",
  "whatsapp_number": "393123456789",
  "whatsapp_message": "Ciao! Voglio iscrivermi alla newsletter WhatsApp",
  "variant": "secondary"
}
```

### Esempio Canale WhatsApp
```json
{
  "title": "Seguici su WhatsApp",
  "subtitle": "Unisciti al nostro canale ufficiale",
  "type": "channel",
  "channel_url": "https://whatsapp.com/channel/0029Vb2BgN0GOj9uUN5Zjp3Z",
  "variant": "primary"
}
```

### Background Personalizzato
```json
{
  "background_image": "/assets/images/newsletter-bg.jpg",
  "background_color": "#1a1a2e"
}
```

## 🎯 Campi Configurabili

### Comuni
- `title`: Titolo principale
- `subtitle`: Sottotitolo/descrizione
- `type`: Tipo di registrazione (`classic`, `whatsapp`, `channel`)
- `variant`: Variante di colore (`primary`, `secondary`, `dark`)
- `background_image`: URL immagine di sfondo
- `background_color`: Colore di sfondo custom

### Newsletter Classica (`type: classic`)
- `name_placeholder`: Placeholder campo nome
- `email_placeholder`: Placeholder campo email
- `privacy_text`: Testo checkbox privacy
- `privacy_link`: URL privacy policy
- `button_text`: Testo pulsante invio

### WhatsApp Messaggio (`type: whatsapp`)
- `whatsapp_number`: Numero WhatsApp (formato internazionale senza +)
- `whatsapp_message`: Messaggio precompilato
- `whatsapp_button_text`: Testo pulsante
- `whatsapp_description`: Descrizione sopra il pulsante

### Canale WhatsApp (`type: channel`)
- `channel_url`: URL del canale WhatsApp
- `channel_button_text`: Testo pulsante
- `channel_description`: Descrizione canale

## 💻 JavaScript

### Funzionalità
- **Validazione Real-time**: Controllo immediato di nome ed email
- **Gestione Form**: Submit asincrono tramite AJAX
- **Messaggi Feedback**: Success/error con auto-dismiss (5 secondi)
- **Tracking Conversioni**: Supporto Google Analytics e Facebook Pixel
- **Intersection Observer**: Animazioni all'ingresso viewport
- **Error Handling**: Gestione completa degli errori

### Eventi Personalizzati
```javascript
// Listener per iscrizione newsletter
document.addEventListener('newsletter:subscribe', function(event) {
  console.log('Newsletter subscribed:', event.detail);
  // { type: 'classic', timestamp: 1234567890 }
});
```

### API Endpoint
Il form classico invia i dati a `/api/newsletter-subscribe.php`:
```php
// Expected response format
{
  "success": true,
  "message": "Iscrizione completata con successo"
}
```

## 🔒 Sicurezza

### Validazione
- **Nome**: Minimo 2 caratteri
- **Email**: Validazione formato standard (regex)
- **Privacy**: Checkbox obbligatorio

### Sanitizzazione
- Tutti gli output sono sanitizzati con `htmlspecialchars()`
- URL validati prima dell'uso
- Protezione XSS integrata

## 🎯 Best Practices

### UX
- ✅ Placeholder chiari e descrittivi
- ✅ Validazione immediata con feedback visivo
- ✅ Messaggi di errore specifici
- ✅ Conferma visiva dell'invio
- ✅ Link privacy policy sempre accessibile

### Performance
- ✅ CSS minificato in produzione
- ✅ JavaScript lazy-loading
- ✅ Icone SVG inline (no richieste extra)
- ✅ Animazioni GPU-accelerated

### Accessibilità
- ✅ Label ARIA su tutti i campi
- ✅ Navigazione da tastiera completa
- ✅ Contrasti colori WCAG AA
- ✅ Focus states chiari
- ✅ Screen reader friendly

## 🔍 Troubleshooting

### Form non invia
- Verifica che l'endpoint `/api/newsletter-subscribe.php` esista
- Controlla la console browser per errori JavaScript
- Verifica che il campo email sia valido

### Stili non applicati
- Verifica che `newsletter.css` sia incluso
- Controlla che le CSS Variables siano definite
- Pulisci la cache del browser

### WhatsApp non si apre
- Verifica il formato del numero (es: `393123456789` senza +)
- Controlla che WhatsApp sia installato (mobile) o WhatsApp Web (desktop)
- Testa il link manualmente

### Canale WhatsApp non funziona
- Verifica che l'URL del canale sia corretto
- I canali WhatsApp sono disponibili solo su versioni recenti dell'app
- Testa l'URL direttamente nel browser

## 🎨 Personalizzazione CSS

### Override Colori
```css
.newsletter--primary {
  --newsletter-bg: linear-gradient(135deg, #your-color-1, #your-color-2);
}
```

### Modificare Spaziature
```css
.newsletter__content {
  padding: var(--space-xl); /* Personalizza */
}
```

### Custom Button
```css
.newsletter__button {
  background: your-custom-gradient;
  border-radius: 30px; /* Personalizza */
}
```

## 📊 Analytics

### Tracking Implementato
- Google Analytics (gtag)
- Facebook Pixel (fbq)
- Custom events (newsletter:subscribe)

### Eventi Tracciati
- `newsletter_subscribe`: Iscrizione completata
- `newsletter_error`: Errore durante iscrizione
- Parametri: tipo newsletter, timestamp

## 🚀 Deployment

### Checklist
- [ ] Configurare endpoint API (`/api/newsletter-subscribe.php`)
- [ ] Verificare URL privacy policy
- [ ] Testare su tutti i dispositivi
- [ ] Configurare numero WhatsApp (se usato)
- [ ] Verificare URL canale WhatsApp (se usato)
- [ ] Testare invio form e validazione
- [ ] Configurare tracking analytics

## 📝 Note

### WhatsApp Business
Per gestire le iscrizioni WhatsApp professionalmente:
1. Configura WhatsApp Business API
2. Usa webhook per ricevere messaggi
3. Implementa risposte automatiche
4. Gestisci opt-in/opt-out

### GDPR Compliance
- ✅ Checkbox privacy obbligatorio
- ✅ Link a privacy policy chiaro
- ✅ Double opt-in consigliato
- ✅ Gestione unsubscribe necessaria

## 🔗 Riferimenti

- [WhatsApp Business API](https://business.whatsapp.com/)
- [WhatsApp Channels](https://faq.whatsapp.com/channels/)
- [GDPR Newsletter](https://gdpr.eu/newsletter/)

---

**Modulo Newsletter** - Bologna Marathon 🏃‍♂️  
*Versione 1.0.0 - Gennaio 2025*

