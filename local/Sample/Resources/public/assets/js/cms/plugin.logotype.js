/**
 * Logo block - In CMS on property change update page styles if possible
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
	// On logo block property change reload content
	//
	$.refresh.on('update/logotype', function (event, info) {
		switch (info.propertyName) {
			case "layout":
				//This will tell CMS to reload block content
				return false;
			case "align":
				var value  = info.propertyValue,
					values = info.propertyValueList,
					i      = 0,
					ii     = values.length,
					node   = info.target;
				
				for (; i<ii; i++) {
					node.toggleClass('align-' + values[i].id, value == values[i].id);
				}
				
				break;
		}
	});
	
}));