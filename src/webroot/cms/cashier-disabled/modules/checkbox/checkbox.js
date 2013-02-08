//Invoke strict mode
"use strict";

YUI.add("website.input-checkbox-standard", function (Y) {
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = "input-checkbox-standard";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		/**
		 * Default value
		 */
		'defaultValue': {
			value: true
		}
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			//Style
			if (this.get('value')) {
				this.get('boundingBox').addClass('checked');
			}
			
			//On key press change selected value
			this.get('boundingBox').on('keyup', this._onKeyUp, this);
			this.get('boundingBox').on('click', this._toggleValue, this);
			
			//Hide INPUT or SELECT element
			this.get('inputNode').addClass('hidden');
		},
		
		/**
		 * Toggle value
		 * 
		 * @private
		 */
		_toggleValue: function () {
			this.set('value', !this.get('value'));
		},
		
		/**
		 * Value getter.
		 * Returns value as boolean
		 * 
		 * @return Value
		 * @type {Boolean}
		 */
		_getValue: function () {
			return this.get('inputNode').get('checked') ? 1 : 0;
		},
		
		/**
		 * Value setter.
		 * 
		 * @param {Boolean} value Value
		 * @return New value
		 * @type {Boolean}
		 * @private
		 */
		_setValue: function (value) {
			value = (value === true || value == '1') ? 1 : 0;
			
			if (!this.get('rendered')) return value;
			
			//Check
			this.get('inputNode').set('checked', !!value);
			
			//Update style
			var node = this.get('boundingBox');
			if (node) node.toggleClass('checked', value);
			
			//Trigger event
			this.fire('change', {'value': value});
			
			return value;
		},
		
		_onKeyUp: function (event) {
			if (this.get('disabled')) return;
			
			var key = event.keyCode;
			
			if (key == 32 || key == 13) {	//Space or return key
				this.set('value', !this.get('value'));
			}
		},
		
		/**
		 * After value change trigger event
		 */
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal === true || evt.newVal == '1' ? true : false});
			}
		}
		
	});
	
	Supra.Input.CheckboxStandard = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto", "anim"]});