/**
 * jQuery $.app Ajax Content
 * Ajax content loader / reloader for $.app
 * 
 * @version 1.0.5
 * @license Copyright 2014 Vide Infra
 */
(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'app/app'], function ($) {
            return factory($);
        });
	} else if (typeof module !== "undefined" && module.exports) {
		// CommonJS
		module.exports = factory(jQuery);
    } else {
        // AMD is not supported, assume all dependencies are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	"use strict";
	
    var _super = $.app.module.proto;

    $.app.AjaxContent = $.app.module({
    	
    	/**
    	 * Default method for 'trigger'
    	 * @type {String}
    	 */
    	'default_method': 'reload',
    	
    	/**
    	 * Initial content
    	 * @type {String}
    	 */
    	'default_content': '',
    	
    	/**
    	 * Request URL
    	 * @type {String}
    	 * @private
    	 */
    	'url': '',
    	
    	/**
    	 * Request method
    	 * @type {String}
    	 */
    	'method': 'get',
    	
    	/**
    	 * Request response data type
    	 * @type {String}
    	 */
    	'reload_response_type': 'html',
    	
    	/**
    	 * Active element before content replacement
    	 * @type {Object|String|Null}
    	 */
    	'active_element': null,
    	
    	
    	
    	/**
    	 * Initialize module
    	 * 
    	 * @param {Object} element
    	 * @param {Object} options
    	 * @constructor
    	 */
    	'init': function (element, options) {
    		_super.init.apply(this, arguments);
    		
    		if (options.reloadResponseType) {
    			this.reload_response_type = options.reloadResponseType;
    		}
    		
    		this.default_content = this.element.html();
    		this.url = this.options.url;
    	},
    	
    	/**
    	 * Reload HTML content from the server
    	 * 
    	 * @param {Object} params Additional request parameters which will be sent to server. Optional
    	 */
    	'reload': function (params) {
    		//Reload content
    		this.beforeReload();
    		
    		$.ajax(this.url, {
    			'cache': false,
    			'type': this.method,
    			'data': params,
    			'dataType': this.reload_response_type
    		}).done(this.proxy(this.onReload));
    		
    		//Prevent default behaviour
    		return false;
    	},
    	
    	/**
    	 * Reset HTML content to the default/initial HTML
    	 */
    	'reset': function () {
    		this.beforeReload();
    		this.element.html(this.default_content);
    		this.afterReload();
    	},
    	
    	/**
    	 * Before reload destroy children element module instances
    	 * 
    	 * @private
    	 */
    	'beforeReload': function () {
    		this.active_element = this.getActiveElement();
    		
    		$.app.cleanup(this.element);
    	},
    	
    	/**
    	 * On reload response set html
    	 * 
    	 * @private
    	 */
    	'onReload': function (response) {
    		if (this.reload_response_type === 'html') {
    			this.element.html(response);
    		} else if (this.reload_response_type === 'json') {
    			if (response && 'html' in response) {
    				this.element.html(response.html);
    			}
    		}
    		
    		this.afterReload(response);
    	},
    	
    	/**
    	 * On reload complete instantiate modules inside new content
    	 * 
    	 * @param {Obiect|String} response Request response
    	 * @private
    	 */
    	'afterReload': function (response) {
    		$.app.parse(this.element);
    		
    		// Restore focus on element, which was focused previously
    		this.restoreActiveElement(this.active_element);
    		this.active_element = null;
    		
    		// Event
    		this.element.trigger('reload');
    	},
    	
    	
    	// ------------------ Active element ------------------
    	
    	
    	/**
    	 * Returns active element in page (inputs, buttons, etc) and selection
    	 * 
    	 * @returns {String|Null} CSS selector and selection range or null
    	 */
    	'getActiveElement': function () {
    		var element = $(document.activeElement),
    			selection = null;
    		
    		if (element.size() && element.is('button, input, textarea, select, [tabindex], a') && $.contains(this.element.get(0), element.get(0))) {
    			if (element.is('input, textarea') && element && !element.is(':checkbox, :radio')) {
    				if ('selectionStart' in element.get(0)) {
    					// IE9+
    					selection = [
    						element.get(0).selectionStart,
    						element.get(0).selectionEnd
    					];
    				}
    			}
    			
    			return [this.getElementSelector(element), selection];
    		}
    		
    		// Element is not focusable, ignore 
    		return null;
    	},
    	
    	/**
    	 * Restore active element
    	 * 
    	 * @param {Object|String|Null} element Element or selector
    	 */
    	'restoreActiveElement': function (elements) {
    		if (!elements) {
    			return;
    		} else {
    			var selection = elements[1];
    			elements = elements[0];
    			
    			if (typeof elements === 'string') {
    				elements = this.element.find(elements);
    			}
    			
    			var i = 0,
    				ii = elements.size(),
    				element;
    			
    			for (; i<ii; i++) {
    				element = elements.eq(i);
    				if (element.not(':disabled') && element.is(':visible') && $.contains(document.body, element.get(0))) {
    					// Found element, which can be focused
    					element.focus();
    					
    					// Move selection
    					if (selection) {
    						if ('selectionStart' in element.get(0)) {
    							// IE9+
    							element.get(0).selectionStart = selection[0];
    							element.get(0).selectionEnd = selection[1];
    						}
    					}
    					
    					return;
    				}
    			}
    		}
    	},
    	
    	/**
    	 * Returns CSS selector which would match given element
    	 * 
    	 * @param {Object} element Element
    	 * @returns {String} CSS selector which would match element
    	 */
    	'getElementSelector': function (element) {
    		var selector = '',
    			tmp;
    		
    		if ((tmp = $.trim(element.attr('id')))) {
    			return '#' + element.attr('id');
    		} else if ((tmp = $.trim(element.attr('name')))) {
    			return element.prop('tagName') + '[name="' + element.attr('name') + '"]';
    		} else if ((tmp = $.trim(element.attr('class')))) {
    			return '.' + tmp.split(/\s+/g).join('.');
    		} else {
    			return element.prop('tagName');
    		}
    	}
    	
    });

	
	return $.app.AjaxContent;

}));
