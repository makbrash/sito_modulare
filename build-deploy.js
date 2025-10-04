const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

console.log('🚀 Build Deployment - Bologna Marathon');
console.log('=====================================\n');

// Cartella di output
const buildDir = 'build';

// File e cartelle da includere
const includeFiles = [
  'index.php',
  'README.md',
  '.cursorrules'
];

const includeFolders = [
  'assets/dist',
  'assets/images', 
  'assets/font',
  'config',
  'core',
  'database',
  'modules',
  'admin'
];

// File da escludere
const excludePatterns = [
  'node_modules',
  'assets/scss',
  'assets/js',
  'gulpfile.js',
  'package.json',
  'package-lock.json',
  '.git',
  'build'
];

console.log('📦 1. Pulizia cartella build...');
if (fs.existsSync(buildDir)) {
  fs.rmSync(buildDir, { recursive: true, force: true });
}
fs.mkdirSync(buildDir);

console.log('🔨 2. Build assets...');
try {
  execSync('npm run build', { stdio: 'inherit' });
  console.log('✅ Assets compilati con successo\n');
} catch (error) {
  console.error('❌ Errore durante il build:', error.message);
  process.exit(1);
}

console.log('📁 3. Copia file...');

// Copia file singoli
includeFiles.forEach(file => {
  if (fs.existsSync(file)) {
    const destPath = path.join(buildDir, file);
    fs.copyFileSync(file, destPath);
    console.log(`   ✅ ${file}`);
  } else {
    console.log(`   ⚠️  ${file} non trovato`);
  }
});

// Copia cartelle
includeFolders.forEach(folder => {
  if (fs.existsSync(folder)) {
    const destPath = path.join(buildDir, folder);
    copyFolderRecursive(folder, destPath);
    console.log(`   ✅ ${folder}/`);
  } else {
    console.log(`   ⚠️  ${folder}/ non trovata`);
  }
});

console.log('\n📝 4. Crea file di configurazione...');

// Crea .htaccess per Apache
const htaccessContent = `# Bologna Marathon - Apache Configuration
RewriteEngine On

# Redirect to HTTPS (opzionale)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"

# Cache static assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
</IfModule>

# Gzip compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
`;

fs.writeFileSync(path.join(buildDir, '.htaccess'), htaccessContent);
console.log('   ✅ .htaccess');

// Crea config.example.php
const configExample = `<?php
/**
 * Configurazione Database - Bologna Marathon
 * Copia questo file in config/database.php e modifica le credenziali
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'bologna_marathon';
    private $username = 'your_username';
    private $password = 'your_password';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
`;

fs.writeFileSync(path.join(buildDir, 'config.example.php'), configExample);
console.log('   ✅ config.example.php');

// Crea install.php
const installContent = `<?php
/**
 * Installazione Automatica - Bologna Marathon
 * Esegui questo file una volta per configurare il database
 */

require_once 'config/database.php';

try {
    \$database = new Database();
    \$db = \$database->getConnection();
    
    // Leggi schema SQL
    \$schema = file_get_contents('database/schema.sql');
    
    // Esegui schema
    \$statements = explode(';', \$schema);
    foreach (\$statements as \$statement) {
        \$statement = trim(\$statement);
        if (!empty(\$statement)) {
            \$db->exec(\$statement);
        }
    }
    
    // Importa dati di test
    \$testData = file_get_contents('database/test_data.sql');
    \$statements = explode(';', \$testData);
    foreach (\$statements as \$statement) {
        \$statement = trim(\$statement);
        if (!empty(\$statement)) {
            \$db->exec(\$statement);
        }
    }
    
    echo '<h1>✅ Installazione completata!</h1>';
    echo '<p>Il database è stato configurato correttamente.</p>';
    echo '<p><a href="index.php">Vai al sito</a></p>';
    echo '<p><strong>IMPORTANTE:</strong> Elimina questo file per sicurezza!</p>';
    
} catch (Exception \$e) {
    echo '<h1>❌ Errore durante l\'installazione</h1>';
    echo '<p>' . \$e->getMessage() . '</p>';
    echo '<p>Controlla le credenziali del database in config/database.php</p>';
}
?>`;

