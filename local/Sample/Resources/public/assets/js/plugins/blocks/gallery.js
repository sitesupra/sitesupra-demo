/**
 * Gallery block
 * @version 1.0.0
 */
"use strict";

var CMS_MODE = $('html').hasClass('supra-cms');

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        if (CMS_MODE) {
        	// Don't do anything
        	define([], function () {});
        } else {
        	// Lightbox
	        define(['jquery', 'lib/jquery.fancybox-1.3.4'], function ($) {
	            return factory($);
	        });
        }
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	//
	// Fancy box (lightbox)
	//
	$('a').filter('[rel="lightbox-iframe"]').fancybox({
		'type': 'iframe',
		'width': '90%',
		'height': '75%'
	});
	$('a').filter('[rel^="lightbox"]').not('[rel="lightbox-iframe"]').fancybox({
		
	});
	
}));