'use strict';

var $ = require('jquery');

module.exports = /*@ngInject*/
function fileUploadDirective(fileInput) {
	return {
		restrict: 'C',
		scope: true,
		link: function(scope, element, attrs) {
			$(element).removeClass("loading");

			scope.container = $(element);
			scope.fakeClick = $(element).find('div.fake-click');
			scope.restoreClick = $(element).find('a.restore-click');
			scope.inputFile = $(element).find('input[type=file]');
			scope.removeFile = $(element).find('a.remove-file');
			scope.warnings = $(element).find('.warnings');
			scope.img = $(element).find('div.preview-file');

			scope.initial = scope.img.attr('init-file');
			scope.loaded = scope.initial !== '';
			scope.changed = false;
			scope.validation = scope.inputFile.attr('filemimetypes');
			scope.withPreview = scope.validation ? scope.validation.indexOf("image") > -1 : false;
			scope.fakeText = 'Upload File';
			scope.filenameName = 'File Uploaded';

			if(scope.img.attr('max-width')){
				scope.img.css('max-width', scope.img.attr('max-width') + 'px');
			}
			if(scope.img.attr('max-height')){
				scope.img.css('max-height', scope.img.attr('max-height') + 'px');
			}

			if(scope.loaded){
				//scope.inputFile.disabled = true;
				scope.img.css('background-image','url("' + scope.initial + '")');
			}

			scope.fakeClickFunction = function(e){
				e.preventDefault();
				//scope.inputFile.click();
			};
			scope.restoreClickFunction = function(e){
				e.preventDefault();
				scope.img.css('background-image','url("' + scope.initial + '")');
				scope.loaded = true;
				scope.warnings.empty();
				scope.changed = false;
				scope.inputFile.parsley().validate();
			};
			scope.removeFileFunction = function(e){
				e.preventDefault();
				e.stopPropagation();
				//scope.loaded = false;
				fileInput.removeInput(scope.inputFile.attr('name'));
				scope.inputFile.val('').change(); 
			};
			scope.inputFileFunction = function(files){
				scope.fakeClick.blur();

				if (files && files[0]) {
					var selected_file = files[0];
					scope.warnings.empty();

					if(scope.mimeIsValid(selected_file)){
						if(scope.withPreview){
							var reader = new FileReader();
							reader.onload = (function(aImg) {
								return function(e) {
									scope.img.css('background-image', 'url("' + reader.result + '")');
									scope.loaded = true;
									scope.changed = true;
									scope.$apply();
									scope.inputFile.parsley().validate();
								};
							})(scope);
							reader.readAsDataURL(selected_file);
						}else{
							scope.filenameName = selected_file.name;
							scope.loaded = true;
							scope.changed = true;
							scope.$apply();
							scope.inputFile.parsley().validate();
						}
						
					}else{
						console.log(conf.trans.fileMimeError);
						scope.warnings.append(conf.trans.fileMimeError);
						scope.inputFile.val('').change();
						scope.$apply();
					}
				}else{
					scope.img.css('background-image', '');
					scope.loaded = false;
					scope.changed = scope.initial !== "";
				}
			};
			scope.mimeIsValid = function (selected_file){
				var isValid = true;
				if(scope.validation){
					var requirement = scope.validation;
					var allowedMimeTypes = requirement.replace(/\s/g, "").split(',');
					//console.log('type: '+selected_file.type,allowedMimeTypes);
					isValid = allowedMimeTypes.indexOf(selected_file.type) !== -1;
					if(!isValid){
						isValid = allowedMimeTypes.indexOf(selected_file.type.split('/')[0]) !== -1;
					}
				}
				return isValid;
			};
	  		scope.fakeClickNew = (element,callback) => {
  				/*let input = document.createElement('input');
				input.setAttribute('type', 'file');
				input.setAttribute('multiple', false);
				input.style.display = 'none';*/
				let input = scope.inputFile[0];

				input.addEventListener('change', triggerCallback);
				//element.appendChild(input);

				element.addEventListener('dragover', function(e) {
					e.preventDefault();
					e.stopPropagation();
					element.classList.add('is-dragover');
				});

				element.addEventListener('dragleave', function(e) {
					e.preventDefault();
					e.stopPropagation();
					element.classList.remove('is-dragover');
				});

				element.addEventListener('drop', function(e) {
					e.preventDefault();
					e.stopPropagation();
					element.classList.remove('is-dragover');
					triggerCallback(e);
				});

				element.addEventListener('click', function() {
					input.value = null;
					input.click();
				});

				function triggerCallback(e) {
					var files;
					if(e.dataTransfer) {
						files = e.dataTransfer.files;
					} else if(e.target) {
						files = e.target.files;
					}
					callback.call(null, files);
				}
	  		};
	  		scope.fakeClickNew(scope.fakeClick[0], files => {
	  			scope.inputFileFunction(files);
	  			fileInput.addInput(scope.inputFile.attr('name'),files);
	  		});
		}
	};
};