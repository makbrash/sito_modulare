#!/usr/bin/env node

/**
 * Build Script per Cloud Deployment
 * Versione semplificata senza memory leak
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

console.log('🚀 Avvio Build per Cloud...');

// Configurazione
const config = {
    source: '.',
    output: 'dist-cloud',
    exclude: [
        'node_modules',
        '.git',
        'dist-cloud',
        'assets/scss',
        'gulpfile.js',
        'package.json',
        'package-lock.json',
        'com.chrome.devtools.json',
        'README-DEV-SYSTEM.md',
        '.vscode',
        '.idea'
    ]
};

// Funzione per copiare file/directory
function copyRecursive(src, dest, excludeList = []) {
    const stats = fs.statSync(src);
    
    if (stats.isDirectory()) {
        if (excludeList.some(exclude => src.includes(exclude))) {
            console.log(`⏭️  Escluso: ${src}`);
            return;
        }
        
        if (!fs.existsSync(dest)) {
            fs.mkdirSync(dest, { recursive: true });
        }
        
        const files = fs.readdirSync(src);
        files.forEach(file => {
            const srcPath = path.join(src, file);
            const destPath = path.join(dest, file);
            copyRecursive(srcPath, destPath, excludeList);
        });
    } else {
        if (excludeList.some(exclude => src.includes(exclude))) {
            console.log(`⏭️  Escluso: ${src}`);
            return;
        }
        
        fs.copyFileSync(src, dest);
        console.log(`📁 Copiato: ${src} → ${dest}`);
    }
}

// Funzione per compilare SCSS
function compileSCSS() {
    console.log('🎨 Compilazione SCSS...');
    
    try {
        execSync('npx sass assets/scss/main.scss assets/dist/css/main.css --style=compressed --no-source-map', {
            stdio: 'inherit',
            cwd: process.cwd()
        });
        console.log('✅ SCSS compilato con successo');
    } catch (error) {
        console.error('❌ Errore compilazione SCSS:', error.message);
        process.exit(1);
    }
}

// Funzione per ottimizzare CSS
function optimizeCSS() {
    console.log('🔧 Ottimizzazione CSS...');
    
    try {
        execSync('npx postcss assets/dist/css/main.css -o assets/dist/css/main.min.css --use autoprefixer --use cssnano', {
            stdio: 'inherit',
            cwd: process.cwd()
        });
        console.log('✅ CSS ottimizzato');
    } catch (error) {
        console.log('⚠️  PostCSS non disponibile, uso CSS diretto');
    }
}

// Funzione per compilare JS
function compileJS() {
    console.log('📜 Compilazione JavaScript...');
    
    try {
        // Concatena tutti i JS
        execSync('npx concat -o assets/dist/js/app.js assets/js/core/app.js assets/js/modules/*.js', {
            stdio: 'inherit',
            cwd: process.cwd()
        });
        
        // Minifica se terser è disponibile
        try {
            execSync('npx terser assets/dist/js/app.js -o assets/dist/js/app.min.js --compress --mangle', {
                stdio: 'inherit',
                cwd: process.cwd()
            });
            console.log('✅ JS minificato');
        } catch (error) {
            console.log('⚠️  Terser non disponibile, uso JS non minificato');
        }
    } catch (error) {
        console.error('❌ Errore compilazione JS:', error.message);
        process.exit(1);
    }
}

// Funzione principale
function build() {
    console.log('🧹 Pulizia directory di output...');
    
    // Rimuovi directory di output se esiste
    if (fs.existsSync(config.output)) {
        fs.rmSync(config.output, { recursive: true, force: true });
    }
    
    // Crea directory di output
    fs.mkdirSync(config.output, { recursive: true });
    
    console.log('📦 Copia file sorgente...');
    copyRecursive(config.source, config.output, config.exclude);
    
    console.log('🎨 Compilazione assets...');
    compileSCSS();
    optimizeCSS();
    compileJS();
    
    console.log('📋 Generazione file di configurazione...');
    
    // Crea file di configurazione per il cloud
    const cloudConfig = {
        build_date: new Date().toISOString(),
        version: '1.0.0',
        assets: {
            css: 'assets/dist/css/main.min.css',
            js: 'assets/dist/js/app.min.js'
        },
        environment: 'production'
    };
    
    fs.writeFileSync(
        path.join(config.output, 'cloud-config.json'),
        JSON.stringify(cloudConfig, null, 2)
    );
    
    // Crea .htaccess per Apache
    const htaccess = `# Cloud Build - Bologna Marathon
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Compression
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

# Cache
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>`;
    
    fs.writeFileSync(path.join(config.output, '.htaccess'), htaccess);
    
    console.log('✅ Build completato!');
    console.log(`📁 Output: ${config.output}/`);
    console.log('🌐 Pronto per il deployment cloud');
}

// Esegui build
build();
