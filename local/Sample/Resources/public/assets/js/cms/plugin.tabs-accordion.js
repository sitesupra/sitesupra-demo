/**
 * Tabs/Accordion block - In CMS on design change reload block content
 * 
 * @version 1.0.1
 */
"use strict";

define(['jquery', 'app/refresh'], function ($) {
	
    // When page is resized in CMS, update block styles
	$.refresh.on('resize/tabs-accordion', function (event, info) {
		
		// Update tabs style by calling jQuery plugin (plugins/blocks/tabs.js)
		info.target.tabs('update');
		
	});
	
});
