/**
 * Shop block - In CMS on property change update styles if possible
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
	// On shop block property change update UI
	//
	$.refresh.on('update/shop', function (event, info) {
		switch (info.propertyName) {
			case "next_page":
				info.target.find('.pagination .next').text(info.propertyValue);
				break;
			case "previous_page":
				info.target.find('.pagination .prev').text(info.propertyValue);
				break;
			case "buy_now":
				info.target.find('.overlay-text span').text(info.propertyValue);
				break;
			case "categories_per_row":
			case "vendors_per_row":
			case "products_per_page":
			case "products_per_row":
				return false;
		}
	});
	$.refresh.on('update/shop-product', function (event, info) {
		switch (info.propertyName) {
			case "show_title":
				// Show or hide title
				if (info.propertyValue) {
					info.target.find('.page-title').removeClass('hidden');
				} else {
					info.target.find('.page-title').addClass('hidden');
				}
				break;
		}
	});
	
}));