'use strict';

var $ = require('jquery');
var simpleMDE = require('simplemde');

module.exports = /*@ngInject*/
function editController($scope, $element) {

	//uploadrequired parsley
	window.Parsley.addValidator('uploadrequired', {
		requirementType: 'string',
		validateString: function (value, requirement, parsleyInstance) {
			return angular.element(parsleyInstance.$element).scope().loaded;
		},
			messages: {
			en: 'This value is required'
		}
	});

	let form = $($element).find('form').parsley();
	form.on('form:submit', function() {
		// return false;
		return form.isValid();
	});

	$element.removeClass('ng-hide');
};
