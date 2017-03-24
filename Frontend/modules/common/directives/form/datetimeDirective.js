'use strict';

//var Pikaday = require('pikaday');
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
				locale: 'en'
			});
      		//var picker  = new Pikaday({ field: element[0] });
		}
	};
};
