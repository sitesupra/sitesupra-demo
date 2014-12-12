/**
 * Menu blocks - In CMS on property change update page styles if possible
 * 
 * @version 1.0.0
 */
"use strict";

define(['jquery', 'app/refresh'], function ($) {
	
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
	
});
