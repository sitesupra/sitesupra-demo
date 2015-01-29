/**
 * Responsive resize
 * 
 * @version 1.0.1
 */
define(['jquery', 'frontend/util/debounce', 'lib/matchmedia-polyfill'], function ($, debounce) {
	'use strict';
    
	var responsive = $.responsive = $.extend($({}), {
        
        /**
         * Size names
         */
        'xs': 1,
        'sm': 2,
        'md': 3,
        'lg': 4,
        'xl': 5,
        
		/**
		 * Last known size
		 */
		'size': 5,
		
		/**
		 * Handle browser resize
		 * 
		 * @protected
		 */
		'handleResize': function () {
			var width = 0,
				size = null;
			
			// Media queries is most reliable way of detection
			// it's on par with CSS
			
			if (window.matchMedia('(min-width: 80em)').matches) {
				size = $.responsive.xl;
			} else if (window.matchMedia('(min-width: 64em) and (max-width: 79.999em)').matches) {
				size = $.responsive.lg;
			} else if (window.matchMedia('(min-width: 48em) and (max-width: 63.999em)').matches) {
				size = $.responsive.md;
			} else if (window.matchMedia('(min-width: 35.5em) and (max-width: 47.999em)').matches) {
				size = $.responsive.sm;
			} else if (window.matchMedia('(max-width: 34.499em)').matches) {
				size = $.responsive.xs;
			}
            
			if (size != $.responsive.size) {
				$.responsive.size = size;
				$.responsive.trigger('resize', size);
			}
		}
	});
	
	$(window).on('resize', debounce(responsive.handleResize, responsive));
	responsive.handleResize();
	
	// requirejs
	return $.responsive;
    
});
