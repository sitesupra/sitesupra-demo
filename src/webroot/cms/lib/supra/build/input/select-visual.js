//Invoke strict mode
"use strict";

YUI.add('supra.input-select-visual', function (Y) {
	
	/**
	 * Vertical button list for selecting value
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = 'input-select-visual';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	
	Input.ATTRS = {
		/**
		 * Icon background color
		 */
		'backgroundColor': {
			value: 'transparent',
			setter: '_setBackgroundColor'
		},
		
		/**
		 * Style:
		 * "" or "no-labels"
		 */
		'style': {
			value: '',
			setter: '_setStyle'
		},
		
		/**
		 * Icon image style:
		 * "center", "fill" or "button"
		 */
		'iconStyle': {
			value: 'center',
			setter: '_setIconStyle'
		},
		
		/**
		 * Loading state
		 */
		'loading': {
			value: false,
			setter: '_setLoading'
		},
		
		/**
		 * Loading icon
		 */
		'nodeLoading': {
			value: null
		}
	};
	
	Input.HTML_PARSER = {
		'backgroundColor': function (srcNode) {
			return srcNode.getAttribute('suBackgroundColor') || 'transparent';
		},
		'style': function (srcNode) {
			if (srcNode.getAttribute('suStyle')) {
				return srcNode.getAttribute('suStyle') || '';
			}
		},
		'iconStyle': function (srcNode) {
			if (srcNode.getAttribute('suIconStyle')) {
				return srcNode.getAttribute('suIconStyle') || '';
			}
		}
	};
	
	Y.extend(Input, Supra.Input.SelectList, {
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			//Classnames, etc.
			var boundingBox = this.get("boundingBox");
			boundingBox.removeClass(Supra.Input.SelectList.CLASS_NAME);
			
			if (this.get('style')) {
				classname = Y.ClassNameManager.getClassName(Input.NAME, this.get('style'));
				boundingBox.addClass(classname);
			}
			
			if (this.get('iconStyle')) {
				classname = Y.ClassNameManager.getClassName(Input.NAME, this.get('iconStyle'));
				boundingBox.addClass(classname);
			}
		},
		
		renderButton: function (input, definition, first, last, button_width) {
			var contentBox = this.get('contentBox'),
				button = new Supra.Button({'label': definition.title, 'type': 'toggle', 'style': 'group'}),
				value = this._getInternalValue(),
				has_value_match = false;
			
			if (contentBox.test('input,select')) {
				contentBox = this.get('boundingBox');
			}
			
			button.ICON_TEMPLATE = '<span class="img"><img src="" alt="" /></span>';
			button.LABEL_TEMPLATE = this.getButtonLabelTemplate(definition);
			this.buttons[definition.id] = button;
			
			if (first) {
				button.get('boundingBox').addClass('su-button-first');
			}
			if (last) {
				button.get('boundingBox').addClass('su-button-last');
			}
			
			if (input && input.options) {
				//Add options to allow selecting value
				input.options[input.options.length] = new Option(definition.title, definition.id);
				if (value == definition.id) input.value = value;
			}
			
			if (definition.id == value) {
				//Mark value as found
				has_value_match = true;
			}
			
			button.render(contentBox);
			
			//Set button width
			button.get('boundingBox').setStyle('width', button_width + '%');
			
			//On click update input value
			button.on('click', this._onClick, this, definition.id);
			
			return has_value_match;
		},
		
		/**
		 * Returns button label template
		 * 
		 * @return Label template
		 * @type {String}
		 * @private
		 */
		getButtonLabelTemplate: function (definition) {
			return '<div class="su-button-bg"><div style="' + this.getButtonBackgroundStyle(definition) + '"></div><p></p></div>';
		},
		
		/**
		 * Returns button background style
		 * 
		 * @param {Object} definition Button definition
		 * @return Background CSS style
		 * @type {String}
		 * @private
		 */
		getButtonBackgroundStyle: function (definition) {
			var style = 'background-color: ' + this.get('backgroundColor') +';';
			
			if (definition.icon) {
				if (this.get('iconStyle') == 'button') {
					style += 'background-image: url(' + definition.icon + '), url(' + definition.icon + '), url(' + definition.icon + ');';
				} else {
					style += 'background-image: url(' + definition.icon + ');';
				}
			}
			
			return style;
		},
		
		
		/*
		 * ---------------------------------------- ATTRIBUTES ----------------------------------------
		 */
		
		
		/**
		 * Background color attribute setter
		 * 
		 * @param {String} value Background color
		 * @return New background color attribute value
		 * @type {String}
		 * @private
		 */
		_setBackgroundColor: function (value) {
			var nodes = this.get('boundingBox').all('.su-button-bg div');
			
			nodes.setStyle('backgroundColor', value);
			
			return value;
		},
		
		/**
		 * Style attribute setter
		 * 
		 * @param {String} value Style value
		 * @return New style attribute value
		 * @type {String}
		 * @private
		 */
		_setStyle: function (value) {
			var prev = this.get('style'),
				classname = null;
			
			if (prev != value) {
				if (prev) { 
					classname = Y.ClassNameManager.getClassName(Input.NAME, prev);
					this.get('boundingBox').removeClass(classname);
				}
				if (value) {
					classname = Y.ClassNameManager.getClassName(Input.NAME, value);
					this.get('boundingBox').addClass(classname);
				}
			}
			
			return value;
		},
		
		/**
		 * Icon style attribute setter
		 * 
		 * @param {String} value Style value
		 * @return New icon style attribute value
		 * @type {String}
		 * @private
		 */
		_setIconStyle: function (value) {
			var prev = this.get('iconStyle'),
				classname = null;
			
			if (prev != value) {
				if (prev) { 
					classname = Y.ClassNameManager.getClassName(Input.NAME, prev);
					this.get('boundingBox').removeClass(classname);
				}
				if (value) {
					classname = Y.ClassNameManager.getClassName(Input.NAME, value);
					this.get('boundingBox').addClass(classname);
				}
			}
			
			return value;
		},
		
		/**
		 * Loading attribute setter
		 * 
		 * @param {Boolean} loading Loading attribute value
		 * @return New value
		 * @type {Boolean}
		 * @private
		 */
		_setLoading: function (loading) {
			var box = this.get('contentBox');
			
			if (box) {
				if (loading && !this.get('nodeLoading')) {
					var node = Y.Node.create('<span class="loading-icon"></span>');
					box.append(node);
					this.set('nodeLoading', node);
				}
				
				box.toggleClass(this.getClassName('loading'), loading);
			}
			
			this.set('disabled', loading);
			return loading;
		},
		
	});
	
	Supra.Input.SelectVisual = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-select-list']});