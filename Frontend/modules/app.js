'use strict';

require("babel-polyfill");

//browserify-shim dependencies (can be edited in package.json)
require('angular');
// require('angular-animate');
// require('ng-tags-input');

require('parsleyjs');
require('parsley-en');

//app entry point
require('./app/index.js');
