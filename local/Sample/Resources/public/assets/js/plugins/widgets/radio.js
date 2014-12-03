/**
 * Replaces standard <input type="radio" /> radio buttons with custom
 * 
 * @version 1.0.1
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	var NAMESPACE = 'radio';
	
	function Radio (node, options) {
		var options = this.options = $.extend({}, Radio.defaults, options || {});
		var name = $(this).attr('name');
		
		// We need to find all radio buttons in group
		node = node.closest(options.container || 'form').find('input.radio[name="' + name + '"]');
		
		this.nodeInputs = $(node);
		
		//Update class names
		//replace %c in classnames with options 'classname' value
		var classnames = ['classnameFocus', 'classnameFocus', 'classnameChecked', 'classnameInput'];
		for(var i=0,ii=classnames.length; i<ii; i++) {
			options[classnames[i]] = options[classnames[i]].split('%c').join(options.classname);
		}
		
		this._renderUI();
		this._bindUI();
	}
	
	/**
	 * Default plugin options
	 */
	Radio.defaults = {
		'classname': 'radio',
		'classnameFocus': '%c-focus',
		'classnameChecked': '%c-checked',
		'classnameInput': 'invisible',
		
		'renderer': null						// Custom HTML Renderer
	};
	
	Radio.prototype = {
		
		/**
		 * Input elements (jQuery)
		 * @type {Object}
		 * @private
		 */
		nodeInputs: null,
		
		/**
		 * Custom radio button elements (jQuery)
		 * @type {Object}
		 * @private
		 */
		nodeRadios: null,
		
		/**
		 * Create HTML for custom radio buttons
		 */
		_renderUI: function () {
			var options = this.options,
				nodeRadio = null,
				nodeRadios = $(),
				nodeInput = null,
				nodeInputs = this.nodeInputs,
				classname,
				checked;
			
			//Create HTML
			for(var i=0,ii=nodeInputs.length; i<ii; i++) {
				nodeInput = nodeInputs.eq(i);
				checked = nodeInput.data('index', i).is(':checked');
				
				classname = options.classname + ' ' + (checked ? options.classnameChecked : '');
				nodeRadio = $('<span class="' + classname + '" tabindex="0"></span>')
									.data('index', i)
									.insertAfter(nodeInput);
				
				nodeRadios = nodeRadios.add(nodeRadio);
			}
			
			//Hide input
			this.nodeInputs.addClass(this.options.classnameInput);
			
			this.nodeRadios = nodeRadios;
		},
		
		/**
		 * Bind event listeners
		 */
		_bindUI: function () {
			this.nodeInputs.bind('change click', $.proxy(this._onChange, this));
			this.nodeRadios.bind('click', $.proxy(this._onClick, this));
			this.nodeRadios.bind('keyup', $.proxy(this._onKeyup, this));
			this.nodeRadios.bind('focus', $.proxy(this._onFocus, this));
			this.nodeRadios.bind('blur', $.proxy(this._onBlur, this));
			
			this.nodeRadios.siblings('label').bind('click', $.proxy(this._onClick, this));
		},
		
		_onFocus: function (evt) {
			var index = $(evt.currentTarget).data('index');
			this.nodeRadios.eq(index).addClass(this.options.classnameFocus);
		},
		
		_onBlur: function (evt) {
			var index = $(evt.currentTarget).data('index');
			this.nodeRadios.eq(index).removeClass(this.options.classnameFocus);
		},
		
		_onKeyup: function (evt) {
			var key = evt.which || evt.keyCode || evt.charCode;
			
			/* Return key or Space key */
			if (key == 13 || key == 32) {
				this._onClick(evt);
			}
		},
		
		_onClick: function (evt) {
			var target = $(evt.currentTarget);
			if (!target.is('input')) target = target.siblings('input').eq(0);
			
			var index = target.data('index');
			
			this.nodeInputs.get(index).checked = true;
			this.nodeInputs.eq(index).change();
			
			evt.preventDefault();
		},
		
		/**
		 * Handle change event
		 */
		_onChange: function (evt) {
			var index = $(evt.currentTarget).data('index'),
				classname = this.options.classnameChecked;
			
			if (this.isChecked(index)) {
				this.nodeRadios.removeClass(classname).eq(index).addClass(classname);
			} else {
				this.nodeRadios.eq(index).removeClass(classname);
			}
		},
		
		/**
		 * Checked state setter/getter
		 * 
		 * @param {Number} index Checked item index
		 */
		checked: function (index) {
			var node = this.nodeInput.eq(index),
				old = node.is(':checked');
			
			if (!old) {
				node.get(0).checked = true;
			}
			
			return old;
		}
	};
	
			
	/**
	 * jQuery radio button plugin
	 * 
	 * @param {Object} options Optional parameters
	 */
	$.fn.radio = function (options) {
		var instance = null,
			options = options || {};
		
		this.filter('input').each(function () {
			var object = $(this).data(NAMESPACE);
			if (!object) {
				object = new Radio($(this), typeof command === "object" ? command : {});
				$(this).data(NAMESPACE, object);
			}
			
			if (command && typeof object[command] === "function" && command[0] !== "_") {
				var args = Array.prototype.slice.call(arguments, 1);
				return object[command].apply(object, args) || this;
			}
		});
		
		return this;
	};
	
	return Radio;

}));