//Invoke strict mode
"use strict";
	
YUI.add("supra.input-inline-html", function (Y) {
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = "input-html-inline";
	Input.ATTRS = {
		'doc': null,
		'win': null,
		'toolbar': null
	};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '',
		LABEL_TEMPLATE: '',
		
		htmleditor: null,
		
		bindUI: function () {
			
		},
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			this.set('boundingBox', this.get('srcNode'));
			
			var doc = this.get('doc'),
				win = this.get('win'),
				src = this.get('srcNode'),
				toolbar = this.get('toolbar');
			
			if (doc && win && src) {
				this.htmleditor = new Supra.HTMLEditor({
					'doc': doc,
					'win': win,
					'srcNode': src,
					'toolbar': toolbar,
				});
				this.htmleditor.render();
				this.htmleditor.set('disabled', true);
			}
		},
		
		getEditor: function () {
			return this.htmleditor;
		},
		
		getAttribute: function (key) {
			return this.get('srcNode').getAttribute(key);
		},
		
		_setDisabled: function (value) {
			if (this.htmleditor) {
				this.htmleditor.set('disabled', !!value);
				return !!value;
			}
			
			return false;
		},
		
		_getValue: function (value) {
			if (this.htmleditor) {
				return {
					'html': this.htmleditor.getHTML(),
					'data': this.htmleditor.getAllData()
				};
			} else {
				return value;
			}
		},
		
		_getSaveValue: function (value) {
			if (this.htmleditor) {
				return {
					'html': this.htmleditor.getProcessedHTML(),
					'data': this.htmleditor.getProcessedData()
				};
			} else {
				return value;
			}
		},
		
		_setValue: function (value) {
			if (this.htmleditor) {
				this.htmleditor.setHTML(value.html);
				this.htmleditor.setAllData(value.data);
			}
			
			return value;
		}
		
	});
	
	Supra.Input.InlineHTML = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto", "supra.htmleditor"]});