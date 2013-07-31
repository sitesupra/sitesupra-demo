YUI.add('supra.input-string', function (Y) {
	//Invoke strict mode
	"use strict";
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
		
		this._last_value = '';
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'input-string';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		'replacementNode': {
			value: null
		},
		'useReplacement': {
			value: false
		},
		'valueMask': {
			value: null,
			setter: '_setValueMask'
		},
		'valueSource': {
			value: null
		},
		'blurOnReturn': {
			value: false
		},
		'maxLength': {
			value: 0,
			setter: '_setMaxLength'
		}
	};
	
	Input.HTML_PARSER = {
		'maxLength': function (srcNode) {
			if (srcNode.hasAttribute('maxlength')) {
				return parseInt(srcNode.getAttribute('maxlength'), 10) || 0;
			}
		},
		'useReplacement': function (srcNode) {
			var use_replacement = srcNode.hasClass('input-label-replacement');
			this.set('useReplacement', use_replacement);
			return use_replacement;
		},
		'replacementNode': function (srcNode) {
			if (srcNode.hasClass('input-label-replacement')) {
				return srcNode.one('span');
			}
			return null;
		}
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<input type="text" value="" />',
		LABEL_TEMPLATE: '<label></label>',
		
		/**
		 * Key code constants
		 */
		KEY_RETURN: 13,
		KEY_ESCAPE: 27,
		KEY_UP: 38,
		KEY_DOWN: 40,
		
		/**
		 * If keys are allowed, overwriten when String class
		 * is extended
		 */
		KEY_RETURN_ALLOW: true,
		KEY_ESCAPE_ALLOW: true,
		
		/**
		 * Character which is used instead of invalid characters
		 */
		MASK_REPLACEMENT_CHARACTER: '',
		
		/**
		 * Last known value, used to restore input value if new value doesn't
		 * pass mask validation
		 * @type {String}
		 * @private
		 */
		_last_value: null,
		
		/**
		 * Value source target input event listener
		 * @type {Object}
		 * @private
		 */
		_value_source_listener: null,
		
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			var input = this.get('inputNode');
			
			input.on('focus', this._onFocus, this);
			input.on('blur', this._onBlur, this);
			
			//Max length
			var maxlength = this.get('maxLength');
			if (maxlength) {
				input.setAttribute('maxlength', maxlength);
			}
			
			//Clicking on replacement node triggers focuses
			var node = this.get('replacementNode');
			if (node) {
				node.on('click', this._onFocus, this);
			}
			
			//Handle keydown
			input.on('keydown', this._onKeyDown, this);
			
			//Handle input, with yui .on("input"...) event doesn't work
			var node = input.getDOMNode();
			if (node.addEventListener) {
				node.addEventListener('input', Y.bind(this._onInput, this), false);
			} else {
				input.on('keypress', this._onKeyPress, this);
			}
			
			//Handle value attribute change
			if (!this.get('srcNode').compareTo(input)) {
				this.on('valueChange', this._afterValueChange, this);
			}
			
			this.on('input', this._onWidgetInputEvent, this);
			
			// Value source
			this.after('valueSourceChange', this._afterValueSourceChange, this);
			
			if (this.get('valueSource')) {
				this._afterValueSourceChange({'prevVal': undefined, 'newVal': this.get('valueSource')});
			}
		},
		
		/**
		 * Handle keypress event:
		 * FF - charCode is for characters, keyCode is for non output keys
		 * Opera - which is for characters, keyCode is for all keys
		 * Chrome, Safari, IE9 - charCode and keyCode is for characters, but non output keys doesn't trigger keyPress
		 *
		 * @param {Event} e Event
		 * @private
		 */
		_onKeyPress: function (e) {
			var charCode = Y.UA.opera ? e._event.which : e._event.charCode,
				keyCode = e._event.keyCode || e._event.which || e._event.charCode,
				input = this.get('inputNode'),
				mask = this.get('valueMask');
			
			if (charCode >= 186 && charCode <= 222) {
				//Normalize to match fromCharCode with charCodeAt
				charCode = charCode - 144;
			}
			
			if (keyCode == this.KEY_RETURN && this.KEY_RETURN_ALLOW) {
				//Already handled by _onKeyDown
			} else if (keyCode == this.KEY_ESCAPE && this.KEY_ESCAPE_ALLOW) {
				//Already handled by _onKeyDown
			} else if (charCode) {
				//46 - 'Delete'
				//Validate against mask
				var str = String.fromCharCode(charCode),
					inputNode = Y.Node.getDOMNode(input),
					value = inputNode.value;
				
				value = value.substr(0, inputNode.selectionStart) + str + value.substr(inputNode.selectionEnd).replace(/^\s*|\s*$/, '');

				if (e.ctrlKey && charCode == 118) return;
				if (mask && !mask.test(value)) return e.preventDefault();
				
				//Trigger input event
				if (this._last_value != value) {
					this._last_value = value;
					this.fire('input', {'value': value});
				}
			}
		},
		
		/**
		 * Handle key down event
		 * Chrome doesn't trigger escape on keypress event
		 *
		 * @param {Event} e Event
		 * @private
		 */
		_onKeyDown: function (e) {
			var keyCode   = e._event.keyCode || e._event.which || e._event.charCode,
				input     = this.get('inputNode'),
				inputNode = input.getDOMNode(),
				value     = inputNode.value,
				isNumber  = (value == parseInt(value)),
				mask      = this.get('valueMask');
			
			if (keyCode == this.KEY_RETURN && this.KEY_RETURN_ALLOW) {
				if (this.get('replacementNode') || this.get('blurOnReturn')) {
					//If using replacement node then show it
					input.blur();
				}
			} else if ((keyCode == this.KEY_UP || keyCode == this.KEY_DOWN) && isNumber) {
				//On up or down arrow press if content is a number, then add or subtract 1
				//if shift/meta key is pressed then add or subtract 10
				value = parseInt(value) + (keyCode == this.KEY_UP ? 1 : -1) * (e.shiftKey || e.metaKey ? 10 : 1);
				if (mask && !mask.test(String(value))) return;
				
				//Trigger input event
				value = this._onKeyDownNumberChange(value);
				
				if (this._last_value != value) {
					inputNode.value = value;
					this._last_value = value;
					this.fire('input', {'value': value});
				}
			} else if (keyCode == this.KEY_ESCAPE && this.KEY_ESCAPE_ALLOW) {
				input.set('value', this._original_value);
				input.blur();
				this.fire('input', {'value': this._original_value});
				this.fire('reset');
			}
		},
		
		/**
		 * Handle number value change using keys
		 * 
		 * @param {String} value New value
		 * @returns {String} New value
		 * @private
		 */
		_onKeyDownNumberChange: function (value) {
			return value;
		},
		
		/**
		 * On data input validate value
		 * 
		 * @param {Object} e
		 */
		_onInput: function (e) {
			var value = this.get('value'),
				mask = this.get('valueMask');
			
			if (mask) {
				if (value && !mask.test(value)) {
					//It's not possible to prevent input event, so we set previous value
					this.set('value', this._last_value);
				} else {
					if (this._last_value != value) {
						this._last_value = value;
						this.fire('input', {'value': value});
					}
				}
			} else if (this._last_value != value) {
				this._last_value = value;
				this.fire('input', {'value': value});
			}
		},
		
		_onFocus: function () {
			if (this.get('disabled') || this.get('boundingBox').hasClass('yui3-input-focused')) return;
			
			this.get('boundingBox').addClass('yui3-input-focused');
			this.get('inputNode').focus();
		},
		_onBlur: function () {
			this.get('boundingBox').removeClass('yui3-input-focused');
			
			var node = this.get('replacementNode');
			if (node) {
				node.set('innerHTML', Y.Escape.html(this.get('value')) || '&nbsp;');
			}
			
			this._original_value = this.get('value');
		},
		
		_onWidgetInputEvent: function (e) {
			var value = e.value;
			
			if (value) {
				this.get('boundingBox').removeClass(this.getClassName('empty'));
			} else {
				this.get('boundingBox').addClass(this.getClassName('empty'));
			}
		},
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			if (!this.get('useReplacement') && this.get('srcNode').getAttribute('data-use-replacement') == 'true') {
				this.set('useReplacement', true);
				var labelNode = this.get('labelNode');
				if (labelNode) {
					labelNode.addClass('hidden');
				}
			}
			
			if (this.get('srcNode').getAttribute('data-blur-on-return') == 'true') {
				this.set('blurOnReturn', true);
			}
			
			if (this.get('useReplacement')) {
				var node = this.get('replacementNode');
				var srcNode = this.get('srcNode');
				var srcNodeIsInput = srcNode.test('input,select,textarea');
				
				if (!srcNodeIsInput) {
					srcNode.addClass('input-label-replacement');
				} else {
					this.get('boundingBox').addClass('input-label-replacement');
				}
				
				if (!node) {
					node = Y.Node.create('<span class="replacement"></span>');
					
					if (srcNodeIsInput) {
						srcNode.insert(node, 'before');
					} else {
						srcNode.append(node);
						var input = this.get('inputNode');
						if (input) srcNode.append(input);
					}
					
					this.set('replacementNode', node);
				}
				
				node.set('innerHTML', Y.Escape.html(this.get('value')) || '&nbsp;');
				
				//If there is no label text then hide it
				var labelNode = this.get('labelNode');
				if (labelNode && !labelNode.get('text')) {
					labelNode.addClass('hidden');
				}
			}
			
			//Value mask
			if (!this.get('valueMask')) {
				var mask = this.get('inputNode').getAttribute('data-value-mask');
				if (mask) {
					this.set('valueMask', new RegExp(mask));
				}
			}
			
			//Value source
			if (!this.get('valueSource')) {
				var mask = this.get('inputNode').getAttribute('data-value-source');
				if (mask) {
					this.set('valueSource');
				}
			}
		},
		
		_setValue: function (value) {
			value = (value === undefined || value === null ? '' : value);
			
			this.get('inputNode').set('value', value);
			var node = this.get('replacementNode');
			if (node) {
				node.set('innerHTML', Y.Escape.html(value) || '&nbsp;');
			}
			
			if (value) {
				this.get('boundingBox').removeClass(this.getClassName('empty'));
			} else {
				this.get('boundingBox').addClass(this.getClassName('empty'));
			}
			
			this._original_value = value;
			
			if (this._last_value != value) {
				this._last_value = value;
				this.fire('input', {'value': value});
			}
			
			return value;
		},
		
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal});
			}
		},
		
		/**
		 * Mask attribute setter
		 * 
		 * @param {String|RegExp} mask New value mask
		 * @returns {RegExp} New attribute value
		 * @private
		 */
		_setValueMask: function (mask) {
			if (typeof mask === 'string') {
				mask = new RegExp(mask);
			}
			return mask;
		},
		
		/**
		 * Max length attribute setter
		 * 
		 * @param {Number|String} maxlength New maxlength value
		 * @returns {Number} New attribute value
		 * @private
		 */
		_setMaxLength: function (maxlength) {
			maxlength = parseInt(maxlength, 10) || 0;
			
			var input = this.get('inputNode');
			if (input) {
				if (maxlength) {
					input.setAttribute('maxlength', maxlength);
				} else {
					input.removeAttribute('maxlength');
				}
			}
			
			return maxlength;
		},
		
		/**
		 * After value source change rebind listeners
		 * 
		 * @param {Object} evt valueSource attribute value change event object
		 * @private 
		 */
		_afterValueSourceChange: function (evt) {
			var form = this.getParentWidget("form"),
				input = null;
			
			if (!form || evt.prevVal == evt.newVal) return;
			
			if (this._value_source_listener) {
				this._value_source_listener.detach();
				this._value_source_listener = null;
			}
			
			if (evt.newVal) {
				input = form.getInput(evt.newVal);
				
				if (input) {
					this._value_source_listener = input.on('input', this._afterValueSourceInputChange, this);
				}
			}
		},
		
		/**
		 * After value source input value change update this input value
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
				
				value = out;
				/*if (repl) {
					out = out.replace(new RegExp('[' + Y.Escape.regex(repl) + ']{2,}', repl));
				}*/
			}
			
			this.set('value', value);
		}
		
	});
	
	Input.lipsum = function () {
		return Supra.Lipsum.sentence({'count': 4, 'variation': 1});
	};
	
	Supra.Input.String = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});
