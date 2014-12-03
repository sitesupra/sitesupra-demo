/**
 * Tabs block
 * @version 1.0.2
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'plugins/helpers/responsive', 'plugins/helpers/throttle'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	//Elements data property on which widget instance is set
	var DATA_INSTANCE_PROPERTY = 'tabs';
	
	var Tabs = function (element, options) {
		var container = this._container = $(element),
			options   = this._options = $.extend({}, Tabs.defaultOptions, options || {}),
			headings  = this._headings = container.find(options.headingSelector),
			contents  = this._contents = container.find(options.contentSelector),
			active    = headings.filter('.' + options.activeClassName);
		
		this._length = Math.min(headings.length, contents.length);
		
		if (active.size()) {
			this._activeIndex = active.index();
		} else {
			this._activeIndex = 0;
			headings.eq(0).addClass(options.activeClassName);
			contents.eq(0).addClass(options.activeClassName);
		}
		
		
		headings.off('click.tabs').on('click.tabs', $.proxy(this._handleClick, this));
		
		// On resize check if tabs fit into view with default style
		$.responsive.on('resize', $.proxy(this.update, this));
		$(window).on('resize', $.throttle(this.update, this, 100, true));
		
		this.update();
	};
	
	Tabs.defaultOptions = {
		'headingSelector': '.tabs-heading',
		'contentSelector': '.tabs-content',
		'activeClassName': 'active',
		'animationDuration': 'fast'
	};
	
	Tabs.prototype = {
		
		'constructor': Tabs,
		
		/**
		 * Widget options
		 * @type {Object}
		 * @private
		 */
		'_options': null,
		
		/**
		 * Container element
		 * @type {Object}
		 * @private
		 */
		'_containner': null,
		
		/**
		 * Heading elements
		 * @type {Object}
		 * @private
		 */
		'_headings': null,
		
		/**
		 * Content elements
		 * @type {Object}
		 * @private
		 */
		'_contents': null,
		
		/**
		 * Active tab index
		 * @type {Number}
		 * @private
		 */
		'_activeIndex': 0,
		
		/**
		 * Number of tabs in the widget
		 * @type {Number}
		 * @private
		 */
		'_length': 0,
		
		/**
		 * Returns heading with given index
		 * 
		 * @param {Number} index Tab index
		 * @returns {Object} Element matching index
		 */
		'heading': function (index) {
			return this._headings.eq(index);
		},
		
		/**
		 * Returns content with given index
		 * 
		 * @param {Number} index Tab index
		 * @returns {Object} Element matching index
		 */
		'content': function (index) {
			return this._contents.eq(index);
		},
		
		/**
		 * Change active tab
		 * Returns active tab index
		 * 
		 * @param {Number} index Tab index, optional
		 */
		'active': function (index) {
			if (typeof index === 'number' && index >= 0 && index < this._length && index !== this._activeIndex) {
				var old_index = this._activeIndex,
					headings  = this._headings,
					contents  = this._contents,
					classname = this._options.activeClassName,
					duration  = this._options.animationDuration;
				
				headings.eq(old_index).removeClass(classname);
				headings.eq(index).addClass(classname);
				
				contents.eq(old_index).removeClass(classname);
				contents.eq(index).css('opacity', 0).addClass(classname).animate({'opacity': 1}, duration, $.proxy(function () {
					// Remove IE alpha style
					this._contents.eq(index).removeAttr('style');
				}, this));
				
				this._container.trigger('tabChange', {'oldVal': this._activeIndex, 'newVal': index});
				this._activeIndex = index;
			}
			
			return this._activeIndex;
		},
		
		/**
		 * Handle click event
		 * 
		 * @param {Object} event Event object
		 * @private
		 */
		'_handleClick': function (event) {
			var target = $(event.target).closest(this._options.headingSelector),
				index  = target.index();
			
			this.active(index);
			
			event.preventDefault();
		},
		
		/**
		 * Check if tabs with default style fit into view
		 * and if not change to accordion like style
		 * 
		 * @private
		 */
		'update': function () {
			var element = this._container,
				headings = this._headings,
				container = headings.parent(),
				accordionMode = false;
			
			if ($.responsive.size == $.responsive.SIZE_DESKTOP || $.responsive.size == $.responsive.SIZE_TABLET) {
				element.removeClass('tabs-accordion');
				
				if (Math.abs(headings.eq(0).outerHeight() - container.outerHeight()) > 10) {
					// Doesn't fit
					accordionMode = true;
				}
			} else {
				accordionMode = true;
			}
			
			element.toggleClass('tabs-accordion', accordionMode);
		}
		
	};
	
	/*
	 * jQuery plugin
	 * Create widget or call a function
	 */
	$.fn.tabs = function (prop) {
		var options = typeof prop === 'object' ? prop : null,
			fn = typeof prop === 'string' && typeof Tabs.prototype[prop] === 'function' && prop[0] !== '_' ? prop : null,
			args = fn ? Array.prototype.slice.call(arguments, 1) : null;
		
		return this.each(function () {
			
			var element = $(this),
				widget = element.data(DATA_INSTANCE_PROPERTY);
			
			if (!widget) {
				widget = new Tabs (element, $.extend({}, element.data(), options || {}));
				element.data(DATA_INSTANCE_PROPERTY, widget);
			} else if (fn) {
				widget[fn].apply(widget, args);
			}
		});
	};
	
}));