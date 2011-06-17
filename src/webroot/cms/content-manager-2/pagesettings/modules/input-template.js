/**
 * Template selection input
 */
YUI.add("website.input-template", function (Y) {
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
		
		this._templates = null;
	}
	
	Input.NAME = "input-template";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		"previewNode": {
			value: null
		},
		"templateRequestUri": {
			value: ""
		}
	};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		LABEL_TEMPLATE: null,
		
		_templates: null,
		
		_loadTemplates: function () {
			Supra.io(this.get('templateRequestUri'), this._loadTemplatesComplete, this);
		},
		_loadTemplatesComplete: function (transaction, data) {
			this._templates = data;
			this.syncUI();
		},
		
		syncUI: function () {
			Input.superclass.syncUI.apply(this, arguments);
			
			var templates = this._templates;
			if (!templates) {
				this._loadTemplates();
				return;
			}
			
			var value = this.get('value');
			var template_title = '';
			var template_src = '/cms/supra/img/px.gif';
			
			if (!(value in templates)) {
				for(var i in templates) {
					value = i;
					break;
				}
			}
			
			if (value) {
				template_title = templates[value].title;
				template_src = templates[value].thumbnail;
			}
			
			var node = this.get('replacementNode');
			if (node) {
				node.set("innerHTML", Y.Lang.escapeHTML(template_title));
			}
			
			var node = this.get('previewNode');
			if (node) {
				node.set("src", template_src);
			}
		},
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			//Clicking on replacement node or image triggers focuses
			var node = this.get("replacementNode");
			if (node) {
				node.on("click", this._onFocus, this);
			}
			
			var node = this.get("previewNode");
			if (node) {
				node.on("click", this._onFocus, this);
			}
			
			//On change update template title and src
			this.on('change', function () {
				this.syncUI();
			}, this);
		},
		
		_onFocus: function () {
			//TODO
		},
		_onBlur: function () {
			this.get('boundingBox').removeClass("yui3-input-focused");
			this._original_value = this.get('value');
			this.syncUI();
		},
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			//Find or create preview node
			var node = this.get("srcNode").one("img");
			if (!node) {
				node = Y.Node.create("<img src=\"/cms/supra/img/px.gif\" alt=\"\" />");
				this.get("srcNode").prepend(node);
			}
			this.set("previewNode", node);
			
			//Find or create replacement node
			if (this.get("useReplacement")) {
				var node = this.get("srcNode").one("span");
				if (!node) {
					node = Y.Node.create("<span></span>");
					this.get("srcNode").prepend(node);
				}
				node.set("innerHTML", Y.Lang.escapeHTML(this.get("value")));
				this.set("replacementNode", node);
			}
			
			//Make it focusable
			var node = Y.Node.getDOMNode(this.get('srcNode').one('div'));
				node.setAttribute('tabindex', 0);
		},
		
		_setValue: function (value) {
			this.get("inputNode").set("value", value);
			var node = this.get("replacementNode");
			if (node) {
				node.set("innerHTML", Y.Lang.escapeHTML(value));
			}
			
			this._original_value = value;
			return value;
		}
		
	});
	
	Supra.Input.Template = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto"]});