/**
 * Menu blocks - In CMS on property change update page styles if possible
 * 
 * @version 1.0.0
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
	// On style property change reload content, on other update content
	//
	$.refresh.on('update/menu', function (event, info) {
		switch (info.propertyName) {
			case "style":
				//This will tell CMS to reload block content
				return false;
			case "align":
				var value  = info.propertyValue,
					values = info.propertyValueList,
					i      = 0,
					ii     = values.length,
					node   = info.target;
				
				for (; i<ii; i++) {
					node.toggleClass('page-navigation-align-' + values[i].id, value == values[i].id);
				}
				
				break;
			case "labelPrevious":
				if (info.target.hasClass('page-navigation-nextprev')) {
					info.target.find('a.prev').text(info.propertyValue);
				}
				break;
			case "labelNext":
				if (info.target.hasClass('page-navigation-nextprev')) {
					info.target.find('a.next').text(info.propertyValue);
				}
				break;
			case "menuLabel":
				// Sidebar menu label while displayed as drop-down
				info.target.find('.select-item span').text(info.propertyValue);
				break;
		}
	});
	
}));