YUI.add('supra.input-path', function (Y) {
	//Invoke strict mode
	"use strict";
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'input-path';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		'path': {
			value: '',
			setter: '_setPath'
		},
		'pathNode': {
			value: null
		}
	};
	
	Input.HTML_PARSER = {
		'path': function (srcNode) {
			return srcNode.getAttribute('data-path') || '';
		}
	};
	
	Y.extend(Input, Supra.Input.String, {
		
		/**
		 * Character which is used instead of invalid characters
		 */
		MASK_REPLACEMENT_CHARACTER: '-',
		
		_setPath: function (value) {
			var node = this.get('pathNode'),
				input = this.get('inputNode'),
				replacement = this.get('replacementNode');
			
			if (!node && replacement) {
				node = replacement.one('small');
			}
			if (!node) {
				node = input.previous('small');
			}
			if (!node) {
				node = Y.Node.create('<small></small>');
				
				if (replacement) {
					replacement.prepend(node);
				} else {
					input.insert(node, 'before');
				}
			}
			
			if (node) {
				this.set('pathNode', node);
				node.set('innerHTML', Y.Escape.html(value));
				node.toggleClass('empty', !value);
			}
			
			return value;
		},
		
		_onBlur: function () {
//			var input = this.get('inputNode');
//			this.set('value', input.get('value').replace(/[^a-z0-9\-\_]/gi, ''));
			
			Input.superclass._onBlur.apply(this, arguments);
			
			var node = this.get('replacementNode');
			if (node) {
				node.set('innerHTML', '<small>' + this.get('path') + '</small>' + this.get('value'));
				this.set('pathNode', node.one('small'));
			}
		},
		
		_setValue: function (value) {
			this.get('inputNode').set('value', value);
			var node = this.get('replacementNode');
			
			if (node) {
				node.set('innerHTML', '<small>' + this.get('path') + '</small>' + Y.Escape.html(value) || '&nbsp;');
				this.set('pathNode', node.one('small'));
			}
			
			this._original_value = value;
			return value;
		},
		
		renderUI: function () {
			var r = Input.superclass.renderUI.apply(this, arguments);
			
			//Replacement text
			var replacement_node = this.get('replacementNode');
			if (replacement_node) {
				replacement_node.set('innerHTML', '<small>' + Y.Escape.html(this.get('path')) + '</small>' + Y.Escape.html(this.get('value')));
			}
			
			//Path text
			var path = this.get('path');
			if (path && !this.get('useReplacement')) {
				this._setPath(path);
			}
			
			return r;
		},
		
		/**
		 * After value source input value change update this input value
		 * Overwrite String implementation for correct path value
		 * 
		 * @param {Object} evt
		 * @private
		 */
		_afterValueSourceInputChange: function (evt) {
			var value = evt.value,
				mask  = this.get('valueMask'),
				out   = '',
				i     = 0,
				ii    = value.length,
				repl  = this.MASK_REPLACEMENT_CHARACTER;
			
			if (mask) {
				for (; i<ii; i++) {
					if (mask.test(value[i])) {
						out += value[i];
					} else {
						out += repl;
					}
				}
				
				// Remove repeated characters
				if (repl) {
					out = out.replace(new RegExp('[' + Y.Escape.regex(repl) + ']{2,}', 'ig'), repl);
					out = out.replace(new RegExp('(^' + Y.Escape.regex(repl) + '|' + Y.Escape.regex(repl) + '$)', 'ig'), '');
				}
				
				// Path is lower case
				value = out.toLowerCase();
			}
			
			this.set('value', value);
		}
		
	});
	
	Supra.Input.Path = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto', 'supra.input-string']});