/*global requirejs require */
requirejs.config({
	'urlArgs': (typeof HOST_KEY == 'string' ? HOST_KEY : ''),
	'paths': {
		'jquery': ['//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min', 'lib/jquery-1.10.2.min']
	},
	'shim': {
		'lib/jquery.sticky.min': {
			'deps': ['jquery']
		},
		'lib/jquery.parallax.min': {
			'deps': ['jquery']
		},
		'lib/jquery.scrollto.min': {
			'deps': ['jquery']
		}
	},
	'deps': ['jquery']
});

require([
	'app/app', 'refresh/refresh',
	'plugins/helpers/responsive'
], function () {
	'use strict';
	
	var htmlElement = $('html');
	
	// CMS mode
	var CMS_MODE = htmlElement.hasClass('supra-cms');
	
	// Touch mode
	if ('ontouchstart' in document.documentElement) {
		htmlElement.addClass('touch');
	}
	
	// It's important to bind to resize before any other plugin, because
	// other plugins may depend on content size (eq. Slideshow)
	$.responsive.images('img.responsive');
	
	// Because of InlineMediaFilter we need to add data-require for videos
	$('div.video[data-attach="$.fn.video"]').not('[data-require]').attr('data-require', 'plugins/helpers/resize');
	
	// Initialize
	if (CMS_MODE) {
		// Load CMS scripts
		require(['cms/index'], function () {
			// When all plugins are loaded initialize
			$.app.parse($('body'));
		});
	} else {
		// Initialize immediatelly
		$.app.parse($('body'));
	}	
});

//
document.getElementsByTagName('iframe')