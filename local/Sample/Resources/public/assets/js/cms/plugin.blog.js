/**
 * Blog list block - In CMS on property change update styles if possible
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
	// On blog 'style' property change force CMS to reload content
	//
	function updateUI (event, info) {
		switch (info.propertyName) {
			case "next_page":
				info.target.closest('.block').find('.pagination .next').text(info.propertyValue);
				break;
			case "previous_page":
				info.target.closest('.block').find('.pagination .prev').text(info.propertyValue);
				break;
			case "style":
			case "items_per_row":
			case "posts_per_page":
			case "blog_page":
				//This will tell CMS to reload block content
				return false;
		}
	};
	
	$.refresh.on('update/blog', updateUI);
	
}));