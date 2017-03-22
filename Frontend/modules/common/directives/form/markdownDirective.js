'use strict';

var SimpleMDE = require('simplemde');

module.exports = /*@ngInject*/
function markdownDirective() {
    return {
		restrict:'A',
		scope: {
			alert: '@'
		},
		link: function(scope, element) {
      var simplemde = new SimpleMDE({ element: element[0],status: false });
		}
	};
};
