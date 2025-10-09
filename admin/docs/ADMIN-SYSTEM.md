# 🎛️ Sistema Admin - Guida Completa

## 📋 Panoramica

Il sistema amministrativo di Bologna Marathon è una **Single Page Application** moderna costruita con:
- **Backend**: PHP 8+ con architettura a servizi
- **Frontend**: Alpine.js + Tailwind CSS
- **API**: REST endpoints per comunicazione AJAX
- **Auth**: Sistema autenticazione con sessioni e ruoli
- **Error Handling**: Gestione centralizzata errori e logging

---

## 🏗️ Architettura Sistema

### Backend Services

```
core/Services/
├── PageService.php          # Gestione pagine
├── ModuleService.php        # Gestione moduli
├── ThemeService.php         # Gestione temi
├── AssetService.php         # Collezione asset
├── AssetCollector.php       # Asset collector
├── ConfigManager.php        # Configurazioni moduli
└── ModuleDataProvider.php   # Provider dati moduli
```

### Auth System

```
core/Auth/
├── AuthService.php          # Servizio autenticazione
└── AuthMiddleware.php       # Middleware protezione route
```

### Error Handling & Logging

```
core/Utils/
├── ErrorHandler.php         # Handler errori centralizzato
├── Logger.php               # Sistema logging strutturato
└── DotEnv.php              # Caricamento .env
```

### API Endpoints

```
admin/api/
├── pages.php               # CRUD pagine
├── modules.php             # CRUD moduli
├── themes.php              # CRUD temi
├── auth.php                # Autenticazione
└── BaseController.php      # Controller base
```

---

## 🎨 Frontend Admin

### Struttura Layout

```
admin/
├── dashboard.php           # Dashboard principale
├── page-builder.php        # Page Builder drag & drop
├── login.php              # Pagina login
├── components/
│   ├── layout.php         # Layout wrapper principale
│   ├── header.php         # Header con menu utente
│   ├── sidebar.php        # Sidebar navigazione
│   └── footer.php         # Footer admin
└── pages/
    ├── pages-list.php     # Lista pagine
    ├── modules-manager.php # Gestore moduli
    ├── themes-editor.php  # Editor temi
    └── settings.php       # Impostazioni
```

### Tecnologie Frontend

- **Alpine.js**: Reattività UI (dark mode, sidebar, dropdown)
- **Tailwind CSS**: Utility-first styling
- **Font Awesome**: Icone
- **Native Fetch API**: Chiamate AJAX

---

## 🔐 Sistema Autenticazione

### Configurazione

Il sistema auth è **opt-in** (disabilitato di default).

**File `.env`:**
```env
# Autenticazione (DISABILITATA di default)
AUTH_ENABLED=false
SESSION_LIFETIME=120
CSRF_PROTECTION=true
AUTH_MAX_LOGIN_ATTEMPTS=5
AUTH_LOCKOUT_DURATION=900
```

### Attivazione Auth

**Passo 1: Migrazione Database**
```bash
mysql -u root -p bologna_marathon < database/migrations/add_admin_users.sql
```

Questo crea:
- `admin_users` - Tabella utenti
- `admin_sessions` - Sessioni attive
- `admin_activity_log` - Log attività
- `admin_password_resets` - Token reset password

**Passo 2: Abilita Auth in `.env`**
```env
AUTH_ENABLED=true
```

**Passo 3: Login Iniziale**
- Username: `admin`
- Password: `admin123`
- **⚠️ IMPORTANTE**: Cambia la password al primo accesso!

### Funzionalità Auth

✅ **Login/Logout**
- Form login con validazione
- Brute-force protection (max 5 tentativi)
- Lockout temporaneo dopo tentativi falliti
- Logout con conferma utente

✅ **Sessioni**
- Gestione sessioni sicure
- Session lifetime configurabile
- Auto-logout dopo inattività
- Tracking IP e User Agent

✅ **Ruoli Utente**
- `super_admin` - Accesso completo
- `admin` - Gestione contenuti
- `editor` - Modifica contenuti
- `viewer` - Solo lettura

✅ **Activity Log**
- Log di tutte le azioni admin
- Tracking modifiche (pagine, moduli, temi)
- IP e timestamp

✅ **Password Security**
- Hash Bcrypt (cost 10)
- Cambio password forzato al primo accesso
- Password reset via email (futuro)

### Protezione Route

Tutte le pagine admin includono:

```php
<?php
require_once __DIR__ . '/auth-check.php';
// Ora $currentUser è disponibile
?>
```

Il file `auth-check.php`:
- Verifica se auth è abilitata
- Controlla sessione utente
- Redirect a login se non autenticato
- Carica dati utente corrente

