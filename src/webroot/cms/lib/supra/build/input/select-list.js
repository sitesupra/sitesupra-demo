//Invoke strict mode
"use strict";

YUI.add('supra.input-select-list', function (Y) {
	
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
			Input.superclass.renderUI.apply(this, arguments);
			
			if (!this.buttons_rendered) {
				this.renderButtons(this.get('values'));
			}
		},
		
		_setValues: function (values) {
			this.renderButtons(values);
			return values;
		},
		
		_onFocus: function () {
			if (this.get('boundingBox').hasClass('yui3-input-focused')) return;
			
			this.get('boundingBox').addClass('yui3-input-focused');
			this.get('inputNode').focus();
		},
		_onBlur: function () {
			this.get('boundingBox').removeClass('yui3-input-focused');
		},
		
		renderButtons: function (values) {
			
			//Remove old buttons
			if (this.buttons) {
				for(var i in this.buttons) {
					this.buttons[i].destroy();
				}
			}
			
			this.buttons = {};
			
			var value = null,
				has_value_match = false,
				input = Y.Node.getDOMNode(this.get('inputNode'));
			
			if (this.buttons_rendered && input.options && input.options.length) {
				//Remove old options
				while(input.options.length) {
					input.remove(input.options[0]);
				}
			} else if (!this.buttons_rendered) {
				//No need to remove options if this is initial render
				input = null;
			}
			
			
			//Buttons will be placed instead of input
			this.get('inputNode').addClass('hidden');
			
			var button_width = 100 / values.length;
			
			for(var i=0,ii=values.length-1; i<=ii; i++) {
				if (this.renderButton(input, values[i], i == 0, i == ii, button_width)) {
					has_value_match = true;
				}
			}
			
			if (!has_value_match) {
				if (values.length) {
					value = values[0].id;
					if (input) input.value = value;
					this.set('value', value);
				}
			}
			
			if (value in this.buttons) {
				this.buttons[value].set('down', true);
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
			button.get('boundingBox').setStyle('width', button_width + '%');
			
			//On click update input value
			button.on('click', this._onClick, this, definition.id);
			
			return has_value_match;
		},
		
		_onClick: function (event, id) {
			if (this.get('multiple')) {
				this.set('value', this.get('value'));
			} else {
				this.set('value', id);
			}
		},
		
		_setValue: function (value) {
			
			// Convert boolean values to string
			if (typeof value == 'boolean') {
				value = value ? "1" : "0";
			}
			
			//Input value is not valid if 'multiple' attribute is true
			this.get('inputNode').set('value', value);
			
			if (this.get('multiple') && Y.Lang.isArray(value)) {
				//Update button states
				for(var i in this.buttons) {
					this.buttons[i].set('down', Y.Array.indexOf(value, i) != -1);
				}
			} else {
				for(var i in this.buttons) {
					this.buttons[i].set('down', i == value);
				}
			}
			
			return value;
		},
		
		_getInternalValue: function () {
			return this.get('value');
		},
		
		_getValue: function () {
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
		
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal});
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
		
	});
	
	Supra.Input.SelectList = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto', 'supra.button']});