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

  watch( '' + config.paths.src.scripts, ['browserify'] );
  watch( '' + config.paths.src.index, ['index'] );
  watch( '' + config.paths.src.templates, ['browserify'] );
  watch( '' + config.paths.src.macros, ['browserify'] );
  watch( '' + config.paths.src.stylesGlob, ['styles'] );
});