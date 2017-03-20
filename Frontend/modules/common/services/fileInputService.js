'use strict';

var _ = require('underscore');

module.exports = /*@ngInject*/
function fileInputService( $compile, $rootScope ) {
    var fileInput = {
    	initialize: function(options)
		{
			this.listOfFiles = [];
		},
		addInput : function(name,files){
			this.listOfFiles.push({name,files});
		},
		removeInput : function(name){
			this.listOfFiles = _.reject(this.listOfFiles, function(input){ return input.name == name; });
		},
		getInput : function(name){
			return _.find(this.listOfFiles, function(input){ return input.name == name });
		},
		getInputs : function (){
			return this.listOfFiles;
		}
	};
	fileInput.initialize();
	return fileInput;
};
