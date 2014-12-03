/**
 * Replaces standard <input type="checkbox" /> checkbox buttons with custom
 * @version 1.0.2
 */
(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], function ($) {
            return factory($);
        });
	} else if (typeof module !== "undefined" && module.exports) {
		// CommonJS
		module.exports = factory(jQuery);
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
})(this, function ($) {
	"use strict";
	
	var NAMESPACE = "checkbox",
		NAMESPACE_GUID = 0;
	
	function Checkbox (node, _options) {
		var options = this.options = $.extend({}, this.DEFAULT_OPTIONS, _options || {});
		
		this.ns = NAMESPACE + (++NAMESPACE_GUID);
		this.nodeInput = $(node);
		
		//Update class names
		//replace %c in classnames with options 'classname' value
		var classnames = ['classnameFocus', 'classnameChecked', 'classnameInput'];
		for(var i=0,ii=classnames.length; i<ii; i++) {
			options[classnames[i]] = options[classnames[i]].split('%c').join(options.classname);
		}
		
		this._renderUI();
		this._bindUI();
	}
	Checkbox.prototype = {
		/**
		 * Default options for class
		 */
		DEFAULT_OPTIONS: {
			'classname': 'checkbox',
			'classnameFocus': '%c-focus',
			'classnameChecked': '%c-checked',
			'classnameInput': 'invisible',
			
			'animate': true
		},
		
		/**
		 * Event namespace
		 * @type {String}
		 * @private
		 */
		ns: null,
		
		/**
		 * Input element (jQuery)
		 * @type {Object}
		 * @private
		 */
		nodeInput: null,
		
		/**
		 * Custom checkbox element (jQuery)
		 * @type {Object}
		 * @private
		 */
		nodeCheckbox: null,
		
		/**
		 * Custom checkbox inner element (jQuery)
		 * @type {Object}
		 * @private
		 */
		nodeInner: null,
		
		
		/**
		 * Create HTML for custom checkbox
		 */
		_renderUI: function () {
			var options = this.options,
				checked = this.checked();
			
			//Create HTML
			var classname = options.classname + ' ' + (checked ? options.classnameChecked : ''),
				nodeCheckbox = this.nodeCheckbox = $('<span class="' + classname + '" tabindex="0"><span></span></span>'),
				nodeInner = this.nodeInner = nodeCheckbox.find('span'),
				nodeInput = this.nodeInput;
			
			if (options.animate) {
				nodeInner.css({
					'display': 'inline-block',
					'opacity': checked ? 1 : 0
				});
			}
			
			//Disable orignal input focusing
			nodeInput.attr('tabindex', '-1');
			
			//Hide input
			nodeInput.addClass(this.options.classnameInput);
			
			//Add checkbox
			nodeCheckbox.insertAfter(nodeInput);
		},
		
		/**
		 * Bind event listeners
		 */
		_bindUI: function () {
			var ns = '.' + this.ns;
			
			this.nodeInput.bind('change' + ns + ' click' + ns, $.proxy(this.update, this));
			this.nodeCheckbox.bind('click' + ns, $.proxy(this._onClick, this));
			this.nodeCheckbox.bind('keyup' + ns, $.proxy(this._onKeyup, this));
			this.nodeCheckbox.bind('focus' + ns, $.proxy(this._onFocus, this));
			this.nodeCheckbox.bind('blur' + ns, $.proxy(this._onBlur, this));
			
			this.nodeCheckbox.siblings('label').bind('click' + ns, $.proxy(this._onClick, this));
		},
		
		_onFocus: function () {
			this.nodeCheckbox.addClass(this.options.classnameFocus);
		},
		
		_onBlur: function () {
			this.nodeCheckbox.removeClass(this.options.classnameFocus);
		},
		
		_onKeyup: function (evt) {
			var key = evt.which || evt.keyCode || evt.charCode;
			
			/* Return key or Space key */
			if (key == 13 || key == 32) {
				this._onClick(evt);
			}
		},
		
		_onClick: function (event) {
			if ($(evt.target).closest('label a').length) {
				// If inside label is a link, then this shouldn't toggle checkbox
				return;
			}
			
			if (this.nodeInput.prop('disabled') || this.nodeInput.prop('readonly')) {
				// If input is disabled or can't be changed then ignore click,
				// Unline browser default behaviour we ignore clicks if checkbox is "readonly" 
				if (evt) {
					evt.preventDefault();
				}
				return;
			}
			
			if (this.checked()) {
				this.nodeInput.get(0).checked = false;
			} else {
				this.nodeInput.get(0).checked = true;
			}
			this.nodeInput.change();
			
			if (evt) {
				evt.preventDefault();
			}
		},
		
		/**
		 * Checked state setter/getter
		 */
		checked: function (value) {
			var old = this.nodeInput.is(':checked');
			if (value === true && !old) {
				this.nodeInput.get(0).checked = true;
			} else if (value === false && old) {
				this.nodeInput.get(0).checked = false;
			}
			
			return old;
		},
		
		/**
		 * Manually update UI
		 */
		update: function () {
			var checkbox = this.nodeCheckbox,
				inner = this.nodeInner,
				fn = $.fn.velocity || $.fn.animate;
			
			if (this.checked()) {
				checkbox.addClass(this.options.classnameChecked);
				
				if (this.options.animate) {
					inner[fn]({
						'opacity': 1
					}, {
						'duration': 175
					});
				}
			} else {
				checkbox.removeClass(this.options.classnameChecked);
				
				if (this.options.animate) {
					inner[fn]({
						'opacity': 0
					}, {
						'duration': 175
					});
				}
			}
		},
		
		/**
		 * @destructor
		 */
		destroy: function () {
			var ns = '.' + this.ns,
				nodeInput = this.nodeInput,
				nodeCheckbox = this.nodeCheckbox;
			
			nodeInput.unbind('change' + ns + ' click' + ns);
			nodeInput.removeData(NAMESPACE);
			
			nodeCheckbox.siblings('label').unbind('click' + ns);
			nodeCheckbox.remove();
			
			//Enable orignal input focusing
			nodeInput.removeAttr('tabindex');
			
			//Show input
			nodeInput.removeClass(this.options.classnameInput);
			
			//Clean up
			this.nodeInner = this.nodeInput = this.nodeCheckbox = null;
		}
	};
	
			
	/**
	 * jQuery checkbox plugin
	 * 
	 * @param {Object} options Optional parameters
	 * @return Checkbox object instance
	 * @type {Object}
	 */
	$.fn.checkbox = function (options) {
		var fn = null,
			args = Array.prototype.slice.call(arguments, 1),
			inputs = this.filter('input[type="checkbox"]').add(this.find('input[type="checkbox"]'));
			output = this;
		
		if (typeof options === 'string') {
			fn = options;
			options = {};
		} else if (typeof options !== 'object') {
			options = {};
		}
		
		inputs.find('input[type="checkbox"]').each(function () {
			
			var instance = $(this).data(NAMESPACE);
			if (!instance) {
				instance = new Checkbox($(this), options);
				$(this).data(NAMESPACE, instance);
			}
			
			if (fn && typeof instance[fn] === "function" && fn[0] !== "_") {
				var ret = instance[fn].apply(instance, args);
				if (ret !== undefined) {
					output = ret;
				}
			}
			
		});
		
		return output;
	};

})(jQuery);