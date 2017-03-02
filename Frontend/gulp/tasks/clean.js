'use strict';

var gulp = require('gulp');
var rimraf = require('gulp-rimraf');

module.exports = gulp.task('clean', function () {
  return gulp.src(RELEASE_FOLDER, {read: false})
    .pipe(rimraf());
});
