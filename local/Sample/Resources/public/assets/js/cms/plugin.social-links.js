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
	$.refresh.on('update/social-links', function (event, info) {
		switch (info.propertyName) {
			case "align":
				var value  = info.propertyValue,
					values = info.propertyValueList,
					i      = 0,
					ii     = values.length,
					node   = info.target.find('.socials-inner');
				
				for (; i<ii; i++) {
					node.toggleClass('socials-align-' + values[i].id, value == values[i].id);
				}
				
				break;
		}
	});
	
}));