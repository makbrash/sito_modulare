/**
 * Gulp Build System
 * Sistema di build per Bologna Marathon
 * Separazione DEV vs PROD con live-reload
 */

const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const autoprefixer = require('gulp-autoprefixer');
const cleanCSS = require('gulp-clean-css');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const imagemin = require('gulp-imagemin');
const imageminMozjpeg = require('imagemin-mozjpeg');
const imageminOptipng = require('imagemin-optipng');
const imageminSvgo = require('imagemin-svgo');
const sourcemaps = require('gulp-sourcemaps');
const watch = require('gulp-watch');
const gulpIf = require('gulp-if');
const browserSync = require('browser-sync').create();

const isProd = process.env.NODE_ENV === 'production';

// Paths
const paths = {
    css: {
        entry: 'assets/scss/main.scss',
        sass: 'assets/scss/**/*.scss',
        dest: 'assets/dist/css/'
    },
    js: {
        src: 'assets/js/**/*.js',
        dest: 'assets/dist/js/'
    },
    images: {
        src: 'assets/images/**/*',
        dest: 'assets/dist/images/'
    }
};

// CSS DEV (leggibile, con sourcemap "ricca" per edit SCSS da DevTools)
function cssDev() {
    console.log('🔨 Building CSS DEV (non minificato)...');
    return gulp.src(paths.css.entry)
        .pipe(sourcemaps.init())
        .pipe(sass({ outputStyle: 'expanded' }).on('error', sass.logError))
        .pipe(autoprefixer({ cascade: false }))
        .pipe(concat('main.css'))                       // NON minificato in DEV
        .pipe(sourcemaps.write('.', {
            includeContent: true,                         // embed sorgenti nello .map
            sourceRoot: '/assets/scss'                    // aiuta il mapping SCSS
        }))
        .pipe(gulp.dest(paths.css.dest))
        .pipe(browserSync.stream({ match: '**/*.css' })) // inietta CSS live
        .on('end', () => {
            console.log('✅ CSS DEV build completed!');
        });
}

// CSS PROD (minificato)
function cssProd() {
    console.log('🔨 Building CSS PROD (minificato)...');
    return gulp.src(paths.css.entry)
        .pipe(sourcemaps.init())
        .pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
        .pipe(autoprefixer({ cascade: false }))
        .pipe(cleanCSS({ level: 2 }))
        .pipe(concat('main.min.css'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.css.dest))
        .on('end', () => {
            console.log('✅ CSS PROD build completed!');
        });
}

function buildCSS() { return isProd ? cssProd() : cssDev(); }

// JS invariato ma con reload
function buildJS() {
    return gulp.src([
        'assets/js/core/app.js',
        'assets/js/modules/**/*.js'
    ])
    .pipe(sourcemaps.init())
    .pipe(concat('app.min.js'))
    .pipe(gulpIf(isProd, uglify()))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(paths.js.dest))
    .pipe(browserSync.stream());
}

// Images Task
function optimizeImages() {
    return gulp.src(paths.images.src)
        .pipe(imagemin([
            imageminMozjpeg({ quality: 80 }),
            imageminOptipng({ optimizationLevel: 5 }),
            imageminSvgo({
                plugins: [
                    { removeViewBox: false },
                    { removeEmptyAttrs: false }
                ]
            })
        ]))
        .pipe(gulp.dest(paths.images.dest));
}

// Fonts Task
function copyFonts() {
    return gulp.src('assets/font/**/*')
        .pipe(gulp.dest('assets/dist/font/'));
}

// 🔥 Server + watch (proxy se usi PHP con XAMPP/Ngrok)
function serve() {
    browserSync.init({
        // se sviluppi in locale:
        proxy: "http://localhost/sito_modulare/index.php",
        // se vuoi testare via ngrok:
        // proxy: "https://toed-preintelligent-beatrice.ngrok-free.dev?ngrok-skip-browser-warning=1",
        open: false,
        notify: false,
        serveStatic: ['.']
    });

    watch(paths.css.sass, cssDev);
    gulp.watch(paths.js.src, buildJS);
    gulp.watch(paths.images.src, optimizeImages).on('change', browserSync.reload);
    gulp.watch(['**/*.php','modules/**/*.php']).on('change', browserSync.reload);
}

// Development Task
function dev() {
    console.log('👀 Development mode: Watching SCSS files:', paths.css.sass);
    console.log('👀 Development mode: Watching JS files:', paths.js.src);
    
    watch(paths.css.sass, cssDev)
        .on('change', (path) => {
            console.log('📝 SCSS file changed:', path);
        });
    
    gulp.watch(paths.js.src, buildJS);
}

// EXPORT
exports.css = buildCSS;
exports.js = buildJS;
exports.images = optimizeImages;
exports.fonts = copyFonts;
exports.dev = gulp.series(gulp.parallel(cssDev, buildJS, copyFonts), serve);
exports.build = gulp.series(
    cb => { process.env.NODE_ENV = 'production'; cb(); },
    gulp.parallel(cssProd, buildJS, copyFonts)
);
exports.default = exports.dev;
