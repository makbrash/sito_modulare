/**
 * Gulp Build System
 * Sistema di build per Bologna Marathon
 * Separazione DEV vs PROD con live-reload
 */

const gulp = require('gulp');
const autoprefixer = require('gulp-autoprefixer');
const cleanCSS = require('gulp-clean-css');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const imagemin = require('gulp-imagemin');

// Plugin di compressione con fallback
function getImagePlugins() {
    const plugins = [];
    
    // MozJPEG per JPEG (con fallback)
    try {
        const mozjpeg = require('imagemin-mozjpeg');
        const mozjpegFn = typeof mozjpeg.default === 'function' ? mozjpeg.default : mozjpeg;
        plugins.push(mozjpegFn({ quality: 80 }));
        console.log('✅ MozJPEG plugin caricato');
    } catch (e) {
        console.log('⚠️  MozJPEG non disponibile, JPEG non ottimizzati');
    }
    
    // OptiPNG per PNG (con fallback)
    try {
        const optipng = require('imagemin-optipng');
        const optipngFn = typeof optipng.default === 'function' ? optipng.default : optipng;
        plugins.push(optipngFn({ optimizationLevel: 5 }));
        console.log('✅ OptiPNG plugin caricato');
    } catch (e) {
        console.log('⚠️  OptiPNG non disponibile, PNG non ottimizzati');
    }
    
    // SVGO per SVG (con fallback)
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
const sourcemaps = require('gulp-sourcemaps');
const watch = require('gulp-watch');
const gulpIf = require('gulp-if');
const browserSync = require('browser-sync').create();
const clean = require('gulp-clean');
const replace = require('gulp-replace');
const rename = require('gulp-rename');

const isProd = process.env.NODE_ENV === 'production';

// Paths
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
            'modules/**/**/*.css'
        ],
        destDev: 'build/assets/css/',
        destProd: 'build/assets/css/'
    },
    js: {
        src: [
            'assets/js/core/*.js',
            'modules/**/*.js'
        ],
        dest: 'build/assets/js/'
    },
    images: {
        src: 'assets/images/**/*',
        dest: 'build/assets/images/'
    }
};

// CSS DEV: concat core solo per debug (i moduli vengono caricati singolarmente da PHP)
function cssDev() {
    console.log('🔨 Building CSS DEV (non minificato)...');
    return gulp.src([ ...paths.css.core ])
        .pipe(sourcemaps.init())
        .pipe(autoprefixer({ cascade: false }))
        .pipe(concat('main.css'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.css.destDev))
        .pipe(browserSync.stream({ match: '**/*.css' }))
        .on('end', () => console.log('✅ CSS DEV build completed!'));
}

// CSS PROD (minificato): concat core + tutti i CSS moduli
function cssProd() {
    console.log('🔨 Building CSS PROD (minificato)...');
    return gulp.src([ ...paths.css.core, ...paths.css.modules ])
        .pipe(sourcemaps.init())
        .pipe(autoprefixer({ cascade: false }))
        .pipe(cleanCSS({ level: 2 }))
        .pipe(concat('main.min.css'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.css.destProd))
        .on('end', () => console.log('✅ CSS PROD build completed!'));
}

function buildCSS() { return isProd ? cssProd() : cssDev(); }

// JS invariato ma con reload
function buildJS() {
    return gulp.src(paths.js.src)
    .pipe(sourcemaps.init())
    .pipe(concat('app.min.js'))
    .pipe(gulpIf(isProd, uglify()))
    .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.js.dest))
    .pipe(browserSync.stream());
}

// Images Task
function optimizeImages() {
    console.log('🖼️  Ottimizzazione immagini...');
    const plugins = getImagePlugins();
    
    if (plugins.length === 0) {
        console.log('⚠️  Nessun plugin di compressione disponibile, copia semplice');
        return gulp.src(paths.images.src)
            .pipe(gulp.dest(paths.images.dest));
    }
    
    console.log(`✅ ${plugins.length} plugin di compressione attivi`);
    
    // Handle imagemin function compatibility
    const imageminFn = typeof imagemin.default === 'function' ? imagemin.default : imagemin;
    
    return gulp.src(paths.images.src)
        .pipe(imageminFn(plugins))
        .pipe(gulp.dest(paths.images.dest))
        .on('end', () => console.log('✅ Immagini ottimizzate'));
}

