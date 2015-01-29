/**
 * Menu block
 * 
 * @version 1.0.0
 */
define(['jquery', 'frontend/util/debounce'], function ($, debounce) {
    'use strict';
	
	var DATA_INSTANCE_PROPERTY = 'anchorMenu',
		NAMESPACE_COUNTER = 1;
	
	//
	// Make main menu fallout menus work as on iOS
	// where first click opens dropdown and second click opens page
	//
	function AnchorMenu (node) {
		this.eventNameSpace = '.anchorMenu' + (NAMESPACE_COUNTER++);
		this.node = $(node);
		this.links = this._getAnchorLinks();
		
		if (!this.links.length) {
			this.destroy();
			return this;
		}
		
		this.update();
		$(document).on('scroll' + this.eventNameSpace, debounce(this.update, this, 60));
	}
	AnchorMenu.prototype = {
		
		/**
		 * Active item index
		 * @type {Number}
		 * @private
		 */
		active: -1,
		
		/**
		 * Returns anchor links, which have valid target on same page
         * 
         * @returns {Array} List of links, targets and target positions
         * @protected
		 */
		_getAnchorLinks: function () {
			var path  = document.location.pathname,
				links = this.node.find('a[href*="#"]'),
				i     = 0,
				ii    = links.length,
				
				parts,
				target,
				
				results = [],
				active = -1;
			
			for (; i<ii; i++) {
				parts = links.eq(i).attr('href').match(/(.*)#(.*)/);
				
				if (!parts[1] || parts[1] === path) {
					target = $(document.getElementsByName(parts[2]));
					
					if (target.length) {
						results.push({
							'link': links.eq(i).parent(),
							'target': target.eq(0),
							'offset': target.offset().top
						});
						
						// Currently active item
						if (links.eq(i).parent().hasClass('active')) {
							this.active = results.length - 1;
						}
					}
				}
			}
			
			return results.sort(function (a, b) {
				return a.offset > b.offset ? 1 : a.offset < b.offset ? -1 : 0;
			});
		},
		
		/**
		 * On scroll update
		 */
		update: function () {
			var links = this.links,
				i  = 0,
				ii = links.length,
				active = -1,
				scroll = $(document).scrollTop() + $(window).height() / 2,
				offset;
			
			for (; i<ii; i++) {
				offset = links[i].target.offset().top;
				if (offset > scroll) break;
				active = i;
			}
			
			if (active !== this.active) {
				if (this.active !== -1) links[this.active].link.removeClass('active');
				if (active !== -1) links[active].link.addClass('active');
				
				this.active = active;
			}
		},
		
		/**
		 * @destructor
		 */
		destroy: function () {
			// Detach listeners
			$(document).off('scroll' + this.eventNameSpace);
			
			// Remove references
			this.node = null;
			this.links = null;
		}
		
	};
	
	
	/*
	 * jQuery plugin
	 * Create widget or apply options or call a function
	 */
	$.fn.anchorMenu = function (prop) {
		var options = typeof prop === 'object' ? prop : null,
			fn = typeof prop === 'string' && typeof AnchorMenu.prototype[prop] === 'function' && prop[0] !== '_' ? prop : null;
		
		return this.each(function () {
			
			var element = $(this),
				widget = element.data(DATA_INSTANCE_PROPERTY);
			
			if (!widget) {
				widget = new AnchorMenu (element, $.extend({}, element.data(), options || {}));
				element.data(DATA_INSTANCE_PROPERTY, widget);
			} else {
				if (fn) {
					widget[fn].call(widget);
				}
			}
		});
	};

});
