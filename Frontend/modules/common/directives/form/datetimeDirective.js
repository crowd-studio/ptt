'use strict';

var $ = require('jquery');
require('eonasdan-bootstrap-datetimepicker');

module.exports = /*@ngInject*/
function datetimeDirective() {
	return {
		restrict:'A',
		scope: {
			alert: '@'
		},
		link: function(scope, element) {
			$(element).datetimepicker({
				format: $(element).attr('format'),
				locale: window.conf.lang
			});
		}
	};
};