// Fonts Task
function copyFonts() {
    return gulp.src('assets/css/core/font/**/*')
        .pipe(gulp.dest('build/assets/css/font/'));
}

// 🔥 Server + watch (proxy se usi PHP con XAMPP/Ngrok)
function serve() {
    const proxyUrl = process.env.BROWSERSYNC_PROXY || "http://localhost/sito_modulare/index.php";
    browserSync.init({
        proxy: proxyUrl,
        // se vuoi testare via ngrok:
        // proxy: "https://toed-preintelligent-beatrice.ngrok-free.dev?ngrok-skip-browser-warning=1",
        open: false,
        notify: false,
        serveStatic: ['.']
    });

    gulp.watch(['assets/css/**/*.css','modules/**/**/*.css'], cssDev);
    gulp.watch(paths.js.src, buildJS);
    gulp.watch(paths.images.src, optimizeImages).on('change', browserSync.reload);
    gulp.watch(['**/*.php','modules/**/*.php']).on('change', browserSync.reload);
}

// Development Task
function dev() {
    console.log('👀 Development mode: Watching CSS files: assets/css/**, modules/**/**');
    console.log('👀 Development mode: Watching JS files:', paths.js.src);
    gulp.watch(['assets/css/**/*.css','modules/**/**/*.css'], cssDev);
    gulp.watch(paths.js.src, buildJS);
}

// Clean build directory
function cleanBuild() {
    console.log('🧹 Cleaning build directory...');
    return gulp.src('build', { read: false, allowEmpty: true })
        .pipe(clean());
}

// Copy entire site to build directory
function copySiteFiles() {
    console.log('📁 Copying site files to build...');
    return gulp.src([
        '**/*',
        '!node_modules/**',
        '!build/**',
        '!*.md',
        '!css-dev/**',
        '!gulpfile.js',
        '!package.json',
        '!package-lock.json',
        '!index-prod.php',
        '!index.php',
        // Escludi CSS/JS individuali (già nel bundle)
        '!assets/css/core/**',
        // I font vengono copiati con task dedicato
        '!assets/font/**',
        '!modules/**/*.css',
        '!modules/**/*.js',
        '!assets/js/**'
    ])
    .pipe(gulp.dest('build/'));
}

// Copy SortableJS to build directory
function copySortableJS() {
    console.log('📦 Copying SortableJS to build...');
    return gulp.src('node_modules/sortablejs/**/*')
        .pipe(gulp.dest('build/node_modules/sortablejs/'));
}

// Copy production index to build as index.php
function copyIndexProdAsIndex() {
    console.log('🔄 Copying index-prod.php to build/index.php...');
    return gulp.src('index-prod.php')
        .pipe(rename('index.php'))
        .pipe(gulp.dest('build/'));
}

// Set production env
function setProdEnv(cb) { process.env.NODE_ENV = 'production'; cb(); }

// Release task: full site ready for cloud (no Node required on server)
const release = gulp.series(
    setProdEnv,
    cleanBuild,
    copySiteFiles,
    copySortableJS,
    copyIndexProdAsIndex,
    gulp.parallel(cssProd, buildJS, copyFonts, optimizeImages)
);

// EXPORT
exports.css = buildCSS;
exports.js = buildJS;
exports.images = optimizeImages;
exports.fonts = copyFonts;
exports.sortable = copySortableJS;
exports.dev = gulp.series(gulp.parallel(cssDev, buildJS, copyFonts), serve);
exports.build = gulp.series(
    cb => { process.env.NODE_ENV = 'production'; cb(); },
    gulp.parallel(cssProd, buildJS, copyFonts)
);
exports.release = release;
exports.default = exports.dev;
