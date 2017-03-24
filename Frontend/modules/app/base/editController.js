'use strict';

var $ = require('jquery');
require('parsleyjs');
require('parsleyjs/dist/i18n/en');

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

	window.Parsley.addValidator('url', {
		requirementType: 'string',
		validateString: function (value, requirement, parsleyInstance) {
			return value.search(/(http|https):\/\//i) == 0;
		},
			messages: {
			en: 'This value should be a valid url.'
		}
	});

	$scope.form = $($element).find('form');
	$scope.form.parsley().on('form:error', function() {
		angular.forEach($scope.form.find('.tabs'), function(tab) {
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
	$scope.saveEvent = function(ev,action){
		ev.preventDefault();
		$scope.action = action;
		$scope.$$postDigest(()=>{$scope.form.submit();});
	}

	$element.removeClass('hide');
};
