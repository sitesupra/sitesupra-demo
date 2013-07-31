YUI.add('supra.input-slider', function (Y) {
	//Invoke strict mode
	"use strict";
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'input-slider';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		/**
		 * Value/option list
		 */
		'values': {
			value: []
		},
		/**
		 * Slider length in px
		 */
		'length': {
			value: 200
		},
		
		/**
		 * Label "Less"
		 */
		'labelLess': {
			value: ''
		},
		
		/**
		 * Label "More"
		 */
		'labelMore': {
			value: ''
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
		'length': function (srcNode) {
			var input = this.get('inputNode');
			return input.getAttribute('data-length') || 200;
		},
		'labelLess': function (srcNode) {
			var input = this.get('inputNode');
			return input.getAttribute('data-label-less') || '';
		},
		'labelMore': function (srcNode) {
			var input = this.get('inputNode');
			return input.getAttribute('data-label-more') || '';
		}
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<select class="hidden"></select>',
		LABEL_TEMPLATE: '<label></label>',
		
		/**
		 * Slider instance
		 * @see Y.Slider
		 * @type {Object}
		 * @private
		 */
		slider: null,
		
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
				index = 0,
				has_value_match = false,
				contentBox = this.get('contentBox'),
				input = Y.Node.getDOMNode(this.get('inputNode'));
			
			//No need to add options if they already exist
			if (!input.options || input.options.length) {
				input = null;
			}
			
			if (contentBox.test('input,select')) {
				contentBox = this.get('boundingBox');
			}
			
			//Slide will be placed instead of input
			this.get('inputNode').addClass('hidden');
			
			for(var i=0,ii=values.length-1; i<=ii; i++) {
				
				if (input) {
					//Add options to allow selecting value
					input.options[input.options.length] = new Option(values[i].title, values[i].id);
					if (value == values[i].id) input.value = value;
				}
				
				if (values[i].id == value) {
					//Mark value as found
					has_value_match = true;
					index = i;
				}
				
			}
			
			//
			var labelLess = this.get('labelLess'),
				labelMore = this.get('labelMore');
			
			if (labelLess) {
				var node = Y.Node.create('<span class="less">' + labelLess + '</span>');
				contentBox.append(node);
				node.on('click', this._setValueMinus, this);
			}
			
			//Create slider
			this.slider = new Supra.Slider({
				'axis': 'x',
				'min': 0,
				'max': values.length - 1,
				'value': index,
				'length': this.get('length')
			});
			this.slider.after('slideEnd', this._onChange, this);
			this.slider.after('slideEnd', this.slider.syncUI, this.slider);
			this.slider.render(contentBox);
			
			if (labelMore) {
				var node = Y.Node.create('<span class="more">' + labelMore + '</span>');
				contentBox.append(node);
				node.on('click', this._setValuePlus, this);
			}
			
			//Set value
			if (!has_value_match) {
				if (values.length) {
					value = values[0].id;
					if (input) input.value = value;
				}
			}
			
		},
		
		/**
		 * Move to next value
		 * 
		 * @private
		 */
		_setValuePlus: function (e) {
			if (this.slider && !this.get('disabled')) {
				var index = this.slider.get('value'),
					values = this.get('values');
				
				if (index < values.length - 1) {
					this.set('value', values[index + 1].id);
				}
			}
			if (e) e.halt();
		},
		
		/**
		 * Move to previous value
		 * 
		 * @private
		 */
		_setValueMinus: function (e) {
			if (this.slider && !this.get('disabled')) {
				var index = this.slider.get('value'),
					values = this.get('values');
				
				if (index > 0) {
					this.set('value', values[index - 1].id);
				}
			}
			if (e) e.halt();
		},
		
		_onChange: function (event) {
			var index = this.slider.get('value'),
				values = this.get('values'),
				value = values[index].id;
			
			this.set('value', value);
		},
		
		_setValue: function (value) {
			this.get('inputNode').set('value', value);
			
			if (this.slider) {
				var index = 0,
					values = this.get('values');
				
				for(var i=0,ii=values.length; i<ii; i++) if (values[i].id == value) {
					index = i;
					break;
				}
				
				if (this.slider.get('value') != index) {
					this.slider.set('value', index);
				}
			}
			
			return value;
		},
		
		_getValue: function () {
			return this.get('inputNode').get('value');
		},
		
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal});
			}
		},
		
		_setDisabled: function (value) {
			value = Input.superclass._setDisabled.apply(this, arguments);
			
			//Disable slider
			if (this.slider) this.slider.set('disabled', value);
			
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
	
	Supra.Input.Slider = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto', 'supra.slider']});