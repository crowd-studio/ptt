'use strict';

var is = require('is_js');

module.exports = /*@ngInject*/
function menuUserDirective() {
	return {
		restrict: 'C',
		scope: true,
		link: function(scope, element) {
			scope.user = false;
			scope.clickUser = function(e){
				if(is.desktop()){
					scope.user = true;
				}else{
					e.preventDefault();
					scope.user = !scope.user;
				}
			};
			
			scope.overUser = function(e){
				e.preventDefault();
				if(is.desktop()){
					scope.user = true;
				}
			};

			scope.outUser = function(e){
				e.preventDefault();
				if(is.desktop()){
					scope.user = false;
				}
			};
		}
	};
};