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
			console.log(element[0].innerHTML);
			var simplemde = new SimpleMDE({ 
				element: element[0],
				status: false,
				spellChecker: false,
				autofocus: true
			});
			simplemde.value(element[0].innerHTML);
		}
	};
};
