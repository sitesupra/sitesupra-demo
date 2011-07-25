//Invoke strict mode
"use strict";
	
YUI.add("supra.input-proto", function (Y) {
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
		
		this._original_value = null;
	}
	
	Input.NAME = "input";
	Input.ATTRS = {
		"inputNode": {
			value: null
		},
		"labelNode": {
			value: null
		},
		"value": {
			value: "",
			setter: "_setValue",
			getter: "_getValue"
		},
		"saveValue": {
			value: "",
			getter: "_getSaveValue"
		},
		"defaultValue": {
			value: null
		},
		"disabled": {
			value: null,
			setter: "_setDisabled"
		},
		"label": {
			value: null,
			setter: "_setLabel"
		},
		"validationRules": {
			value: [],
			setter: "_processValidationRules"
		},
		"id": {
			value: null
		}
	};
	
	Input.HTML_PARSER = {
		"inputNode": function (srcNode) {
			var inp = srcNode;
			if (!srcNode.test('input,select,textarea')) {
				inp = srcNode.one("input") || srcNode.one("select") || srcNode.one("textarea");
			}
			
			this.set("inputNode", inp);
			return inp;
		},
		"labelNode": function (srcNode) {
			var label = this.get('labelNode');
			if (!label) {
				var label = srcNode.one("label");
				if (!label) {
					label = srcNode.previous();
					if (label && !label.test('label')) label = null;
				}
				this.set("labelNode", label);
			}
			return label;
		},
		"disabled": function (srcNode) {
			var val = this.get("disabled");
			var inp = this.get("inputNode");
			
			if (inp) {
				if (val === null) {
					return inp.get("disabled");
				} else {
					this.set("disabled", val);
				}
			}
			
			return !!val;
		}
	};
	
	Y.extend(Input, Y.Widget, {
		INPUT_TEMPLATE: '<input type="text" value="" />',
		LABEL_TEMPLATE: '<label></label>',
		
		_original_value: null,
		
		bindUI: function () {
			var r = Input.superclass.bindUI.apply(this, arguments);
			
			var input = this.get('inputNode');
			
			//On value change fire "change" event if srcNode is not input
			//because it's automatically fired in that case
			if (!this.get('srcNode').test('input,textarea,select')) {
				input.on("change", function () {
					this.fire("change", {value: this.get("value")});
				}, this);
			}
			
			//On Input focus, focus input element
			this.on('focusedChange', function (event) {
				if (event.newVal && event.newVal != event.prevVal) {
					this.get('inputNode').focus();
				}
			}, this);
			
			//On input element blur, blur Input
			input.on('blur', this.blur, this);
			
			return r;
		},
		
		/**
		 * Show error message
		 * 
		 * @param {String} message
		 */
		showError: function (message) {
			
		},
		
		/**
		 * Hide error message
		 */
		hideError: function () {
			
		},
		
		renderUI: function () {
			var r = Input.superclass.renderUI.apply(this, arguments);
			var inp = this.get("inputNode");
			var lbl = this.get("labelNode");
			var cont = this.get("contentBox");
			var bound = this.get("boundingBox");
			
			if (!inp && this.INPUT_TEMPLATE) {
				inp = Y.Node.create(this.INPUT_TEMPLATE);
				cont.prepend(inp);
				this.set("inputNode", inp);
			}
			
			if (inp && !lbl && this.LABEL_TEMPLATE) {
				var id = inp.getAttribute("id");
				
				lbl = Y.Node.create(this.LABEL_TEMPLATE);
				lbl.setAttribute("for", id);
				lbl.set('innerHTML', this.get('label') || '');
				
				if (cont.compareTo(inp)) {
					inp.insert(lbl, 'before');
				} else {
					cont.prepend(lbl);
				}
				
				this.set("labelNode", lbl);
			}
			
			if (this.get("disabled")) {
				this.set("disabled", true);
			}
			
			//Move label inside bounding box
			if (lbl && inp && cont.compareTo(inp)) {
				bound.prepend(lbl);
			}
			
			//Add classnames
			bound.addClass(Y.ClassNameManager.getClassName('input'));
			bound.addClass(Y.ClassNameManager.getClassName(this.constructor.NAME));
			
			
			this.set("value", this.get("value"));
			
			return r;
		},
		
		getAttribute: function (key) {
			return this.get("inputNode").getAttribute(key);
		},
		
		addClass: function (c) {
			this.get('boundingBox').addClass(c);
			return this;
		},
		
		removeClass: function (c) {
			this.get('boundingBox').removeClass(c);
			return this;
		},
		
		hasClass: function (c) {
			return this.get('boundingBox').hasClass(c);
		},
		
		toggleClass: function (c) {
			this.get('boundingBox').toggleClass(c);
			return this;
		},
		
		_setDisabled: function (value) {
			var node = this.get("inputNode");
			if (node) {
				node.set("disabled", !!value);
			}
			
			if (value) {
				this.get('boundingBox').addClass("yui3-input-disabled");
			} else {
				this.get('boundingBox').removeClass("yui3-input-disabled");
			}
			
			return !!value;
		},
		
		_getValue: function () {
			return this.get("inputNode").get("value");
		},
		
		_getSaveValue: function () {
			return this.get("value");
		},
		
		_setValue: function (value) {
			var value = !!value;
			this.get("inputNode").set("value", value);
			
			this._original_value = value;
			return value;
		},
		
		_setLabel: function (lbl) {
			var node = this.get('labelNode');
			if (node) node.set('innerHTML', Y.Lang.escapeHTML(lbl));
			
			return lbl;
		},
		
		/**
		 * Set input value
		 * 
		 * @param {Object} value
		 */
		setValue: function (value) {
			this.set('value', value);
			return this;
		},
		
		/**
		 * Returns input value
		 * 
		 * @return Input value
		 * @type {Object}
		 */
		getValue: function () {
			return this.get('value');
		},
		
		/**
		 * Reset value to default
		 */
		resetValue: function () {
			this.setValue(this.get('defaultValue') || '');
			return this;
		},
		
		/**
		 * Set label
		 * 
		 * @param {String} label
		 */
		setLabel: function (label) {
			this.set('label', label);
			return this;
		},
		
		/**
		 * Returns label
		 * 
		 * @return Label
		 * @type {String}
		 */
		getLabel: function () {
			return this.get('label');
		},
		
		/**
		 * Disable/enable input
		 * 
		 * @param {Boolean} disabled
		 */
		setDisabled: function (disabled) {
			this.set('disabled', disabled);
			return this;
		},
		
		/**
		 * Returns true if input is disabled, otherwise false
		 * 
		 * @return True if input is disabled
		 * @type {Boolean}
		 */
		getDisabled: function () {
			return this.get('disabled');
		},
		
		/**
		 * Add validation rule
		 * 
		 * @param {Object} rule
		 */
		addValidationRule: function (rule) {
			//@TODO
			return this;
		},
		
		/**
		 * Add validation rules
		 * 
		 * @param {Array} rules
		 */
		addValidationRules: function (rules) {
			//@TODO
			return this;
		},
		
		/**
		 * Returns input validation rules
		 * 
		 * @return Array with validation rules
		 * @type {Array}
		 */
		getValidationRules: function () {
			//@TODO
		},
		
		/**
		 * Validate input value against validation rules
		 * 
		 * @return True on success, false on failure
		 * @type {Boolean}
		 */
		validate: function () {
			//@TODO
		},
		
		/**
		 * Returns value as string
		 * 
		 * @return Value
		 * @type {String}
		 */
		toString: function () {
			return String(this.getValue());
		}
		
	});
	
	Supra.Input = {
		"Proto": Input
	};
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["widget"]});