//Invoke strict mode
"use strict";

YUI.add("supra.input-checkbox", function (Y) {
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = "input-checkbox";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<input type="checkbox" value="1" />',
		
		_original_value: null,
		
		bindUI: function () {
			var r = Input.superclass.bindUI.apply(this, arguments);
			
			var input = this.get('inputNode');
			
			//On value change fire "change" event
			input.on("click", function () {
				this.fire("change", {value: this.get("value")});
			}, this);
			
			return r;
		},
		
		renderUI: function () {
			var r = Input.superclass.renderUI.apply(this, arguments);
			return r;
		},
		
		_getValue: function () {
			return this.get("inputNode").get("checked");
		},
		
		_setValue: function (value) {
			var value = !!value;
			this.get("inputNode").set("checked", value);
			
			this._original_value = value;
			return value;
		}
		
	});
	
	Supra.Input.Checkbox = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto"]});