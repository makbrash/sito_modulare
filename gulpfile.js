/**
 * Gulp Build System Unificato - Bologna Marathon
 * Sistema di build ottimizzato con validazione pre-build e rollback automatico
 * Sostituisce build-cloud.js e build-deploy.js
 */

const gulp = require('gulp');
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

// Gulp plugins
const autoprefixer = require('gulp-autoprefixer');
const cleanCSS = require('gulp-clean-css');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const imagemin = require('gulp-imagemin');
const sourcemaps = require('gulp-sourcemaps');
const watch = require('gulp-watch');
const gulpIf = require('gulp-if');
const browserSync = require('browser-sync').create();
const clean = require('gulp-clean');
const replace = require('gulp-replace');
const rename = require('gulp-rename');

// Configurazione
const isProd = process.env.NODE_ENV === 'production';
const BUILD_DIR = 'build';
const BACKUP_DIR = 'build-backup';

// Paths ottimizzati
const paths = {
    css: {
        core: [
            'assets/css/core/variables.css',
            'assets/css/core/reset.css',
            'assets/css/core/typography.css',
            'assets/css/core/fonts.css'
        ],
        modules: [
            'assets/css/modules/**/*.css',
            'modules/**/*.css'
        ],
        dest: `${BUILD_DIR}/assets/css/`
    },
    js: {
        src: [
            'assets/js/core/*.js',
            'modules/**/*.js'
        ],
        dest: `${BUILD_DIR}/assets/js/`
    },
    images: {
        src: 'assets/images/**/*',
        dest: `${BUILD_DIR}/assets/images/`
    },
    fonts: {
        src: 'assets/css/core/font/**/*',
        dest: `${BUILD_DIR}/assets/css/font/`
    }
};

// File critici per validazione
const criticalFiles = [
    'config/database.php',
    'core/ModuleRenderer.php',
    'index.php',
    'index-prod.php'
];

// File da escludere dal build
const excludePatterns = [
    'node_modules/**',
    'build/**',
    'build-backup/**',
    'dist-cloud/**',
    '*.md',
    'gulpfile.js',
    'package.json',
    'package-lock.json',
    'com.chrome.devtools.json',
    'README-DEV-SYSTEM.md',
    '.git/**',
    '.vscode/**',
    '.idea/**',
    'assets/scss/**',
    'assets/js/**',
    'assets/css/core/**',
    'modules/**/*.css',
    'modules/**/*.js'
];

/**
 * VALIDAZIONE PRE-BUILD
 */
function validatePreBuild() {
    console.log('🔍 Validazione pre-build...');
    
    const errors = [];
    
    // Controlla file critici
    criticalFiles.forEach(file => {
        if (!fs.existsSync(file)) {
            errors.push(`❌ File critico mancante: ${file}`);
        }
    });
    
    // Controlla struttura moduli
    const modulesDir = 'modules';
    if (fs.existsSync(modulesDir)) {
        const modules = fs.readdirSync(modulesDir);
        modules.forEach(module => {
            const modulePath = path.join(modulesDir, module);
            if (fs.statSync(modulePath).isDirectory()) {
                const moduleJson = path.join(modulePath, 'module.json');
                const modulePhp = path.join(modulePath, `${module}.php`);
                
                if (!fs.existsSync(moduleJson)) {
                    errors.push(`❌ module.json mancante in: ${module}`);
                }
                if (!fs.existsSync(modulePhp)) {
                    errors.push(`❌ ${module}.php mancante in: ${module}`);
                }
            }
        });
    }
    
    // Controlla dipendenze Node
    try {
        require.resolve('gulp');
        require.resolve('gulp-autoprefixer');
        require.resolve('gulp-clean-css');
        require.resolve('gulp-concat');
        require.resolve('gulp-uglify');
    } catch (e) {
        errors.push(`❌ Dipendenza mancante: ${e.message}`);
    }
    
    if (errors.length > 0) {
        console.error('❌ Errori di validazione:');
        errors.forEach(error => console.error(error));
        throw new Error('Validazione pre-build fallita');
    }
    
    console.log('✅ Validazione pre-build completata');
    return Promise.resolve();
}

/**
 * SISTEMA ROLLBACK
 */
function createBackup() {
    console.log('💾 Creazione backup...');
    
    if (fs.existsSync(BUILD_DIR)) {
        if (fs.existsSync(BACKUP_DIR)) {
            fs.rmSync(BACKUP_DIR, { recursive: true, force: true });
        }
        fs.renameSync(BUILD_DIR, BACKUP_DIR);
        console.log(`✅ Backup creato: ${BACKUP_DIR}/`);
    }
    
    return Promise.resolve();
}

function rollback() {
    console.log('🔄 Rollback in corso...');
    
    if (fs.existsSync(BACKUP_DIR)) {
        if (fs.existsSync(BUILD_DIR)) {
            fs.rmSync(BUILD_DIR, { recursive: true, force: true });
        }
        fs.renameSync(BACKUP_DIR, BUILD_DIR);
        console.log('✅ Rollback completato');
    } else {
        console.log('⚠️  Nessun backup disponibile per rollback');
    }
    
    return Promise.resolve();
}

