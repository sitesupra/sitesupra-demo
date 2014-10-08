/**
 * Replaces standard <input type="checkbox" /> checkbox buttons with custom
 * @version 1.0
 */
(function ($) {
	
	var NAMESPACE = "checkbox";
	
	function Checkbox (node, options) {
		var options = this.options = $.extend({}, this.DEFAULT_OPTIONS, options || {});
		
		this.nodeInput = $(node);
		
		//Update class names
		//replace %c in classnames with options 'classname' value
		var classnames = ['classnameFocus', 'classnameFocus', 'classnameChecked', 'classnameInput'];
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
			
			'renderer': null						//HTML Renderer
		},
		
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
		 * Create HTML for custom checkbox
		 */
		_renderUI: function () {
			var options = this.options,
				checked = this.checked();
			
			//Create HTML
			var classname = options.classname + ' ' + (checked ? options.classnameChecked : '');
			var nodeCheckbox = this.nodeCheckbox = $('<span class="' + classname + '" tabindex="0"></span>');
			
			//Hide input
			this.nodeInput.addClass(this.options.classnameInput);
			
			//Add checkbox
			nodeCheckbox.insertAfter(this.nodeInput);
		},
		
		/**
		 * Bind event listeners
		 */
		_bindUI: function () {
			this.nodeInput.bind('change click', $.proxy(this.update, this));
			this.nodeCheckbox.bind('click', $.proxy(this._onClick, this));
			this.nodeCheckbox.bind('keyup', $.proxy(this._onKeyup, this));
			this.nodeCheckbox.bind('focus', $.proxy(this._onFocus, this));
			this.nodeCheckbox.bind('blur', $.proxy(this._onBlur, this));
			
			this.nodeCheckbox.siblings('label').bind('click', $.proxy(this._onClick, this));
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
				this._onClick();
			}
		},
		
		_onClick: function (event) {
			if (this.checked()) {
				this.nodeInput.get(0).checked = false;
			} else {
				this.nodeInput.get(0).checked = true;
			}
			this.nodeInput.change();
			
			event.preventDefault();
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
			if (this.checked()) {
				this.nodeCheckbox.addClass(this.options.classnameChecked);
			} else {
				this.nodeCheckbox.removeClass(this.options.classnameChecked);
			}
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
		this.filter('input[type="checkbox"]').each(function () {
			
			var object = $(this).data(NAMESPACE);
			if (!object) {
				object = new Checkbox($(this), typeof command === "object" ? command : {});
				$(this).data(NAMESPACE, object);
			}
			
			if (command && typeof object[command] === "function" && command[0] !== "_") {
				var args = Array.prototype.slice.call(arguments, 1);
				return object[command].apply(object, args) || this;
			}
			
		});
		
		return this;
	};

})(jQuery);