---

## 🛠️ API REST

### Endpoints Disponibili

#### **Pages API** (`/admin/api/pages.php`)

```javascript
// GET - Lista pagine
fetch('/admin/api/pages.php?action=list')

// GET - Dettaglio pagina
fetch('/admin/api/pages.php?action=get&id=1')

// POST - Crea pagina
fetch('/admin/api/pages.php?action=create', {
    method: 'POST',
    body: JSON.stringify({
        title: 'Nuova Pagina',
        slug: 'nuova-pagina',
        status: 'draft'
    })
})

// PUT - Aggiorna pagina
fetch('/admin/api/pages.php?action=update', {
    method: 'PUT',
    body: JSON.stringify({
        id: 1,
        title: 'Titolo Aggiornato'
    })
})

// DELETE - Elimina pagina
fetch('/admin/api/pages.php?action=delete&id=1', {
    method: 'DELETE'
})
```

#### **Modules API** (`/admin/api/modules.php`)

```javascript
// GET - Lista moduli
fetch('/admin/api/modules.php?action=list')

// GET - Dettaglio modulo
fetch('/admin/api/modules.php?action=get&name=hero')

// POST - Registra modulo
fetch('/admin/api/modules.php?action=register', {
    method: 'POST',
    body: JSON.stringify({
        name: 'my-module',
        component_path: 'my-module/my-module.php'
    })
})
```

#### **Themes API** (`/admin/api/themes.php`)

```javascript
// GET - Lista temi
fetch('/admin/api/themes.php?action=list')

// POST - Salva tema
fetch('/admin/api/themes.php?action=save', {
    method: 'POST',
    body: JSON.stringify({
        name: 'Marathon Theme',
        variables: {
            '--primary': '#23a8eb',
            '--secondary': '#dc335e'
        }
    })
})
```

#### **Auth API** (`/admin/api/auth.php`)

```javascript
// POST - Login
fetch('/admin/api/auth.php?action=login', {
    method: 'POST',
    body: JSON.stringify({
        username: 'admin',
        password: 'admin123'
    })
})

// POST - Logout
fetch('/admin/api/auth.php?action=logout', {
    method: 'POST'
})

// GET - Status autenticazione
fetch('/admin/api/auth.php?action=status')

// GET - Utente corrente
fetch('/admin/api/auth.php?action=me')

// POST - Cambia password
fetch('/admin/api/auth.php?action=change-password', {
    method: 'POST',
    body: JSON.stringify({
        old_password: 'admin123',
        new_password: 'nuova_password',
        confirm_password: 'nuova_password'
    })
})
```

### Response Format

**Success:**
```json
{
    "success": true,
    "data": { ... },
    "message": "Operazione completata"
}
```

**Error:**
```json
{
    "success": false,
    "message": "Descrizione errore",
    "code": 400
}
```

---

## 📊 Error Handling & Logging

### Error Handler

Gestisce automaticamente:
- **Eccezioni PHP** non catturate
- **Errori PHP** (E_ERROR, E_WARNING, etc.)
- **Fatal errors** durante shutdown

**Configurazione `.env`:**
```env
APP_DEBUG=true    # Mostra errori dettagliati in sviluppo
```

**Debug Mode (APP_DEBUG=true):**
- Pagina errore dettagliata con stack trace
- Informazioni contesto request
- File e linea errore
- Variabili ambiente

**Production Mode (APP_DEBUG=false):**
- Pagina errore user-friendly generica
- Errori loggati ma non mostrati
- Messaggio "Qualcosa è andato storto"

### Logger

Sistema logging strutturato con livelli PSR-3:

```php
<?php
$logger = new \BolognaMarathon\Utils\Logger();

$logger->emergency('Sistema non disponibile');
$logger->alert('Azione immediata richiesta');
$logger->critical('Errore critico');
$logger->error('Errore runtime', ['file' => 'test.php']);
$logger->warning('Attenzione', ['user_id' => 123]);
$logger->notice('Evento significativo');
$logger->info('Info interessante');
$logger->debug('Debug dettagliato', ['query' => 'SELECT...']);
?>
```

**Configurazione `.env`:**
```env
LOG_ENABLED=true
LOG_LEVEL=error    # emergency|alert|critical|error|warning|notice|info|debug
LOG_FILE=logs/app.log
```

**Features:**
- ✅ Livelli log configurabili
- ✅ Rotazione automatica (>10MB)
- ✅ Compressione vecchi log (gz)
- ✅ Pulizia automatica (mantiene ultimi 10)
- ✅ Context data supportato
- ✅ Interpolazione placeholders `{key}`

