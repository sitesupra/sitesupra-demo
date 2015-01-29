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
