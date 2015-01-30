/**
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */


/*
 * Detect if page is loaded inside CMS
 */
var isCMSMode = (document.documentElement.className.indexOf('supra-cms') !== -1);


/*
 * Configure vendor scripts
 */
require.config({
	shim: {
		// For PhotoSwipe load default skin
		'lib/photoswipe.min': {
			'deps': ['lib/photoswipe-ui-default.min'],
			'exports': 'PhotoSwipe'
		},
		'lib/photoswipe-ui-default.min': {
			'exports': 'PhotoSwipeUI_Default'
		},
		// Polyfill
		'lib/matchmedia-polyfill': {
			'exports': 'matchMedia'
		}
	}
});


/*
 * Start application
 */
require([
	'frontend/app',
	'frontend/page'
], function () {
	'use strict';
	
	// Because of InlineMediaFilter we need to add data-require for videos,
	// otherwise script won't be loaded and plugin will not be initialized on
	// those elements
	$('div.video[data-attach="$.fn.video"]').not('[data-require]').attr('data-require', 'frontend/util/resize');
	
	// Load and initialize modules for elements which have
	// data-require="..." and data-attach="..." attributes
	$.app.parse($('body'));
});


/*
 * Load additional scripts for CMS which enhances UX
 */
if (isCMSMode) {
	
	require([
		'cms/plugin.block-title'
	], function () {
		
	});
	
}
