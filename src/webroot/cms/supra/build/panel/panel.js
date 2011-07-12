//Invoke strict mode
"use strict";

YUI.add('supra.panel', function (Y) {
	
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
		 * Close button visibility
		 */
		closeVisible: {
			value: false,
			setter: '_setCloseVisible'
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
		ARROW_OFFSET: 15,
		
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
					this._arrow.addClass(Y.ClassNameManager.getClassName(Panel.NAME, this.ARROW_CLASSNAME));
					this._arrow.addClass(Y.ClassNameManager.getClassName(Panel.NAME, this.ARROW_CLASSNAME, ARROW_CLASSNAMES[pos[0]]));
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
				var classname = Y.ClassNameManager.getClassName(Panel.NAME, this.ARROW_CLASSNAME, ARROW_CLASSNAMES[old[0]]);
				this._arrow.removeClass(classname);
				
				var classname = Y.ClassNameManager.getClassName(Panel.NAME, this.ARROW_CLASSNAME, ARROW_CLASSNAMES[pos[0]]);
				this._arrow.addClass(classname);
			}
			
			return pos;
		},
		
		/**
		 * Update arrow position
		 * 
		 * @private
		 */
		_syncArrowPosition: function () {
			if (!this.get('arrowVisible')) return;
			
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
					default: offset = parseInt(position[1]) || 0;
				}
				this._arrow.setStyle('top', offset + 'px');
			} else {
				//Arrow is positioned at the top or bottom of the panel
				switch(position[1]) {
					case 'L': offset = this.ARROW_OFFSET; break;
					case 'R': offset = this.get('boundingBox').get('offsetWidth') - this._arrow.get('offsetWidth') - this.ARROW_OFFSET; break;
					case 'C': offset = ~~((this.get('boundingBox').get('offsetWidth') - this._arrow.get('offsetWidth')) / 2); break;
					//Arrow position is set in pixels
					default: offset = parseInt(position[1]) || 0;
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
			else if (!(target instanceof Y.Node) && 'hasClass' in target && target.hasClass(Y.Widget)) target = target.get('boundingBox');
			
			var position = this.get('arrowPosition'), box = this.get('boundingBox'),
				host_offset = 0, host_size = 0,
				target_offset = 0, target_size = 0,
				style_attr = 'left', offset = null;
			
			if (position[0] == 'L' || position[1] == 'R') {
				//Arrow is positioned on the left or right side of panel
				host_offset = box.getY();
				host_size = box.get('offsetHeight');
				target_size = target.get('offsetHeight');
				target_offset = target.getY() + ~~(target_size / 2);
				style_attr = 'top';
			} else {
				//Arrow is positioned at the top or bottom of panel
				host_offset = box.getX();
				host_size = box.get('offsetWidth');
				target_size = target.get('offsetWidth');
				target_offset = target.getX() + ~~(target_size / 2);
			}
			
			offset = Math.min(Math.max(this.ARROW_OFFSET, target_offset - host_offset), host_size);
			position[1] = offset;
			
			this.set('arrowPosition', position);
			this._arrow.setStyle(style_attr, offset + 'px');
			
			return target;
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
			
			this.get('contentBox').removeClass('hidden');
			
			if (this.get('closeVisible')) {
				this._setCloseVisible(true);
			}
			
			if (this.get('arrowVisible')) {
				this._setArrowVisible(this.get('arrowVisible'));
			}
			
			Y.later(1, this, this.syncUI);
		},
		
		syncUI: function () {
			Panel.superclass.syncUI.apply(this, arguments);
			this._syncArrowPosition();
		},
		
		/**
		 * Hide panel
		 */
		hide: function () {
			this.get('boundingBox').addClass('yui3-panel-hidden');
			Panel.superclass.hide.apply(this, arguments);
			
			return this;
		},
		
		/**
		 * Show panel
		 */
		show: function () {
			this.get('boundingBox').removeClass('yui3-panel-hidden');
			
			Panel.superclass.show.apply(this, arguments);
			
			this.syncUI();
			
			return this;
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
	
}, YUI.version, {requires: ['overlay', 'supra.button', 'supra.panel-css']});