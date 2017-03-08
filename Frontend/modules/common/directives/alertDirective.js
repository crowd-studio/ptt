'use strict';

module.exports = /*@ngInject*/
function alertDirective() {
    return {
        restrict:'A',
        scope: {
            alert: '@'
        },
        link: function(scope, element) {
        	angular.element(element).on( "click", function(e){
                if(!confirm(scope.alert)){
               		e.preventDefault();
                }
            });
        }
    };
};