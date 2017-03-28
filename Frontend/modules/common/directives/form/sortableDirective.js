'use strict';

var Sortable = require('sortablejs');

module.exports = /*@ngInject*/
function sortableDirective() {
    return {
		restrict:'A',
		scope: true,
		link: function(element) {
      		var sortable = new Sortable(element[0]);
		}
	};
};
