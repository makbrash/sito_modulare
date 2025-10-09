# 🚀 Quick Start - Admin Bologna Marathon

Guida rapida per iniziare subito con il sistema admin.

---

## ⚡ Setup Rapido (5 minuti)

### 1. Configura Database

```bash
# Copia file .env
cp env.example .env

# Modifica credenziali database in .env
DB_HOST=localhost
DB_DATABASE=bologna_marathon
DB_USERNAME=root
DB_PASSWORD=
```

### 2. Attiva Autenticazione (Opzionale)

```bash
# Esegui migrazione
mysql -u root bologna_marathon < database/migrations/add_admin_users.sql

# Abilita auth in .env
AUTH_ENABLED=true
```

### 3. Accedi

- URL: `http://localhost/sito_modulare/admin/`
- Username: `admin`
- Password: `admin123`
- **⚠️ Cambia password al primo accesso!**

---

## 📋 Menu Principale

### 🏠 Dashboard
Panoramica sistema con statistiche:
- Pagine pubblicate
- Moduli attivi
- Ultimi accessi
- Attività recente

### 📄 Gestione Pagine
Lista completa pagine del sito:
- **Crea nuova** pagina
- **Modifica** esistenti
- **Pubblica/Bozza**
- **Elimina** pagine

### 🎨 Page Builder
Editor visuale drag & drop:
- Trascina moduli sulla pagina
- Configura ogni modulo
- Anteprima live
- Salva e pubblica

### 🧩 Moduli
Gestione moduli disponibili:
- Attiva/Disattiva moduli
- Visualizza configurazioni
- Registra nuovi moduli

### 🎨 Editor Temi
Personalizza colori e stili:
- Modifica CSS Variables
- Crea temi per gare diverse
- Preview in tempo reale
- Salva e applica

### ⚙️ Impostazioni
Configurazioni sistema:
- Profilo utente
- Cambio password
- Preferenze
- Log sistema

---

## 🎯 Operazioni Comuni

### Creare Nuova Pagina

1. **Dashboard** → **Gestione Pagine**
2. Click **"Nuova Pagina"**
3. Compila:
   - **Titolo**: Nome pagina
   - **Slug**: URL (es: `contatti`)
   - **Status**: `draft` o `published`
4. **Salva**

### Modificare Homepage

1. **Dashboard** → **Page Builder**
2. Seleziona **"Home"** dal dropdown
3. Trascina moduli da **Sidebar** → **Canvas**
4. Click **"Configura"** su ogni modulo
5. Modifica JSON config
6. **Salva Modifiche**

### Cambiare Colori Tema

1. **Dashboard** → **Editor Temi**
2. Click **"Nuovo Tema"** o modifica esistente
3. Modifica CSS Variables:
   ```css
   --primary: #23a8eb
   --secondary: #dc335e
   ```
4. **Anteprima** live
5. **Salva** tema

### Aggiungere Modulo a Pagina

1. **Page Builder** → Seleziona pagina
2. Trova modulo in sidebar (es: **Hero**)
3. **Drag** modulo → **Drop** su canvas
4. Click **"Configura"**
5. Modifica config JSON
6. **Salva**

---

## 🔐 Gestione Utenti

### Cambiare Password

1. Click **avatar** (in alto a destra)
2. **Impostazioni**
3. **Sicurezza** → **Cambia Password**
4. Inserisci:
   - Vecchia password
   - Nuova password
   - Conferma password
5. **Aggiorna**

### Logout

1. Click **avatar** (in alto a destra)
2. Click **"Logout"**
3. Conferma

---

## 🎨 Dark Mode

Toggle **🌙 Dark Mode**:
- Click icona sole/luna in header
- Preferenza salvata automaticamente
- Persiste tra sessioni

---

## 🐛 Problemi Comuni

### ❌ "Pagina non trovata"

**Causa**: Status pagina non pubblicato  
**Soluzione**: Gestione Pagine → Modifica → Status: `published`

### ❌ "Credenziali non valide"

