//Invoke strict mode
"use strict";

/**
 * Dial input type
 */
YUI.add("website.input-dial", function (Y) {
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = "input-dial";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		LABEL_TEMPLATE: '',
		
		/**
		 * Dial widget instance
		 * @type {Object}
		 * @private
		 */
		dial: null,
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			//Handle value attribute change
			this.on('valueChange', this._afterValueChange, this);
		},
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			var contentBox = this.get('contentBox');
			if (contentBox.test('input,select')) {
				contentBox = this.get('boundingBox');
			}
			
			this.dial = new y.Dial({
				'min': -1,
				'max': 1,
				'stepsPerRev': 4,
				'value': 1
			});
			this.dial.render(contentBox);
		},
		
		_getValue: function (value) {
			return value;
		},
		
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal});
			}
		}
	});
	
	Supra.Input.Hidden = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto"]});