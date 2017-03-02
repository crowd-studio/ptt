'use strict';

module.exports =
  angular.module('app.common', [
    require('./directives').name,
    require('./filters').name,
    require('./services').name
  ]);
