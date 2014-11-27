YUI.add('supra.ua-normalize', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//UA for IE11
	var tmp;
	if (tmp = Y.UA.userAgent.match(/Trident\/.*rv:(\d+\.\d+)/)) {
		Y.UA.ie = parseFloat(tmp[1]);
	}
	
	//Set browser UA classname
	var html = Y.one('html');
	
	for(var browser in Y.UA) {
		if (Y.UA[browser] && browser != 'os') {
			html.addClass(browser);
			
			if (browser == 'ie') {
				html.addClass(browser + '-' + Y.UA[browser]);
			}
		}
	}
	
	//Touch?
	Y.UA.touch = !!('ontouchstart' in document.documentElement);
	
	//Prevent content scrolling on iPad
	if (Y.UA.touch) {
		// Touch device
		Y.Node(document).on('touchmove', function (e) {
			e.preventDefault();
		});
	}
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: []});