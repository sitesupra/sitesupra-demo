/**
 * Social links blocks - In CMS on property change reload block content
 * 
 * @version 1.0.0
 */
"use strict";

define(['jquery', 'app/refresh'], function ($) {
    
	/*
     * On any property change reload
     */
	$.refresh.on('update/social-links', function (event, info) {
        return false;
	});
	
});
