//Invoke strict mode
"use strict";

YUI.add("supra.input-hidden", function (Y) {
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = "input-hidden";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		LABEL_TEMPLATE: '<label></label>',
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			//Handle value attribute change
			this.on('valueChange', this._afterValueChange, this);
		},
		
		_setValue: function (value) {
			this.get("inputNode").set("value", value);
			
			this._original_value = value;
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