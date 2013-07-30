YUI.add('supra.button', function (Y) {
	//Invoke strict mode
	"use strict";
	
	function Button (config) {
		Button.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Button.NAME = 'button';
	Button.CSS_PREFIX = 'su-' + Button.NAME;
	
	Button.ATTRS = {
		buttonId: {
			value: null
		},
		nodeWrapper: {
			value: null
		},
		nodeButton: {
			value: null
		},
		nodeLabel: {
			value: null
		},
		nodeLoading: {
			value: null
		},
		label: {
			value: '',
			setter: '_setLabel'
		},
		title: {
			value: '',
			setter: '_setTitle'
		},
		type: {
			value: 'push'		// Valid types are 'push', 'toggle'
		},
		/**
		 * Button style:
		 * "small", "small-gray", "small-blue", "mid", "mid-blue", "group"
		 */
		style: {
			value: 'small',
			setter: '_setStyle'
		},
		disabled: {
			value: false,
			setter: '_setDisabled'
		},
		loading: {
			value: false,
			setter: '_setLoading'
		},
		down: {
			value: false,
			setter: '_setDown'
		},
		visible: {
			value: true,
			setter: '_setVisible'
		},
		icon: {
			value: null,
			setter: '_setIcon'
		},
		
		
		/**
		 * Group style if style is "group":
		 * "mid" or "no-labels"
		 */
		groupStyle: {
			value: '',
			setter: '_setGroupStyle'
		},
		/**
		 * Icon image style style is "group":
		 * "normal", "center", "fill", "button" or "html"
		 */
		iconStyle: {
			value: 'normal',
			setter: '_setIconStyle'
		},
		
		/**
		 * Icon background color if style is "group" and iconStyle is "html"
		 */
		iconBackgroundColor: {
			value: 'transparent'
		},
		
		/**
		 * Icon HTML if style is "group" and iconStyle is "html"
		 */
		iconHTML: {
			value: ''
		},
		
		/**
		 * Icon CSS if style is "group" and iconStyle is "html"
		 */
		iconCSS: {
			value: ''
		}
	},
	
	Button.CLASS_NAME = Y.ClassNameManager.getClassName(Button.NAME);
	
	/* 
     * The HTML_PARSER static constant is used by the Widget base class to populate 
     * the configuration for the button instance from markup already on the page.
     *
     * The Button class attempts to set the label, style, disabled, wrapper element of the Button widget if it
     * finds the appropriate elements on the page
     */
    Button.HTML_PARSER = {
        nodeButton: function (srcNode) {
			var tag = srcNode.get('tagName');
			
			if (tag == 'INPUT' || tag == 'BUTTON') {
				this.set('nodeButton', srcNode);
				return srcNode;
			} else {
				var node = srcNode.one('BUTTON,INPUT');
				if (!node) {
					node = Y.Node.create('<button type="button">' + Y.Escape.html(Supra.Intl.replace(this.get('label') || '')) + '</button>');
					this.get('contentBox').append(node);
				}
				
				this.set('nodeButton', node);
				return node;
			}
		},
		nodeWrapper: function (srcNode) {
			var tag = srcNode.get('tagName');
			if (tag != 'INPUT' && tag != 'BUTTON') {
				this.set('nodeWrapper', srcNode);
				return srcNode;
			};
			return null;
		},
		nodeLabel: function (srcNode) {
			// find SPAN inside button
			var btn = this.get('nodeButton');
			if (btn) {
				var label = btn.one('p');
				if (label) {
					this.set('nodeLabel', label);
					return;
				}
			}
			
			this.set('nodeLabel', btn);
		},
		disabled: function (srcNode) {
			var btn = this.get('nodeButton');
			if (btn) {
				var disabled = btn.get('disabled') ? true : false;
				return disabled;
			}
		},
		label: function (srcNode) {
			var label = this.get('nodeLabel'),
				text = null;
			
			if (label) {
				text = label.get('innerHTML');
			}
			return text || this.get('label') || ' ';
        },
		style: function (srcNode) {
			var button = this.get('nodeButton'),
				style = null;
			
			if (button) {
				style = button.getAttribute('data-style');
			}
			
			return style || 'small';
		},
		type: function (srcNode) {
			var button = this.get('nodeButton'),
				type = null;
			
			if (button) {
				type = button.getAttribute('data-type');
			}
			
			return type || 'push';
		},
		icon: function (srcNode) {
			var button = this.get('nodeButton'),
				icon = null;
			
			if (button) {
				icon = button.getAttribute('data-icon');
			}
			
			return icon;
		},
		down: function (srcNode) {
			var button = this.get('nodeButton'),
				down = false;
			
			if (button) {
				down = (button.getAttribute('data-state-down') === 'true');
			}
			
			return down;
		},
		
		iconStyle: function (srcNode) {
			var button = this.get('nodeButton'),
				style = null;
			
			if (button) {
				style = button.getAttribute('data-icon-style') || undefined;
			}
		},
		
		groupStyle: function (srcNode) {
			var button = this.get('nodeButton'),
				style = null;
			
			if (button) {
				style = button.getAttribute('data-group-style') || '';
			}
		},
		
		iconBackgroundColor: function (srcNode) {
			var button = this.get('nodeButton'),
				style = button.getAttribute('data-icon-background-color');
			
			if (button && style) {
				return style;
			}
		}

    };
	
	Y.extend(Button, Y.Widget, {
		
		/**
		 * Icon template
		 * @type {String}
		 */
		ICON_TEMPLATE: '<img src="" alt="" />',
		
		ICON_TEMPLATE_GROUP: '<span class="img"><img src="" alt="" /></span>',
		
		/**
		 * Label template
		 * @type {String}
		 */
		LABEL_TEMPLATE: '<p></p>',
		
		initializer: function () {
			
		},
		
		destructor: function () {
			
		},
		
		renderUI : function() {
			//Add DIV around button
			if (!this.get('nodeWrapper')) {
				var btn = this.get('nodeButton');
				var nodeWrapper = Y.Node.create('<div></div>');
				btn.ancestor().appendChild(nodeWrapper);
				nodeWrapper.appendChild(btn);
			}
			
			//Add label inside button
			var btn = this.get('nodeButton');
			if (btn) {
				if (!this.get('nodeLabel') || this.get('nodeLabel').get('tagName') != 'P') {
					var tpl = Y.Node.create(this._getLabelTemplate()),
						p = tpl.test('P') ? tpl : tpl.one('P');
					
					p.set('innerHTML', Y.Escape.html(Supra.Intl.replace(this.get('label') || '')));
					
					btn.set('innerHTML', '');
					btn.appendChild(tpl);
					
					this.set('nodeLabel', p);
				}
				
				//Title attribute
				btn.setAttribute('title', this.get('title') || '');
				
				//Buttons with group or toolbar style doesn't have labels, use "title" attribute
				if ((this.get('style') == 'group' || this.get('style') == 'toolbar') && this.get('icon')) {
					this.set('title', this.get('title') || this.get('label') || '');
				}
				
				if (!btn.getAttribute('type')) {
					btn.setAttribute('type', 'button');
				}
			}
			
			//ClassName
			if (btn) {
				var className = btn.getAttribute('className').replace(/\s?su-button-content\s?/, '');
				
				if (className) {
					btn.removeClass(className);
					this.get('boundingBox').addClass(className);
				}
			}
			
			if (this.get('down')) {
				this.set('down', true);
			}
			
			if (this.get('disabled')) {
				this.set('disabled', true);
			}
			
			if (this.get('icon')) {
				this.set('icon', this.get('icon'));
			}
		},
		
		syncUI: function () {
			this._syncUIStyle();
			
			//Change label if needed
			var label = this.get('nodeLabel');
			if (label && label.get('value') != this.get('label')) {
				label.set('value', this.get('label'));
			}
		},
		
		bindUI: function () {
			this.on('mousedown', this._onMouseDown, this);
			this.on('mouseup', this._onMouseUp, this);
			this.on('mouseover', this._onMouseOver, this);
			this.on('mouseout', this._onMouseOut, this);
			
			this.get('contentBox').on('click', this._onDisabledPreventClick, this);
			this.get('boundingBox').on('click', this._onDisabledPreventClick, this);
			
			this.on('click', this._onClick, this);
		},
		
		
		/* ---------------------------- Label ---------------------------- */
		
		
		/**
		 * Returns button label template
		 * 
		 * @return Label template
		 * @type {String}
		 * @private
		 */
		_getLabelTemplate: function (definition) {
			if (this.get('style') == 'group') {
				if (this.get('iconStyle') == 'html') {
					return '<div class="su-button-bg"><div>' + (this.get('iconHTML') || '') + '</div><p></p></div>';
				} else {
					return '<div class="su-button-bg"><div style="' + this._getButtonBackgroundStyle(this.get('icon')) + '"></div><p></p></div>';
				}
			} else {
				return this.LABEL_TEMPLATE;
			}
		},
		
		/**
		 * Returns button background style
		 * 
		 * @param {Object} definition Button definition
		 * @return Background CSS style
		 * @type {String}
		 * @private
		 */
		_getButtonBackgroundStyle: function (icon, asObject) {
			var color = this.get('iconBackgroundColor'),
				image = '',
				style = '';
			
			if (icon) {
				if (this.get('iconStyle') == 'button') {
					image = 'url(' + icon + '), url(' + icon + '), url(' + icon + ')';
				} else {
					image = 'url(' + icon + ')';
				}
			}
			
			if (asObject) {
				style = {
					'backgroundColor': color,
					'backgroundImage': image
				};
			} else {
				style = 'background-color: ' + color + '; background-image: ' + image + ';';
			}
			
			return style;
		},
		
		
		/* ---------------------------- ... ---------------------------- */
		
		
		_syncUIStyle: function (name, add) {
			var box = this.get('boundingBox');
			if (box) {
				var style = this.get('style');
				if (style) box.addClass(this.getClassName(style));
				
				var style = this.get('groupStyle');
				if (style) box.addClass(this.getClassName('group', style));
				
				var icon_style = this.get('iconStyle');
				if (style && icon_style) {
					box.addClass(this.getClassName('group', style, icon_style));
				}
			}
		},
		_setDisabled: function (disabled) {
			var btn = this.get('nodeButton');
			if (btn) {
				btn.set('disabled', disabled);
			}
		},
		_setLoading: function (loading) {
			var box = this.get('boundingBox');
			
			if (box) {
				if (loading && !this.get('nodeLoading')) {
					var node = Y.Node.create('<span class="loading-icon"></span>');
					this.get('nodeLabel').insert(node, 'after');
					this.set('nodeLoading', node);
				}
				
				box.toggleClass(this.getClassName('loading'), loading);
			}
			
			this.set('disabled', loading);
			return loading;
		},
		
		_setStyle: function (new_style) {
			var box = this.get('boundingBox');
			if (!box) return new_style;
			
			var old_style = this.get('style');
			if (new_style == old_style) return;
			
			if (box) {
				if (old_style) box.removeClass(this.getClassName(old_style));
				if (new_style) box.addClass(this.getClassName(new_style));
			}
		},
		
		_setGroupStyle: function (new_style) {
			var box = this.get('boundingBox');
			if (!box) return new_style;
			
			var old_style = this.get('groupStyle'),
				icon_style = this.get('iconStyle');
			
			if (new_style == old_style) return;
			
			if (box) {
				if (old_style) {
					box.removeClass(this.getClassName('group', old_style));
					if (icon_style) {
						box.removeClass(this.getClassName('group', old_style, icon_style));
					}
				}
				if (new_style) {
					box.addClass(this.getClassName('group', new_style));
					if (icon_style) {
						box.addClass(this.getClassName('group', new_style, icon_style));
					}
				}
			}
		},
		
		_setIconStyle: function (new_style) {
			var box = this.get('boundingBox');
			if (!box) return new_style;
			
			var group_style = this.get('groupStyle'),
				old_style = this.get('iconStyle');
			
			if (new_style == old_style) return;
			
			if (box) {
				if (old_style) box.removeClass(this.getClassName('group', group_style, old_style));
				if (new_style) box.addClass(this.getClassName('group', group_style, new_style));
			}
		},
		
		_setDown: function (down) {
			var box = this.get('boundingBox');
			if (!box) return !!down;
			if (down == this.get('down')) return !!down;
			
			if (box) {
				box.toggleClass(this.getClassName('down'), down);
				box.removeClass(this.getClassName('mouse-hover'));
			} 
			
			return !!down;
		},
		
		_setLabel: function (label) {
			var labelNode = this.get('nodeLabel'),
				escaped = null,
				node = null;
			
			if (labelNode) {
				label = label ? Supra.Intl.replace(label) : '';
				escaped = label ? Y.Escape.html(label) : '&nbsp;';
				labelNode.set('innerHTML', escaped);
				
				//Buttons with group style doesn't have labels, use "title" attribute
				if (this.get('style') == 'group' && this.get('icon')) {
					this.set('title', label);
				}
			}
		},
		
		_setTitle: function (title) {
			var btn = this.get('nodeButton');
			if (btn) {
				btn.setAttribute('title', title || '');
			}
			
			return title || '';
		},
		
		_setVisible: function (visible) {
			var box = this.get('boundingBox');
			if (box) box.toggleClass('hidden', !visible);
			return visible;
		},
		
		_setIcon: function (value) {
			
			if (this.get('style') == 'group') {
				if (this.get('iconStyle') != 'html') {
					var node = this.get('contentBox').one('.su-button-bg > div'),
						styles = null;
					
					if (node) {
						styles = this._getButtonBackgroundStyle(value, true);
						node.setStyles(styles);
					}
				}
			} else {
				var img = this.get('contentBox').one('img');
				if (!img) {
					if (!value) return value;
					var button = this.get('nodeButton');
					if (!button) return value;
					
					var template = this.get('style') == 'group' ? this.ICON_TEMPLATE_GROUP : this.ICON_TEMPLATE;
					var node = Y.Node.create(template);
					button.prepend(node);
					
					img = node.test('img') ? node : node.one('img');
					img.setAttribute('src', value);
				} else {
					if (!value) {
						img.remove();
						return value;
					}
					img.setAttribute('src', value);
				}
			}
			
			return value;
		},
		
		_onMouseDown: function () {
			if (this.get('disabled')) return;
			var box = this.get('boundingBox');
			if (box) {
				box.addClass(this.getClassName('mouse-down'));
			}
		},
		
		_onMouseUp: function () {
			if (this.get('disabled')) return;
			var box = this.get('boundingBox');
			if (box) {
				box.removeClass(this.getClassName('mouse-down'));
				box.toggleClass(this.getClassName('down'), this.get('down'));
				
				if (this.get('down')) {
					box.removeClass(this.getClassName('mouse-hover'));
				}
			}
		},
		
		_onMouseOver: function () {
			if (this.get('disabled')) return;
			var box = this.get('boundingBox');
			if (box) box.addClass(this.getClassName('mouse-hover'));
		},
		
		_onMouseOut: function () {
			if (this.get('disabled')) return;
			var box = this.get('boundingBox');
			if (box) {
				box.removeClass(this.getClassName('mouse-down'));
				box.removeClass(this.getClassName('mouse-hover'));
			}
		},
		
		_onDisabledPreventClick: function (evt) {
			if (this.get('disabled')) evt.halt(true);
		},
		
		_onClick: function (evt) {
			if (this.get('disabled')) return evt.halt(true);
			if (this.get('type') == 'toggle') {
				var down = !this.get('down');
				this.set('down', down);
			}
		},
		
		addClass: function () {
			var box = this.get('boundingBox');
			if (box) box.addClass.apply(box, arguments);
			return this;
		},
		
		removeClass: function () {
			var box = this.get('boundingBox');
			if (box) box.removeClass.apply(box, arguments);
			return this;
		},
		
		toggleClass: function () {
			var box = this.get('boundingBox');
			if (box) box.toggleClass.apply(box, arguments);
			return this;
		},
		
		hasClass: function () {
			var box = this.get('boundingBox');
			if (box) return box.hasClass.apply(box, arguments);
			return false;
		}
	});
	
	Supra.Button = Button;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
	
}, YUI.version, {'requires': ['node-focusmanager', 'widget', 'widget-child']});