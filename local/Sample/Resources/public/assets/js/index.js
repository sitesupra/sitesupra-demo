require.config({
	shim: {
		// For photoswipe load default skin
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

require([
	'app/app',
	'app/refresh',
	'plugins/helpers/responsive',
	
	'lib/jquery.sticky.min',
	'lib/jquery.parallax.min',
	'lib/jquery.scrollto.min',
	'lib/jquery.localscroll.min',
	'plugins/helpers/responsive'
], function () {
	'use strict';
	
	// Touch device
	if ('ontouchstart' in document.documentElement) {
		$('html').addClass('touch');
	}
	
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
	
	// Load and initialize modules for elements which have
	// data-require="..." and data-attach="..." attributes
	function ready () {
		$.app.parse($('body'));
	}
	
	// Sticky header
	$('header.header').eq(0).sticky({
		'disabled': $.responsive.size < $.responsive.md
	});
	
	$.responsive.on('resize', function () {
		var state = $.responsive.size < $.responsive.md ? 'disable' : 'enable';
		$('header.header').eq(0).sticky(state);
	});
	
	// Background parallax
	$('section, header.header, footer.footer').each(function () {
		var node = $(this),
			background_attach = node.css('backgroundAttachment'),
			background_position_x = null;
		
		if (background_attach === 'fixed') {
			background_position_x = node.css('backgroundPositionX');
			node.parallax(background_position_x);
		}
	});
	
	// Scrollto
	$.localScroll({
		'duration': 700,
		'offset': -$('header.header').height(),
		'lazy': true,
		// Except links with target="_blank"
		'filter': ':not([target="_blank"])'
	});
});
