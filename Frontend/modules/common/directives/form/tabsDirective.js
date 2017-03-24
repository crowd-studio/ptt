'use strict';

var $ = require('jquery');

module.exports = /*@ngInject*/
function tabsDirective($timeout) {
	return {
		restrict: 'C',
		scope: true,
		link: function(scope, element, attrs) {
			scope.nav = $(element).find('>.tab-nav>li>a');
			scope.tabs = $(element).find('>.tab-content>*');

			scope.actualTab = false;
			scope.actualNav = false;

			scope.changeTabEvent = function(e){
				e.preventDefault();
				if(!angular.element(e.currentTarget).hasClass('active')){
					scope.changeTab(e.currentTarget);
				}
			}
			scope.changeTab = function(element){
				var id = angular.element(element).attr('data-toggle');

				var result = $.grep(scope.tabs, function(e){ return "#"+e.id == id; });
				if (result.length === 1) {
					var newTab = result[0];

						angular.element(element).addClass('active');
						if(scope.actualNav){
							scope.actualNav.removeClass('active');
						}
						scope.actualNav = $(element);

						angular.element(newTab).addClass('active in');
						if(scope.actualTab){
							scope.actualTab.removeClass('active in');
						}
						scope.actualTab = angular.element(newTab);
				}
			}
			scope.changeTab(scope.nav[0]);
		}
	};
};
