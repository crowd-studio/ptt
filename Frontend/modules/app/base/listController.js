'use strict';

var Sortable = require('sortablejs');

module.exports = /*@ngInject*/
function listController($scope, $element, $http, loader) {
	$scope.showFilters = ( angular.element(document.querySelector('.filters')).attr('active-filters') != "[]" );
	$scope.changeOrder = false;

	history.pushState("", document.title, window.location.pathname);
	
	if( angular.element(document.querySelector('.btn-sort')).length > 0){	
		$scope.sortable = Sortable.create(document.getElementById('items'),{
			disabled: !$scope.changeOrder,
			chosenClass: "chosen",
			ghostClass: "ghost"
		});
		$scope.actualOrder = $scope.sortable.toArray();
	}

	$scope.activeOrderEvent = function(e){e.preventDefault();$scope.activeOrder()}
	$scope.activeOrder = function(){
		$scope.clickOrder(true);
	}
	$scope.cancelOrderEvent = function(e){e.preventDefault();$scope.cancelOrder()}
	$scope.cancelOrder = function(){
		$scope.clickOrder(false);
		$scope.sortable.sort($scope.actualOrder);
	}

	$scope.saveOrderEvent = function(e){e.preventDefault();$scope.saveOrder()}
	$scope.saveOrder = function(){
		let data = [];
		angular.forEach($scope.sortable.toArray(), function(value, key) {
			data.push({id: value, _order: key});
		});

		loader.show();
		$http({
				method: 'PUT',
				url: window.conf.baseURL + 'admin_panel/sortable/order',
				data
			}).then(function successCallback(response) {
				loader.hide();
				if(response.data.success){
					$scope.clickOrder(false);
					$scope.actualOrder = $scope.sortable.toArray();
				}else{
					console.log(response.data);
					alert('error in save order. Please try again.')
				}
			}, function errorCallback(response) {
				loader.hide();
				console.log('ERROR code: ' + response.status + ' message: ' + response.statusText);
				alert('error in save order. Please try again.')
			});
	}

	

	$scope.clickOrder = function(value){
		$scope.changeOrder = value;
		$scope.sortable.option("disabled", !$scope.changeOrder);
	}

	

	$element.removeClass('hide');
};