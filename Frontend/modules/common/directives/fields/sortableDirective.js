'use strict';

var Sortable = require('sortablejs');

module.exports = /*@ngInject*/
function markdownDirective() {
    return {
		restrict:'A',
		scope: {
			alert: '@'
		},
		link: function(scope, element) {
      var sortable = new Sortable(element[0]);
		}
	};
};
