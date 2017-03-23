'use strict';

//var Pikaday = require('pikaday');
var $ = require('jquery');

module.exports = /*@ngInject*/
function datetimeDirective() {
    return {
		restrict:'A',
		scope: {
			alert: '@'
		},
		link: function(scope, element) {
			console.log("datetime");

			//$(element).datetimepicker();
      		//var picker  = new Pikaday({ field: element[0] });
		}
	};
};
