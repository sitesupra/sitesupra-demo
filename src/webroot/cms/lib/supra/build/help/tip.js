YUI().add('supra.help-tip', function (Y) {
	//Invoke strict mode
	"use strict";
	
	function Note(config) {
		Note.superclass.constructor.apply(this, arguments);
	}
	
	Note.NAME = 'HelpNote';
	Note.CSS_PREFIX = 'su-help-note';
	
	Note.ATTRS = {
		'title': {
			'value': 'Tip',
			'validator': Y.Lang.isString
		},
		
		'description': {
			'value': '',
			'validator': Y.Lang.isString
		},
		
		'width': {
			// Allows to specify fixed width
			'value': 0,
			'validator': Y.Lang.isNumber
		},
		'height': {
			// Allows to speficy fixed height
			'value': 0,
			'validator': Y.Lang.isNumber
		},
		
		'xPosition': {
			// X position within container
			// or array [property, value] where property is 'left' or 'right'
			'value': null
		},
		
		'yPosition': {
			// Y position within container
			// or array [property, value] where property is 'top' or 'bottom'
			'value': null
		},
		
		'position': {
			// CSS position style
			'value': 'absolute',
			'validator': Y.Lang.isString,
			'setter': '_attrSetPosition'
		},
		
		'zIndex': {
			// CSS z-index value
			'value': 1,
			'validator': Y.Lang.isNumber
		}
	};
	
	Y.extend(Note, Y.Widget, {
		
		/**
		 * Heading node
		 * @type {Object}
		 * @private
		 */
		_nodeHeading: null,
		
		/**
		 * Content node
		 * @type {Object}
		 * @private
		 */
		_nodeContent: null,
		
		/**
		 * Close button
		 * @type {Object}
		 * @private
		 */
		_nodeClose: null,
		
		
		/**
		 * Render UI
		 * 
		 * @private
		 */
		renderUI: function () {
			var heading = this._nodeHeading = Y.Node.create('<h2></h2>'),
				content = this._nodeContent = Y.Node.create('<div></div>'),
				close   = this._nodeClose   = Y.Node.create('<a></a>'),
				box     = this.get('contentBox');
			
			heading.addClass(this.getClassName('heading'));
			content.addClass(this.getClassName('inner'));
			close.addClass(this.getClassName('close'));
			
			box.append(close);
			box.append(heading);
			box.append(content);
			
			close.on('click', this._eventClose, this);
		},
		
		/**
		 * Bind UI events
		 * 
		 * @private
		 */
		bindUI: function () {
			this.after('titleChange', this._uiSetTitle, this);
			this.after('descriptionChange', this._uiSetDescription, this);
			
			this.after('widthChange', this._uiSetWidth, this);
			this.after('heightChange', this._uiSetHeight, this);
			this.after('positionChange', this._uiSetPosition, this);
			this.after('xPositionChange', this._uiSetPositionX, this);
			this.after('yPositionChange', this._uiSetPositionY, this);
			this.after('zIndexChange', this._uiSetZIndex, this);
			
			
			this.after('visibleChange', this._uiSetVisible, this);
		},
		
		/**
		 * Sync UI state with widget attribute states at the time of the rendering
		 * 
		 * @private
		 */
		syncUI: function () {
			this._uiSetTitle(this.get('title'));
			this._uiSetDescription(this.get('description'));
			this._uiSetWidth(this.get('width'));
			this._uiSetHeight(this.get('height'));
			this._uiSetPosition(this.get('position'));
			this._uiSetZIndex(this.get('zIndex'));
		},
		
		/**
		 * Destruction life-cycle, clean up
		 * 
		 * @private
		 */
		destructor: function () {
			this._nodeHeading.destroy(true);
			this._nodeHeading = null;
			
			this._nodeContent.destroy(true);
			this._nodeContent = null;
			
			this._nodeClose.destroy(true);
			this._nodeClose = null;
		},
		
		
		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		
		/**
		 * Handle close button click
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		_eventClose: function (event) {
			this.fire('close');
			this.hide();
		},
		
		
		/**
		 * ------------------------------ UI ------------------------------
		 */
		
		
		/**
		 * Update widget position style
		 * 
		 * @param {String} value CSS position
		 * @private
		 */
		_uiSetPosition: function (value) {
			this.get('boundingBox').setStyle('position', value);
			
			this._uiSetPositionX(this.get('xPosition'));
			this._uiSetPositionY(this.get('yPosition'));
		},
		
		/**
		 * Position attribute setter
		 * 
		 * @param {String} value Position value
		 * @private
		 */
		_attrSetPosition: function (value) {
			value = value || 'relative';
			if (value !== 'absolute' && value !== 'relative') value = 'relative';
			return value;
		},
		
		/**
		 * Update widget X position style
		 * 
		 * @param {String|Array} value X position value or array with CSS property and value
		 * @private
		 */
		_uiSetPositionX: function (value) {
			if (Y.Lang.type(value) === 'object' && 'newVal' in value) value = value.newVal;
			
			var property = 'left',
				node     = this.get('boundingBox');
			
			if (Y.Lang.isArray(value)) {
				property = value[0];
				value = value[1];
			}
			if (Y.Lang.isNumber(value)) {
				value = value + 'px';
			}
			
			if (property === 'left') {
				node.setStyle('left', value || 'auto');
				node.setStyle('right', 'auto');
			} else {
				node.setStyle('right', value || 'auto');
				node.setStyle('left', 'auto');
			}
		},
		
		/**
		 * Update widget Y position style
		 * 
		 * @param {String|Array} value Y position value or array with CSS property and value
		 * @private
		 */
		_uiSetPositionY: function (value) {
			if (Y.Lang.type(value) === 'object' && 'newVal' in value) value = value.newVal;
			
			var property = 'top',
				node     = this.get('boundingBox');
			
			if (Y.Lang.isArray(value)) {
				property = value[0];
				value = value[1];
			}
			if (Y.Lang.isNumber(value)) {
				value = value + 'px';
			}
			
			if (property === 'top') {
				node.setStyle('top', value || 'auto');
				node.setStyle('bottom', 'auto');
			} else {
				node.setStyle('bottom', value || 'auto');
				node.setStyle('top', 'auto');
			}
		},
		
		/**
		 * Update widget z-index style
		 * 
		 * @param {Number} value CSS z-index value
		 * @private
		 */
		_uiSetZIndex: function (value) {
			if (Y.Lang.type(value) === 'object' && 'newVal' in value) value = value.newVal;
			if (Y.Lang.type(value) !== 'number') return;
			
			var node = this.get('boundingBox');
			node.setStyle('zIndex', value);
		},
		
		/**
		 * Update widget title
		 * 
		 * @param {String|Object} value Title or attribute change event
		 * @private
		 */
		_uiSetTitle: function (value) {
			if (Y.Lang.type(value) === 'object' && 'newVal' in value) value = value.newVal;
			if (Y.Lang.type(value) !== 'string') return;
			
			this._nodeHeading.set('text', value);
		},
		
		/**
		 * Update widget description
		 * 
		 * @param {String|Object} value Description or attribute change event
		 * @private
		 */
		_uiSetDescription: function (value) {
			if (Y.Lang.type(value) === 'object' && 'newVal' in value) value = value.newVal;
			if (Y.Lang.type(value) !== 'string') return;
			
			this._nodeContent.set('text', value);
		},
		
		/**
		 * Update widget width
		 * 
		 * @param {Number|Object} value Width or attribute change event
		 * @private
		 */
		_uiSetWidth: function (value) {
			if (Y.Lang.type(value) === 'object' && 'newVal' in value) value = value.newVal;
			if (Y.Lang.type(value) !== 'number') {
				value = '';
			}
			
			var node = this.get('boundingBox');
			node.setStyle('width', value);
		},
		
		/**
		 * Update widget height
		 * 
		 * @param {Number|Object} value Height or attribute change event
		 * @private
		 */
		_uiSetHeight: function (value) {
			if (Y.Lang.type(value) === 'object' && 'newVal' in value) value = value.newVal;
			if (Y.Lang.type(value) !== 'number') {
				value = '';
			}
			
			var node = this.get('boundingBox');
			node.setStyle('height', value);
		},
		
		/**
		 * Show/hide widget
		 * 
		 * @param {Boolean|Object} value Visible state or attribute change event
		 * @private
		 */
		_uiSetVisible: function (value) {
			if (Y.Lang.type(value) === 'object' && 'newVal' in value) value = value.newVal;
			
			var node = this.get('boundingBox');
			node.toggleClass('hidden', !value);
		}
		
	});
	
	
	Supra.HelpTip = Note;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['widget']});