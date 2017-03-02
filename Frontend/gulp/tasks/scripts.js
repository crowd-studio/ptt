'use strict';

var gulp = require('gulp');
var gulpif = require('gulp-if');

module.exports = gulp.task('scripts', function () {
  return gulp.src(config.paths.src.scripts)
    .pipe(gulpif(!release, gulp.dest(config.paths.dest.build.scripts)));
});
