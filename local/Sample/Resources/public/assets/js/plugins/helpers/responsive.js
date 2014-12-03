/**
 * Responsive resize
 * 
 * @version 1.0.1
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'plugins/helpers/throttle'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	var SIZE_DESKTOP = 1,
		SIZE_TABLET = 2,
		SIZE_MOBILE_LANDSCAPE = 3,
		SIZE_MOBILE_PORTRAIT = 4;
	
	var responsive = $.responsive = $.extend($({}), {
		/**
		 * Last known size
		 */
		'size': SIZE_DESKTOP,
		
		/**
		 * Handle browser resize
		 * 
		 * @private
		 */
		'handleResize': function () {
			var width = 0,
				size = null;
			
			if (window.matchMedia) {
				// Media queries is most reliable way of detection
				// it's on par with CSS
				
				if (window.matchMedia('(min-width: 1024px)').matches) {
					size = SIZE_DESKTOP;
				} else if (window.matchMedia('(min-width: 768px) and (max-width: 1023px)').matches) {
					size = SIZE_TABLET;
				} else if (window.matchMedia('(min-width: 481px) and (max-width: 767px)').matches) {
					size = SIZE_MOBILE_LANDSCAPE;
				} else if (window.matchMedia('(max-width: 480px)').matches) {
					size = SIZE_MOBILE_PORTRAIT;
				}
			} else {
				// jQuery report window + scrollbar width, which is consistent
				// width media queries
				width = $(document).width();
				
				if (width >= 1024) {
					size = SIZE_DESKTOP;
				} else if (width >= 768 && width <= 1023) {
					size = SIZE_TABLET;
				} else if (width >= 481 && width <= 767) {
					size = SIZE_MOBILE_LANDSCAPE;
				} else if (width <= 480) {
					size = SIZE_MOBILE_PORTRAIT;
				}
			}
			
			if (size != $.responsive.size) {
				$.responsive.size = size;
				$.responsive.trigger('resize', size);
			}
		},
		
		'SIZE_DESKTOP': SIZE_DESKTOP,
		'SIZE_TABLET': SIZE_TABLET,
		'SIZE_MOBILE_LANDSCAPE': SIZE_MOBILE_LANDSCAPE,
		'SIZE_MOBILE_PORTRAIT': SIZE_MOBILE_PORTRAIT
	});
	
	$(window).on('resize', $.throttle(responsive.handleResize, responsive));
	responsive.handleResize();
	
	
	/**
	 * Responsive images
	 * 
	 * @param {String} selector CSS selector for matching image nodes
	 */
	$.responsive.images = function (selector) {
		
		var update = function () {
			var responsive = $.responsive,
				size = responsive.size,
				images = $(selector),
				image = null,
				i = 0,
				ii = images.length,
				
				src_val = null,
				height_attr = null;
			
			for (; i<ii; i++) {
				image = images.eq(i);
				
				if (size == responsive.SIZE_DESKTOP) {
					src_val = image.data('src-desktop');
					height_attr = 'height-desktop';
				} else if (size == responsive.SIZE_TABLET) {
					src_val = image.data('src-tablet');
					height_attr = 'height-tablet';
				} else if (size == responsive.SIZE_MOBILE_LANDSCAPE) {
					src_val = image.data('src-mobile');
					height_attr = 'height-mobile';
				} else if (size == responsive.SIZE_MOBILE_PORTRAIT) {
					src_val = image.data('src-mobile-portrait');
					height_attr = 'height-mobile-portrait';
				}
				
				if (!src_val && (size == responsive.SIZE_MOBILE_LANDSCAPE || size == responsive.SIZE_MOBILE_PORTRAIT || size == responsive.SIZE_TABLET)) {
					src_val = image.data('src-mobile-all');
					height_attr = 'height-mobile-all';
				}
				
				if (src_val) {
					image.attr('src', src_val);
					image.attr('height', image.data(height_attr));
					//image.css('height', image.data(height_attr) + 'px');
				}
			
			}
		};
		
		$.responsive.on('resize', update);
		update();
		
	};
	
	// requirejs
	return $.responsive;
	
}));