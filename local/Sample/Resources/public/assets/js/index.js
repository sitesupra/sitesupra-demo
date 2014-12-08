require([
	'app/app',
	'app/refresh',
	'plugins/helpers/responsive'
], function () {
	'use strict';
	
	// Touch device
	if ('ontouchstart' in document.documentElement) {
		$('html').addClass('touch');
	}
	
	// It's important to bind to resize before any other plugin, because
	// other plugins may depend on content size (eq. Slideshow)
	$.responsive.images('img.responsive');
	
	// Because of InlineMediaFilter we need to add data-require for videos
	$('div.video[data-attach="$.fn.video"]').not('[data-require]').attr('data-require', 'plugins/helpers/resize');
	
	// Initialize
	if ($('html').hasClass('supra-cms')) {
		// Load CMS scripts
		require(['cms/index'], ready);
	} else {
		// Initialize immediatelly
		ready();
	}
	
	function ready () {
		$.app.parse($('body'));
	}
});
