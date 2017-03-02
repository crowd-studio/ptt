'use strict';

var gulp = require('gulp');
var gulpif = require('gulp-if');
var rename = require('gulp-rename');
var csso = require('gulp-csso');
var less = require('gulp-less');
var sourcemaps = require('gulp-sourcemaps');
var livereload = require('gulp-livereload');


function handleError(err) {
  console.log(err.toString());
  this.emit('end');
}


module.exports = gulp.task('styles', function(){
    return gulp.src(config.paths.src.styles)
    .pipe(gulpif(!release, sourcemaps.init()))
    .pipe(less().on('error', handleError))
    .pipe(gulpif(release, csso()))
    .pipe(gulpif(!release, sourcemaps.write()))
    .pipe(gulpif(release, rename(config.filenames.release.styles), rename(config.filenames.build.styles)))
    .pipe(gulp.dest(config.paths.dest.styles))
    .pipe(gulpif(!release, livereload()));
});
