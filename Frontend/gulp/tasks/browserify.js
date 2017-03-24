'use strict';


var watchify = require('watchify');
var browserify = require('browserify');
var gulp = require('gulp');
var source = require('vinyl-source-stream');
var buffer = require('vinyl-buffer');
var gutil = require('gulp-util');
var sourcemaps = require('gulp-sourcemaps');
var assign = require('lodash.assign');
var gulpif = require('gulp-if');
var browserifyShim = require('browserify-shim');
var p = require('partialify/custom');
var babelify = require('babelify');
var folderify = require('folderify');
var livereload = require('gulp-livereload');

// add custom browserify options here
var customOpts = {
  entries: [config.paths.src.modules],
  debug: (release == true) ? false : true
};
var opts = assign({}, watchify.args, customOpts);
var b = watchify(browserify(opts))

// add transformations here
.transform(browserifyShim)
.transform(p.alsoAllow(['twig']))
.transform(babelify, {presets: ["es2015"]})
.transform(folderify);

gulp.task('browserify', bundle); // so you can run `gulp js` to build the file
b.on('update', bundle); // on any dep update, runs the bundler
b.on('log', gutil.log); // output build logs to terminal

function bundle() {
  return b.bundle()
    // log errors if they happen
    .on('error', gutil.log.bind(gutil, 'Browserify Error'))
    .pipe(gulpif(release, source(config.filenames.release.scripts), source(config.filenames.build.scripts)))
    // optional, remove if you don't need to buffer file contents
    .pipe(buffer())
    // optional, remove if you dont want sourcemaps
    .pipe(sourcemaps.init({loadMaps: true})) // loads map from browserify file
       // Add transformation tasks to the pipeline here.
    .pipe(sourcemaps.write('./')) // writes .map file
    .pipe(gulpif(release, gulp.dest(config.paths.dest.release.scripts), gulp.dest(config.paths.dest.build.scripts)))
    .pipe(gulpif(!release, livereload()));
}
