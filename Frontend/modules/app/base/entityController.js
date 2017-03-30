'use strict';

var Sortable = require('sortablejs');
require('angular-sanitize');

module.exports = /*@ngInject*/
function entityController($scope, $element, $http, loader, $sce) {

	$scope.items = JSON.parse($element[0].querySelector('.entity-block').getAttribute('items'));
	$scope.templates = JSON.parse($element[0].querySelector('.entity-block').getAttribute('templates'));

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

	$scope.newEvent = function(){$scope.new();}
	$scope.new = function (){
		if($scope.selectedTemplate !== ''){
			let newTemplate = angular.copy($scope.selectedTemplate);
			newTemplate.data = newTemplate.data.split('[{key}]').join('['+$scope.items.length+']')
			$scope.items.push(newTemplate);
		}		
	}

	$scope.toggleEntityEvent = function(e,index) {e.preventDefault();$scope.toggleEntity(index)}
	$scope.toggleEntity = function(index){
		$scope.items[index].opened = $scope.items[index].opened == undefined ? true : !$scope.items[index].opened;
	}

	$scope.deleteEntityEvent = function(e,index) {
		e.preventDefault();
		if(confirm(window.conf.trans['sureDelete'] )){
			$scope.deleteEntity(index);
		}
	}
	$scope.deleteEntity = function(index){
		$scope.items.splice(index, 1);
	}
	

	$scope.renderHtml = function(html_code){return $sce.trustAsHtml(html_code);};

	$scope.setSortable(false);

	$element.removeClass('hide');
};