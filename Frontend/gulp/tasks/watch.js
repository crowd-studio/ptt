/* liveReload */
'use strict';

var gulp = require('gulp');
var watch = require('gulp-watch');
var gulpif = require('gulp-if');
var livereload = require('gulp-livereload');
var livereloadServer = livereload(config.ports.livereloadServer);
var runSequence = require('run-sequence');

module.exports = gulp.task('watch', function () {
  livereload.listen();

  watch({ glob: [config.paths.src.scripts]}, ['browserify']);
  watch({ glob: [config.paths.src.index]}, ['index']);
  watch({ glob: [config.paths.src.templates]}, ['browserify']);
  watch({ glob: [config.paths.src.macros]}, ['browserify']);
  watch({ glob: [config.paths.src.stylesGlob]}, ['styles']);
});