**Log Format:**
```
[2025-01-09 14:30:45] ERROR    : Database connection failed {"host":"localhost"}
[2025-01-09 14:30:50] WARNING  : Cache miss {"key":"user_123"}
[2025-01-09 14:31:00] INFO     : User logged in {"user_id":1,"ip":"127.0.0.1"}
```

---

## 🎯 Page Builder

### Funzionalità

- **Drag & Drop**: Riordina moduli trascinando
- **Live Preview**: Vedi modifiche in tempo reale
- **Configurazione Moduli**: Editor JSON per config
- **Theme Override**: CSS variables per pagina
- **Responsive**: Layout adattivo

### Utilizzo

1. Vai su **Page Builder** dalla sidebar
2. Seleziona pagina da modificare
3. Trascina moduli da sidebar a canvas
4. Configura ogni modulo cliccando "Configura"
5. Salva modifiche

---

## 🚀 Workflow Sviluppo Admin

### 1. Aggiungere Nuova Pagina Admin

```php
// admin/pages/my-page.php
<?php
require_once __DIR__ . '/../auth-check.php';

$pageTitle = 'La Mia Pagina';
$currentPage = 'my-page';

ob_start();
?>

<div class="p-6">
    <h1 class="text-2xl font-bold mb-4">La Mia Pagina</h1>
    <!-- Contenuto -->
</div>

<?php
$pageContent = ob_get_clean();
require_once __DIR__ . '/../components/layout.php';
?>
```

### 2. Aggiungere Link Sidebar

```php
// admin/components/sidebar.php
<a href="<?= $basePath ?>pages/my-page.php" 
   class="sidebar-link <?= $currentPage === 'my-page' ? 'active' : '' ?>">
    <i class="fas fa-star"></i>
    <span>La Mia Pagina</span>
</a>
```

### 3. Creare Nuovo Endpoint API

```php
// admin/api/my-endpoint.php
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/API/BaseController.php';

class MyController extends \BolognaMarathon\API\BaseController
{
    public function handleRequest(): void
    {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'list':
                $this->sendResponse(['items' => []]);
                break;
            default:
                $this->sendError('Action not found', 404);
        }
    }
}

$database = new Database();
$db = $database->getConnection();
$controller = new MyController();
$controller->handleRequest();
?>
```

---

## 🔒 Best Practices

### Sicurezza

✅ **Sempre sanitizzare output**
```php
<?= htmlspecialchars($userInput) ?>
```

✅ **Prepared statements per query**
```php
$stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
$stmt->execute([$pageId]);
```

✅ **Validare input utente**
```php
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception('Email non valida');
}
```

✅ **CSRF protection**
```php
// Include automaticamente da auth-check.php
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
```

### Performance

✅ **Cache query frequenti**
✅ **Output buffering per layout**
✅ **Lazy loading moduli**
✅ **Minify CSS/JS in produzione**

### Manutenibilità

✅ **Separa logica da presentazione**
✅ **Usa servizi per business logic**
✅ **Commenta codice complesso**
✅ **Segui PSR-12 coding standards**

---

## 🐛 Troubleshooting

### Problema: Non riesco a fare login

**Soluzione:**
1. Verifica `.env` abbia `AUTH_ENABLED=true`
2. Controlla tabella `admin_users` esista
3. Verifica credenziali: `admin` / `admin123`
4. Controlla log in `logs/app.log`

### Problema: Pagina bianca dopo errore

**Soluzione:**
1. Abilita debug: `APP_DEBUG=true` in `.env`
2. Controlla log PHP: `XAMPP/php/logs/php_error_log`
3. Verifica sintassi PHP: `php -l file.php`

### Problema: API ritorna 404

**Soluzione:**
1. Verifica path API corretto
2. Controlla `.htaccess` configurato
3. Verifica permessi file (644)
4. Testa con strumenti debug (Postman, curl)

### Problema: Sessione persa dopo poco

**Soluzione:**
1. Aumenta `SESSION_LIFETIME` in `.env`
2. Verifica `php.ini`: `session.gc_maxlifetime`
3. Controlla spazio disco per sessioni

---

## 📚 Riferimenti

- **Sistema Generale**: `/docs/README.md`
- **Moduli**: `/modules/docs/DEVELOPMENT-GUIDE.md`
- **Database**: `/database/docs/SCHEMA-REFERENCE.md`
- **Build System**: `/docs/BUILD-SYSTEM.md`
- **Temi**: `/docs/THEME-SYSTEM-FINAL.md`

---

**Bologna Marathon Admin System v2.0** 🏃‍♂️  
*Sistema amministrazione moderno, sicuro e scalabile*

