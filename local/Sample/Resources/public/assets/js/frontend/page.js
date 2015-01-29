define([
	'jquery',
	'frontend/util/responsive',
	
	'lib/jquery.sticky.min',
	'lib/jquery.parallax.min',
	'lib/jquery.scrollto.min',
	'lib/jquery.localscroll.min'
], function ($) {
	var header = $('header.header').eq(0);
	
	if (!isCMSMode) {
		/*
		 * Sticky header
		 * Header should always stick to the top of the view on desktop and
		 * tablet devices, but not on mobile
		 */
		header.sticky({
			'disabled': $.responsive.size < $.responsive.md
		}).sticky('update');
		
		$.responsive.on('resize', function () {
			// Enable / disable sticky header based on resolution
			var state = $.responsive.size < $.responsive.md ? 'disable' : 'enable';
			header.eq(0).sticky(state);
		});
	}
	
	
	/*
	 * Enable parallax effect for fixed background images
	 */
	$('section, header.header, footer.footer').each(function () {
		var node = $(this),
			background_attach = node.css('backgroundAttachment'),
			background_position_x = null;
		
		if (background_attach === 'fixed') {
			background_position_x = node.css('backgroundPositionX');
			node.parallax(background_position_x);
		}
	});
	
	
	/*
	 * When user clicks on hash link scroll to it smoothly instead of
	 * jumping to the place
	 */
	
	function getHeaderOffset () {
		if ($.responsive.size >= $.responsive.md) {
			// Only for tablet and desktop; on mobile header is not 'sticky'
			// and should not affect scroll position
			return -header.height();
		} else {
			return 0;
		}
	}
	
	$.localScroll({
		'duration': 700,
		'offset': getHeaderOffset(),
		'lazy': true,
		// Except links which should be opened in new tab
		'filter': ':not([target="_blank"])'
	});
	
});
