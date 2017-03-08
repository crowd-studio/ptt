'use strict';

module.exports = /*@ngInject*/
function listController($scope, $element) {
	$scope.showFilters = ( angular.element(document.querySelector('.filters')).attr('active-filters') != "[]" );

	$element.removeClass('ng-hide');
};