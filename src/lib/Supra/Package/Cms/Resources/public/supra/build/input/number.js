YUI.add("supra.input-number", function (Y) {
	//Invoke strict mode
	"use strict";
	
	var MASK_INTEGER = /^\-?[0-9]*$/,
		MASK_REAL    = /^\-?([0-9]*|[0-9]+[.,][0-9]*)$/;
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = "input-number";
	Input.CLASS_NAME = Input.CSS_PREFIX = 'su-' + Input.NAME;
	
	Input.HTML_PARSER = {
		
		'minValue': function (srcNode) {
			var attr = parseFloat(srcNode.getAttribute('data-min-value'));
			if (!isNaN(attr)) return attr;
		},
		
		'maxValue': function (srcNode) {
			var attr = parseFloat(srcNode.getAttribute('data-max-value'));
			if (!isNaN(attr)) return attr;
		},
		
		'showButtons': function (srcNode) {
			var attr = srcNode.getAttribute('data-show-buttons');
			if (attr === 'true' || attr === '1') return true;
		},
		
		'allowRealNumbers': function (srcNode) {
			var attr = srcNode.getAttribute('data-allow-real-numbers');
			if (attr === 'true' || attr === '1') return true;
		}
		
	};
	Input.ATTRS = {
		/**
		 * Min value
		 */
		'minValue': {
			value: null,
			setter: '_setMinValue'
		},
		
		/**
		 * Max value
		 */
		'maxValue': {
			value: null,
			setter: '_setMaxValue'
		},
		
		/**
		 * Value mask to allow only numbers
		 */
		'valueMask': {
			value: MASK_INTEGER
		},
		
		/**
		 * Default value
		 */
		'defaultValue': {
			value: 0
		},
		
		/**
		 * Add/subtract button step
		 */
		'step': {
			value: 1
		},
		
		/**
		 * Show add/subtract buttons
		 */
		'showButtons': {
			value: true,
			setter: '_setAttrShowButtons'
		},
		
		/**
		 * Allow real numbers, not only integeres
		 */
		'allowRealNumbers': {
			value: false,
			setter: '_setAttrAllowRealNumbers'
		},
		
		/**
		 * Default value
		 */
		'getDefaultValue': {
			value: null,
			getter: '_getDefaultValue'
		}
	};
	
	Y.extend(Input, Supra.Input.String, {
		
		/**
		 * Button to add 1 to the number
		 * @see Supra.Button
		 * @type {Object}
		 * @private
		 */
		button_add: null,
		
		/**
		 * Button to subtract 1 from the number
		 * @see Supra.Button
		 * @type {Object}
		 * @private
		 */
		button_sub: null,
		
		/**
		 * Add buttons
		 */
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			var contentBox = this.get('contentBox'),
				tmp = null;
			
			if (contentBox.test('input')) {
				tmp = Y.Node.create('<div></div>');
				tmp.addClass(this.getClassName('content'));
				contentBox.removeClass(this.getClassName('content'));
				contentBox.insert(tmp, 'before');
				tmp.append(contentBox);
				
				contentBox = tmp;
			}
			
			//Add +/- buttons
			this.button_add = new Supra.Button({'label': '+', 'style': 'small'});
			this.button_add.render(contentBox);
			this.button_add.addClass('button-add');
			this.button_add.on('click', this._addOne, this);
			
			this.button_sub = new Supra.Button({'label': '-', 'style': 'small'});
			this.button_sub.render(contentBox);
			this.button_sub.addClass('button-sub');
			this.button_sub.on('click', this._subOne, this);
			
			if (!this.get('showButtons')) {
				this.button_add.hide();
				this.button_sub.hide();
			} else {
				this.addClass(this.getClassName('buttons-visible'));
			}
			
			if (this.get('allowRealNumbers')) {
				this.set('valueMask', MASK_REAL);
			}
		},
		
		/**
		 * Handle number value change using keys
		 * 
		 * @param {String} value New value
		 * @returns {String} New value
		 * @private
		 */
		_onKeyDownNumberChange: function (value) {
			value = this._validateValue(value);
			this._uiUpdateButtonStates(value);
			return value;
		},
		
		/**
		 * Update button states
		 * 
		 * @param {String} value Value
		 * @private
		 */
		_uiUpdateButtonStates: function (value) {
			var min   = this.get('minValue'),
				max   = this.get('maxValue');
			
			if (this.button_add) {
				this.button_add.set('disabled', max !== null && max == value);
			}
			if (this.button_sub) {
				this.button_sub.set('disabled', min !== null && min == value);
			}
		},
		
		/**
		 * Value setter.
		 * 
		 * @param {String} value Value
		 * @return New value
		 * @type {Number}
		 * @private
		 */
		_setValue: function (value) {
			var value = this._validateValue(value);
			
			this.get('inputNode').set('value', value);
			
			var node = this.get('replacementNode');
			if (node) {
				node.set('innerHTML', Y.Escape.html(value) || '0');
			}
			
			this._uiUpdateButtonStates(value);
			
			this._original_value = value;
			return value;
		},
		
		/**
		 * Value getter
		 * 
		 * @return Value
		 * @type {Number}
		 * @private
		 */
		_getValue: function () {
			var value = this.get('inputNode').get('value');
			return this._validateValue(value);
		},
		
		/**
		 * Validate value to make sure it's in min-max range
		 * 
		 * @param {Number} value Value
		 * @return Correct value
		 * @type {Number}
		 * @private
		 */
		_validateValue: function (value) {
			if (typeof value === 'string') {
				// Decimal point must be dot for parseFloat to work
				value = value.replace(',', '.');
			}
			
			var value = this.get('allowRealNumbers') ? parseFloat(value) : parseInt(value, 10),
				min   = this.get('minValue'),
				max   = this.get('maxValue');
			
			if (isNaN(value)) {
				value = this.get('defaultValue');
			}
			
			//Swap min and max if needed
			if (min !== null && max !== null && min > max) {
				var tmp = min; min = max; max = tmp;
			}
			
			if (min !== null) value = Math.max(value, min);
			if (max !== null) value = Math.min(value, max);
			
			return value;
		},
		
		/**
		 * Min value setter
		 * To remove min value validation set to null
		 * 
		 * @param {Number} min Min value
		 * @returns {Number} Min value
		 * @private
		 */
		_setMinValue: function (min) {
			var value = this.get('value');
			if (min !== null) {
				min = parseFloat(min);
				if (value < min) this.set('value', min);
			}
			return min;
		},
		
		/**
		 * Max value setter
		 * To remove max value validation set to null
		 * 
		 * @param {Number} min Max value
		 * @returns {Number} Max value
		 * @private
		 */
		_setMaxValue: function (max) {
			var value = this.get('value');
			if (max !== null) {
				max = parseFloat(max);
				if (value > max) this.set('value', max);
			}
			return max;
		},
		
		/**
		 * allowRealNumbers attribute setter
		 * 
		 * @param {Boolean} allow Allow real numbers
		 * @returns {Boolean} Attribute value
		 * @private
		 */
		_setAttrAllowRealNumbers: function (allow) {
			if (allow) {
				this.set('valueMask', MASK_REAL);
			} else {
				this.set('valueMask', MASK_INTEGER);
			}
			
			return allow;
		},
		
		/**
		 * Show buttons attribute setter
		 * 
		 * @param {Boolean} show Attribute value
		 * @returns {Boolean} Attribute value
		 * @private
		 */
		_setAttrShowButtons: function (show) {
			if (this.button_sub) {
				if (show) {
					this.button_add.show();
					this.button_sub.show();
					this.addClass(this.getClassName('buttons-visible'));
				} else {
					this.button_add.hide();
					this.button_sub.hide();
					this.removeClass(this.getClassName('buttons-visible'));
				}
			}
			return show;
		},
		
		/**
		 * Default value attribute getter
		 * 
		 * @returns {String} Default value
		 * @private
		 */
		_getDefaultValue: function () {
			var min = this.get('minValue');
			return min !== null ? min : 0;
		},
		
		/**
		 * Add one to the number
		 * 
		 * @private
		 */
		_addOne: function () {
			var value = this.get('value'),
				max   = this.get('maxValue'),
				next  = value + this.get('step');
			
			if (max !== null) {
				next = Math.min(next, max);
			}
			
			if (next != value) {
				this.set('value', next);
			}
		},
		
		/**
		 * Remove one from the number
		 * 
		 * @private
		 */
		_subOne: function () {
			var value = this.get('value'),
				min = this.get('minValue'),
				prev  = value - this.get('step');
			
			if (min !== null) {
				prev = Math.max(prev, min);
			}
			
			if (prev != value) {
				this.set('value', prev);
			}
		}
		
	});
	
	Supra.Input.Number = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-string"]});