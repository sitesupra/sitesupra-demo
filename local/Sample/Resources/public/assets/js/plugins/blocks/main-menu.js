/**
 * Menu block
 * @version 1.0.0
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'app/refresh'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	var DATA_INSTANCE_PROPERTY_ANDROID = 'androidTouchShim',
		DATA_INSTANCE_PROPERTY_POSITIONING = 'menuDirection',
		IS_ANDROID = (function () {
			return	navigator.userAgent.toString().match(/Android\s/) &&	// Android
					'ontouchstart' in document.documentElement;				// touch
		})(),
		NAMESPACE_COUNTER = 1;
	
	//
	// Make main menu fallout menus work as on iOS
	// where first click opens dropdown and second click opens page
	//
	function AndroidTouchShim (node) {
		this.eventNameSpace = '.androidShim' + (NAMESPACE_COUNTER++);
		this.node = $(node);
		
		this.node.on('click' + this.eventNameSpace, 'a', $.proxy(this._handleClick, this));
	}
	AndroidTouchShim.prototype = {
		/**
		 * Event namespace for instance
		 * @type {String}
		 * @private
		 */
		eventNameSpace: '',
		
		/**
		 * List of opened dropdowns
		 * @type {Array}
		 * @private
		 */
		opened: [],
		
		/**
		 * Handle click on one of the links in the menu
		 * If link has submenu, then show it
		 * 
		 * @param {Object} e Event facade object
		 * @private 
		 */
		_handleClick: function (e) {
			var node = $(e.target).closest('a'),
				ul   = node.next('ul.hidden');
			
			if (ul.length) {
				ul.removeClass('hidden');
				this.opened.push(ul);
				
				$(document).on('click' + this.eventNameSpace, $.proxy(this._handleDocumentClick, this));
				
				e.preventDefault();
			}
		},
		
		/**
		 * Handle click on document
		 * If user clicked outside menu link then close all dropdowns
		 * 
		 * @param {Object} e Event facade object
		 * @private 
		 */
		_handleDocumentClick: function (e) {
			if (!$(e.target).closest('a').closest(this.node).length) {
				this.close();
			}
		},
		
		/**
		 * Close all opened dropdowns
		 */
		close: function () {
			// Close all menus
			var opened = this.opened,
				i      = 0,
				ii     = opened.length;
			
			for (; i<ii; i++) {
				opened[i].addClass('hidden');
			}
			
			this.opened = [];
			
			// Detach listeners
			$(document).off('click' + this.eventNameSpace);
		},
		
		/**
		 * @destructor
		 */
		destroy: function () {
			// Detach listeners
			$(document).off('click' + this.eventNameSpace);
			this.node.off('click' + this.eventNameSpace);
			
			// Remove references
			this.node = this.opened = null;
		}
	};
	
	
	//
	// Check main menu drop direction (up or down)
	//
	function MenuPositioning (node) {
		this.eventNameSpace = '.menuPositioning' + (NAMESPACE_COUNTER++);
		this.node = $(node);
		
		this.node.find('ul.second-level').prev().on('mouseenter' + this.eventNameSpace, $.proxy(this._handleMouseOver, this));
	}
	MenuPositioning.prototype = {
		/**
		 * Event namespace for instance
		 * @type {String}
		 * @private
		 */
		eventNameSpace: '',
		
		/**
		 * On mouse over check
		 */
		_handleMouseOver: function (e) {
			var target = $(e.target).closest('a'),
				menu   = target.next('ul.second-level');
			
			if (!target.hasClass('direction-up')) {
				if (this.checkOverflow(menu)) {
					menu.addClass('direction-up');
				}
			}
		},
		
		/**
		 * Check if menu overflows content
		 */
		checkOverflow: function (node) {
			var docHeight = $(document).height(),
				top       = node.offset().top,
				height    = node.outerHeight();
			
			if (top + height >= docHeight) {
				return true;
			} else {
				return false;
			}
			
		},
		
		/**
		 * @destructor
		 */
		destroy: function () {
			// Detach listeners
			this.node.off('mouseenter' + this.eventNameSpace);
			
			// Remove references
			this.node = null;
		}
	};
	
	
	/**
	 * jQuery plugin
	 * 
	 * @param {Object} options
	 */
	$.fn.mainMenu = function (options) {
		var fn = null,
			args = null,
			output = this;
		
		// Normalize arguments
		if (typeof options === 'string') {
			args = Array.prototype.slice.call(arguments, 1);
			fn = options;
			options = {};
		} else if (typeof options !== 'object') {
			options = {};
		}
		
		// iOS behaviour emulation for Android
		if (IS_ANDROID) {
			this.each(function () {
				var instance = $(this).data(DATA_INSTANCE_PROPERTY_ANDROID);
				
				// Create
				if (!instance) {
					instance = new AndroidTouchShim($(this), $.extend({}, options, $(this).data()));
					$(this).data(DATA_INSTANCE_PROPERTY_ANDROID, instance);
				}
				
				// Call API method
				if (fn && typeof instance[fn] === "function" && fn[0] !== "_") {
					var ret  = instance[fn].apply(instance, args);
					
					if (ret !== undefined) {
						output = ret;
					}
				}
			});
		}
		
		// Sub-menu direction check
		this.each(function () {
			var instance = $(this).data(DATA_INSTANCE_PROPERTY_POSITIONING);
			
			// Create
			if (!instance) {
				instance = new MenuPositioning($(this), $.extend({}, options, $(this).data()));
				$(this).data(DATA_INSTANCE_PROPERTY_POSITIONING, instance);
			}
			
			// Call API method
			if (fn && typeof instance[fn] === "function" && fn[0] !== "_") {
				var ret  = instance[fn].apply(instance, args);
				
				if (ret !== undefined) {
					output = ret;
				}
			}
		});
		
		return output;
	};
	
	//$.refresh implementation
	if ($.refresh) {
		$.refresh.on('refresh/mainMenu', function (event, info) {
			info.target.mainMenu(info.target.data());
		});
		
		$.refresh.on('cleanup/mainMenu', function (event, info) {
			var instance = info.target.data(DATA_INSTANCE_PROPERTY_ANDROID);
			if (instance && instance.destroy) {
				instance.destroy();
				info.target.data(DATA_INSTANCE_PROPERTY_ANDROID, null)
			}
			
			instance = info.target.data(DATA_INSTANCE_PROPERTY_POSITIONING);
			if (instance && instance.destroy) {
				instance.destroy();
				info.target.data(DATA_INSTANCE_PROPERTY_POSITIONING, null)
			}
		});
	}

}));