//Invoke strict mode
"use strict";

YUI.add('supra.input-select-list', function (Y) {
	
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
			value: []
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
		
		bindUI: function () {
			var input = this.get('inputNode');
			input.on('focus', this._onFocus, this);
			input.on('blur', this._onBlur, this);
			
			//Handle value attribute change
			this.on('valueChange', this._afterValueChange, this);
		},
		
		_onFocus: function () {
			if (this.get('boundingBox').hasClass('yui3-input-focused')) return;
			
			this.get('boundingBox').addClass('yui3-input-focused');
			this.get('inputNode').focus();
		},
		_onBlur: function () {
			this.get('boundingBox').removeClass('yui3-input-focused');
		},
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			this.buttons = {};
			
			var values = this.get('values'),
				value = this.get('value'),
				multiple = this.get('multiple'),
				has_value_match = false,
				contentBox = this.get('contentBox'),
				button,
				input = Y.Node.getDOMNode(this.get('inputNode'));
			
			//No need to add options if they already exist
			if (!input.options || input.options.length) {
				input = null;
			}
			
			if (contentBox.test('input,select')) {
				contentBox = this.get('boundingBox');
			}
			
			//Buttons will be placed instead of input
			this.get('inputNode').addClass('hidden');
			
			for(var i=0,ii=values.length-1; i<=ii; i++) {
				button = new Supra.Button({'label': values[i].title, 'icon': values[i].icon, 'type': 'toggle', 'style': 'group'});
				this.buttons[values[i].id] = button;
				
				if (i == 0) {
					button.get('boundingBox').addClass('yui3-button-first');
				}
				if (i == ii) {
					button.get('boundingBox').addClass('yui3-button-last');
				}
				
				if (input) {
					//Add options to allow selecting value
					input.options[input.options.length] = new Option(values[i].title, values[i].id);
					if (value == values[i].id) input.value = value;
				}
				
				if (values[i].id == value) {
					//Mark value as found
					has_value_match = true;
				}
				
				button.render(contentBox);
				
				//On click update input value
				button.on('click', this._onClick, this, values[i].id);
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
		},
		
		_onClick: function (event, id) {
			if (this.get('multiple')) {
				this.set('value', this.get('value'));
			} else {
				this.set('value', id);
			}
		},
		
		_setValue: function (value) {
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
		
	});
	
	Supra.Input.SelectList = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto', 'supra.button']});