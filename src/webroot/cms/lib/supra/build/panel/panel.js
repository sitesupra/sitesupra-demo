YUI.add('supra.panel', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var KEY_ESCAPE = 27;
	
	var ARROW_CLASSNAMES = {
		'L': 'left',
		'R': 'right',
		'T': 'top',
		'B': 'bottom',
		'C': 'center'
	};
	
	/**
	 * Panel class
	 * 
	 * @extends Y.Overlay, Y.Widget
	 * @param {Object} config Attribute values
	 */
	function Panel (config) {
		Panel.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Panel.NAME = 'panel';
	Panel.CSS_PREFIX = 'su-' + Panel.NAME;
	Panel.CLASS_NAME = Y.ClassNameManager.getClassName(Panel.NAME);
	
	Panel.ARROW_L = 'L';
	Panel.ARROW_R = 'R';
	Panel.ARROW_T = 'T';
	Panel.ARROW_B = 'B';
	Panel.ARROW_C = 'C';
	
	/*
	 * Panel attributes, default attribute values
	 */
	Panel.ATTRS = {
		/**
		 * Arrow visibility
		 */
		arrowVisible: {
			value: false,
			setter: '_setArrowVisible'
		},
		
		/**
		 * Arrow position
		 */
		arrowPosition: {
			value: ['T', 'C'],
			setter: '_setArrowPosition'
		},
		
		/**
		 * Arrow alignment
		 */
		arrowAlign: {
			value: null,
			setter: '_arrowAlign'
		},
		
		/**
		 * Align target
		 */
		alignTarget: {
			value: null
		},
		
		/**
		 * Align position
		 */
		alignPosition: {
			value: null,
			setter: '_setAlignPosition'
		},
		
		/**
		 * Close button visibility
		 */
		closeVisible: {
			value: false,
			setter: '_setCloseVisible'
		},
		
		/**
		 * Automatically close when clicked outside
		 */
		autoClose: {
			value: false
		},
		
		/**
		 * Close when user presses return key
		 */
		closeOnEscapeKey: {
			value: false
		},
		
		/**
		 * UI style, ("", "dark")
		 */
		style: {
			value: ''
		},
		
		/**
		 * Mask all other content
		 */
		useMask: {
			value: false,
			setter: '_useMask'
		}
	};
	
	Y.extend(Panel, Y.Overlay, {
		/**
		 * Arrow node (Y.Node instance)
		 * @type {Object}
		 * @private
		 */
		_arrow: null,
		
		/**
		 * Close button node (Y.Node instance)
		 * @type {Object}
		 * @private
		 */
		_close: null,
		
		/**
		 * Fade animation (Y.Anim instance)
		 * @type {Object}
		 * @private
		 */
		_fade_anim: null,
		
		/**
		 * Document click handler
		 * @type {Object}
		 * @private
		 */
		_on_click: null,
		
		/**
		 * Arrow template
		 * @type {String}
		 * @private
		 */
		ARROW_TEMPLATE: '<div></div>',
		
		/**
		 * Arrow classname
		 * @type {String}
		 * @private
		 */
		ARROW_CLASSNAME: 'arrow',
		
		/**
		 * Arrow offset from corners
		 * @type {Number}
		 * @private
		 */
		ARROW_OFFSET: 20,
		
		/**
		 * Content padding, used to calculate arrow / node position
		 * @type {Number}
		 * @private
		 */
		CONTENT_PADDING: 10,
		
		/**
		 * Close button template
		 * @type {String}
		 * @private
		 */
		CLOSE_TEMPLATE: '<button type="button">Close</button>',
		
		/**
		 * Close button classname
		 * @type {String}
		 * @private
		 */
		CLOSE_CLASSNAME: 'close',
		
		/**
		 * useMask attribute setter
		 * Use mask to cover all other content
		 * 
		 * @param {Boolean} useMask
		 * @return New value
		 * @type {Boolean}
		 * @private
		 */
		_useMask: function (useMask) {
			var maskNode = this.get('maskNode');
			
			if (useMask) {
				if (!maskNode) {
					var classname = this.getClassName('mask'),
						body = new Y.Node(document.body);
					
					if (!this.get('visible')) classname += ' hidden';
					
					maskNode = Y.Node.create('<div class="' + classname + '"></div>');
					maskNode.setStyle('zIndex', this.get('zIndex'));
					body.prepend(maskNode);
					
					this.set('maskNode', maskNode);
				}
			} else {
				if (maskNode) {
					maskNode.remove();
					this.set('maskNode', null);
				}
			}
			
			return useMask;
		},
		
		/**
		 * 
		 */
		_handleStyleChange: function (e) {
			var node = this.get('boundingBox'),
				className = '';
			
			if (e.prevVal) {
				className = this.getClassName('style', e.prevVal);
				node.removeClass(className);
			}
			if (e.newVal) {
				className = this.getClassName('style', e.newVal);
				node.addClass(className);
			}
		},
		
		/**
		 * Set arrow visiblity.
		 * Creates arrow node if it doesn't exist
		 * 
		 * @param {Boolean} visible
		 * @return New value
		 * @type {Boolean}
		 * @private
		 */
		_setArrowVisible: function (visible) {
			if (visible) {
				if (this._arrow) {
					this._arrow.removeClass('hidden');
				} else {
					var pos = this.get('arrowPosition');
					
					this._arrow = Y.Node.create(this.ARROW_TEMPLATE);
					this._arrow.addClass(this.getClassName(this.ARROW_CLASSNAME));
					this.get('boundingBox').addClass(this.getClassName(this.ARROW_CLASSNAME, ARROW_CLASSNAMES[pos[0]]));
					this.get('contentBox').prepend(this._arrow);
				}
				
				this._syncArrowPosition();
			} else {
				if (this._arrow) this._arrow.addClass('hidden');
			}
			
			return visible;
		},
		
		/**
		 * Set arrow position
		 * 
		 * @param {Array} position
		 * @return New value
		 * @type {Array}
		 * @private
		 */
		_setArrowPosition: function (pos) {
			var old = this.get('arrowPosition') || ['T', 'C'];
			if (!Y.Lang.isArray(pos) &&
				pos.length != 2 && (
				pos[0] != 'L' ||
				pos[0] != 'R' ||
				pos[0] != 'T' ||
				pos[0] != 'B'))
			{
					return old;
			}
			
			if (old[0] != pos[0] && this._arrow) {
				var boundingBox = this.get('boundingBox');
				var classname = this.getClassName(this.ARROW_CLASSNAME, ARROW_CLASSNAMES[old[0]]);
				boundingBox.removeClass(classname);
				
				classname = this.getClassName(this.ARROW_CLASSNAME, ARROW_CLASSNAMES[pos[0]]);
				boundingBox.addClass(classname);
			}
			
			return pos;
		},
		
		/**
		 * Set align position
		 * 
		 * @param {String} position Align position
		 * @return New value
		 * @type {String}
		 * @private
		 */
		_setAlignPosition: function (position) {
			switch(position) {
				case 'L':
					this.set('arrowPosition', ['L', 'C']);
					this.set('align', {'node': this.get('alignTarget'), 'points': [Y.WidgetPositionAlign.LC, Y.WidgetPositionAlign.RC]});
					this.set('arrowAlign', this.get('alignTarget'));
					break;
				case 'R':
					this.set('arrowPosition', ['R', 'C']);
					this.set('align', {'node': this.get('alignTarget'), 'points': [Y.WidgetPositionAlign.RC, Y.WidgetPositionAlign.LC]});
					this.set('arrowAlign', this.get('alignTarget'));
					break;
				case 'T':
					this.set('arrowPosition', ['T', 'C']);
					this.set('align', {'node': this.get('alignTarget'), 'points': [Y.WidgetPositionAlign.TC, Y.WidgetPositionAlign.BC]});
					this.set('arrowAlign', this.get('alignTarget'));
					break;
				case 'B':
					this.set('arrowPosition', ['B', 'C']);
					this.set('align', {'node': this.get('alignTarget'), 'points': [Y.WidgetPositionAlign.BC, Y.WidgetPositionAlign.TC]});
					this.set('arrowAlign', this.get('alignTarget'));
					break;
			}
		},
		
		/**
		 * Update arrow position
		 * 
		 * @private
		 */
		_syncArrowPosition: function () {
			if (!this.get('arrowVisible')) return;
			
			this._arrow.setStyle('top', null);
			
			var target = this.get('arrowAlign');
			if (target) {
				this._arrowAlign(target);
			}
			
			var position = this.get('arrowPosition');
			var offset = 0;
			
			if (position[0] == 'L' || position[0] == 'R') {
				//Arrow is positioned on the left or right side of the panel
				
				switch(position[1]) {
					//Arrow should be near left/right top corner
					case 'T': offset = this.ARROW_OFFSET; break;
					//Arrow should be near left/right bottom corner
					case 'B': offset = this.get('boundingBox').get('offsetHeight') - this._arrow.get('offsetHeight') - this.ARROW_OFFSET; break;
					//Arrow should be vertically centered
					case 'C': offset = ~~((this.get('boundingBox').get('offsetHeight') - this._arrow.get('offsetHeight')) / 2); break;
					//Arrow position is set in pixels
					default: offset = parseInt(position[1], 10) || 0;
				}
				this._arrow.setStyle('top', offset + 'px');
			} else {
				//Arrow is positioned at the top or bottom of the panel
				switch(position[1]) {
					case 'L': offset = this.ARROW_OFFSET; break;
					case 'R': offset = this.get('boundingBox').get('offsetWidth') - this._arrow.get('offsetWidth') - this.ARROW_OFFSET; break;
					case 'C': offset = ~~((this.get('boundingBox').get('offsetWidth') - this._arrow.get('offsetWidth')) / 2); break;
					//Arrow position is set in pixels
					default: offset = parseInt(position[1], 10) || 0;
				}
				
				this._arrow.setStyle('left', offset + 'px');
			}
		},
		
		/**
		 * Set close button visibility
		 * Creates button if it doesn't exist
		 * 
		 * @param {Boolean} value
		 * @return New visibility value
		 * @type {Boolean}
		 * @private
		 */
		_setCloseVisible: function (value) {
			if (value) {
				if (this._close) {
					this._close.set('visible', true);
				} else {
					var btn = Y.Node.create(this.CLOSE_TEMPLATE);
					this.get('contentBox').prepend(btn);
					
					this._close = new Supra.Button({
						srcNode: btn,
						style: this.CLOSE_CLASSNAME
					});
					this._close.render();
					
					this._close.on('click', function () {
						this.hide();
					}, this);
				}
			} else if (this._close) {
				this._close.set('visible', false);
			}
			
			return !!value;
		},
		
		/**
		 * Align arrow to element
		 * 
		 * @param {Object} target
		 * @private
		 */
		_arrowAlign: function (target) {
			if (!this.get('arrowVisible') || !target) return this;
			
			//Create Y.Node from HTMLElement 
			if (target.nodeType) target = new Y.Node(target);
			
			//If widgets then get bounding box 
			else if (!(target instanceof Y.Node) && 'isInstanceOf' in target && target.isInstanceOf('widget')) target = target.get('boundingBox');
			
			var position = this.get('arrowPosition'), box = this.get('boundingBox'),
				host_offset = 0, host_size = 0,
				target_offset = 0, target_size = 0,
				style_attr = 'left', offset = null;
			
			if (position[0] == 'L' || position[0] == 'R') {
				//Arrow is positioned on the left or right side of panel
				host_offset = box.getY();
				host_size = box.get('offsetHeight');
				target_size = target.get('offsetHeight');
				target_offset = target.getY() + ~~(target_size / 2 - this.CONTENT_PADDING / 2);
				style_attr = 'top';
			} else {
				//Arrow is positioned at the top or bottom of panel
				host_offset = box.getX();
				host_size = box.get('offsetWidth');
				target_size = target.get('offsetWidth');
				target_offset = target.getX() + ~~(target_size / 2 - this.CONTENT_PADDING / 2);
			}
			
			offset = Math.min(Math.max(this.ARROW_OFFSET, target_offset - host_offset), host_size);
			position[1] = offset;
			
			this.set('arrowPosition', position);
			this._arrow.setStyle(style_attr, offset + 'px');
			
			return target;
		},
		
		/**
		 * Align the given point on the widget, with the XY page co-ordinates provided.
         *
         * @param {String} widgetPoint Supported point constant (e.g. WidgetPositionAlign.TL)
         * @param {Number} x X page co-ordinate to align to
         * @param {Number} y Y page co-ordinate to align to
         * @private
		 */
		_doAlign: function (widgetPoint, x, y) {
			var arrowVisible  = this.get('arrowVisible'),
				arrowAlign    = this.get('arrowAlign'),
				arrowPosition = this.get('arrowPosition');
			
			if (arrowVisible && arrowAlign && arrowPosition) {
				switch (arrowPosition[0]) {
					case 'L':
						x += 17;
						break;
					case 'R':
						x -= 17;
						break;
					case 'T':
						y += 17;
						break;
					case 'B':
						y -= 17;
						break;
				}
			}
			
			Panel.superclass._doAlign.call(this, widgetPoint, x, y);
		},
		
		/**
		 * Align arrow to point at target element
		 * 
		 * @param {HTMLElement} target Target element
		 * @return Panel instance
		 * @type {Object}
		 */
		arrowAlign: function (target) {
			this.set('arrowAlign', target);
		},
		
		/**
		 * Set close button visibility
		 * 
		 * @param {Boolean} visible
		 * @return Panel instance
		 * @type {Object}
		 */
		setCloseVisible: function (visible) {
			this.set('closeVisible', visible);
			return this;
		},
		
		/**
		 * Set arrow position
		 * 
		 * @param {Array} position
		 */
		setArrowPosition: function (position) {
			this.set('arrowPosition', position);
			return this;
		},
		
		/**
		 * Set arrow visibility
		 * 
		 * @param {Boolean} visible
		 * @return Panel instance
		 * @type {Object}
		 */
		setArrowVisible: function (visible) {
			this.set('arrowVisible', visible);
			return this;
		},
		
		renderUI: function () {
			Panel.superclass.renderUI.apply(this, arguments);
			
			this.get('contentBox').removeClass('hidden').setAttribute('tabindex', 0);
			
			if (this.get('closeVisible')) {
				this._setCloseVisible(true);
			}
			
			if (this.get('arrowVisible')) {
				this._setArrowVisible(this.get('arrowVisible'));
			}
			if (this.get('style')) {
				this._handleStyleChange({'newVal': this.get('style'), 'prevVal': ''});
			}
			
			Supra.immediate(this, this.syncUI);
		},
		
		bindUI: function () {
			Panel.superclass.bindUI.apply(this, arguments);
			
			//On visible change show/hide mask
			this.on('visibleChange', function (evt) {
				var maskNode = this.get('maskNode');
				if (evt.newVal != evt.prevVal) {
					if (maskNode) {
						if (evt.newVal) {
							maskNode.removeClass('hidden');
						} else {
							maskNode.addClass('hidden');
						}
					}
					
					if (!evt.newVal && this._on_click) {
						this._on_click.detach();
						this._on_click = null;
					}
				}
			}, this);
			
			//On zIndex change update mask
			this.on('zIndexChange', function (evt) {
				var maskNode = this.get('maskNode');
				if (maskNode) {
					maskNode.setStyle('zIndex', evt.newVal);
				}
			});
			
			//Before destroy remove mask
			this.before('destroy', function () {
				var maskNode = this.get('maskNode');
				if (maskNode) maskNode.remove();
				
				//Remove animation if it exists
				if (this._fade_anim) {
					this._fade_anim.destroy();
					delete(this._fade_anim);
				}
			});
			
			//On style change update it
			this.on('styleChange', this._handleStyleChange, this);
			
			this.get('contentBox').on('keydown', this.onKeyDown, this);
		},
		
		syncUI: function () {
			Panel.superclass.syncUI.apply(this, arguments);
			this._syncArrowPosition();
			
			//Update position
			var position = this.get('alignPosition');
			if (position) {
				this._setAlignPosition(position);
			}
		},
		
		/**
		 * Handle key down
		 * 
		 * @private 
		 */
		onKeyDown: function (e) {
			if (e.keyCode == KEY_ESCAPE && this.get('closeOnEscapeKey')) {
				this.hide();
			}
		},
		
		/**
		 * Hide panel
		 */
		hide: function () {
			this.get('boundingBox').addClass('su-panel-hidden');
			Panel.superclass.hide.apply(this, arguments);
			
			return this;
		},
		
		/**
		 * Check if user clicked outside panel
		 * 
		 * @param {Event} event Event facade object
		 * @private
		 */
		validateClick: function (event) {
			if (this.get('autoClose')) {
				var target = event.target.closest('div.su-panel');
				if (!target || !target.compareTo(this.get('boundingBox'))) {
					this.hide();
				}
			}
		},
		
		/**
		 * Show panel
		 */
		show: function () {
			this.get('boundingBox').removeClass('su-panel-hidden');
			
			Panel.superclass.show.apply(this, arguments);
			
			this.syncUI();
			
			Supra.immediate(this, function () {
				this.syncUI();
				
				//Auto hide when clicked outside panel
				if (this.get('visible') && this.get('autoClose')) {
					this._on_click = Y.one(document).on('click', this.validateClick, this);
				}
			});
			
			this.get('contentBox').focus();
			
			return this;
		},
		
		/**
		 * Fade in
		 */
		fadeIn: function () {
			var bounding_box = this.get('boundingBox');
			bounding_box.setStyle('opacity', 0);
			
			this.show();
			
			var from = {opacity: 0},
				to   = {opacity: 1},
				pos  = this.get('alignPosition');
			
			if (this.get('arrowVisible')) {
				switch(pos) {
					case 'L':
						from['margin-left'] = '-18px';
						to['margin-left'] = '0';
						break;
					case 'R':
						from['margin-left'] = '18px';
						to['margin-left'] = '0';
						break;
					case 'T':
						from['margin-top'] = '-18px';
						to['margin-top'] = '0';
						break;
					case 'B':
						from['margin-top'] = '18px';
						to['margin-top'] = '0';
						break;
				}
			}
			
			if (this._fade_anim) {
				this._fade_anim.stop(true);
				this._fade_anim.set('from', from);
				this._fade_anim.set('to', to);
			} else {
				this._fade_anim = new Y.Anim({
					'node': bounding_box,
					'from': from,
					'to': to,
					'duration': 0.25,
					'easing': Y.Easing.easeOut
				});
			}
			
			this._fade_anim.run();
		},
		
		/**
		 * Fade out
		 */
		fadeOut: function () {
			var bounding_box = this.get('boundingBox');
			bounding_box.setStyle('opacity', 1);
			this.show();
			
			if (this._fade_anim) {
				this._fade_anim.stop(true);
				this._fade_anim.set('from', {opacity: 1});
				this._fade_anim.set('to', {opacity: 0});
			} else {
				this._fade_anim = new Y.Anim({
					'node': bounding_box,
					'from': {opacity: 1},
					'to': {opacity: 0},
					'duration': 0.25,
					'easing': Y.Easing.easeOut
				});
			}
			
			//this._fade_anim.once('end', this.hide, this);
			this._fade_anim.run();
		},
		
		/**
		 * Add classname to panels most outer element
		 * 
		 * @param {String} classname
		 */
		addClass: function (classname) {
			var box = this.get('boundingBox');
			if (box) box.addClass(classname);
			return this;
		},
		
		/**
		 * Add or remove classname to panels most outer element
		 * 
		 * @param {String} classname
		 */
		toggleClass: function (classname, value) {
			var box = this.get('boundingBox');
			if (box) box.toggleClass(classname, value);
			return this;
		},
		
		/**
		 * Remove classname from panels most outer element
		 * 
		 * @param {String} classname
		 */
		removeClass: function (classname) {
			var box = this.get('boundingBox');
			if (box) box.removeClass(classname);
			return this;
		},
		
		/**
		 * Returns if panels most outer element has clasname
		 * 
		 * @param {String} classname
		 * @return True if panels most outer element has classname, otherwise false
		 * @type {Boolean}
		 */
		hasClass: function (classname) {
			var box = this.get('boundingBox');
			if (box) return box.hasClass(classname);
			return false;
		}
	});
	
	Supra.Panel = Panel;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['overlay', 'supra.button']});