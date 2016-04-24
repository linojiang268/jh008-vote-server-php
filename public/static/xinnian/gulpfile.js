var gulp = require('gulp');
var concat = require('gulp-concat');
var minifyCss = require('gulp-minify-css');
var minJs    = require('gulp-uglify');
var reactify = require('reactify');
var browserify = require('browserify');
var source = require('vinyl-source-stream');
var buffer = require('vinyl-buffer');
var sourcemaps = require('gulp-sourcemaps');

// concat & uglify css
gulp.task('css', function() {
    gulp.src([
        './css/normalize.css',
        './css/main.css',
        './css/modal.css',
        '/static/plugins/webuploader/webuploader.css'
    ])
    .pipe(concat('main.min.css'))
    .pipe(minifyCss())
    .pipe(gulp.dest('./css/'));
});

gulp.task('react_js', function() {
   /* gulp.src('./js/home.js')
    .pipe(reactify('home_test.js'))
    .pipe(gulp.dest('./'));*/

    browserify({
        entries: ['./js/init.js'],
        debug: true,
        transform: [reactify]
    })
    .bundle()
    .pipe(source('./bundle.min.js'))
    .pipe(buffer())
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('./js/'));
});

gulp.task('common_js', function() {
    gulp.src([
        './js/common.js',
        './js/server.js',
        './js/ajaxfileuploader.js'
    ])
    .pipe(concat('common.min.js'))
    .pipe(gulp.dest('./js/'));
});


gulp.task('default', ['css', 'react_js', 'common_js']);