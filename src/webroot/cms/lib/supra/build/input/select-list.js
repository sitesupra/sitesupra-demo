YUI.add('supra.input-select-list', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Horizontal button list for selecting values
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'input-select-list';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		/**
		 * Allow selecting multiple values
		 */
		'multiple': {
			value: false
		},
		
		/**
		 * Style
		 */
		'style': {
			value: '',
			setter: '_setStyle'
		},
		
		/**
		 * Button container node
		 */
		'buttonBox': {
			value: null
		},
		
		/**
		 * Show empty value in the list
		 * @type {Boolean}
		 */
		'showEmptyValue': {
			value: true
		},
		
		/**
		 * Value/option list
		 */
		'values': {
			value: [],
			setter: '_setValues'
		}
	};
	
	Input.HTML_PARSER = {
		'values': function () {
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
		},
		'style': function (srcNode) {
			if (srcNode.getAttribute('data-style')) {
				return srcNode.getAttribute('data-style') || '';
			}
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
		
		/**
		 * List of values matching buttons
		 * @type {Object}
		 * @private
		 */
		button_value_map: null,
		
		/**
		 * Buttons has been rendered
		 * @type {Boolean}
		 * @private
		 */
		buttons_rendered: false,
		
		/**
		 * Last known value
		 * @type {String}
		 * @private
		 */
		last_value: null,
		
		
		bindUI: function () {
			var input = this.get('inputNode');
			input.on('focus', this._onFocus, this);
			input.on('blur', this._onBlur, this);
			
			//Handle value attribute change
			this.on('valueChange', this._afterValueChange, this);
		},
		
		renderUI: function () {
			this.button_value_map = {};
			
			Input.superclass.renderUI.apply(this, arguments);
			
			if (this.get('style')) {
				var classname = this.getClassName(this.get('style')),
					boundingBox = this.get('boundingBox');
				
				boundingBox.addClass(classname);
			}
			
			if (!this.buttons_rendered) {
				this.renderButtons(this.get('values'));
			}
		},
		
		renderButtons: function (values) {
			
			//Remove old buttons
			if (this.buttons) {
				for(var i in this.buttons) {
					this.buttons[i].destroy();
				}
			}
			
			this.buttons = {};
			
			var buttons = this.buttons,
				value = this._getInternalValue(),
				has_value_match = false,
				inputNode = this.get('inputNode'),
				input = inputNode.getDOMNode(),
				show_empty_value = this.get('showEmptyValue'),
				button_value_map = this.button_value_map;
			
			if (this.buttons_rendered && input.options && input.options.length) {
				//Remove old options
				while(input.options.length) {
					input.remove(input.options[0]);
				}
			}
			
			
			//Buttons will be placed instead of input
			inputNode.addClass('hidden');
			
			var button_width = 100 / values.length;
			
			for(var i=0,ii=values.length-1; i<=ii; i++) {
				if (values[i].id || show_empty_value) {
					if (this.renderButton(input, values[i], i == 0, i == ii, button_width)) {
						has_value_match = true;
					}
				}
			}
			
			if (!has_value_match) {
				if (values.length) {
					value = values[0].id;
					if (input) input.value = value;
					this.set('value', value);
				}
			}
			
			//Set value
			if (this.get('multiple') && Y.Lang.isArray(value)) {
				for(var id in buttons) {
					if (id in button_value_map) {
						id = button_value_map[id];
					}
					this.buttons[id].set('down', Y.Array.indexOf(value, id) != -1);
				}
			} else {
				inputNode.set('value', value);
				if (value in button_value_map) {
					value = button_value_map[value];
				}
				if (value in buttons) {
					buttons[value].set('down', true);
				}
			}
			
			//Buttons rendered
			this.buttons_rendered = true;
		},
		
		renderButton: function (input, definition, first, last, button_width) {
			var contentBox = this.get('contentBox'),
				buttonBox = this.get('buttonBox'),
				
				button = new Supra.Button({
					'label': definition.title,
					'icon': definition.icon,
					'type': 'toggle',
					'style': '',
					'disabled': !!definition.disabled
				}),
				value = this._getInternalValue(),
				has_value_match = false,
				
				description;
			
			if (contentBox.test('input,select')) {
				contentBox = this.get('boundingBox');
			}
			
			button.after('visibleChange', this._uiAfterButtonVisibleChange, this);
			button.ICON_TEMPLATE = '<span class="img"><img src="" alt="" /></span>';
			this.buttons[definition.id] = button;
			
			if (first) {
				button.addClass('su-button-first');
			}
			if (last) {
				button.addClass('su-button-last');
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
			
			if (!buttonBox) {
				buttonBox = Y.Node.create('<div class="' + this.getClassName('buttons') + '"></div>');
				contentBox.append(buttonBox);
				
				this.set('buttonBox', buttonBox);
				
				// Place description node inside button box
				this._placeDescription();
			}
			
			button.render(buttonBox);
			
			//Set button width
			if (this.get('style') != 'items' && this.get('style') != 'vertical') {
				button.get('boundingBox').setStyle('width', button_width + '%');
			}
			
			//On click update input value
			button.on('click', this._onClick, this, definition.id);
			
			return has_value_match;
		},
		
		
		/*
		 * ---------------------------------------- Buttons ----------------------------------------
		 */
		
		
		/**
		 * Returns button by options value
		 * 
		 * @param {String} value Option value
		 * @returns {Object} Supra.Button instance for given value
		 */
		getButton: function (value) {
			var buttons = this.buttons;
			return buttons && value in buttons ? buttons[value] : null;
		},
		
		/**
		 * After button visible change update first and last classnames
		 * 
		 * @private
		 */
		_uiAfterButtonVisibleChange: function () {
			var buttons = this.buttons,
				first_visible = null,
				last_visible = null,
				tmp = null,
				visible_count = 0,
				button_width;
			
			for (tmp in buttons) {
				buttons[tmp].removeClass('su-button-first').removeClass('su-button-last');
				if (buttons[tmp].get('visible')) {
					first_visible = last_visible = buttons[tmp];
					visible_count++;
				}
			}
			
			// First first and last visible button
			tmp = first_visible;
			while (tmp) {
				if (tmp.get('visible')) first_visible = tmp;
				tmp = tmp.get('boundingBox').previous();
				
				if (tmp && tmp.test('.su-button')) {
					tmp = Y.Widget.getByNode(tmp);
				} else {
					tmp = null;
				}
			}
			
			tmp = last_visible;
			while (tmp) {
				if (tmp.get('visible')) last_visible = tmp;
				tmp = tmp.get('boundingBox').next();
				
				if (tmp && tmp.test('.su-button')) {
					tmp = Y.Widget.getByNode(tmp);
				} else {
					tmp = null;
				}
			}
			
			if (first_visible) {
				first_visible.addClass('su-button-first');
			}
			if (last_visible) {
				last_visible.addClass('su-button-last');
			}
			
			// Update button width
			if (this.get('style') != 'items' && this.get('style') != 'vertical') {
				button_width = 100 / visible_count;
				
				for (tmp in buttons) {
					buttons[tmp].get('boundingBox').setStyle('width', button_width + '%');
				}
			}
		},
		
		
		/*
		 * ---------------------------------------- API ----------------------------------------
		 */
		
		
		/**
		 * Returns full data for value
		 * If value is an array of values then returns array of data
		 * 
		 * @param {String} value Optional, value for which to return full data
		 * @returns {Object} Value data
		 */
		getValueData: function (value, values) {
			var value  = value === null || typeof value === 'undefined' ? this._getInternalValue() : value,
				values = values || this.get('values'),
				i = 0,
				ii = values.length,
				tmp = null;
			
			if (Y.Lang.isArray(value)) {
				// Multiple values
				var out = [];
				for (; i<ii; i++) {
					if (Y.Array.indexOf(value, values[i].id) != -1) {
						out.push(values[i]);
					}
					if (values[i].values) {
						out = out.concat(this.getValueData(value, values[i].values));
					}
				}
				return out;
			} else {
				// Single value
				for (; i<ii; i++) {
					if (values[i].id == value) {
						return values[i];
					}
					if (values[i].values) {
						// Go through sub-values
						tmp = this.getValueData(value, values[i].values);
						if (tmp) {
							return tmp;
						}
					}
				}
			}
			
			return null;
		},
		
		/**
		 * Reset value to default
		 */
		resetValue: function () {
			var value = this.get('defaultValue'),
				values = this.get('values');
			
			this.set('value', value !== null ? value : (values.length ? values[0].id : ''));
			return this;
		},
		
		/**
		 * Returns true if list has options with given id
		 * 
		 * @param {String} id Option ID
		 * @return True if has option with given id, otherwise false
		 * @type {Boolean}
		 */
		hasValue: function (id) {
			var values = this.get("values"),
				i = 0,
				ii = values.length;
			
			 for (; i<ii; i++) if (values[i].id == id) return true;
			 return false
		},
		
		
		/*
		 * ---------------------------------------- EVENT LISTENERS ----------------------------------------
		 */
		
		
		/**
		 * On focus style input
		 * 
		 * @private
		 */
		_onFocus: function () {
			if (this.get('boundingBox').hasClass('yui3-input-focused')) return;
			
			this.get('boundingBox').addClass('yui3-input-focused');
			this.get('inputNode').focus();
		},
		
		/**
		 * On blur style input
		 * 
		 * @private
		 */
		_onBlur: function () {
			this.get('boundingBox').removeClass('yui3-input-focused');
		},
		
		/**
		 * On click update value
		 * 
		 * @param {Object} event Event facade object
		 * @param {String} id Value id on which user clicked
		 * @private
		 */
		_onClick: function (event, id) {
			if (this.get('multiple')) {
				id = this.get('value');
			}
			
			this.set('value', id);
			this.fire('itemClick', {'value': id});
		},
		
		/**
		 * Returns selected value
		 * 
		 * @returns {String} Selected value
		 * @private
		 */
		_getInternalValue: function () {
			return this.last_value;
		},
		
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal});
			}
		},
		
		
		/*
		 * ---------------------------------------- DESCRIPTION ----------------------------------------
		 */
		
		
		/**
		 * Insert description node in correct place
		 * 
		 * @private
		 */
		_placeDescription: function () {
			var container = this.get('buttonBox') || this.get('contentBox'),
				node = this.get('descriptionNode');
			
			if (node) {
				container.prepend(node);
			}
		},
		
		
		
		/*
		 * ---------------------------------------- ATTRIBUTES ----------------------------------------
		 */
		
		
		/**
		 * Values attribute setter
		 * 
		 * @param {Array} values List of values
		 * @returns {Array} New values list
		 * @private
		 */
		_setValues: function (values) {
			if (this.get('rendered')) {
				this.renderButtons(values);
			}
			return values;
		},
		
		/**
		 * Value attribute setter
		 * 
		 * @param {String} value Value id
		 * @returns {String} New value
		 * @private
		 */
		_setValue: function (value) {
			if (typeof value == 'boolean') {
				// Convert boolean values to string
				value = value ? "1" : "0";
			} else if (value && typeof value === 'object' && 'id' in value) {
				// Extract id from objects
				value = value.id;
			}
			
			if (!this.get('rendered')) {
				// Not rendered, there are no buttons yet
				this.last_value = value;
				return value;
			}
			
			//Input value is not valid if 'multiple' attribute is true
			this.get('inputNode').set('value', value);
			
			//Map for buttons and values
			var button_value_map = this.button_value_map;
			
			if (this.get('multiple') && Y.Lang.isArray(value)) {
				// Extract id from objects
				for (var i=0, ii=value.length; i<ii; i++) {
					if (value[i] && typeof value[i] === 'object' && 'id' in value[i]) {
						value[i] = value[i].id;
					}
				}
				
				//Update button states
				for(var i in this.buttons) {
					if (i in button_value_map) {
						i = button_value_map[i];
					}
					this.buttons[i].set('down', Y.Array.indexOf(value, i) != -1);
				}
			} else {
				var _value = value;
				if (_value in button_value_map) {
					_value = button_value_map[value];
				}
				for(var i in this.buttons) {
					this.buttons[i].set('down', i == _value);
				}
			}
			
			this.last_value = value;
			return value;
		},
		
		/**
		 * Value attribute getter
		 * 
		 * @returns {String} Selected value
		 * @private
		 */
		_getValue: function (value) {
			var values = this.get('values');
			if (!values || !values.length) {
				// There are no options, so any value will be considered as ok
				return this.last_value;
			}
			
			if (this.get('multiple')) {
				var buttons = this.buttons,
					value = [];
				
				for(var i in this.buttons) {
					if (this.buttons[i].get('down')) {
						value.push(i);
					}
				}
				
				return value;
			} else {
				return this.last_value;
			}
		},
		
		_setDisabled: function (value) {
			value = Input.superclass._setDisabled.apply(this, arguments);
			
			//Disable buttons
			for(var i in this.buttons) {
				this.buttons[i].set('disabled', value);
			}
			
			return value;
		},
		
		/**
		 * Style attribute setter
		 * 
		 * @param {String} value Style value
		 * @returns {String} New style attribute value
		 * @private
		 */
		_setStyle: function (value) {
			var prev = this.get('style'),
				classname = null;
			
			if (prev != value) {
				if (prev) { 
					classname = this.getClassName(prev);
					this.get('boundingBox').removeClass(classname);
				}
				if (value) {
					classname = this.getClassName(value);
					this.get('boundingBox').addClass(classname);
				}
			}
			
			return value;
		}
	});
	
	Supra.Input.SelectList = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto', 'supra.button']});
