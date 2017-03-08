'use strict';

var $ = require('jquery');

module.exports = /*@ngInject*/
function editController($scope, $element) {
	console.log("EDITCONTROLLER");


	/*$($element).find('form'.parsley().on('field:validated', function() {
		
	})
	.on('form:submit', function() {
		return false; // Don't submit form for this demo
	});*/
	$element.removeClass('ng-hide');
};