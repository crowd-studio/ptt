'use strict';

var gulp = require('gulp');
var args = require('yargs').argv;
var insert = require('gulp-insert');
var file = require('gulp-file');

//--- Provider ---//

var providerIndexStr = "'use strict';\r\rmodule.exports =\r    angular.module('app." + args.name + "', [\r        //load your " + args.name + " submodules here, e.g.:\r        //require('./bar').name\r    ])\r    .config(function($stateProvider) {\r        $stateProvider\r            .state('" + args.name + "', {\r                url: '',\r                templateUrl: 'app/" + args.name + "/layout.html',\r                controller: '" + args.name + "Controller'\r            });\r    })\r    .controller('" + args.name + "Controller', require('./" + args.name + "Controller'));",
    providerControllerStr = "'use strict';\r\rmodule.exports = /*@ngInject*/\r    function " + args.name + "Controller($scope) {\r        \r    };"

module.exports = gulp.task('angular:provider', function() {
    return file('index.js', providerIndexStr)
        .pipe(file(args.name + 'Controller.js', providerControllerStr))
        .pipe(file('layout.html', ''))
        .pipe(gulp.dest(config.paths.src.app + '/' + args.name));
});

//--- ProviderController ---//

var providerController2Str = "'use strict';\r\rmodule.exports = /*@ngInject*/\r    function " + args.name + "Controller($scope) {\r        \r    };"

module.exports = gulp.task('angular:providerController', function() {
    return file(args.name + 'Controller.js', providerController2Str)
        .pipe(gulp.dest(config.paths.src.app + '/' + args.provider));
});

//--- Controller ---//

var controllerStr = "'use strict';\r\rmodule.exports =\r    angular.module('app." + args.name + "', [\r        //load your foo submodules here, e.g.:\r        //require('./bar').name\r    ])\r    .controller('" + args.name + "Controller', ['$scope', function($scope){\r\r    }]);";

module.exports = gulp.task('angular:controller', function() {
    return file(args.name + 'Controller.js', controllerStr, {
            src: true
        })
        .pipe(gulp.dest(config.paths.src.controllers));
});

//--- Directive ---//

var directiveStr = "'use strict';\r\rmodule.exports = /*@ngInject*/\rfunction " + args.name + "Directive( /* inject dependencies here, i.e. : $rootScope */ ) {\r    return {\r        link: function(scope, element, attrs) {\r            // Do something awesome\r        }\r    };\r};";

module.exports = gulp.task('angular:directive', function() {
    return gulp.src(config.paths.src.directives + '/index.js')
        .pipe(insert.transform(function(contents) {
            return contents.substring(0, contents.length - 1);
        }))
        .pipe(insert.append("\r    .directive('" + args.name + "', require('./" + args.name + "Directive'));"))
        .pipe(file(args.name + 'Directive.js', directiveStr))
        .pipe(gulp.dest(config.paths.src.directives));
});

//--- Filter ---//

var filterStr = "'use strict';\r\rmodule.exports = /*@ngInject*/\r    function " + args.name + "Filter( /* inject dependencies here, i.e. : $rootScope */ ) {\r        return function(input) {\r            // Do something awesome\r        };\r    };";

module.exports = gulp.task('angular:filter', function() {
    return gulp.src(config.paths.src.filters + '/index.js')
        .pipe(insert.transform(function(contents) {
            return contents.substring(0, contents.length - 1);
        }))
        .pipe(insert.append("\r    .filter('" + args.name + "', require('./" + args.name + "Filter'));"))
        .pipe(file(args.name + 'Filter.js', filterStr))
        .pipe(gulp.dest(config.paths.src.filters));
});

//--- Service ---//

var serviceStr = "'use strict';\r\rmodule.exports = /*@ngInject*/\r    function " + args.name + "Service( /* inject dependencies here, i.e. : $rootScope */ ) {\r        return {\r            // Do something awesome\r        };\r    };\r";

module.exports = gulp.task('angular:service', function() {
    return gulp.src(config.paths.src.services + '/index.js')
        .pipe(insert.transform(function(contents) {
            return contents.substring(0, contents.length - 1);
        }))
        .pipe(insert.append("\r    .factory('" + args.name + "', require('./" + args.name + "Service'));"))
        .pipe(file(args.name + 'Service.js', serviceStr))
        .pipe(gulp.dest(config.paths.src.services));
});

//--- Template ---//

module.exports = gulp.task('angular:template', function() {
    return file(args.name + '.html', '', {
            src: true
        })
        .pipe(gulp.dest(config.paths.src.templates + '/' + args.group));
});
