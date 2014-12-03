/**
 * Equal height columns plugin
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
	
	function EqualHeight (columns, options) {
		this.columns = $(columns);
		this.modes = this.getModes();
		this.onResize();
		
		if (!options || options.auto_resize !== false) {
			this.update = $.proxy(this.update, this);
			this.onResize = $.proxy(this.onResize, this);
			$.responsive.on('resize', this.onResize);
			
			// When some content inside changes update height
			this.columns.on('toggle', $.proxy(this.update, this));
		}
	}
	EqualHeight.prototype = {
		/**
		 * Columns
		 * @private
		 */
		'columns': null,
		
		/**
		 * Disabled
		 * @private
		 */
		'disabled': false,
		
		/**
		 * List of modes in which plugin should be enabled
		 * @type {Object}
		 * @private
		 */
		'modes': {},
		
		/**
		 * Returns modes in which this plugins should be enabled
		 * 
		 * @returns {Object} List of modes
		 * @private
		 */
		'getModes': function () {
			var modes = (this.columns.data('modes') || 'desktop,tablet').split(','),
				i     = 0,
				ii    = modes.length,
				ret   = {};
			
			for (; i<ii; i++) {
				if (modes[i] == 'all') {
					ret[$.responsive.SIZE_DESKTOP] = true;
					ret[$.responsive.SIZE_TABLET] = true;
					ret[$.responsive.SIZE_MOBILE_LANDSCAPE] = true;
					ret[$.responsive.SIZE_MOBILE_PORTRAIT] = true;
				} else if (modes[i] == 'desktop') {
					ret[$.responsive.SIZE_DESKTOP] = true;
				} else if (modes[i] == 'tablet') {
					ret[$.responsive.SIZE_TABLET] = true;
				} else if (modes[i] == 'mobile') {
					ret[$.responsive.SIZE_MOBILE_LANDSCAPE] = true;
					ret[$.responsive.SIZE_MOBILE_PORTRAIT] = true;
				}
			}
			
			return ret;
		},
		
		/**
		 * Handle resize event
		 */
		'onResize': function () {
			var size = $.responsive.size;
			
			if (this.modes[size]) {
				if (this.disabled) {
					this.enable();
				} else {
					this.update();
				}
			} else {
				this.disable();
			}
		},
		
		/**
		 * Update column heights
		 */
		'update': function () {
			if (this.disabled) return;
			
			var columns  = this.columns, i=0, ii=columns.length, max = 0,
				column   = null,
				paddings = [];
			
			columns.height('auto');
			
			for (i=0; i<ii; i++) {
				column = columns.eq(i);
				max = Math.max(column.outerHeight(), max);
				paddings[i] = column.outerHeight() - column.height();
			}
			for (i=0; i<ii; i++) {
				columns.eq(i).css({
					'height': max - paddings[i] + 'px'
				});
			}
		},
		
		/**
		 * Disable plugin
		 */
		'disable': function () {
			if (!this.disabled) {
				this.disabled = true;
				this.columns.height('auto');
			}
		},
		
		/**
		 * Enable plugin
		 */
		'enable': function () {
			if (this.disabled) {
				this.disabled = false;
				this.update();
			}
		},
		
		/**
		 * Destroy
		 */
		'destroy': function () {
			if (!this.columns) return;
			
			$.responsive.off('resize', this.update);
			this.columns.data('equalHeight', null);
			this.columns.height('auto');
			this.columns.update = null;
			this.columns = null;
		}
	};
	
	$.fn.equalHeight = function (options) {
		var columns = $(this), i=0, ii=columns.length, obj = columns.data('equalHeight');
		
		if (typeof options === 'string') {
			for (; i<ii; i++) {
				obj = columns.eq(i).data('equalHeight');
				if (obj && typeof obj[options] === 'function') obj[options]();
			}
			return columns;
		}
		if (!obj) {
			columns.data('equalHeight', new EqualHeight(columns, options));
		}
		
		return columns;
	};
	
	$.fn.columns = function (options) {
		var groups = {'default': $()},
			attr   = options && options.attr ? options.attr : 'group';
		
		// Group all nodes by data-group attribute
		this.each(function () {
			var group = $(this).data(attr) || 'default';
			if (!(group in groups)) {
				groups[group] = $();
			}
			groups[group] = groups[group].add($(this));
		});
		
		for (key in groups) {
			groups[key].equalHeight(options);
		}
		
		return this;
	};
	
}));