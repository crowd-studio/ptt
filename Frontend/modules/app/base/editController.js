'use strict';

var $ = require('jquery');
var simpleMDE = require('simplemde');

module.exports = /*@ngInject*/
function editController($scope, $element) {

	let form = $($element).find('form').parsley();
	form.on('form:submit', function() {
		return false;
		return form.isValid();
	});

	$element.removeClass('ng-hide');
};
