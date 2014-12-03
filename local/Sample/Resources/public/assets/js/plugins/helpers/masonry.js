/**
 * Masonry like
 * @version 1.0.1
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'plugins/helpers/responsive'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	//Elements data property on which widget instance is set
	var DATA_INSTANCE_PROPERTY = 'masonry';
	
	function Masonry (element, options) {
		this._options = $.extend({}, Masonry.defaultOptions, options || {}),
		this._node = $(element);
		
		// Attach listeners
		this.update = $.proxy(this.update, this);
		$.responsive.on('resize', this.update);
		
		this._collectData();
		
		this._column_count = -1; // -1 for update to work
		this.update();
	}
	
	Masonry.defaultOptions = {
		'columnCount': 1,
		'itemSelector': 'article',
		'classNameHidden': 'hidden'
	};
	
	Masonry.prototype = {
		
		/**
		 * Find columns and their locations
		 * 
		 * @private
		 */
		'_collectData': function () {
			this._containers = this._node.children().not(':empty');
			this._elements = this._containers.find(this._options.itemSelector);
		},
		
		/**
		 * Returns column count
		 * 
		 * @returns {Number} Current column count
		 * @private
		 */
		'_getColumnCount': function () {
			var containers = this._containers,
				i = 1, ii = containers.size(),
				left = containers.eq(0).offset().left,
				last_left = left;
			
			for (; i<ii; i++) {
				last_left = containers.eq(i).offset().left;
				if (last_left <= left) {
					return i;
				} else {
					left = last_left;
				}
			}
			
			return ii;
		},
		
		/**
		 * Relaculate and update
		 */
		'update': function () {
			var column_count = 0,
				containers   = this._containers,
				elements     = this._elements,
				i            = 0,
				ii           = elements.size(),
				column;
			
			containers.removeClass(this._options.classNameHidden);
			column_count = this._getColumnCount();
			
			if (this._column_count != column_count) {
				for (; i<ii; i++) {
					column = i % column_count;
					containers.eq(column).append(elements.eq(i));
				}
				
				this._column_count = column_count;
			}
			
			containers.filter(':empty').addClass(this._options.classNameHidden);
		}
	};
	
	$.fn.simpleMasonry = function (prop) {
		var options = typeof prop === 'object' ? prop : null,
			fn = typeof prop === 'string' && typeof Masonry.prototype[prop] === 'function' ? prop : null;
		
		return this.each(function () {
			
			var element = $(this),
				widget = element.data(DATA_INSTANCE_PROPERTY);
			
			if (!widget) {
				widget = new Masonry (element, $.extend({}, element.data(), options || {}));
				element.data(DATA_INSTANCE_PROPERTY, widget);
			} else {
				if (fn) {
					widget[fn].call(widget);
				}
			}
		});
	};
	
}));