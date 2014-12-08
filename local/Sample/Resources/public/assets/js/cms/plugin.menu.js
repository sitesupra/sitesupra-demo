/**
 * Menu blocks - In CMS on property change update page styles if possible
 * 
 * @version 1.0.0
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
	
	/*
     * On property change update texts
     */
	$.refresh.on('update/menu', function (event, info) {
		switch (info.propertyName) {
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
