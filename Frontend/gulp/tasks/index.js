'use strict';

var gulp = require('gulp');
var gulpif = require('gulp-if');
var replace = require('gulp-replace');
var livereload = require('gulp-livereload');
var moment = require('moment');

module.exports = gulp.task('index', function () {
  return gulp.src(config.paths.src.index)
    .pipe(gulpif(release,
      replace(/<!-- styles -->\n([\s\S]*?)<!-- \/styles -->/gm, '<!-- styles -->\n<link href="{{ asset(\'' + FRONTEND_URL + config.filenames.release.styles + '\') }}?v=' + moment().unix() + '" rel="stylesheet">\n<!-- /styles -->'),
      replace(/<!-- styles -->\n([\s\S]*?)<!-- \/styles -->/gm, '<!-- styles -->\n<link href="{{ asset(\'' + FRONTEND_URL + config.filenames.build.styles + '\') }}" rel="stylesheet">\n<!-- /styles -->')
    ))
    .pipe(gulpif(release,
      replace(/<!-- scripts -->\n([\s\S]*?)<!-- \/scripts -->/gm, '<!-- scripts -->\n<script src="{{ asset(\'' + FRONTEND_URL + config.filenames.release.scripts + '\') }}?v=' + moment().unix() + '"></script>\n<!-- /scripts -->'),
      replace(/<!-- scripts -->\n([\s\S]*?)<!-- \/scripts -->/gm, '<!-- scripts -->\n<script src="{{ asset(\'' + FRONTEND_URL + config.filenames.build.scripts + '\') }}"></script>\n<!-- /scripts -->')
    ))
    .pipe(gulp.dest(config.paths.dest.index))
    .pipe(gulpif(!release, livereload()));
});
