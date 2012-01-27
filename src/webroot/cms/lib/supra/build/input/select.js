//Invoke strict mode
"use strict";

YUI.add("supra.input-select", function (Y) {
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = "input-select";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		/**
		 * List of values
		 * @type {Array}
		 */
		"values": {
			value: [],
			setter: '_setValues'
		},
		
		/**
		 * Show empty value in the list
		 * @type {Boolean}
		 */
		"showEmptyValue": {
			value: true
		},
		
		/**
		 * Don't use replacement
		 * @type {Boolean}
		 */
		"useReplacement": {
			readOnly: true,
			value: false
		},
		
		/**
		 * Custom select container node
		 * @type {Object}
		 */
		"innerNode": {
			value: null
		},
		
		/**
		 * Text node
		 * @type {Object}
		 */
		"textNode": {
			value: null
		},
		/**
		 * Dropdown node
		 * @type {Object}
		 */
		"dropdownNode": {
			value: null
		},
		/**
		 * Dropdown content node
		 * @type {Object}
		 */
		"contentNode": {
			value: null
		},
		
		/**
		 * Dropdown is opened
		 * @type {Boolean}
		 */
		"opened": {
			value: false,
			setter: '_setDropdownOpened'
		},
		
		/**
		 * Item renderer, allows to create
		 * custom styled items
		 * @type {Function}
		 */
		"itemRenderer": {
			value: null
		}
	};
	
	Input.HTML_PARSER = {
		"values": function (srcNode) {
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
		}
	};
	
	Y.extend(Input, Supra.Input.String, {
		CONTENT_TEMPLATE: '<div></div>',
		INPUT_TEMPLATE: '<select></select>',
		LABEL_TEMPLATE: '<label></label>',
		
		/**
		 * Dropdown opening animation
		 * @type {Object}
		 * @private
		 */
		anim_open: null,
		
		/**
		 * Dropdown close animation
		 * @type {Object}
		 * @private
		 */
		anim_close: null,
		
		/**
		 * Add nodes needed for widget
		 */
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			//Wrap SELECT into DIV if input is contentBox
			if (this.get('contentBox').test('select')) {
				var node = Y.Node.create(this.CONTENT_TEMPLATE);
				node.addClass(this.getClassName('inner'));
				node.setAttribute('tabindex', '0');
				this.get('inputNode').insert(node, 'after');
				this.set('innerNode', node);
			}
			
			//Input doesn't need to be visible
			this.get('inputNode').addClass('hidden');
			
			//Text node
			var text_node = Y.Node.create('<p></p>');
			this.get('innerNode').append(text_node);
			this.set('textNode', text_node);
			
			//Dropdown node
			var dropdown_node = Y.Node.create('\
					<div class="' + this.getClassName('dropdown') + '">\
						<div class="' + this.getClassName('arrow') + '"></div>\
						<div class="' + this.getClassName('dropdown-content') + '"></div>\
					</div>');
			
			this.set('dropdownNode', dropdown_node);
			this.set('contentNode', dropdown_node.one('.' + this.getClassName('dropdown-content')));
			this.get('innerNode').append(dropdown_node);
			
			//Sync values
			this.set('values', this.get('values'));
		},
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			var inner_node = this.get('innerNode');
			inner_node.on('mouseenter', this._onMouseOver, this);
			inner_node.on('mouseleave', this._onMouseOut, this);
			inner_node.on('mousedown', this._onMouseDown, this);
			inner_node.on('keydown', this._onKeyDown, this);
			
			//Handle list item  click
			inner_node.delegate('click', this._onItemClick, 'a[data-id]', this);
		},
		
		/**
		 * On mouse over set style
		 * 
		 * @private
		 */
		_onMouseOver: function () {
			if (this.get('disabled')) return;
			this.get('boundingBox').addClass(this.getClassName('mouse-over'));
		},
		
		/**
		 * On mouse out remove style
		 * 
		 * @private
		 */
		_onMouseOut: function () {
			if (this.get('disabled')) return;
			this.get('boundingBox').removeClass(this.getClassName('mouse-over'));
		},
		
		/**
		 * On mouse down open dropdown
		 * 
		 * @private
		 */
		_onMouseDown: function () {
			this.set('opened', true);
		},
		
		/**
		 * On key down open dropdown
		 */
		_onKeyDown: function (e) {
			if (e.keyCode == 13) {
				//Return key, open dropdown
				if (!this.get('opened')) this.set('opened', true);
			} else if (e.keyCode == 27 || e.keyCode == 9) {
				//Escape key, close dropdown
				if (this.get('opened')) this.set('opened', false);
			}
		},
		
		/**
		 * On item click change value and close dropdown
		 */
		_onItemClick: function (e) {
			if (this.get('disabled')) return;
			
			var item = e.target.closest('a'),
				value = item.getAttribute('data-id');
			
			this.set('value', value);
			this.set('opened', false);
		},
		
		/**
		 * Open dropdown
		 * 
		 * @private
		 */
		_openDropDown: function () {
			if (this.get('disabled')) return;
			
			var inner_node = this.get('innerNode'),
				dropdown_node = this.get('dropdownNode'),
				bounding_node = this.get('boundingBox');
			
			bounding_node.addClass(this.getClassName('open'));
			dropdown_node.setStyles({
				'opacity': 0,
				'margin-top': -15,
				'min-width': inner_node.get('offsetWidth')
			});
			
			//Animations
			if (!this.anim_open) {
				this.anim_open = new Y.Anim({
					node: dropdown_node,
				    duration: 0.25,
				    easing: Y.Easing.easeOutStrong,
					from: {opacity: 0, marginTop: -15},
					to: {opacity: 1, marginTop: 0}
				});
			}
			if (this.anim_close) {
				this.anim_close.stop();
			}
			
			this.anim_open
					.stop()
					.run();
			
			//Listeners
			this.close_event_listener = Y.one(document).on('mousedown', this._closeDropDownAttr, this);
		},
		
		/**
		 * Close dropdown by setting attribute
		 */
		_closeDropDownAttr: function (e) {
			//Check validity
			if (e && e.target) {
				var node = e.target.closest('.' + this.getClassName('inner'));
				if (node && node === this.get('innerNode')) {
					return;
				}
			}
			
			if (this.get('opened')) {
				this.set('opened', false);
			}
		},
		
		/**
		 * Close dropdown without updating attribute value
		 */
		_closeDropDown: function () {
			//Remove listener
			if (this.close_event_listener) {
				this.close_event_listener.detach();
				this.close_event_listener = null;
			}
			
			//Animations
			if (!this.anim_close) {
				this.anim_close = new Y.Anim({
					node: this.get('dropdownNode'),
				    duration: 0.25,
				    easing: Y.Easing.easeOutStrong,
					from: {opacity: 1, marginTop: 0},
					to: {opacity: 0, marginTop: -15}
				});
				this.anim_close.on('end', function () {
					this.get('boundingBox').removeClass(this.getClassName('closing'));
				}, this);
			}
			if (this.anim_open) {
				this.anim_open.stop();
			}
			
			this.get('boundingBox').addClass(this.getClassName('closing'));
			this.get('boundingBox').removeClass(this.getClassName('open'));
			
			this.anim_close
					.stop()
					.run();
		},
		
		/**
		 * 'Opened' attribute value setter
		 * 
		 * @param {Boolean} value Opened state
		 * @private
		 */
		_setDropdownOpened: function (value) {
			var prev = !!this.get('opened'),
				value = !!value;
			
			if (this.get('disabled')) {
				return false;
			}
			
			if (value != prev) {
				if (value) {
					this._openDropDown();
				} else {
					this._closeDropDown();
				}
			}
			
			return value;
		},
		
		/**
		 * Values attribute setter
		 * 
		 * @param {Array} values
		 * @return New values
		 * @type {Array}
		 * @private
		 */
		_setValues: function (values) {
			if (!Y.Lang.isArray(values)) values = [];
			
			var inputNode = this.get('inputNode'),
				contentNode = this.get('contentNode'),
				item_class = this.getClassName('item'),
				renderer = this.get('itemRenderer'),
				show_empty_value = this.get('showEmptyValue'),
				html = null;
			
			if (inputNode) {
				var domNode = Y.Node.getDOMNode(inputNode),
					value = this.get('value'),
					text_node = this.get('textNode');
				
				domNode.options = [];
				
				if (contentNode) {
					contentNode.empty();
				}
				
				for(var i=0,ii=values.length; i<ii; i++) {
					domNode.options[i] = new Option(values[i].title, values[i].id, values[i].id == value);
					
					if (values[i].id == value && text_node) {
						text_node.set('text', values[i].title);
					}
					
					if (contentNode && (show_empty_value || values[i].id !== '')) {
						if (renderer) {
							html = renderer(values[i], i);
						} else {
							html = '<a class="' + item_class + '" data-id="' + values[i].id + '">' + Supra.Y.Escape.html(values[i].title) + '</a>';
						}
						if (html) {
							contentNode.append(html);
						}
					}
				}
			}
			
			return values;
		},
		
		/**
		 * Value attribute setter
		 * 
		 * @param {String} value
		 * @return New value
		 * @type {String}
		 * @private
		 */
		_setValue: function (value) {
			value = Input.superclass._setValue.apply(this, arguments);
			
			//If not rendered yet, then textNode will not exist
			if (!this.get('textNode')) return value;
			
			var values = this.get('values');
			for(var i=0,ii=values.length; i<ii; i++) {
				if (values[i].id == value) {
					this.get('textNode').set('text', values[i].title);
					break;
				}
			}
			
			return value;
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
		 * On destroy remove animations and listeners
		 */
		destructor: function () {
			if (this.anim_open) this.anim_open.destroy();
			if (this.anim_close) this.anim_close.destroy();
			if (this.close_event_listener) {
				this.close_event_listener.detach();
				this.close_event_listener = null;
			}
			
			this.get('innerNode').destroy(true);
			
			Input.superclass.destructor.apply(this, arguments);
		}
		
	});
	
	Supra.Input.Select = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-string", "anim"]});