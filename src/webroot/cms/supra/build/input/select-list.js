//Invoke strict mode
"use strict";

YUI.add("supra.input-select-list", function (Y) {
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = "input-select-list";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		"values": {
			value: []
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
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<select class="hidden"></select>',
		LABEL_TEMPLATE: '<label></label>',
		
		/**
		 * Button list
		 * @type {Object}
		 * @private
		 */
		buttons: {},
		
		bindUI: function () {
			var input = this.get('inputNode');
			input.on("focus", this._onFocus, this);
			input.on("blur", this._onBlur, this);
		},
		
		_onFocus: function () {
			if (this.get('boundingBox').hasClass("yui3-input-focused")) return;
			
			this.get('boundingBox').addClass("yui3-input-focused");
			this.get("inputNode").focus();
		},
		_onBlur: function () {
			this.get('boundingBox').removeClass("yui3-input-focused");
		},
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			this.buttons = {};
			
			var values = this.get('values'),
				value = this.get('value'),
				contentBox = this.get('contentBox'),
				button,
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
			
			for(var i=0,ii=values.length-1; i<=ii; i++) {
				button = new Supra.Button({"label": values[i].title, "icon": values[i].icon, "type": "toggle", "style": "group"});
				this.buttons[values[i].id] = button;
				
				if (i == 0) {
					button.get('boundingBox').addClass('yui3-button-first');
				}
				if (i == ii) {
					button.get('boundingBox').addClass('yui3-button-last');
				}
				
				if (input) {
					//Add options to allow selecting value
					input.options[input.options.length] = new Option(values[i].title, values[i].id);
					if (value == values[i].id) input.value = value;
				}
				
				button.render(contentBox);
				
				//On click update input value
				button.on('click', function (event, id) {
					this.set('value', id);
				}, this, values[i].id);
			}
			
			if (value in this.buttons) {
				this.buttons[value].set('down', true);
			}
		},
		
		_setValue: function (value) {
			this.get("inputNode").set("value", value);
			this.fire("change", {value: value});
			
			var buttons = this.buttons;
			for(var i in this.buttons) {
				this.buttons[i].set('down', i == value);
			}
			
			return value;
		},
		
		/**
		 * Reset value to default
		 */
		resetValue: function () {
			var value = this.get('defaultValue'),
				values = this.get('values');
			
			this.setValue(value !== null ? value : (values.length ? values[0].id : ''));
			return this;
		},
		
	});
	
	Supra.Input.SelectList = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto", "supra.button"]});