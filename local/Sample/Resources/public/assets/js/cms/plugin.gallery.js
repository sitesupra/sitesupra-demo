/**
 * Gallery block
 * carousel.js or promo.js handles everything 
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
	
	$.refresh.on('update/gallery', function (event, info) {
		if (info.propertyName === 'style' || info.propertyName === 'columns' || info.propertyName === 'items_per_row') {
			//Style HTMLs are too different; instruct CMS to reload HTML
			//by stoping the event
			event.originalEvent.preventDefault();
			return false;
		}
	});
	
}));