**Causa**: Auth non configurata o password errata  
**Soluzione**: 
1. Verifica `.env`: `AUTH_ENABLED=true`
2. Reset password con SQL:
   ```sql
   UPDATE admin_users 
   SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
   WHERE username = 'admin';
   ```
3. Login: `admin` / `admin123`

### ❌ Modifiche non salvate

**Causa**: Errore JavaScript o API  
**Soluzione**:
1. Apri **Console Browser** (F12)
2. Verifica errori
3. Controlla `logs/app.log`
4. Riprova operazione

### ❌ Sessione scaduta

**Causa**: Inattività prolungata  
**Soluzione**:
1. Fai login nuovamente
2. Aumenta `SESSION_LIFETIME` in `.env`

---

## 📱 Mobile Admin

Il pannello admin è **responsive**:
- ✅ Tablet: Layout ottimizzato
- ✅ Mobile: Sidebar collassabile
- ✅ Touch: Gesture supportate

---

## ⌨️ Scorciatoie Tastiera

| Azione | Shortcut |
|--------|----------|
| Apri/Chiudi Sidebar | `Ctrl + B` |
| Cerca | `Ctrl + K` |
| Salva | `Ctrl + S` |
| Annulla | `Ctrl + Z` |
| Toggle Dark Mode | `Ctrl + D` |

*(Implementazione futura)*

---

## 📊 Dashboard Widgets

### Statistiche Rapide
- **Pagine Totali**: Conteggio pagine
- **Moduli Attivi**: Moduli disponibili
- **Ultimo Accesso**: Data/ora

### Attività Recente
- Ultime 10 azioni admin
- Timestamp e utente
- Link diretti a risorse

### Link Rapidi
- Nuova Pagina
- Page Builder
- Editor Temi
- Visualizza Sito

---

## 🔄 Workflow Tipico

### Creare Nuova Landing Page

1. **Crea Pagina**:
   - Titolo: "Iscrizioni 2026"
   - Slug: `iscrizioni-2026`
   - Status: `draft`

2. **Page Builder**:
   - Aggiungi **Hero** (banner principale)
   - Aggiungi **Race Cards** (gare disponibili)
   - Aggiungi **Highlights** (perché iscriversi)
   - Aggiungi **Footer**

3. **Configura Moduli**:
   - Hero: Titolo, immagine, CTA
   - Race Cards: Collegamento database gare
   - Highlights: Vantaggi iscrizione

4. **Tema Custom**:
   - Editor Temi → Nuovo
   - Colori personalizzati gara
   - Applica alla pagina

5. **Pubblica**:
   - Preview pagina
   - Status: `published`
   - Condividi URL

---

## 🎓 Prossimi Passi

1. **Esplora Moduli**: `/modules/docs/DEVELOPMENT-GUIDE.md`
2. **Sistema Temi**: `/docs/THEME-SYSTEM-FINAL.md`
3. **API Reference**: `/admin/docs/ADMIN-SYSTEM.md`
4. **Database Schema**: `/database/docs/SCHEMA-REFERENCE.md`

---

## 💡 Tips & Tricks

### Tip 1: Copia Config tra Moduli
1. Esporta JSON config da modulo esistente
2. Copia e incolla in nuovo modulo
3. Modifica solo valori necessari

### Tip 2: Temi per Gare Diverse
Crea un tema per ogni gara:
- **Marathon Theme**: Rosso/Blu classico
- **Portici Theme**: Verde/Oro UNESCO
- **Run Tune Theme**: Viola/Rosa accessibile

### Tip 3: Draft per Test
Usa status `draft` per testare modifiche senza pubblicare.

### Tip 4: Browser Cache
Dopo modifiche CSS/JS, forza refresh: `Ctrl + F5`

### Tip 5: Log per Debug
Abilita `APP_DEBUG=true` e `LOG_LEVEL=debug` per troubleshooting.

---

## 📞 Supporto

**Problemi tecnici?**
- Controlla `/admin/docs/TROUBLESHOOTING.md`
- Log sistema: `logs/app.log`
- Activity log: Dashboard → Attività

**Email**: support@bolognamarathon.run

---

**Buon lavoro! 🏃‍♂️**  
*Sistema Admin Bologna Marathon v2.0*

