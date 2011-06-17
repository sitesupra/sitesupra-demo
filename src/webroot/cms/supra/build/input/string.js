YUI.add("supra.input-string", function (Y) {
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = "input-string";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		"replacementNode": {
			value: null
		},
		"useReplacement": {
			value: false
		},
		"valueMask": {
			value: null
		}
	};
	
	Input.HTML_PARSER = {
		"useReplacement": function (srcNode) {
			var use_replacement = srcNode.hasClass("input-label-replacement");
			this.set("useReplacement", use_replacement);
			return use_replacement;
		},
		"replacementNode": function (srcNode) {
			if (srcNode.hasClass("input-label-replacement")) {
				return srcNode.one("span");
			}
			return null;
		}
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<input type="text" value="" />',
		LABEL_TEMPLATE: '<label></label>',
		
		KEY_RETURN: 13,
		KEY_ESCAPE: 27,
		
		bindUI: function () {
			var input = this.get('inputNode');
			
			input.on("focus", this._onFocus, this);
			input.on("blur", this._onBlur, this);
			
			//Clicking on replacement node triggers focuses
			var node = this.get("replacementNode");
			if (node) {
				node.on("click", this._onFocus, this);
			}
			
			//Handle keydown
			input.on("keydown", this._onKeyDown, this);
		},
		
		_onKeyDown: function (e) {
			var key = e.which || e.charCode || e.keyCode,
				input = this.get('inputNode'),
				mask = this.get('valueMask');
			
			if (key == this.KEY_RETURN) {
				input.blur();
			} else if (key == this.KEY_ESCAPE) {
				input.set('value', this._original_value);
				input.blur();
				this.fire("reset");
			} else if (mask) {
				//Validate against mask
				var str = String.fromCharCode(key),
					inputNode = Y.Node.getDOMNode(input),
					value = inputNode.value;
				
				value = value.substr(0, inputNode.selectionStart) + str + value.substr(inputNode.selectionEnd).replace(/^\s*|\s*$/, '');

				if (e.ctrlKey && key == 118) return;
				if (!mask.test(value)) return e.halt();
			}
		},
		
		_onFocus: function () {
			if (this.get('boundingBox').hasClass("yui3-input-focused")) return;
			
			this.get('boundingBox').addClass("yui3-input-focused");
			this.get("inputNode").focus();
		},
		_onBlur: function () {
			this.get('boundingBox').removeClass("yui3-input-focused");
			
			var node = this.get("replacementNode");
			if (node) {
				node.set("innerHTML", Y.Lang.escapeHTML(this.get('value')) || '&nbsp;');
			}
			
			this._original_value = this.get('value');
		},
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			if (this.get("useReplacement")) {
				var node = this.get("replacementNode");
				var srcNode = this.get("srcNode");
				var srcNodeIsInput = srcNode.test("input,select,textarea");
				
				if (!srcNodeIsInput) {
					srcNode.addClass("input-label-replacement");
				}
				
				if (!node) {
					node = Y.Node.create("<span></span>");
					
					if (srcNodeIsInput) {
						node.insertBefore(srcNode);
					} else {
						srcNode.append(node);
						var input = this.get('inputNode');
						if (input) srcNode.append(input);
					}
					
					this.set("replacementNode", node);
				}
				
				node.set("innerHTML", Y.Lang.escapeHTML(this.get("value")) || '&nbsp;');
			}
			
			//Value mask
			if (!this.get('valueMask')) {
				var mask = this.get('inputNode').getAttribute('suValueMask');
				if (mask) {
					this.set('valueMask', new RegExp(mask));
				}
			}
		},
		
		_setValue: function (value) {
			this.get("inputNode").set("value", value);
			var node = this.get("replacementNode");
			if (node) {
				node.set("innerHTML", Y.Lang.escapeHTML(value) || '&nbsp;');
			}
			
			this._original_value = value;
			return value;
		}
		
	});
	
	Supra.Input.String = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto"]});