'use strict';

var Sortable = require('sortablejs');

module.exports = /*@ngInject*/
function entityController($scope, $element, $http, loader) {

	$scope.sortable = Sortable.create($element[0].querySelector('.items-to-order'),{
		disabled: !$scope.changeOrder,
		chosenClass: "chosen",
		ghostClass: "ghost"
	});
	$scope.actualOrder = $scope.sortable.toArray();

	$scope.activeOrderEvent = function(e){e.preventDefault();$scope.activeOrder()}
	$scope.activeOrder = function(){
		$scope.setSortable(true);
	}

	$scope.cancelOrderEvent = function(e){e.preventDefault();$scope.cancelOrder()}
	$scope.cancelOrder = function(){
		$scope.setSortable(false);
		$scope.sortable.sort($scope.actualOrder);
	}

	$scope.saveOrderEvent = function(e){e.preventDefault();$scope.saveOrder()}
	$scope.saveOrder = function(path){
		$scope.setSortable(false);
		$scope.actualOrder = $scope.sortable.toArray();
	}

	$scope.setSortable = function(value){
		$scope.changeOrder = value;
		$scope.sortable.option("disabled", !$scope.changeOrder);
	}

	

	$scope.newEvent = function(){
		console.log("NEW");
	}

	$scope.toggleEntityEvent = function(e,id) {e.preventDefault();$scope.toggleEntity(id)}
	$scope.toggleEntity = function(id){
		angular.forEach($element[0].querySelectorAll('[data-id]'), function(value, key) {
			if(id == value.getAttribute('data-id')){
				let entity = angular.element(value);
				if(entity.hasClass('opened')){
					angular.element(value).removeClass('opened');
				}else{
					angular.element(value).addClass('opened');
				}
			}
		});
	}
	

	

	$scope.setSortable(false);

	$element.removeClass('hide');
};