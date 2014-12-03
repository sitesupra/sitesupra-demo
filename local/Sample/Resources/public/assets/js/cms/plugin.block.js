/**
 * Block - In CMS on block_title property change update block title and style
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
	
	$.refresh.on('update', function (event, info) {
		switch (info.propertyName) {
			case "form_title":
			case "block_title":
			case "menuLabel":
				// Update block title
				var node       = null,
					node_block = info.target.closest('.block');
				
				if (!node_block.size()) {
					node_block = info.target.find('.block');
				}
				
				node = node_block.find('.block-title');
				if (!node.size()) {
					node = node_block.prevAll('.block-title');
				}
				
				if (info.propertyValue) {
					// Show heading, update text and swap "no-heading" and "has-heading" classnames
					node.removeClass('hidden').find('div').text(info.propertyValue);
					node.next().removeClass('no-heading').addClass('has-heading');
				} else {
					// Hide heading and swap "no-heading" and "has-heading" classnames
					node.addClass('hidden');
					node.next().removeClass('has-heading').addClass('no-heading');
				}
				break;
		}
	});
		
}));