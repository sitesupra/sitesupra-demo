/**
 * Block - In CMS on 'title' block property change update preview
 * 
 * @version 1.1.0
 */
define(['jquery', 'cms/refresh'], function ($) {
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
		 * preview can't be updated without reloading block content and CMS
		 * will reload block content
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
