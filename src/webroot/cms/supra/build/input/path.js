YUI.add("supra.input-path", function (Y) {
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = "input-path";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		"path": {
			value: "",
			setter: '_setPath'
		},
		"pathNode": {
			value: null
		}
	};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.String, {
		
		_setPath: function (value) {
			var node = this.get('pathNode'),
				input = this.get('inputNode');
			
			if (!node) {
				node = input.previous('small');
				if (!node) {
					node = Y.Node.create('<small></small>');
					input.insert(node, 'before');
				}
				this.set('pathNode', node);
			}
			
			if (node) {
				node.set("innerHTML", Y.Lang.escapeHTML(value));
			}
			
			return value;
		},
		
		_onFocus: function () {
			Input.superclass._onFocus.apply(this, arguments);
			
			var node = this.get("replacementNode");
			if (node) {
				node.set("innerHTML", '<small>' + Y.Lang.escapeHTML(this.get('path')) + '</small>');
			}
		},
		_onBlur: function () {
			var input = this.get("inputNode");
			this.set('value', input.get('value').replace(/[^a-z0-9\-\_]/gi, ''));
			
			Input.superclass._onBlur.apply(this, arguments);
			
			var node = this.get("replacementNode");
			if (node) {
				node.set("innerHTML", '<small>' + this.get('path') + '</small>' + this.get('value'));
			}
		},
		
		bindUI: function () {
			var r = Input.superclass.bindUI.apply(this, arguments);
			
			return r;
		},
		
		renderUI: function () {
			var r = Input.superclass.renderUI.apply(this, arguments);
			
			//Replacement text
			var replacement_node = this.get('replacementNode');
			if (replacement_node) {
				replacement_node.set("innerHTML", '<small>' + Y.Lang.escapeHTML(this.get('path')) + '</small>' + Y.Lang.escapeHTML(this.get('value')));
			}
			
			//Path text
			var path = this.get('path');
			if (path && !this.get('useReplacement')) {
				this._setPath(path);
			}
			
			return r;
		}
		
	});
	
	Supra.Input.Path = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto", "supra.input-string"]});