fs.writeFileSync(path.join(buildDir, 'install.php'), installContent);
console.log('   ✅ install.php');

// Crea DEPLOYMENT.md
const deploymentGuide = `# 🚀 Guida Deployment - Bologna Marathon

## 📋 Prerequisiti
- Server web (Apache/Nginx)
- PHP 8.0+
- MySQL 5.7+

## 📦 Installazione

### 1. Upload files
Carica tutti i file nella cartella build/ sul tuo server web.

### 2. Configura database
1. Crea un database MySQL
2. Copia config.example.php in config/database.php
3. Modifica le credenziali nel file config/database.php

### 3. Installazione automatica
1. Apri install.php nel browser
2. Verifica che l'installazione sia completata
3. **IMPORTANTE: Elimina install.php per sicurezza**

### 4. Configurazione server

#### Apache
Il file .htaccess è già incluso per:
- Cache static assets
- Compressione Gzip
- Security headers
- Redirect HTTPS (opzionale)

#### Nginx
Configura il tuo server Nginx:

server {
    listen 80;
    server_name your-domain.com;
    root /path/to/bologna-marathon;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \\.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* \\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}

## 🔧 Configurazione

### Variabili CSS
Personalizza i colori modificando assets/dist/css/main.min.css o ricompilando da SCSS.

### Moduli
Aggiungi nuovi moduli in modules/ seguendo la struttura esistente.

### Admin Panel
Accesso: /admin/
- Gestione risultati
- Gestione contenuti
- Page Builder
- Gestione moduli

## 📁 Struttura file

/
├── index.php              # Entry point
├── .htaccess             # Configurazione Apache
├── config/
│   └── database.php      # Configurazione DB
├── core/
│   └── ModuleRenderer.php # Sistema moduli
├── modules/              # Moduli PHP
├── admin/                # Pannello admin
├── assets/
│   ├── dist/            # CSS/JS compilati
│   ├── images/          # Immagini
│   └── font/            # Font
└── database/            # Schema e dati

## 🔒 Sicurezza

1. **Elimina install.php** dopo l'installazione
2. **Cambia password admin** se presente
3. **Configura HTTPS** per produzione
4. **Backup database** regolarmente
5. **Aggiorna PHP** alle versioni supportate

## 🆘 Supporto

Per problemi o domande:
- Controlla i log del server
- Verifica le credenziali database
- Controlla i permessi file (755 per cartelle, 644 per file)

---

**Bologna Marathon - Sistema Modulare** 🏃‍♂️
`;

fs.writeFileSync(path.join(buildDir, 'DEPLOYMENT.md'), deploymentGuide);
console.log('   ✅ DEPLOYMENT.md');

console.log('\n🎉 Build completato!');
console.log(`📁 Cartella build creata: ${buildDir}/`);
console.log('\n📋 Prossimi passi:');
console.log('1. Comprimi la cartella build/ in un file ZIP');
console.log('2. Carica il ZIP sul tuo server cloud');
console.log('3. Estrai i file nella cartella web');
console.log('4. Configura il database');
console.log('5. Esegui install.php');
console.log('6. Elimina install.php per sicurezza');
console.log('\n🚀 Pronto per il deployment!');

// Funzione helper per copiare cartelle ricorsivamente
function copyFolderRecursive(source, destination) {
  if (!fs.existsSync(destination)) {
    fs.mkdirSync(destination, { recursive: true });
  }

  const files = fs.readdirSync(source);
  
  files.forEach(file => {
    const sourcePath = path.join(source, file);
    const destPath = path.join(destination, file);
    
    if (fs.statSync(sourcePath).isDirectory()) {
      copyFolderRecursive(sourcePath, destPath);
    } else {
      fs.copyFileSync(sourcePath, destPath);
    }
  });
}
