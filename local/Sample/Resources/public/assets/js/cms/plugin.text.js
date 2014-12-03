/**
 * Text and menu block - In CMS on property change update page styles if possible
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
	// On text and menu block property change update UI and on layout change reload content
	//
	function updateUI (event, info) {
		switch (info.propertyName) {
			case "layout":
				//This will tell CMS to reload block content
				return false;
			case "design":
				//This will tell CMS to reload block content
				return false;
		}
	};
	
	$.refresh.on('update/text', updateUI);
	$.refresh.on('update/menu', updateUI);
	
}));