define([
    "jquery",
    "globals",
    "backbone",
    "underscore",
    "mustache",
    "text!templates/upload-file-modal.mustache",
    "moment",
    "bootstrap",
    "markdown",
    "to-markdown",
    "bootstrap-markdown",
    "asmselect",
    "bootstrap-datepicker",
    "bootstrap-datepicker-es",
    "bootstrap-colorpicker",
    "select2",
    "select2-es",
    "sortable",
    "backboneView",
    "dropzone",
    "selectize"
], function($, globals, Backbone, _, Mustache, uploadFileTemplate, moment){

    window.app = {
        baseUrl : '/app_dev.php/',
        initialize: function()
        {
            var that = this;

            $.each($('textarea[data-type="markdown"]'), function(element){
                var markdownView = new MarkdownView({el:$(this)});
            });

            _.each($('a'), function(link) {
                $(link).bind('click', function(event){
                    if ($(link).attr('target') != '_blank' && typeof $(link).attr('href') != 'undefined' && $(link).attr('href') != '#' 
                        && $(link).attr('href').indexOf('language-') == -1 && $(link).attr('href').indexOf('export-csv') == -1) {
                        $('.loader').addClass('visible');
                    }
                });
            });

            $('div.alert:not(.persist)').delay(3000).fadeOut(200);

            $("select[multiple]").asmSelect();

            $(window).bind('resize', _.bind(this.resize, this));

            $('.mobile-nav a').click(function(e){
                e.preventDefault();
                that.toggleSidebar();
            });

            $('.select-search').each(function(){
                var model = $(this).attr('data-model').toLowerCase();
                var id = $(this).attr('value');
                var title = $(this).attr('data-title');
                var that = $(this);
                el:$(this).selectize({
                valueField: 'id',
                labelField: 'title',
                searchField: 'title',
                options: [],
                create: false,
                render: {
                    option: function(item, escape) {
                        console.log(item);
                        return '<div>' +
                                '<span class="title">' +
                                    '<span class="name">' + escape(item.title) + '</span>' +
                                '</span>' +
                                '<span class="id hidden">' + item.id + '</span>' +
                        '</div>';
                    }
                },
                load: function(query, callback) {
                    if (!query.length) return callback();
                    $.ajax({
                        url: window.app.baseUrl + 'admin/' + model + '/search' ,
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            q: query,
                            page_limit: 30
                        },
                        success: function(res) {
                            console.log(res);
                            callback(res);
                        },
                        error: function() {
                            callback();
                        }
                    });
                }
                });

                var selectize = field[0].selectize;
                selectize.addOption(title);
                selectize.setValue(id);
                
                that.parent().find('input').val(title);
                that.parent().find('input').attr('value', id);
                
                // Omplim el valor si ja tenia guardat a la BBDD
                if(id > -1){
                    $(field[0]).find('option').val(id);
                }
            });
                

            $('[data-fieldtype="entity"]').each(function(){
                var cloneMultipleEntitiesView = new CloneMultipleEntitiesView({el:$(this)});
            });

            $('[data-fieldtype="multipleEntity"]').each(function(){
                var cloneMultipleEntitiesView = new CloneMultipleEntitiesView({el:$(this)});
            });

            $.each($('[data-fieldtype="gallery"]'), function(element){
                var cloneMultipleEntitiesView = new CloneMultipleEntitiesView({el:$(this)});
                var myDropzone = new Dropzone(".dropzone", {
                    acceptedFiles: "image/*",
                    url: window.app.baseUrl + "ptt/media/upload/",
                    success: function(file, response) {
                        cloneMultipleEntitiesView.add_gallery(response["file"], response["path"]);
                        if (file.previewElement) {
                          return file.previewElement.classList.add("dz-success");
                        }
                    },
                });

            });

            $('[data-fieldtype="instagram"]').each(function(){
                var instagramLogout = new InstagramLogout({el:$(this)});
            });

            $('[data-fieldtype="twitter"]').each(function(){
                var twitterLogout = new TwitterLogout({el:$(this)});
            });

            $('div.preview').each(function(){
                var previewFileView = new PreviewFileView({el:$(this)});
            });

            $('div[data-fieldtype="map"] .map').each(function(){
                var addressMapFieldView = new AddressMapFieldView({el:$(this)});
            });

            $('div[data-fieldtype="panel"]').each(function(){
                var panelFieldView = new PanelFieldView({el:$(this)});
            });

            $('.btn-alert-button').click(function(e){
                e.preventDefault();
                if (confirm($(this).attr('data-alert'))) {
                    window.location = $(this).attr('href');
                }
            });

            $('div.camera').each(function(){
                var cameraView = new CameraView({el:$(this)});
            });

            $('form.edit').submit(function(e){
                if ($(this).hasClass('processing')) {
                    return false;
                }
            });
            $('form.disabled').each(function(){
                $(this).find('input,select,textarea,.btn:not(.btn-link)').addClass('disabled').attr('disabled', 'disabled');
            });

            $('form input.datepicker').each(function(){
                $(this).datepicker({
                    format : 'dd/mm/yyyy',
                    language : $(this).attr('data-language'),
                    autoclose : true
                });
            });

            $('form .colorPicker').each(function(){
                $(this).colorpicker();
            });

            $('form .autocomplete-input').each(function(){
                var field = JSON.parse($(this).attr('field'));
                var defaultId = $(this).val();
                $(this).select2({
                    ajax: {
                        url: window.app.baseUrl + 'ptt/media/autocomplete/',
                        dataType: 'json',
                        type : 'POST',
                        delay: 250,
                        data: function(query){
                            return {field : field, query : query, type : 'search'};
                        },
                        processResults: function (data) {
                            return data.results;
                        },
                        cache: true
                    },
                    minimumInputLength: 3,
                    templateResult : function(data) {
                        return '<div>' + data.text + '</div>';
                    },
                    templateSelection : function(data)  {
                        return '<div>' + data.text + '</div>';
                    },
                    initSelection: function (element, callback) {
                        if (defaultId != -1 && defaultId != '') {
                            $.ajax({
                                url: window.app.baseUrl + 'ptt/media/autocomplete/',
                                dataType: 'json',
                                type : 'POST',
                                data : {type : 'init', id : defaultId, field : field},
                                success : function(data) {
                                    callback(data);
                                }
                            });
                        }
                    }
                })
                .select2('val', [])
                .change(function(e) {
                    $(this).val(e.val);
                })
                ;
            });

            $('div.sortable-list').each(function(){
                var sortableList = new SortableList({el:$(this),
                                                    alert: true});
            });

            $('ul.sortable').each(function(){
                var sortableView = new SortableView({el:$(this),
                                                    alert: true});
            });

            $('ul.multi-sortable').each(function(){
                var sortableView = new SortableView({el:$(this),
                                                    alert: false});
            });

            $('.upload-file-container').each(function(){
                var uploadView = new UploadView({el:$(this)});
            })

            $.each($('ul.table .btn-group a.list-eliminar'), function(element){
                var deletebutton = new DeleteButtonView({el:$(this)});
            });

            $.each($('li.subSections'), function(element){
                var subSections = new SubSections({el:$(this)});
            });

            $.each($('.select-multiple'), function(element){

                var selectMultiple = new SelectMultiple({el:$(this)});
            
            //     var xhr;
            //     var select_state, $select_state;
            //     var select_city, $select_city;

            //     $select_state = el:$(this).selectize({
            //         onChange: function(value) {
            //             if (!value.length) return;
            //             select_city.disable();
            //             select_city.clearOptions();
            //             select_city.load(function(callback) {
            //                 xhr && xhr.abort();
            //                 xhr = $.ajax({
            //                     url: 'https://jsonp.afeld.me/?url=http://api.sba.gov/geodata/primary_city_links_for_state_of/' + value + '.json',
            //                     success: function(results) {
            //                         select_city.enable();
            //                         callback(results);
            //                     },
            //                     error: function() {
            //                         callback();
            //                     }
            //                 })
            //             });
            //         }
            //     });

            //     $select_city = $('.select-multiple-result').selectize({
            //         valueField: 'name',
            //         labelField: 'name',
            //         searchField: ['name']
            //     });

            //     select_city  = $select_city[0].selectize;
            //     select_state = $select_state[0].selectize;

            //     select_city.disable();

           });

             $.each($('.nav-tabs'), function(element){
                var tab = new Tab({el:$(this)});
            
            });


        },
        resize : function()
        {
            this.showSidebar(false);
        },
        toggleSidebar : function()
        {
            this.showSidebar($('.sidebar').hasClass('hidden-xs'));
        },
        showSidebar : function(show)
        {
            if (show) {
                $('.sidebar').removeClass('hidden-xs');
                $('.main').addClass('hidden-xs');
                $('body').addClass('nav-active');
            } else {
                $('.sidebar').addClass('hidden-xs')
                $('.main').removeClass('hidden-xs');
                $('body').removeClass('nav-active');
            }
        }
    };

    var MarkdownView = Backbone.View.extend({
        events : {

        },
        initialize : function(options)
        {
            if(typeof(options) == 'undefined'){
                options = {};
            }
            _.extend(this, _.pick(options));

            var height = !_.isUndefined(this.$el.attr('data-height')) ? this.$el.attr('data-height') : 300;

            var that = this;
            this.$el.markdown({
                autofocus : false,
                savable : false,
                height : height,
                fullscreen : {
                    enable : false
                },
                additionalButtons: [
                    [{
                        name: "groupCustom",
                        data: [{
                            name: "cmdUploadImage",
                            toggle: false,
                            title: "Upload file",
                            icon: "glyphicon glyphicon-upload",
                            callback: function(e){
                                that.callback(e);
                            }
                        }]
                    }]
                ]
            });
        },
        callback : function(e)
        {
            var rendered = Mustache.to_html(uploadFileTemplate, {});
            $('body').append($(rendered).attr('id', 'modal-upload-file'));
            $('#modal-upload-file').modal('show');

            $('#modal-upload-file div.btn-group div').click(function(){
                $('#modal-upload-file div.btn-group div.active').removeClass('active');
                $(this).addClass('active');
            });

            $('#modal-upload-file').on('hidden.bs.modal', function(){
                $('#modal-upload-file').remove();
            });

            $('#modal-upload-file form').submit(function(event){

                var that = this;

                event.preventDefault();

                var files = $(this).find('#file')[0].files;
                if (files.length > 0) {

                    $(this).addClass('loading');

                    var formData = new FormData();
                    for (var i = 0; i < files.length; i++) {
                        var file = files[i];
                        if (!file.type.match('image.*')) {
                            continue;
                        }
                        formData.append('files[]', file, file.name);
                    }

                    var selectedElement = $('#modal-upload-file div.btn-group div.active');
                    var width = selectedElement.attr('data-width');
                    var height = selectedElement.attr('data-height');
                    formData.append('width', width);
                    formData.append('height', height);

                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', window.app.baseUrl + $(that).attr('action'), true);
                    xhr.onload = function (data) {
                        if (xhr.status === 200) {
                            var data = JSON.parse(data.target.response);
                            var text = '![' + $(that).find('#description').val() + '](' + data.resized + ')'
                            e.replaceSelection(text);
                            e.setSelection(e.getSelection(), e.getSelection()+text.length);
                            $('#modal-upload-file').modal('hide');
                        } else {
                            $(that).removeClass('loading');
                            alert('An error occurred! Please, try again!');
                        }
                    };
                    xhr.send(formData);
                } else {
                    alert('You must select a file!');
                }
            });
        }
    });

    var CloneMultipleEntitiesView = Backbone.View.extend({
        events : {
            'click .btn.add' : 'add',
            'click .btn.add-multi' : 'add_multi',
            'click .btn.btn-collapse' : 'collapse',
            'click .btn-sort' : 'sort'
        },
        initialize : function()
        {
            if(typeof(options) == 'undefined'){
                options = {};
            }
            _.extend(this, _.pick(options));
            _.each(this.$el.find('li.entity'), function(entity, index){
                var relatedMultilpleEntitiesDetailView = new RelatedMultilpleEntitiesDetailView({index: index, el:entity});
                // relatedMultilpleEntitiesDetailView.initPlugins();
            });

        },
        add : function(e)
        {
            e.preventDefault();

            var index = this.$el.find('li.entity').length +1;
            var template = this.$el.find('.template').html();
            var relatedMultilpleEntitiesDetailView = new RelatedMultilpleEntitiesDetailView({index: index, template: template});

            this.$el.find('.multi-sortable').append(relatedMultilpleEntitiesDetailView.render().$el);
            relatedMultilpleEntitiesDetailView.initPlugins();
        },
        add_multi : function(e)
        {
            e.preventDefault();

            var selectedEntity = this.$el.find('select[data-selector] option:selected').val();
            if (selectedEntity != -1){
                var index = this.$el.find('li.entity').length +1;
                var template = this.$el.find('.template[data-type="'+ selectedEntity +'"]').html();
                var relatedMultilpleEntitiesDetailView = new RelatedMultilpleEntitiesDetailView({index: index, template: template});

                this.$el.find('.multi-sortable').append(relatedMultilpleEntitiesDetailView.render().$el);
                this.$el.find('select[data-selector]').val(-1);
                relatedMultilpleEntitiesDetailView.initPlugins();
            }

        },
        add_gallery : function(name, path)
        {
            var index = this.$el.find('li.entity').length +1;
            var template = this.$el.find('.template').html();
            var relatedMultilpleEntitiesDetailView = new RelatedMultilpleEntitiesDetailView({index: index, template: template});

            this.$el.find('.multi-sortable').append(relatedMultilpleEntitiesDetailView.render().$el);
            relatedMultilpleEntitiesDetailView.gallery(name, path);

            relatedMultilpleEntitiesDetailView.initPlugins();
        },
        sort : function(e){
            var sortButton = this.$el.find('.btn.btn-sort');
            var selector = this.$el.find('#Project-moduleSelector');
            var collapseButton = this.$el.find('.btn.btn-collapse');
            var addButton = this.$el.find('.btn.add');
            sortButton.toggleClass('btn-danger');

            if (sortButton.hasClass('btn-danger')){
                $('ul.multi-sortable').each(function(){
                    var sortableView = new SortableView({el:$(this),
                                                        alert: false});
                });
                $(selector).prop('disabled', true);
                $(addButton).prop('disabled', true);
                collapseButton.removeClass('btn-danger');
                collapseButton.text(collapseButton.attr("data-collapse"));
                sortButton.text(sortButton.attr("data-edit"));
            } else {
                $(selector).prop('disabled', false);
                $(addButton).prop('disabled', false);
                sortButton.text(sortButton.attr("data-order"));
            }
            _.each(this.$el.find('li.entity'), function(entity){
                var view = $(entity).backboneView();
                if (sortButton.hasClass('btn-danger')){
                    $(entity).attr('draggable', 'true');
                    view.startSort();
                } else {
                    $(entity).removeAttr('draggable');
                    view.stopSort();
                }

            });
        },

        collapse : function(e){
            var collapseButton = this.$el.find('.btn.btn-collapse');
            collapseButton.toggleClass('btn-danger');
            if (collapseButton.hasClass('btn-danger')){
                collapseButton.text(collapseButton.attr("data-expand"));
            } else {
                collapseButton.text(collapseButton.attr("data-collapse"));
            }

            _.each(this.$el.find('li.entity'), function(entity){
                var view = $(entity).backboneView();
                if (collapseButton.hasClass('btn-danger')){
                    view.collapse();
                } else {
                    view.expand();
                }

            });
        }
    });

    var RelatedMultilpleEntitiesDetailView = Backbone.View.extend({
        events: {
            'click .remove' : 'removeView',
            'click .title-closed' : 'expand',
            'click .title-open' : 'collapse',
            'click .triangle-closed' : 'expand',
            'click .triangle-open' : 'collapse'
        },
        tagName: 'li',
        className: 'entity',
        attributes: {
            "data-id": 0
        },
        initialize: function(options){
            _.extend(this, options);
            this.model = {index: this.index};
            this.attributes["data-id"] = this.index;
        },
        render: function(){
            var rendered = Mustache.render(this.template, this.model);

            this.$el.html(rendered);
            return this;
        },
        removeView : function(e)
        {
            e.preventDefault();
            if (confirm('Proceed with deletion?')) {
                this.destroy();
            }
        },
        destroy: function(){
            this.remove();
            this.unbind();
        },
        startSort: function(){
            this.collapse();
            this.$el.find('.triangle').removeClass('triangle-closed');
            this.$el.find('.title').removeClass('title-closed');
            this.$el.find('.handle').removeClass('hidden');
            this.$el.find('.list-eliminar').addClass('hidden');
        },
        stopSort: function(){
            this.$el.find('.handle').addClass('hidden');
            this.$el.find('.triangle').addClass('triangle-closed');
            this.$el.find('.title').addClass('title-closed');
            this.$el.find('.list-eliminar').removeClass('hidden');
        },
        expand : function(e)
        {
            if(!_.isUndefined(e)) e.preventDefault();
            this.$el.find('.triangle').removeClass('triangle-closed');
            this.$el.find('.triangle').addClass('triangle-open');
            this.$el.find('.title').removeClass('title-closed');
            this.$el.find('.title').addClass('title-open');
            this.$el.find('.collapse-body').removeClass('hidden');
        },
        collapse : function(e)
        {
            if(!_.isUndefined(e)) e.preventDefault();
            this.$el.find('.triangle').removeClass('triangle-open');
            this.$el.find('.triangle').addClass('triangle-closed');
            this.$el.find('.title').removeClass('title-open');
            this.$el.find('.title').addClass('title-closed');
            this.$el.find('.collapse-body').addClass('hidden');
        },

        gallery : function(name, path)
        {
            this.$el.find('.gallery-input').val(path);

            var img = this.$el.find('img.preview-image');
            var con = this.$el.find('.image-container');
            var inp = this.$el.find('a.fakeClick');

            img.attr("src", name);
            con.removeClass('hidden');
            inp.addClass('hidden');
        },
        initPlugins: function() {
            _.delay(function(){
                this.initMarkdown();
                this.initPreview();
                this.initLanguageTabs();
            }.bind(this), 300);
        },
        initMarkdown: function() {
            this.$el.find('textarea[data-type="markdown"]').each(function(element){
                var markdownView = new MarkdownView({el:$(this)});
            });
        },
        initPreview: function() {
            this.$el.find('div.upload-file-container').each(function(){
                var uploadView = new UploadView({el:$(this)});
            });
        },
        initLanguageTabs: function() {
            this.$el.find('.nav-tabs').each(function(element){
                var tab = new Tab({el:$(this)});
            });
        },
    });

    var PreviewFileView = Backbone.View.extend({
        events : {
            'click .btn.remove-image' : 'remove'
        },
        initialize : function()
        {
            if(typeof(options) == 'undefined'){
                options = {};
            }
            _.extend(this, _.pick(options));
        },
        remove : function(e)
        {
            e.preventDefault();
            if (confirm('Proceed with file deletion?')) {
                $(e.currentTarget).parent().fadeOut(300, function(){
                    $(this).find('input[type="file"]').remove();
                });
                var href = $(e.currentTarget).parent().find('input[type="hidden"]').attr("data-id");
                $(e.currentTarget).parent().find('input[type="hidden"]').val(href);
                $(e.currentTarget).parent().parent().find('.upload-file-container ').removeClass('hidden');
            }
        }
    });

    var AddressMapFieldView = Backbone.View.extend({
        events : {
            'click .search' : 'search',
            'keydown .address' : 'addressSearch'
        },
        initialize : function()
        {
            if(typeof(options) == 'undefined'){
                options = {};
            }
            _.extend(this, _.pick(options));

            this.initializeMap();

        },
        initializeMap : function()
        {
            this.geocoder = new google.maps.Geocoder();

            var latlng = new google.maps.LatLng(41.386999, 2.170032);
            var mapStyles =
            [
                {
                    featureType: "poi",
                    elementType: "labels",
                    stylers: [
                        {
                            visibility: "off"
                        }
                    ]
                }
            ];
            var mapOptions = {
                zoom: 16,
                center: latlng,
                styles : mapStyles
            }
            this.map = new google.maps.Map(this.$el.find('.map-canvas')[0], mapOptions);

            var that = this;
            google.maps.event.addListener(this.map, 'click', function(e) {
                that.addPin(e.latLng, false);
            });

            this.addDefaultAddress();
        },
        addDefaultAddress : function()
        {
            if (this.$el.find('input.coordinates').val().length > 0) {
                var coordinates = this.$el.find('input.coordinates').val().split('/');
                if (coordinates.length == 2) {
                    var latLng = new google.maps.LatLng(parseFloat(coordinates[0]), parseFloat(coordinates[1]));
                    this.addPin(latLng, true);
                }
            }
        },
        addressSearch: function(e)
        {
            if (typeof(e.which) !== 'undefined') {
                if (e.which == 13) {
                    this.search(e);
                }
            }
        },
        search : function(e)
        {
            e.preventDefault();

            var that = this;

            this.geocoder.geocode({'address': this.$el.find('.address').val()}, function(results, status) {

                if (status == google.maps.GeocoderStatus.OK) {
                    that.addPin(results[0].geometry.location, true);

                } else {
                    alert('There was an error: ' + status);
                }
            });
        },
        addPin : function(location, center)
        {
            if (typeof(this.marker) !== 'undefined') {
                this.marker.setMap(null);
            }
            this.marker = new google.maps.Marker({
                map: this.map,
                position: location
            });
            var coordinatesStr = location.lat() + '/' + location.lng();
            this.$el.find('input.coordinates').val(coordinatesStr);

            if (center) {
                this.map.setCenter(location);
            }
         }
    });

    var CameraView = Backbone.View.extend({
        events : {
            'click a.showWebcam' : 'toggle',
            'click a.snapPicture' : 'snap'
        },
        firstTime : true,
        pictureTaken : false,
        initialize : function()
        {
            if(typeof(options) == 'undefined'){
                options = {};
            }
            _.extend(this, _.pick(options));
        },
        toggle : function(e)
        {
            e.preventDefault();
            this.$el.find('.camera-preview').toggleClass('active');
            if (this.$el.find('.camera-preview').hasClass('active') && this.firstTime) {
                this.startCamera();
            }
        },
        startCamera : function()
        {
            this.firstTime = false;

            var canvas = this.$el.find('canvas')[0],
                context = canvas.getContext('2d'),
                video = this.$el.find('video')[0],
                videoObj = {'video' : true},
                errBack = function(error) {
                    console.log('Video capture error: ' + error.code);
                };

            this.video = video;
            this.canvas = canvas;
            this.context = context;

            // Put video listeners into place
            if(navigator.getUserMedia) { // Standard
                navigator.getUserMedia(videoObj, function(stream) {
                    video.src = stream;
                    video.play();
                }, errBack);
            } else if(navigator.webkitGetUserMedia) { // WebKit-prefixed
                navigator.webkitGetUserMedia(videoObj, function(stream){
                    video.src = window.webkitURL.createObjectURL(stream);
                    video.play();
                }, errBack);
            }
            else if(navigator.mozGetUserMedia) { // Firefox-prefixed
                navigator.mozGetUserMedia(videoObj, function(stream){
                    video.src = window.URL.createObjectURL(stream);
                    video.play();
                }, errBack);
            }
        },
        snap : function(e)
        {
            e.preventDefault();

            this.$el.find('video').toggleClass('hidden');
            this.$el.find('canvas').toggleClass('hidden');

            if (!this.pictureTaken) {
                this.context.drawImage(this.video, 0, 0, 800, 600);

                var that = this;

                $('form.edit').addClass('processing');

                $.ajax({
                    type : 'POST',
                    url : window.app.baseUrl + that.$el.attr('data-url'),
                    data : {
                        canvas : 'yes',
                        imgBase64: that.canvas.toDataURL(),
                        sizes : JSON.parse(this.$el.attr('data-sizes'))
                    }
                }).done(function(data) {
                    that.$el.find('input[type="hidden"]').val(data.filename);
                    $('form.edit').removeClass('processing');
                });

            }
            this.pictureTaken = !this.pictureTaken;
        }
    });

    var PanelFieldView = Backbone.View.extend({
        events : {
            'click button.btn.btn-default' : 'selectDay',
        },
        initialize : function()
        {
            if(typeof(options) == 'undefined'){
                options = {};
            }
            _.extend(this, _.pick(options));

        },
        selectDay : function(e)
        {
            e.preventDefault();
            var index = this.$el.find('div.btn-group button.btn').index(e.toElement);
            $(e.toElement).toggleClass('active');
            var valueArr = this.$el.find('input[type="hidden"]').val().split('');
            if (!_.isUndefined(valueArr[index])) {
                valueArr[index] = (valueArr[index] == '0') ? 1 : 0;
            }
            this.$el.find('input[type="hidden"]').val(valueArr.join(''));
        }
    });

    var UploadView = Backbone.View.extend({
        events : {
            'click a.fakeClick' : 'fakeClick',
            'change input.chooseFile' : 'preview',
            'click a.remove-image' : 'delete'
        },
        initialize : function(options) {
            _.extend(this, _.pick(options));
        },
        fakeClick : function(e){
            e.preventDefault();
            this.$el.find('input.chooseFile').click();
        },
        preview : function(e){
            e.preventDefault();
            var img = this.$el.find('img.preview-image');
            var con = this.$el.find('.image-container');
            var inp = this.$el.find('a.fakeClick');
            var input = e.target;
            if (input.files && input.files[0]) {
                var selected_file = input.files[0];
                var isValid = '';
                if (con.hasClass('svg') && selected_file.type != "image/svg+xml") {
                    isValid = 'Must enter a .svg file';
                } else if (con.hasClass('img-input') && selected_file.type.indexOf("image") == -1) {
                    isValid = 'Must enter a image file';
                }

                if(isValid == ''){

                    var reader = new FileReader();
                    reader.onload = (function(aImg) {
                        return function(e) {
                            img.attr("src", e.target.result);
                        };
                    })(img);
                    reader.readAsDataURL(selected_file);
                    con.removeClass('hidden');
                    inp.addClass('hidden');
                } else {
                    alert(isValid);
                }

            }
        },
        delete : function(e){
            var con = this.$el.find('.image-container');
            var inp = this.$el.find('input.chooseFile');
            var inpF = this.$el.find('a.fakeClick');

            inp.val('');
            con.addClass('hidden');
            inpF.removeClass('hidden')
        }
    });

    var Sortable = Backbone.Model.extend();

    var Sortables = Backbone.Collection.extend({
        model: Sortable,
        url: 'order',
        updateDelay: false,
        save: function(options){
            Backbone.sync('update', this, options);
        },
        update: function(options){

        }
    });

    var SortableList = Backbone.View.extend({
        events : {
            'click .btn-sort-list' : 'sort',
        }, 
        initialize: function(options) {
            this.sortables = new Sortables();
        },
        sort: function(event){
            var sortButton = this.$el.find('.btn.btn-sort');
            if(!sortButton.hasClass('.btn-danger')){
                // START SORT
                $('.btn-create-list').addClass('hidden');
                $('.btn-csv').addClass('hidden');
                _.each(this.$el.find('li.list'), function(entity){
                    var view = $(entity).backboneView();
                    $('.handle').removeClass('hidden');
                    $('.actions').addClass('hidden');
                });
            } else {
                // END SORT
                $('.btn-create-list').removeClass('hidden');
                $('.btn-csv').removeClass('hidden');
               _.each(this.$el.find('li.list'), function(entity){
                    var view = $(entity).backboneView();
                    $('.handle').addClass('hidden');
                    $('.actions').removeClass('hidden');
                });

               var orderQuery = _.map(this.$el.find('li[draggable]'), function(handle, order) {
                    return {'id': $(handle).attr('data-id'), '_order':order}
                });

               this.sortables.set(orderQuery);
               this.sortables.save();

               console.log(orderQuery);
            }
            sortButton.toggleClass('.btn-danger');
        }
    });

    var SortableView = Backbone.View.extend({
        initialize : function(options) {
            this.$el.sortable({
                handle: '.handle',
                items: ':not(.head)'
            })
            .bind('sortupdate', this.sortupdate.bind(this));
            _.extend(this, _.pick(options));
        },
        sortupdate: function(event) {
            _.map(this.$el.find('li[draggable]'), function(handle, order) {
                $(handle).find('.field-order').val(order);
            });
        }
    });

    var DeleteButtonView = Backbone.View.extend({
        events: {
            'click': 'deleteView'
        },
        deleteView: function(event) {
            event.preventDefault();
            if (confirm($(event.target).attr('data-alert'))) {
                window.location.href = $(event.target).attr('href');
            }
        }
    });

    var SubSections = Backbone.View.extend({
        events:{
            'click > a': 'toogle'
        },
        initialize: function() {
            var hasTrue = _.some(_.map(this.$el.find('ul li a'), function(element){
                return $(element).hasClass('selected');
            }));
            if (hasTrue) this.$el.addClass('selected');
        },
        toogle: function(event) {
            event.preventDefault();
            this.$el.toggleClass('selected');
        }
    });

    function replaceAll(find, replace, str) {
      return str.replace(new RegExp(find, 'g'), replace);
    }

    var SelectMultiple = Backbone.View.extend({
        events:{
            'change select.select-multiple-model': 'change'
        },
        initialize: function(options) {
            _.extend(this, _.pick(options));
        },
        change: function(event) {
            event.preventDefault();
            var model = event.target;
            var value = model.options[model.selectedIndex].value;
            var text = model.options[model.selectedIndex].text;

            var limit = model.getAttribute('limit');

            var desti = this.$el.find('.select-multiple-result');
            var empty = $('<option>').attr('value', '').text('-- Escoje un ' + text + ' --');
            desti.html(empty);
            $.ajax({
                url: window.app.baseUrl + 'admin/' + value.toLowerCase() + '/last?limit=' + limit,
                type : 'GET',
                // data : $.param({limit : model.getAttribute('limit')}),
                success : function(data) {
                    for(var i=0;i<data.length;i++){
                        opt = document.createElement("option");
                        opt.value = data[i].id;
                        opt.text= data[i].title;
                        desti.append(opt);
                    }
                },
                error : function(data) {
                    console.log(data);
                }
            });
        }
    });

    function replaceAll(find, replace, str) {
      return str.replace(new RegExp(find, 'g'), replace);
    }

    var Tab = Backbone.View.extend({
        events : {
            'click li a' : 'changeTab'
        },
        changeTab : function(event){
            event.preventDefault();
            var TabName = $(event.target).attr('href');
            $('.nav-tabs li').removeClass('active');
            $('.tab-pane').removeClass('active');
            $('.' + TabName).addClass('active');
        },
    });

    var InstagramLogout = Backbone.View.extend({
        events : {
            'click a.btn-instagram' : 'desvincular'
        },
        desvincular : function(event){
            event.preventDefault();

            this.$el.find("input").val('');
            this.$el.find("div.logout").remove();
            this.$el.find("div.login").attr("style", "");
        },
    });

    var TwitterLogout = Backbone.View.extend({
        events : {
            'click a.btn-twitter' : 'desvincular'
        },
        desvincular : function(event){
            event.preventDefault();

            this.$el.find("input").val('');
            this.$el.find("div.logout").remove();
            this.$el.find("div.login").attr("style", "");
        },
    });

    return window.app;
});