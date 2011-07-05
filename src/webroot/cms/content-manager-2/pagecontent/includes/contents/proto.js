//Invoke strict mode
"use strict";

YUI.add('supra.page-content-proto', function (Y) {
	
	//Shortcuts
	var Manager = SU.Manager,
		Action = Manager.PageContent,
		getClassName = Y.bind(Y.ClassNameManager.getClassName, Y.ClassNameManager);
	
	//Templates
	var HTML_CLICK = '<span>Click to edit</span>';
	var HTML_CLICK_DRAG = '<span>Click to edit<br />Drag &amp; drop to move</span>';
	
	/**
	 * Content block
	 */
	function ContentProto () {
		this.children = {};
		this.node = null;
		this.overlay = null;
		
		ContentProto.superclass.constructor.apply(this, arguments);
	}
	
	ContentProto.NAME = 'page-content-proto';
	ContentProto.CLASS_NAME = getClassName(ContentProto.NAME);
	
	//CSS classes
	var CLASSNAME = getClassName('content'),									// yui3-content
		CLASSNAME_OVERLAY = getClassName('content', 'overlay'),					// yui3-content-overlay
		CLASSNAME_OVERLAY_HOVER = getClassName('content', 'overlay', 'hover'),	// yui3-content-overlay-hover
		CLASSNAME_DRAGABLE = getClassName('content', 'dragable'),				// yui3-content-dragable
		CLASSNAME_EDITING = getClassName('content', 'editing');					// yui3-content-editing
	
	ContentProto.ATTRS = {
		'data': {
			value: null
		},
		'win': {
			value: null
		},
		'doc': {
			value: null
		},
		'body': {
			value: null
		},
		'editing': {
			value: false,
			setter: '_setEditing'
		},
		'parent': {
			value: null
		},
		'super': {
			value: null
		},
		'editable': {
			value: false,
			writeOnce: true
		},
		'dragable': {
			value: false,
			setter: '_setDragable'
		},
		'highlight': {
			value: false,
			setter: '_setHighlight'
		},
		'highlightOverlay': {
			value: false,
			setter: '_setHighlightOverlay'
		},
		'title': {
			value: ''
		},
		
		/**
		 * HTML which will be used instead of DOM
		 */
		'html': {
			value: ''
		}
	};
	
	Y.extend(ContentProto, Y.Base, {
		children: {},
		node: null,
		overlay: null,
		
		destructor: function () {
			if (this.get('editing')) {
				this.get('super').set('activeContent', null);
			}
			
			if (this.overlay) {
				this.overlay.remove();
			}
			
			var node = this.getNode();
			if (node) {
				node.remove();
			}
		},
		
		getType: function () {
			var data = this.get('data');
			return (data ? data.type : null);
		},
		
		getId: function () {
			var data = this.get('data');
			return (data ? data.id : null);
		},
		
		getNodeId: function () {
			var id = 'content_' + this.getType() + '_' + this.getId();
			return id;
		},
		
		getTitle: function () {
			return this.get('title');
		},
		
		getNode: function () {
			if (!this.node) {
				this.node = this.get('body').one('#' + this.getNodeId());
			}
			return this.node;
		},
		
		render: function () {
			this.renderUI();
			this.bindUI();
			
			//Use timeout to make sure everything is styled before doing sync
			setTimeout(Y.bind(this.syncUI, this), 1);
		},
		
		bindUI: function () {
			if (this.get('editable') && this.overlay) {
				this.overlay.on('click', function() {
					
					this.get('super').set('activeContent', this);
					
				}, this);
				
				//Handle block save / cancel
				this.on('block:save', function () {
					// Unset active content
					this.get('super').set('activeContent', null);
				});
				this.on('block:cancel', function () {
					// Unset active content
					this.get('super').set('activeContent', null);
				});
			}
		},
		
		syncUI: function (traverse) {
			if (this.overlay) {
				var node = this.getNode();
				var w = node.get('offsetWidth'), h = node.get('offsetHeight');
			
				this.overlay.setStyles({
					width: w + 'px',
					height: h + 'px'
				});
			}
			
			if (traverse !== false) {
				for (var i in this.children) {
					this.children[i].syncUI();
				}
			}
		},
		
		renderOverlay: function () {
			var div = new Y.Node(this.get('doc').createElement('DIV')),
				html = HTML_CLICK;
			
			this.overlay = div;
			
			if (this.get('dragable')) {
				this.overlay.addClass(CLASSNAME_DRAGABLE);
				html = HTML_CLICK_DRAG;
			}
			
			this.overlay.addClass(CLASSNAME_OVERLAY);
			this.overlay.set('innerHTML', html);
			this.getNode().insert(div, 'before');
		},
		
		createBlock: function (data, attrs) {
			var win = this.get('win');
			var doc = this.get('doc');
			var body = this.get('body');
			
			var type = data.type;
			var properties = Manager.Blocks.getBlock(type);
			var classname = properties && properties.classname ? properties.classname : type[0].toUpperCase() + type.substr(1);
			
			if (classname in Action) {
				var block = this.children[data.id] = new Action[classname](SU.mix(attrs || {}, {
					'doc': doc,
					'win': win,
					'body': body,
					'data': data,
					'parent': this,
					'super': this.get('super')
				}));
				block.render();
			} else {
				Y.error('Class "' + classname + '" for content "' + data.id + '" is missing.');
			}
			
			return block;
		},
		
		renderUI: function () {
			var data = this.get('data');
			
			if ('contents' in data) {
				for(var i=0,ii=data.contents.length; i<ii; i++) {
					this.createBlock(data.contents[i], {
						'dragable': !data.contents[i].locked && !this.isLocked(),
						'editable': !data.contents[i].locked
					});
				}
			}
			
			if (!this.getNode()) {
				var type = data.type,
					id = data.id,
					classname_type = CLASSNAME + '-' + type,
					node = Y.Node.create('<div id="content_' + type + '_' + id + '" class="' + CLASSNAME + ' '  + classname_type + '">' + data.value || '' + '</div>');
				
				this.node = node;
				this.get('parent').getNode().append(node);
			}
			
			if (this.get('dragable')) {
				if (!this.isLocked()) {
					this.set('dragable', true);
				} else {
					this.set('dragable', false);
				}
			}
		},
		
		/**
		 * Returns if specific child type is allowed
		 * If is locked then child is not allowed
		 * 
		 * @param {String} type Block type
		 * @return True if child with type is allowed, otherwise false
		 * @type {Boolean}
		 */
		isChildTypeAllowed: function (type) {
			var data = this.get('data');
			if ('allow' in data && !this.isLocked()) {
				for(var i=0, ii=data.allow.length; i<ii; i++) {
					if (data.allow[i] == type) {
						return true;
					}
				}
			}
			return false;
		},
		
		/**
		 * Returns if block is locked
		 * 
		 * @return True if block is locked, otherwise false
		 * @type {Boolean}
		 */
		isLocked: function () {
			var data = this.get('data');
			if ('locked' in data) {
				return data.locked;
			}
			return false;
		},
		
		/**
		 * Returns if parent is locked
		 * 
		 * @return True if parent is locked, otherwise false
		 * @type {Boolean}
		 */
		isParentLocked: function () {
			var parent = this.get('parent');
			if (parent) {
				return parent.isLocked();
			} else {
				return false;
			}
		},
		
		
		removeChild: function (child) {
			for(var i in this.children) {
				if (this.children[i] === child) {
					delete(this.children[i]);
					child.destroy();
				}
			}
		},
		
		/**
		 * Returns all block properties
		 * 
		 * @return List of block properties
		 * @type {Object}
		 */
		getProperties: function () {
			var data = this.get('data'),
				type = data && data.type ? data.type : null,
				properties = type ? Manager.Blocks.getBlock(type) : null;
			
			return properties ? properties.properties : null;
		},
		
		_setDragable: function (value) {
			var node = this.overlay;
			
			if (node) {
				if (value) {
					node.addClass(CLASSNAME_DRAGABLE);
				} else {
					node.removeClass(CLASSNAME_DRAGABLE);
				}	
			}
			
			return !!value;
		},
		
		_setEditing: function (value) {
			if (value == this.get('editing')) return !!value;
			
			if (value) {
				if (this.overlay) {
					this.overlay.addClass(CLASSNAME_EDITING);
				}
				this.getNode().addClass(CLASSNAME_EDITING);
				
				this.fire('editing-start');
				this.get('super').fire('editing-start', this.get('data'));
			} else {
				if (this.overlay) this.overlay.removeClass(CLASSNAME_EDITING);
				this.getNode().removeClass(CLASSNAME_EDITING);
				
				this.fire('editing-end');
				this.get('super').fire('editing-end', this.get('data'));
				this.syncUI();
			}
			
			return !!value;
		},
		
		_setHighlightOverlay: function (value) {
			if (!this.overlay) return false;
			if (value == this.get('highlightOverlay')) return !!value;
			
			if (value) {
				this.overlay.addClass(CLASSNAME_OVERLAY_HOVER);
			} else {
				this.overlay.removeClass(CLASSNAME_OVERLAY_HOVER);
			}
			
			return !!value;
		},
		
		_setHighlight: function (value) {
			if (value) {
				this.getNode().addClass('yui3-highlight-' + this.get('data').type);
				if (this.get('editing')) {
					this.set('editing', false);
				}
				if (this.get('highlightOverlay')) {
					this.set('highlightOverlay', false);
				}
			} else {
				this.getNode().removeClass('yui3-highlight-' + this.get('data').type);
			}
			
			return !!value;
		}
		
	});
	
	Action.Proto = ContentProto;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['yui-base']});