/**
 * PLUGIN IMMAGINI CON FALLBACK
 */
function getImagePlugins() {
    const plugins = [];
    
    // MozJPEG per JPEG
    try {
        const mozjpeg = require('imagemin-mozjpeg');
        const mozjpegFn = typeof mozjpeg.default === 'function' ? mozjpeg.default : mozjpeg;
        plugins.push(mozjpegFn({ quality: 80 }));
        console.log('✅ MozJPEG plugin caricato');
    } catch (e) {
        console.log('⚠️  MozJPEG non disponibile, JPEG non ottimizzati');
    }
    
    // OptiPNG per PNG
    try {
        const optipng = require('imagemin-optipng');
        const optipngFn = typeof optipng.default === 'function' ? optipng.default : optipng;
        plugins.push(optipngFn({ optimizationLevel: 5 }));
        console.log('✅ OptiPNG plugin caricato');
    } catch (e) {
        console.log('⚠️  OptiPNG non disponibile, PNG non ottimizzati');
    }
    
    // SVGO per SVG
    try {
        const svgo = require('imagemin-svgo');
        const svgoFn = typeof svgo.default === 'function' ? svgo.default : svgo;
        plugins.push(svgoFn.optimize({
            plugins: [
                { removeViewBox: false },
                { removeEmptyAttrs: false }
            ]
        }));
        console.log('✅ SVGO plugin caricato');
    } catch (e) {
        console.log('⚠️  SVGO non disponibile, SVG non ottimizzati');
    }
    
    return plugins;
}

/**
 * TASK CSS
 */
function cssDev() {
    console.log('🔨 Building CSS DEV (non minificato)...');
    return gulp.src([...paths.css.core])
        .pipe(sourcemaps.init())
        .pipe(autoprefixer({ cascade: false }))
        .pipe(concat('main.css'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.css.dest))
        .pipe(browserSync.stream({ match: '**/*.css' }))
        .on('end', () => console.log('✅ CSS DEV build completed!'));
}

function cssProd() {
    console.log('🔨 Building CSS PROD (minificato)...');
    return gulp.src([...paths.css.core, ...paths.css.modules])
        .pipe(sourcemaps.init())
        .pipe(autoprefixer({ cascade: false }))
        .pipe(cleanCSS({ level: 2 }))
        .pipe(concat('main.min.css'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.css.dest))
        .on('end', () => console.log('✅ CSS PROD build completed!'));
}

function buildCSS() {
    return isProd ? cssProd() : cssDev();
}

/**
 * TASK JAVASCRIPT
 */
function buildJS() {
    console.log('📜 Building JavaScript...');
    return gulp.src(paths.js.src)
    .pipe(sourcemaps.init())
    .pipe(concat('app.min.js'))
    .pipe(gulpIf(isProd, uglify()))
    .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.js.dest))
        .pipe(browserSync.stream())
        .on('end', () => console.log('✅ JavaScript build completed!'));
}

/**
 * TASK IMMAGINI
 */
function optimizeImages() {
    console.log('🖼️  Ottimizzazione immagini...');
    const plugins = getImagePlugins();
    
    if (plugins.length === 0) {
        console.log('⚠️  Nessun plugin di compressione disponibile, copia semplice');
        return gulp.src(paths.images.src)
            .pipe(gulp.dest(paths.images.dest));
    }
    
    console.log(`✅ ${plugins.length} plugin di compressione attivi`);
    
    const imageminFn = typeof imagemin.default === 'function' ? imagemin.default : imagemin;
    
    return gulp.src(paths.images.src)
        .pipe(imageminFn(plugins))
        .pipe(gulp.dest(paths.images.dest))
        .on('end', () => console.log('✅ Immagini ottimizzate'));
}

/**
 * TASK FONT
 */
function copyFonts() {
    console.log('🔤 Copia font...');
    return gulp.src(paths.fonts.src)
        .pipe(gulp.dest(paths.fonts.dest))
        .on('end', () => console.log('✅ Font copiati'));
}

/**
 * TASK PULIZIA
 */
function cleanBuild() {
    console.log('🧹 Pulizia directory build...');
    return gulp.src(BUILD_DIR, { read: false, allowEmpty: true })
        .pipe(clean());
}

/**
 * COPIA FILE SITO
 */
function copySiteFiles() {
    console.log('📁 Copia file sito...');
    return gulp.src([
        '**/*',
        ...excludePatterns.map(pattern => `!${pattern}`)
    ])
    .pipe(gulp.dest(BUILD_DIR));
}

/**
 * COPIA SORTABLEJS
 */
function copySortableJS() {
    console.log('📦 Copia SortableJS...');
    return gulp.src('node_modules/sortablejs/**/*')
        .pipe(gulp.dest(`${BUILD_DIR}/node_modules/sortablejs/`));
}

/**
 * COPIA INDEX PROD
 */
