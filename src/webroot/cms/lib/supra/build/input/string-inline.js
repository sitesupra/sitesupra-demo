//Invoke strict mode
"use strict";
	
YUI.add("supra.input-inline-string", function (Y) {
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = "input-string-inline";
	Input.ATTRS = {
		'doc': null,
		'win': null
	};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.InlineHTML, {
		/*CONTENT_TEMPLATE: null,*/
		
		renderUI: function () {
			//We overwrite InlineHTML.renderUI, that's why we call parent
			Supra.Input.InlineHTML.superclass.renderUI.apply(this, arguments);
			
			this.set('boundingBox', this.get('srcNode'));
			
			var doc = this.get('doc'),
				win = this.get('win'),
				src = this.get('srcNode');
			
			if (doc && win && src) {
				this.htmleditor = new Supra.HTMLEditor({
					'doc': doc,
					'win': win,
					'srcNode': src,
					'toolbar': this.get('toolbar'),
					'mode': Supra.HTMLEditor.MODE_STRING
				});
				this.htmleditor.render();
				this.htmleditor.set('disabled', true);
			}
		},
		
		_getValue: function (value) {
			if (this.htmleditor) {
				return this.htmleditor.getHTML();
			} else {
				return value;
			}
		},
		
		_getSaveValue: function (value) {
			if (this.htmleditor) {
				return this.htmleditor.getProcessedHTML();
			} else {
				return value;
			}
		},
		
		_setValue: function (value) {
			if (this.htmleditor) {
				this.htmleditor.setHTML(value);
			}
			
			return value;
		},
		
		/**
		 * Clean up
		 */
		destructor: function () {
			if (this.htmleditor) {
				this.htmleditor.detach('change');
				this.htmleditor.destroy();
				this.htmleditor = null;
			}
		}
		
	});
	
	Supra.Input.InlineString = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-inline-html"]});