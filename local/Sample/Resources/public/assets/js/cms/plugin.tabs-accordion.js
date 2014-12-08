/**
 * Tabs/Accordion block - In CMS on design change reload block content
 * 
 * @version 1.0.1
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'app/refresh'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all dependencies are already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
    // When page is resized in CMS, update block styles
	$.refresh.on('resize/tabs-accordion', function (event, info) {
		
		// Update tabs style by calling jQuery plugin (plugins/blocks/tabs.js)
		info.target.tabs('update');
		
	});
	
}));
