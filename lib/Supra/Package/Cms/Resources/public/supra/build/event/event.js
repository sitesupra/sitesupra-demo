/**
 * Adds event helper function to YUI Event class
 */
YUI.add('supra.event', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var Event = Y.Event;
	
	/**
	 * Returns character number from keyboard event
	 * If key doesn't have character number, then returns key code
	 * 
	 * Should be used with 'keypress' event; 'keydown' and 'keyup' events are
	 * inconsistent across browsers
	 * 
	 * Known issues:
	 *  - for non-english languages Firefox on keydown event always returns 0
	 * 
	 * @param {Object} e Event facade object
	 * @returns {Number} Character code
	 */
	Event.charCodeFromEvent = function (e) {
		var event    = e._event,
			keyCode  = null,
			match;
		
		if (typeof event.keyIdentifier === 'string' && (match = event.keyIdentifier.match(/U\+([A-F0-9]+)/i))) {
			// Chrome (keydown event)
			keyCode = parseInt(match[1], 16); // convert from hex
		} else if (typeof event.char === 'string') {
			// IE (keydown event)
			keyCode = event.char.charCodeAt(0); // get first character code
			
			if (keyCode === 10) {
				// Except new line, we want 13 instead
				keyCode = 13;
			}
		} else if (event.which == null) {
			// Old IE
			keyCode = event.keyCode;
		} else if (event.which != 0 && event.charCode != 0) {
			// Modern browsers
			keyCode = event.which;
		} else {
			// Special key, return code anyway
			keyCode = e.charCode || e.keyCode;
		}
		
		return keyCode;
	};
	
	/**
	 * Extend event-key with additional keys
	 */
	Y.Node.DOM_EVENTS.key.eventDef.KEY_MAP.arrowleft = 37;
	Y.Node.DOM_EVENTS.key.eventDef.KEY_MAP.arrowright = 39;
	Y.Node.DOM_EVENTS.key.eventDef.KEY_MAP['delete'] = 46;

}, YUI.version, {requires:['event-custom-base']});