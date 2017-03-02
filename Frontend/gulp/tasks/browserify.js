'use strict';

var gulp = require('gulp');
var browserify = require('browserify');
var source = require('vinyl-source-stream');
var buffer = require('vinyl-buffer');
var browserifyShim = require('browserify-shim');
var uglify = require('gulp-uglify');
var sourcemaps = require('gulp-sourcemaps');
var gulpif = require('gulp-if');
var livereload = require('gulp-livereload');
var p = require('partialify/custom');
var babelify = require('babelify');
var chalk = require('chalk');
var gutil = require('gulp-util');
var plumber = require('gulp-plumber');
var folderify = require('folderify');

function map_error(err) {
    gutil.beep();
    if (err.fileName) {
    // regular error
    gutil.log(chalk.red(err.name)
      + ': '
      + chalk.yellow(err.fileName.replace(__dirname + '/src/js/', ''))
      + ': '
      + 'Line '
      + chalk.magenta(err.lineNumber)
      + ' & '
      + 'Column '
      + chalk.magenta(err.columnNumber || err.column)
      + ': '
      + chalk.blue(err.description))
    } else {
    // browserify error..
    gutil.log(chalk.red(err.name)
      + ': '
      + chalk.yellow(err.message))
    }

    this.emit('end');
}

module.exports = gulp.task('browserify', function() {
    return browserify({
            entries: [config.paths.src.modules],
            debug: (release == true) ? false : true
        })
        .transform(browserifyShim)
        .transform(p.alsoAllow(['twig']))
        .transform(babelify, {presets: ["es2015"]})
        .transform(folderify)
        .bundle()
        .on('error', map_error)
        .pipe(plumber(map_error))
        .pipe(gulpif(release, source(config.filenames.release.scripts), source(config.filenames.build.scripts)))
        .pipe(buffer())
        .pipe(sourcemaps.init({loadMaps: true}))
        // Add transformation tasks to the pipeline here.
            // .pipe(gulpif(release, uglify()))
            .on('error', gutil.log)
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest(config.paths.dest.scripts))
        .pipe(gulpif(!release, livereload()));
});
