define([
	'jquery',
	
	'lib/jquery.sticky.min',
	'lib/jquery.parallax.min',
	'lib/jquery.scrollto.min',
	'lib/jquery.localscroll.min',
	
	'lib/matchmedia-polyfill'
], function ($) {
	// Uses same media query as in CSS
	var isMobile = !window.matchMedia('(min-width: 48em)').matches;
	
	
	/*
	* Sticky header
	* Header should always stick to the top of the view on desktop and
	* tablet devices, but not on mobile
	* In CMS sticky header it may cause usability issues, so we don't
	* enable it either
	*/
	if (!isCMSMode && !isMobile) {
		$('header.header').sticky();
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
		if (!isCMSMode && !isMobile) {
			// Only for tablet and desktop; on mobile header is not 'sticky'
			// and should not affect scroll position
			return -$('header.header').height();
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
