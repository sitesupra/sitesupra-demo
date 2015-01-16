/**
 * Block - In CMS on block_title property change update block title and style
 * 
 * @version 1.0.1
 */
"use strict";

define(['jquery', 'app/refresh'], function ($) {
	
    // Callback will be called when one of the block properties changes
	$.refresh.on('update', function (event, info) {
        /*
         * info:
         *   target - jQuery element which has "data-refresh-event" attribute
         *   propertyValue - new property value
         *   propertyValueList - if property is a list, then all list values
         */
        
        // Find .block element
        var nodeBlock = info.target.closest('.block').add(info.target.find('.block'));
        
		switch (info.propertyName) {
            case "layout":          // text, logotype
            case "design":          // text
            case "style":           // gallery, menu, tabs/accordion
            case "columns":         // gallery
            
                // By returning false we stop default behaviour and 
                // that will instruct CMS to reload block content
                return false;
            
			case "title":
				// Update block title
				var nodeTitle = nodeBlock.find('.block-title').add(nodeBlock.siblings('.block-title'));
				
				// Show / hide heading and update text
                nodeTitle
                    .toggleClass('hidden', !info.propertyValue)
                    .text(info.propertyValue);
                
                // Swap "no-heading" and "has-heading" classnames on the block
				/*
                nodeBlock
                    .toggleClass('no-heading', !info.propertyValue)
                    .toggleClass('has-heading', info.propertyValue);
                */
				
				break;
            case "align":           // menu, social links, logotype
                // Align property is a list with values ['left', 'center', 'right']
				var value  = info.propertyValue,
					values = info.propertyValueList,
					i      = 0,
					ii     = values.length,
                    active,
                    className;
				
				for (; i<ii; i++) {
                    active = !!(value == values[i].id);
                    className = 'block-align-' + values[i].id;
                    
					nodeBlock.toggleClass(className, active);
				}
				
				break;
		}
	});
		
});
