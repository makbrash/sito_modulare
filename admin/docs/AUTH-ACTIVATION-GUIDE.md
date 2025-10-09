# 🔐 Guida Attivazione Sistema Autenticazione

## 📋 Indice
- [Panoramica](#panoramica)
- [Setup Database](#setup-database)
- [Attivazione Sistema](#attivazione-sistema)
- [Creazione Utenti](#creazione-utenti)
- [Ruoli e Permessi](#ruoli-e-permessi)
- [Sicurezza](#sicurezza)
- [Troubleshooting](#troubleshooting)

---

## 🎯 Panoramica

Il sistema di autenticazione è **DISABILITATO di default** per permettere sviluppo rapido. Quando pronto, può essere attivato in pochi passi.

### Funzionalità

- ✅ **Login/Logout** sicuro con bcrypt
- ✅ **Gestione ruoli** (super_admin, admin, editor, viewer)
- ✅ **Protezione brute-force** (max tentativi + lockout temporaneo)
- ✅ **Activity logging** (tutte le azioni admin tracciate)
- ✅ **Sessioni sicure** (stored in database)
- ✅ **Password sicure** (requisiti minimi + must_change_password)
- ✅ **CSRF Protection** (token su richieste modificanti)
- ✅ **Middleware route** protection

---

## 🗄️ Setup Database

### 1. Esegui Migration SQL

Apri **phpMyAdmin** o CLI MySQL:

```bash
mysql -u root -p bologna_marathon < database/migrations/add_admin_users.sql
```

Oppure esegui manualmente il file SQL da phpMyAdmin.

### 2. Verifica Tabelle Create

Controlla che siano state create:
- `admin_users`
- `admin_sessions`
- `admin_activity_log`
- `admin_password_resets`

### 3. Crea Utente Iniziale

**Opzione A**: Uncomment nel file `add_admin_users.sql`:

```sql
INSERT INTO `admin_users` (`username`, `password_hash`, `email`, `display_name`, `role`, `is_active`, `must_change_password`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@bolognamarathon.run', 'Amministratore', 'super_admin', 1, 1);
```

**Opzione B**: Usa API (dopo aver attivato sistema):

```bash
curl -X POST http://localhost/sito_modulare/admin/api/auth.php?action=create-user \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"SecurePass123!","email":"admin@example.com","role":"super_admin"}'
```

---

## ⚙️ Attivazione Sistema

### 1. Modifica File `.env`

Apri `.env` e cambia:

```env
# Prima
AUTH_ENABLED=false

# Dopo
AUTH_ENABLED=true
```

### 2. Verifica Configurazioni

Assicurati che anche queste siano settate:

```env
AUTH_MAX_LOGIN_ATTEMPTS=5
AUTH_LOCKOUT_DURATION=900
SESSION_LIFETIME=120
CSRF_PROTECTION=true
```

### 3. Testa Login

Apri: `http://localhost/sito_modulare/admin/login.php`

**Credenziali default**:
- Username: `admin`
- Password: `admin123`

⚠️ **IMPORTANTE**: Cambia password al primo accesso!

### 4. Verifica Protezione Route

Prova ad accedere a `dashboard.php` senza login → dovrebbe redirigere a `login.php`

---

## 👥 Creazione Utenti

### Tramite API

```php
POST /admin/api/auth.php?action=create-user
Content-Type: application/json

{
  "username": "editor1",
  "password": "SecurePass123!",
  "email": "editor@example.com",
  "display_name": "Editor Principale",
  "role": "editor",
  "is_active": true,
  "must_change_password": true
}
```

### Tramite Database (phpMyAdmin)

```sql
INSERT INTO admin_users (username, password_hash, email, display_name, role, is_active, must_change_password) 
VALUES ('editor1', '$2y$10$...', 'editor@example.com', 'Editor', 'editor', 1, 1);
```

**Genera password hash**:

```php
<?php
echo password_hash('TuaPassword123!', PASSWORD_BCRYPT);
?>
```

---

## 🛡️ Ruoli e Permessi

### Ruoli Disponibili

| Ruolo | Descrizione | Permessi |
|-------|-------------|----------|
| `super_admin` | Amministratore supremo | Tutto (creazione utenti, config sistema) |
| `admin` | Amministratore | Gestione pagine, moduli, temi |
| `editor` | Editor | Modifica pagine e moduli esistenti |
| `viewer` | Visualizzatore | Solo lettura |

### Implementazione Controllo Ruoli

Nel codice admin:

```php
require_once __DIR__ . '/../core/Auth/AuthMiddleware.php';

use BolognaMarathon\Auth\AuthMiddleware;

// Richiedi ruolo specifico
$authMiddleware->requireRole('super_admin');

// Oppure uno tra più ruoli
$authMiddleware->requireAnyRole(['admin', 'super_admin']);
```

### Esempio Protezione Route

```php
// admin/pages/settings.php
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth/AuthService.php';
require_once __DIR__ . '/../../core/Auth/AuthMiddleware.php';

use BolognaMarathon\Auth\AuthService;
use BolognaMarathon\Auth\AuthMiddleware;

$database = new Database();
$db = $database->getConnection();
$authService = new AuthService($db);
$authMiddleware = new AuthMiddleware($authService);

// Proteggi route
$authMiddleware->handle();

// Solo super_admin può accedere
$authMiddleware->requireRole('super_admin');
?>
```

---

## 🔒 Sicurezza

### Requisiti Password

Le password devono rispettare:
- ✅ Minimo 8 caratteri
- ✅ Almeno una maiuscola
- ✅ Almeno una minuscola
- ✅ Almeno un numero
- ⚠️ Consigliato: caratteri speciali

### Protezione Brute-Force

- **Max tentativi**: 5 (configurabile)
- **Lockout**: 15 minuti (configurabile)
- **Reset automatico**: dopo lockout scaduto
- **Log**: tutti i tentativi tracciati in `admin_activity_log`

### CSRF Protection

Tutte le richieste `POST/PUT/DELETE` richiedono token CSRF:

```php
// Genera token nel form
$csrfToken = \BolognaMarathon\Auth\AuthMiddleware::generateCsrfToken();
?>
<input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
```

```javascript
// In AJAX requests
fetch('/admin/api/pages.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': '<?= $csrfToken ?>'
  },
  body: JSON.stringify(data)
});
```

### Session Security

- **Stored in database** (non solo cookie)
- **IP tracking** per rilevare hijacking
- **User Agent tracking**
- **Session timeout** automatico
- **Logout** distrugge sessione database

---

## 🐛 Troubleshooting

### Problema: "Non riesco ad accedere dopo aver attivato auth"

**Soluzione**:
1. Verifica che `AUTH_ENABLED=true` in `.env`
2. Controlla che tabelle siano create correttamente
3. Verifica che esista almeno un utente attivo
4. Controlla log PHP per errori

### Problema: "Account bloccato"

**Soluzione**:
```sql
-- Reset manualmente lockout
UPDATE admin_users SET failed_login_attempts = 0, locked_until = NULL WHERE username = 'admin';
```

### Problema: "Password dimenticata"

**Soluzione**:
```php
<?php
// Genera nuovo hash
$newPassword = 'NuovaPasswordSicura123!';
$hash = password_hash($newPassword, PASSWORD_BCRYPT);
echo "Hash: $hash\n";
?>
```

Poi aggiorna database:
```sql
UPDATE admin_users SET password_hash = '$2y$10$...', must_change_password = 1 WHERE username = 'admin';
```

### Problema: "Redirect loop infinito"

**Soluzione**:
1. Cancella cookies browser
2. Verifica che `login.php` sia in `publicRoutes` di `AuthMiddleware`
3. Controlla sessione PHP attiva

### Problema: "CSRF token invalid"

**Soluzione**:
1. Assicurati che sessione PHP sia inizializzata
2. Genera nuovo token prima del form
3. Verifica che cookie sessione siano abilitati

---

## 📊 Activity Logging

Tutte le azioni sono tracciate in `admin_activity_log`:

```sql
SELECT 
  al.*,
  u.username,
  u.email
FROM admin_activity_log al
LEFT JOIN admin_users u ON al.user_id = u.id
ORDER BY al.created_at DESC
LIMIT 50;
```

**Azioni tracciate**:
- `login_success`, `login_failed`, `login_blocked`
- `logout`
- `password_changed`, `password_change_failed`
- `user_created`, `user_updated`, `user_deleted`
- `page_created`, `page_updated`, `page_deleted`
- `module_created`, `module_updated`, `module_deleted`
- `theme_created`, `theme_updated`, `theme_deleted`

---

## 🚀 Best Practices

1. ✅ **Cambia password default** immediatamente
2. ✅ **Usa password forti** per tutti gli utenti
3. ✅ **Abilita HTTPS** in produzione
4. ✅ **Monitora activity log** regolarmente
5. ✅ **Crea utenti con ruoli minimi** necessari
6. ✅ **Disattiva utenti** non più utilizzati
7. ✅ **Backup database** prima di modifiche
8. ✅ **Testa in staging** prima di production

---

## 📝 Note Finali

- Il sistema auth è **completamente opzionale**
- Se `AUTH_ENABLED=false`, tutto funziona come prima
- Puoi attivare/disattivare in qualsiasi momento
- Nessun impatto su frontend pubblico
- Database auth separato da contenuti

---

**Sistema Autenticazione - Bologna Marathon Admin** 🔐  
*Versione 1.0.0 - Sicuro, Flessibile, Pronto all'uso*

