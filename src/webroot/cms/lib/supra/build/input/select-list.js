YUI.add('supra.input-select-list', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Horizontal button list for selecting values
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = 'input-select-list';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		/**
		 * Allow selecting multiple values
		 */
		'multiple': {
			value: false
		},
		
		/**
		 * Style
		 */
		'style': {
			value: '',
			setter: '_setStyle'
		},
		
		/**
		 * Show empty value in the list
		 * @type {Boolean}
		 */
		'showEmptyValue': {
			value: true
		},
		
		/**
		 * Value/option list
		 */
		'values': {
			value: [],
			setter: '_setValues'
		}
	};
	
	Input.HTML_PARSER = {
		'values': function () {
			var input = this.get('inputNode'),
				values = [];
			
			if (input && input.test('select')) {
				var options = Y.Node.getDOMNode(input).options;
				for(var i=0,ii=options.length; i<ii; i++) {
					values.push({
						'id': options[i].value,
						'title': options[i].text
					});
				}
			} else {
				values = this.get('values') || [];
			}
			
			return values;
		},
		'style': function (srcNode) {
			if (srcNode.getAttribute('suStyle')) {
				return srcNode.getAttribute('suStyle') || '';
			}
		}
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<select class="hidden"></select>',
		LABEL_TEMPLATE: '<label></label>',
		
		
		/**
		 * Button list
		 * @type {Object}
		 * @private
		 */
		buttons: {},
		
		/**
		 * List of values matching buttons
		 * @type {Object}
		 * @private
		 */
		button_value_map: null,
		
		/**
		 * Buttons has been rendered
		 * @type {Boolean}
		 * @private
		 */
		buttons_rendered: false,
		
		
		bindUI: function () {
			var input = this.get('inputNode');
			input.on('focus', this._onFocus, this);
			input.on('blur', this._onBlur, this);
			
			//Handle value attribute change
			this.on('valueChange', this._afterValueChange, this);
		},
		
		renderUI: function () {
			this.button_value_map = {};
			
			Input.superclass.renderUI.apply(this, arguments);
			
			if (this.get('style')) {
				var classname = this.getClassName(this.get('style')),
					boundingBox = this.get('boundingBox');
				
				boundingBox.addClass(classname);
			}
			
			if (!this.buttons_rendered) {
				this.renderButtons(this.get('values'));
			}
		},
		
		renderButtons: function (values) {
			
			//Remove old buttons
			if (this.buttons) {
				for(var i in this.buttons) {
					this.buttons[i].destroy();
				}
			}
			
			this.buttons = {};
			
			var buttons = this.buttons,
				value = this._getInternalValue(),
				has_value_match = false,
				inputNode = this.get('inputNode'),
				input = inputNode.getDOMNode(),
				show_empty_value = this.get('showEmptyValue'),
				button_value_map = this.button_value_map;
			
			if (this.buttons_rendered && input.options && input.options.length) {
				//Remove old options
				while(input.options.length) {
					input.remove(input.options[0]);
				}
			}
			
			
			//Buttons will be placed instead of input
			inputNode.addClass('hidden');
			
			var button_width = 100 / values.length;
			
			for(var i=0,ii=values.length-1; i<=ii; i++) {
				if (values[i].id || show_empty_value) {
					if (this.renderButton(input, values[i], i == 0, i == ii, button_width)) {
						has_value_match = true;
					}
				}
			}
			
			if (!has_value_match) {
				if (values.length) {
					value = values[0].id;
					if (input) input.value = value;
					this.set('value', value);
				}
			}
			
			//Set value
			if (this.get('multiple') && Y.Lang.isArray(value)) {
				for(var id in buttons) {
					if (id in button_value_map) {
						id = button_value_map[id];
					}
					this.buttons[id].set('down', Y.Array.indexOf(value, id) != -1);
				}
			} else {
				inputNode.set('value', value);
				if (value in button_value_map) {
					value = button_value_map[value];
				}
				if (value in buttons) {
					buttons[value].set('down', true);
				}
			}
			
			//Buttons rendered
			this.buttons_rendered = true;
		},
		
		renderButton: function (input, definition, first, last, button_width) {
			var contentBox = this.get('contentBox'),
				button = new Supra.Button({'label': definition.title, 'icon': definition.icon, 'type': 'toggle', 'style': 'group'}),
				value = this._getInternalValue(),
				has_value_match = false;
			
			if (contentBox.test('input,select')) {
				contentBox = this.get('boundingBox');
			}
			
			button.ICON_TEMPLATE = '<span class="img"><img src="" alt="" /></span>';
			this.buttons[definition.id] = button;
			
			if (first) {
				button.get('boundingBox').addClass('su-button-first');
			}
			if (last) {
				button.get('boundingBox').addClass('su-button-last');
			}
			
			if (input && input.options) {
				//Add options to allow selecting value
				input.options[input.options.length] = new Option(definition.title, definition.id);
				if (value == definition.id) input.value = value;
			}
			
			if (definition.id == value) {
				//Mark value as found
				has_value_match = true;
			}
			
			button.render(contentBox);
			
			//Set button width
			if (this.get('style') != 'items') {
				button.get('boundingBox').setStyle('width', button_width + '%');
			}
			
			//On click update input value
			button.on('click', this._onClick, this, definition.id);
			
			return has_value_match;
		},
		
		
		/*
		 * ---------------------------------------- API ----------------------------------------
		 */
		
		
		/**
		 * Returns full data for value
		 * If value is an array of values then returns array of data
		 * 
		 * @param {String} value Optional, value for which to return full data
		 * @returns {Object} Value data
		 */
		getValueData: function (value) {
			var value  = value === null || typeof value === 'undefined' ? this._getInternalValue() : value,
				values = this.get('values'),
				i = 0,
				ii = values.length;
			
			if (Y.Lang.isArray(value)) {
				// Multiple values
				var out = [];
				for (; i<ii; i++) {
					if (Y.Array.indexOf(value, values[i].id) != -1) {
						out.push(values[i]);
					}
				}
				return out;
			} else {
				// Single value
				for (; i<ii; i++) {
					if (values[i].id == value) {
						return values[i];
					}
				}
			}
			
			return null;
		},
		
		/**
		 * Reset value to default
		 */
		resetValue: function () {
			var value = this.get('defaultValue'),
				values = this.get('values');
			
			this.set('value', value !== null ? value : (values.length ? values[0].id : ''));
			return this;
		},
		
		/**
		 * Returns true if list has options with given id
		 * 
		 * @param {String} id Option ID
		 * @return True if has option with given id, otherwise false
		 * @type {Boolean}
		 */
		hasValue: function (id) {
			var values = this.get("values"),
				i = 0,
				ii = values.length;
			
			 for (; i<ii; i++) if (values[i].id == id) return true;
			 return false
		},
		
		
		/*
		 * ---------------------------------------- EVENT LISTENERS ----------------------------------------
		 */
		
		
		/**
		 * On focus style input
		 * 
		 * @private
		 */
		_onFocus: function () {
			if (this.get('boundingBox').hasClass('yui3-input-focused')) return;
			
			this.get('boundingBox').addClass('yui3-input-focused');
			this.get('inputNode').focus();
		},
		
		/**
		 * On blur style input
		 * 
		 * @private
		 */
		_onBlur: function () {
			this.get('boundingBox').removeClass('yui3-input-focused');
		},
		
		/**
		 * On click update value
		 * 
		 * @param {Object} event Event facade object
		 * @param {String} id Value id on which user clicked
		 * @private
		 */
		_onClick: function (event, id) {
			if (this.get('multiple')) {
				this.set('value', this.get('value'));
			} else {
				this.set('value', id);
			}
		},
		
		/**
		 * Returns selected value
		 * 
		 * @returns {String} Selected value
		 * @private
		 */
		_getInternalValue: function () {
			return this.get('value');
		},
		
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal});
			}
		},
		
		
		/*
		 * ---------------------------------------- ATTRIBUTES ----------------------------------------
		 */
		
		
		/**
		 * Values attribute setter
		 * 
		 * @param {Array} values List of values
		 * @returns {Array} New values list
		 * @private
		 */
		_setValues: function (values) {
			if (this.get('rendered')) {
				this.renderButtons(values);
			}
			return values;
		},
		
		/**
		 * Value attribute setter
		 * 
		 * @param {String} value Value id
		 * @returns {String} New value
		 * @private
		 */
		_setValue: function (value) {
			
			// Convert boolean values to string
			if (typeof value == 'boolean') {
				value = value ? "1" : "0";
			}
			
			if (!this.get('rendered')) {
				// Not rendered, there are no buttons yet
				return value;
			}
			
			//Input value is not valid if 'multiple' attribute is true
			this.get('inputNode').set('value', value);
			
			//Map for buttons and values
			var button_value_map = this.button_value_map;
			
			if (this.get('multiple') && Y.Lang.isArray(value)) {
				//Update button states
				for(var i in this.buttons) {
					if (i in button_value_map) {
						i = button_value_map[i];
					}
					this.buttons[i].set('down', Y.Array.indexOf(value, i) != -1);
				}
			} else {
				var _value = value;
				if (_value in button_value_map) {
					_value = button_value_map[value];
				}
				for(var i in this.buttons) {
					this.buttons[i].set('down', i == _value);
				}
			}
			
			return value;
		},
		
		/**
		 * Value attribute getter
		 * 
		 * @returns {String} Selected value
		 * @private
		 */
		_getValue: function (value) {
			var values = this.get('values');
			if (!values || !values.length) {
				// There are no options, so any value will be considered as ok
				return value;
			}
			
			if (this.get('multiple')) {
				var buttons = this.buttons,
					value = [];
				
				for(var i in this.buttons) {
					if (this.buttons[i].get('down')) {
						value.push(i);
					}
				}
				
				return value;
			} else {
				return this.get('inputNode').get('value');
			}
		},
		
		_setDisabled: function (value) {
			value = Input.superclass._setDisabled.apply(this, arguments);
			
			//Disable buttons
			for(var i in this.buttons) {
				this.buttons[i].set('disabled', value);
			}
			
			return value;
		},
		
		/**
		 * Style attribute setter
		 * 
		 * @param {String} value Style value
		 * @returns {String} New style attribute value
		 * @private
		 */
		_setStyle: function (value) {
			var prev = this.get('style'),
				classname = null;
			
			if (prev != value) {
				if (prev) { 
					classname = this.getClassName(prev);
					this.get('boundingBox').removeClass(classname);
				}
				if (value) {
					classname = this.getClassName(value);
					this.get('boundingBox').addClass(classname);
				}
			}
			
			return value;
		}
	});
	
	Supra.Input.SelectList = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto', 'supra.button']});