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

	$scope.saveOrderEvent = function(e){e.preventDefault();$scope.saveOrder()}
	$scope.saveOrder = function(path){
		$scope.setSortable(false);
	}

	$scope.setSortable = function(value){
		$scope.changeOrder = value;
		$scope.sortable.option("disabled", !$scope.changeOrder);
	}

	$scope.newEvent = function(){$scope.new();}
	$scope.new = function (){
		if($scope.selectedTemplate !== ''){
			let newTemplate = angular.copy($scope.selectedTemplate);
			newTemplate.data = newTemplate.data.split('{key_order_entity_'+newTemplate.title.toLowerCase()+'}').join($scope.items.length);
			newTemplate.opened = true;
			$scope.items.push(newTemplate);
		}
	}

	$scope.toggleEntityEvent = function(e,item) {e.preventDefault();$scope.toggleEntity(item)}
	$scope.toggleEntity = function(item){
		item.opened = item.opened == undefined ? true : !item.opened;
	}

	$scope.deleteEntityEvent = function(e,list,index) {
		e.preventDefault();
		if(confirm(window.conf.trans['sureDelete'] )){
			$scope.deleteEntity(list,index);
		}
	}
	$scope.deleteEntity = function(list,index){
		list.splice(index, 1);
	}

	$scope.isOpened = function(item){
		return item.opened == true
	}


	$scope.renderHtml = function(html_code){return $sce.trustAsHtml(html_code);};

	$scope.setSortable(false);

	$element.removeClass('hide');
};
