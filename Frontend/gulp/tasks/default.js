'use strict';

var gulp = require('gulp');
var runSequence = require('run-sequence');

//'lint'

module.exports = gulp.task('default', function (callback) {
  if (release) {
    runSequence(
      'clean',
      ['index', 'styles', 'images', 'assets', 'browserify'],
      callback
    );
  } else {
    runSequence(
      'clean',
      ['index', 'styles', 'images', 'assets', 'browserify', 'watch']
    );
  }
});
