//Invoke strict mode
"use strict";

YUI.add("supra.input-select", function (Y) {
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = "input-select";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		"values": {
			value: [],
			setter: '_setValues'
		}
	};
	
	Input.HTML_PARSER = {
		"values": function () {
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
	
	Y.extend(Input, Supra.Input.String, {
		INPUT_TEMPLATE: '<select></select>',
		LABEL_TEMPLATE: '<label></label>',
		
		
		/**
		 * Add nodes needed for widget
		 */
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			this.set('values', this.get('values'));
		},
		
		/**
		 * Values attribute setter
		 * 
		 * @param {Array} values
		 * @return New values
		 * @type {Array}
		 * @private
		 */
		_setValues: function (values) {
			if (!Y.Lang.isArray(values)) values = [];
			
			var inputNode = this.get('inputNode');
			if (inputNode) {
				var domNode = Y.Node.getDOMNode(inputNode),
					value = this.get('value');
				
				domNode.options = [];
				
				for(var i=0,ii=values.length; i<ii; i++) {
					domNode.options[i] = new Option(values[i].title, values[i].id, values[i].id == value);
				}
			}
			
			return values;
		},
		
		/**
		 * Reset value to default
		 */
		resetValue: function () {
			var value = this.get('defaultValue'),
				values = this.get('values');
			
			this.set('value', value !== null ? value : (values.length ? values[0].id : ''));
			return this;
		}
		
	});
	
	Supra.Input.Select = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-string"]});