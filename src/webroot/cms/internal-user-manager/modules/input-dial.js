//Invoke strict mode
"use strict";

YUI.add('website.input-dial', function (Y) {
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'input-dial';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
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
		 * Label list
		 * @type {Object}
		 * @private
		 */
		labels: {},
		
		/**
		 * Dial node
		 * @type {Object}
		 * @private
		 */
		dial: null,
		
		/**
		 * Center node
		 * @type {Object}
		 * @private
		 */
		center: null,
		
		
		bindUI: function () {
			var input = this.get('inputNode');
			input.on('focus', this._onFocus, this);;
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
		
		destroy: function () {
			Input.superclass.destroy.apply(this, arguments);
			
			this.dial.remove();
			delete(this.dial);
			
			this.center.remove();
			delete(this.center);
			
			for(var id in this.labels) this.labels[id].remove();
			delete(this.labels);
		},
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			this.labels = {};
			
			var values = this.get('values'),
				value = this.get('value'),
				has_value_match = false,
				contentBox = this.get('contentBox'),
				label = null,
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
			
			var angle_per_item = (values.length > 1 ? 180 / (values.length - 1) : 90) / 180 * Math.PI,
				angle = 0,
				pos = [0,0],
				style = 'c'
			
			//Center node
			var center = Y.Node.create('<div class="' + Y.ClassNameManager.getClassName(Input.NAME, 'center') + '"></div>');
			this.center = center;
			contentBox.appendChild(center);
			
			//Dial node
			var dial = Y.Node.create('<div class="' + Y.ClassNameManager.getClassName(Input.NAME, 'dial') + '"></div>');
			this.dial = dial;
			center.appendChild(dial);
			
			for(var i=0,ii=values.length-1; i<=ii; i++) {
				label = Y.Node.create('<em></em>');
				label.setAttribute('data-id', values[i].id);
				label.set('text', values[i].title);
				
				this.labels[values[i].id] = label;
				
				angle = (ii - i) * angle_per_item;
				pos = [~~(Math.cos(angle) * 50), ~~(50 - Math.sin(angle) * 50)];
				
				if (pos[0] < 0) {
					label.addClass('pos-l');
					label.setStyles({
						'right': (-pos[0] + 50) + 'px',
						'top': pos[1] + 'px'
					});
				} else if (pos[0] > 0) {
					label.addClass('pos-r');
					label.setStyles({
						'left': (pos[0] + 50) + 'px',
						'top': pos[1] + 'px'
					});
				} else {
					label.addClass('pos-c');
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
				
				center.appendChild(label);
				
				//On click update input value
				label.on('click', function (event, id) {
					this.set('value', id);
				}, this, values[i].id);
			}
			
			if (!has_value_match) {
				if (values.length) {
					value = values[0].id;
					if (input) input.value = value;
					this.set('value', value);
				}
			}
			
			if (value in this.labels) {
				this.labels[value].addClass('selected');
			}
		},
		
		_setValue: function (value) {
			this.get('inputNode').set('value', value);
			
			var labels = this.labels;
			for(var i in labels) {
				if (i == value) {
					labels[i].addClass('selected');
				} else {
					labels[i].removeClass('selected');
				}
			}
			
			//Rotate dial
			if (this.dial) {
				var values = this.get('values'),
					angle_per_item = (values.length > 1 ? 180 / (values.length - 1) : 90),
					index = null,
					i = 0,
					ii = values.length - 1,
					angle = 0;
				
				for(; i<=ii; i++) if (values[i].id == value) {
					angle = ~~((ii - i) * angle_per_item);
					
					if (Y.UA.ie && Y.UA.ie < 10) {
						this.dial.setStyle('msTransform', 'rotate(-' + angle + 'deg)');
					} else {
						this.dial.setStyle('transform', 'rotate(-' + angle + 'deg)');
					}
					
					break;
				}
			}
			
			return value;
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
	
	Supra.Input.Dial = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});