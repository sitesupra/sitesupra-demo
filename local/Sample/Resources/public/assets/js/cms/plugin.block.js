/**
 * Block - In CMS on block_title property change update block title and style
 * 
 * @version 1.0.1
 */
define(['jquery', 'app/refresh'], function ($) {
	'use strict';
	
    // Callback will be called when one of the block properties changes
	$.refresh.on('update', function (event, info) {
        /*
         * Info:
         *   target - jQuery element which has "data-refresh-event" attribute
		 *   propertyName - property name which changed
         *   propertyValue - new property value
         *   propertyValueList - if property is a list, then all list values
		 *
		 * If this function will return false, then it's assumed that block
		 * preview can't be updated without reloading block content and in CMS
		 * block content reload will be triggered
         */
        
        // Find .block element
        var nodeBlock = info.target.closest('.block').add(info.target.find('.block'));
        
		switch (info.propertyName) {
			case "title":
				// Update block title
				var nodeTitle = nodeBlock.find('.block-title').add(nodeBlock.siblings('.block-title'));
				
				// Show / hide heading and update text
                nodeTitle
                    .toggleClass('hidden', !info.propertyValue)
                    .text(info.propertyValue);
                
				break;
		}
	});
		
});
