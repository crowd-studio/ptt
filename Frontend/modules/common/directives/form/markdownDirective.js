'use strict';

var $ = require('jquery');
require('bootstrap-markdown');

module.exports = /*@ngInject*/
function markdownDirective() {
    return {
		restrict:'A',
		scope: true,
		link: function(scope, element) {
			let notElements = ['cmdPreview','cmdImage'];
			$(element[0]).markdown({
				hiddenButtons: notElements,
				disabledButtons: notElements,
				footer: window.conf.trans['markdownHelp'],
			})
		}
	};
};
