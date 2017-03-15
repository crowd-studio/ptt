'use strict';

module.exports = /*@ngInject*/
function loaderService() {
    var loader = {
		initialize: function()
		{
			this.$el = angular.element(document.querySelector('.loader'));
			this.hide();
			return this;
		},
		show: function(){
			this.$el.addClass('show');
		},
		hide: function(){
			this.$el.removeClass('show');
		}
	};
	return loader.initialize();
};
