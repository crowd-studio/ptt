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
	form.on('form:error', function() {
		angular.forEach($($element).find('form .tabs'), function(tab) {
			let withErrors = [];
			angular.forEach($(tab).find('.tab-pane'), function(pane, index) {
				withErrors[index] = $(pane).find('.parsley-error').length > 0;
			});
			angular.forEach($(tab).find('.tab-nav li a'), function(title, index) {
				if(withErrors[index]){
					$(title).addClass('with-errors');
				}else{
					$(title).removeClass('with-errors');
				}
			});
		});
	});

	$element.removeClass('ng-hide');
};
