/**
 * Tabs/Accordion block - In CMS on design change reload block content
 * 
 * @version 1.0.1
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'refresh/refresh'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	//
	// On text block property change update UIlayout change reload content
	//
	$.refresh.on('update/tabs-accordion', function (event, info) {
		
		switch (info.propertyName) {
			case "design":
				//This will tell CMS to reload block content
				return false;
		}
		
	});
	
	$.refresh.on('resize/tabs-accordion', function (event, info) {
		
		// Update tabs style
		info.target.tabs('update');
		
	});
	
}));