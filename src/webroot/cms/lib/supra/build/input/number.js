YUI.add("supra.input-number", function (Y) {
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
	
	Input.NAME = "input-number";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.HTML_PARSER = {};
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
			value: /^\-?[0-9]*$/
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
			
			//Add +/- buttons
			this.button_add = new Supra.Button({'label': '+', 'style': 'small'});
			this.button_add.render(this.get('contentBox'));
			this.button_add.addClass('button-add');
			this.button_add.on('click', this._addOne, this);
			
			this.button_sub = new Supra.Button({'label': '-', 'style': 'small'});
			this.button_sub.render(this.get('contentBox'));
			this.button_sub.addClass('button-sub');
			this.button_sub.on('click', this._subOne, this);
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
			var value = parseInt(value, 10),
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
		 * @return Min value
		 * @type {Number}
		 */
		_setMinValue: function (min) {
			var value = this.get('value');
			if (min !== null) {
				min = parseInt(min, 10);
				if (value < min) this.set('value', min);
			}
			return min;
		},
		
		/**
		 * Max value setter
		 * To remove max value validation set to null
		 * 
		 * @param {Number} min Max value
		 * @return Max value
		 * @type {Number}
		 */
		_setMaxValue: function (max) {
			var value = this.get('value');
			if (max !== null) {
				max = parseInt(max, 10);
				if (value > max) this.set('value', max);
			}
			return max;
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