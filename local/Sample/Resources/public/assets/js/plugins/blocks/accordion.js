/**
 * Accordion block
 * 
 * @version 1.0.1
 */
define(['jquery'], function ($) {
    'use strict';
	
	//Elements data property on which widget instance is set
	var DATA_INSTANCE_PROPERTY = 'accordion',
		CMS_MODE = $('html').hasClass('supra-cms');
	
	var Accordion = function (element, _options) {
		var container = this._container = $(element),
			options   = this._options = $.extend({}, Accordion.defaultOptions, _options || {}),
			items     = this._items = container.find(options.itemSelector),
			headings  = this._headings = container.find(options.headingSelector),
			contents  = this._contents = container.find(options.contentSelector),
			active    = items.filter('.' + options.activeClassName);
		
		this._length = items.size();
		this._activeIndex = null;
		
		if (active.size()) {
			this._activeIndex = active.index() || null;
		}
		
		headings.off('click.accordion').on('click.accordion', $.proxy(this._handleClick, this));
	};
	
	Accordion.defaultOptions = {
		'itemSelector':      '.accordion-item',
		'headingSelector':   '.accordion-heading',
		'contentSelector':   '.accordion-content',
		'activeClassName':   'active',
		'animationDuration': 350
	};
	
	Accordion.prototype = {
		
		'constructor': Accordion,
		
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
		'_activeIndex': null,
		
		/**
		 * Number of items in the widget
		 * @type {Number}
		 * @private
		 */
		'_length': 0,
		
		/**
		 * Change active tab
		 * Returns active tab index
		 * 
		 * @param {Number} index Tab index, optional
		 */
		'active': function (index) {
			if (index !== null && typeof index !== 'number') return this._activeIndex;
			if (index === null && this._activeIndex === null) return this._activeIndex;
			if (typeof index === 'number' && (index < 0 || index >= this._length || index === this._activeIndex)) return this._activeIndex;
			
			var old_index = this._activeIndex,
				items     = this._items,
				headings  = this._headings,
				contents  = this._contents,
				classname = this._options.activeClassName,
				duration  = this._options.animationDuration;
			
			if (old_index !== null) {
				contents.eq(old_index).css('display', 'block').slideUp(duration, $.proxy(function () {
					// Remove IE alpha style
					contents.eq(old_index).removeAttr('style');
				}, this));
				
				items.eq(old_index).removeClass(classname);
			}
			
			if (index !== null) {
				contents.eq(index).slideDown(duration, $.proxy(function () {
					// Remove IE alpha style
					//this._contents.eq(index).removeAttr('style');
				}, this));
				
				items.eq(index).addClass(classname);
			}
			
			this._activeIndex = index;
			return this._activeIndex;
		},
		
		/**
		 * Handle click event
		 * 
		 * @param {Object} event Event object
		 * @private
		 */
		'_handleClick': function (event) {
			var target = $(event.target).closest(this._options.itemSelector),
				index  = target.prevAll(this._options.itemSelector).size();
			
			// In CMS mode we disable toggle to improve usability
			if (index === this._activeIndex && !CMS_MODE) {
				this.active(null);
			} else {
				this.active(index);
			}
			
			event.preventDefault();
		}
		
	};
	
	/*
	 * jQuery accordion
	 * Create widget or call a function
	 */
	$.fn.accordion = function (prop) {
		var options = typeof prop === 'object' ? prop : null,
			fn = typeof prop === 'string' && typeof Accordion.prototype[prop] === 'function' && prop[0] !== '_' ? prop : null,
			args = fn ? Array.prototype.slice.call(arguments, 1) : null;
		
		return this.each(function () {
			var element = $(this),
				widget = element.data(DATA_INSTANCE_PROPERTY);
			
			if (!widget) {
				widget = new Accordion (element, $.extend({}, element.data(), options || {}));
				element.data(DATA_INSTANCE_PROPERTY, widget);
			} else if (fn) {
				widget[fn].apply(widget, args);
			}
		});
	};
	
	// requirejs
	return Accordion;
	
});
