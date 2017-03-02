require.config({
    paths:{
        "jquery": "vendor/jquery/dist/jquery",
        "backbone": "vendor/backbone-amd/backbone",
        "modernizr": "vendor/modernizr/modernizr",
        "underscore": "vendor/underscore-amd/underscore",
        "mustache": "vendor/mustache/mustache",
        "text": "vendor/requirejs-plugins/lib/text",
        "bootstrap" : "vendor/bootstrap/dist/js/bootstrap",
        "markdown" : "vendor/markdown/lib/markdown",
        "to-markdown" : "vendor/to-markdown/src/to-markdown",
        "bootstrap-markdown" : "vendor/bootstrap-markdown/js/bootstrap-markdown",
        "asmselect" : "vendor-static/asmselect/jquery.asmselect",
        "moment" : "vendor/moment/min/moment-with-locales.min",
        "bootstrap-datepicker" : "vendor/bootstrap-datepicker/js/bootstrap-datepicker",
        "bootstrap-datepicker-es" : "vendor/bootstrap-datepicker/js/locales/bootstrap-datepicker.es",
        "bootstrap-colorpicker" : "vendor/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min",
        "select2" : "vendor/select2/select2",
        "select2-es" : "vendor/select2/select2_locale_es",
        "sortable" : "vendor-static/html5sortable/jquery.sortable",
        "backboneView" : "vendor-static/backboneView/backboneView",
        "dropzone" : "vendor-static/dropzone/dropzone",
        "selectize" : "vendor-static/selectize/src/selectize.jquery"
    },
    shim:{
        "bootstrap" : ["jquery"],
        "bootstrap-markdown" : ["jquery"],
        "asmselect" : ["jquery"],
        "bootstrap-datepicker" : ["jquery"],
        "bootstrap-datepicker-es" : ["bootstrap-datepicker"],
        "bootstrap-colorpicker" : ["jquery"],
        "select2" : ["jquery"],
        "select2-es" : ["select2"],
        "sortable" : ["jquery"],
        "backboneView" : ["jquery", "backbone"]
    }
});

require([
    'app-admin'
], function(app) {
    window.app.initialize();
});