'use strict';var _ = require('underscore'),    $ = require('jquery'),    markdown = require('markdown'),    Twig = require('twig'),    twig = Twig.twig;//---Functions---//Twig.extendFunction("path", function(value, params) {    return _.pathForTwig(value,params);});Twig.extendFunction("asset", function(value) {    return value;});//---Filters---//Twig.extendFilter("md2html", function(value) {    return markdown.markdown.toHTML(value);    return value;});Twig.extendFilter("raw", function(value) {    return value;});Twig.extendFilter("trans", function(value) {    var trans = conf.trans[value];    if(trans == undefined && value){        console.log('FALTA TRADUCCIONS EN JAVASCRIPT: ' + value + ' ' + trans);        return value;    }    return trans;});Twig.extendFilter("getObject", function(object) {    console.log("FALTA FILTRO GETOBJECT",object)    return object;});Twig.extendFilter("extend", function(value, params) {    var response;    if (_.isUndefined(value)) {        response = _.first(params);    } else {        response = _.extend(_.first(params), value);    }    removeObjects(response);    return response;});Twig.extendFilter("process", function(value, params) {    return _.mapObject(value, function (val) {        return params[0][val];    });});var removeObjects = function(data) {    delete data._keys;    delete data.__proto__;    _.each(data, function(val) {        if (_.isObject(val) || _.isArray(val)) removeObjects(val);    });    return data;}_.mixin({    pathForTwig : function(value,params){        var url = '';        value = value.toLowerCase();        switch (value) {            case 'home':            case 'register':            case 'forgot_password':            case 'profile':            case 'cookies': url = window.conf.baseURL + value.replace('_','-'); break;            case 'share': url = window.conf.baseURL + 'share-your-experience'; break;            case 'profile_login_check': url = window.conf.baseURL + 'profile/login_check'; break;            case 'upload_success': url = window.conf.baseURL + 'share-your-experience/success'; break;            case 'user': url = window.conf.baseURL + 'user/' + params.id + '/' + params.slug;break;            case 'createcomment': url = window.conf.baseURL + 'create-comment'; break;            default:                console.log("FALTA PATH DE LA URL: " + value);                return false;        }        return url;    }});module.exports = /*@ngInject*/    function twigService( /* inject dependencies here, i.e. : $rootScope */ ) {        //utilities        twig({            id: 'FrontendBundle:Macros:utilities.html.twig',            data: require('../../../../src/FrontendBundle/Resources/views/Macros/utilities.html.twig')        });        //sprites        twig({            id: 'FrontendBundle:Default:sprites.html.twig',            data: require('../../../../src/FrontendBundle/Resources/views/Default/sprites.html.twig')        });        //form        twig({            id: 'FrontendBundle:Macros:form.html.twig',            data: require('../../../../src/FrontendBundle/Resources/views/Macros/form.html.twig')        });        return {            render: function(template, data) {                data = _.isUndefined(data) ? {} : data;                data.app = {                    request:                    {                        locale: conf.lang                    }                };                var temp = twig({                    allowInlineIncludes: true,                    data: template                });                return temp.render(data);            }        };    };