function copyIndexProdAsIndex() {
    console.log('🔄 Copia index-prod.php come index.php...');
    return gulp.src('index-prod.php')
        .pipe(rename('index.php'))
        .pipe(gulp.dest(BUILD_DIR));
}

/**
 * GENERA FILE CONFIGURAZIONE
 */
function generateConfigFiles() {
    console.log('📋 Generazione file configurazione...');
    
    // Crea .htaccess
    const htaccess = `# Bologna Marathon - Apache Configuration
RewriteEngine On

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
</IfModule>`;

    fs.writeFileSync(path.join(BUILD_DIR, '.htaccess'), htaccess);
    
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
}`;

    fs.writeFileSync(path.join(BUILD_DIR, 'config.example.php'), configExample);
    
    // Crea install.php
    const installContent = `<?php
/**
 * Installazione Automatica - Bologna Marathon
 * Esegui questo file una volta per configurare il database
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Leggi schema SQL
    $schema = file_get_contents('database/schema.sql');
    
    // Esegui schema
    $statements = explode(';', $schema);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $db->exec($statement);
        }
    }
    
    // Importa dati di test
    $testData = file_get_contents('database/test_data.sql');
    $statements = explode(';', $testData);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $db->exec($statement);
        }
    }
    
    echo '<h1>✅ Installazione completata!</h1>';
    echo '<p>Il database è stato configurato correttamente.</p>';
    echo '<p><a href="index.php">Vai al sito</a></p>';
    echo '<p><strong>IMPORTANTE:</strong> Elimina questo file per sicurezza!</p>';
    
} catch (Exception $e) {
    echo '<h1>❌ Errore durante l\'installazione</h1>';
    echo '<p>' . $e->getMessage() . '</p>';
    echo '<p>Controlla le credenziali del database in config/database.php</p>';
}`;

    fs.writeFileSync(path.join(BUILD_DIR, 'install.php'), installContent);
    
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

## 🔧 Configurazione

### Variabili CSS
Personalizza i colori modificando assets/css/main.min.css.

### Moduli
Aggiungi nuovi moduli in modules/ seguendo la struttura esistente.

### Admin Panel
Accesso: /admin/
- Gestione risultati
- Gestione contenuti
- Page Builder
- Gestione moduli

## 🔒 Sicurezza

1. **Elimina install.php** dopo l'installazione
2. **Cambia password admin** se presente
3. **Configura HTTPS** per produzione
4. **Backup database** regolarmente
5. **Aggiorna PHP** alle versioni supportate

---

**Bologna Marathon - Sistema Modulare** 🏃‍♂️`;

    fs.writeFileSync(path.join(BUILD_DIR, 'DEPLOYMENT.md'), deploymentGuide);
    
    console.log('✅ File configurazione generati');
    return Promise.resolve();
}

/**
 * SERVER DEVELOPMENT
 */
function serve() {
    const proxyUrl = process.env.BROWSERSYNC_PROXY || "http://localhost/sito_modulare/index.php";
    browserSync.init({
        proxy: proxyUrl,
        open: false,
        notify: false,
        serveStatic: ['.']
    });

    gulp.watch(['assets/css/**/*.css', 'modules/**/*.css'], cssDev);
    gulp.watch(paths.js.src, buildJS);
    gulp.watch(paths.images.src, optimizeImages).on('change', browserSync.reload);
    gulp.watch(['**/*.php', 'modules/**/*.php']).on('change', browserSync.reload);
}

/**
 * DEVELOPMENT TASK
 */
function dev() {
    console.log('👀 Development mode: Watching files...');
    gulp.watch(['assets/css/**/*.css', 'modules/**/*.css'], cssDev);
    gulp.watch(paths.js.src, buildJS);
}

/**
 * SET PRODUCTION ENV
 */
function setProdEnv(cb) {
    process.env.NODE_ENV = 'production';
    cb();
}

/**
 * TASK PRINCIPALI
 */

// Build base (solo asset)
const build = gulp.series(
    setProdEnv,
    gulp.parallel(cssProd, buildJS, copyFonts, optimizeImages)
);

// Release completo (sito pronto per cloud)
const release = gulp.series(
    validatePreBuild,
    createBackup,
    setProdEnv,
    cleanBuild,
    copySiteFiles,
    copySortableJS,
    copyIndexProdAsIndex,
    gulp.parallel(cssProd, buildJS, copyFonts, optimizeImages),
    generateConfigFiles
);

// Development con server
const devWithServer = gulp.series(
    gulp.parallel(cssDev, buildJS, copyFonts),
    serve
);

// Rollback
const rollbackTask = gulp.series(rollback);

/**
 * EXPORT TASK
 */
exports.css = buildCSS;
exports.js = buildJS;
exports.images = optimizeImages;
exports.fonts = copyFonts;
exports.clean = cleanBuild;
exports.validate = validatePreBuild;
exports.backup = createBackup;
exports.rollback = rollbackTask;
exports.dev = dev;
exports.serve = devWithServer;
exports.build = build;
exports.release = release;
exports.default = exports.serve;