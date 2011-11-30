//Invoke strict mode
"use strict";

YUI.add("supra.input-checkbox", function (Y) {
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = "input-checkbox";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.HTML_PARSER = {};
	Input.ATTRS = {
		/**
		 * Selecting multiple values is not allowed
		 */
		'multiple': {
			readOnly: true,
			value: false
		},
		
		/**
		 * Value/option list
		 */
		'labels': {
			value: ['{#buttons.yes#}', '{#buttons.no#}'],
			setter: '_setLabels'
		},
		
		/**
		 * Default value
		 */
		'defaultValue': {
			value: true
		}
	};
	
	Y.extend(Input, Supra.Input.SelectList, {
		INPUT_TEMPLATE: '<input type="checkbox" value="1" />',
		
		_original_value: null,
		
		renderUI: function () {
			Supra.Input.SelectList.superclass.renderUI.apply(this, arguments);
			
			if (!this.buttons_rendered) {
				this.set('labels', this.get('labels'));
			}
		},
		
		_setLabels: function (labels) {
			if (labels.length == 2) {
				var values = [
					{"id": "1", "title": labels[0]},
					{"id": "0", "title": labels[1]}
				];
				this.set('values', values);
			}
			return labels;
		},
		
		_getInternalValue: function () {
			return this.get('value') ? '1' : '0';
		},
		
		_getValue: function () {
			return !!this.get('inputNode').get('checked');
		},
		
		_setValue: function (value) {
			//Check
			this.get('inputNode').set('checked', value === true || value == '1' ? true : false);
			
			//Update style
			return Input.superclass._setValue.apply(this, [value === true || value == '1' ? '1' : '0']);
		}
		
	});
	
	Supra.Input.Checkbox = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto", "supra.input-select-list"]});