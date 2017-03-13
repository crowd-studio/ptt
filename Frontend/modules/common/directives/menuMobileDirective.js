'use strict';

module.exports = /*@ngInject*/
function menuMobileDirective() {
	return {
		restrict: 'C',
		scope: true,
		link: function(scope, element) {

			scope.sidebar = false;
			
			angular.element(element).on( "click", function(e){
				e.preventDefault();
				scope.sidebar = !scope.sidebar;
				if(scope.sidebar){
					angular.element(document.getElementsByClassName('sidebar')).addClass('active');
				}else{
					angular.element(document.getElementsByClassName('sidebar')).removeClass('active');
				}
				scope.$apply();
		    });
		}
	};
};