//Invoke strict mode
"use strict";

YUI.add('supra.page-content-proto', function (Y) {
	
	//Shortcuts
	var Manager = SU.Manager,
		PageContent = Manager.PageContent,
		getClassName = Y.bind(Y.ClassNameManager.getClassName, Y.ClassNameManager);
	
	//Templates
	var HTML_CLICK = '<span>' + SU.Intl.get(['page', 'click_to_edit']) + '</span>';
	var HTML_CLICK_DRAG = '<span>' + SU.Intl.get(['page', 'click_to_edit_drag_to_move']) + '</span>';
	
	/**
	 * Content block
	 */
	function ContentProto () {
		this.children = {};
		this.children_order = [];
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
		
		/**
		 * Block has changed values
		 */
		'changed': {
			value: false,
			getter: '_getChanged'
		},
		
		/**
		 * HTML which will be used instead of DOM
		 */
		'html': {
			value: ''
		}
	};
	
	Y.extend(ContentProto, Y.Base, {
		/**
		 * Children block list
		 * @type {Object}
		 */
		children: {},
		
		/**
		 * List of block IDs in correct order
		 * @type {Array}
		 */
		children_order: [],
		
		/**
		 * Children order list (children IDs)
		 * @type {Array}
		 */
		order: [],
		
		/**
		 * Block node
		 * @type {Object}
		 */
		node: null,
		
		/**
		 * Block overlay node
		 * @type {Object}
		 */
		overlay: null,
		
		
		
		/**
		 * Returns block instance ID (content ID)
		 * 
		 * @return ID
		 * @type {Number}
		 */
		getId: function () {
			var data = this.get('data');
			return (data ? data.id : null);
		},
		
		/**
		 * Returns container node ID which is inside content
		 * 
		 * @return Container node ID
		 * @type {String}
		 */
		getNodeId: function () {
			var id = 'content_' + this.getBlockType() + '_' + this.getId();
			return id;
		},
		
		/**
		 * Returns container node inside content
		 * 
		 * @return Container node
		 * @type {Object}
		 */
		getNode: function () {
			if (!this.node) {
				this.node = this.get('body').one('#' + this.getNodeId());
			}
			return this.node;
		},
		
		/**
		 * Returns block type
		 * 
		 * @return Block type
		 * @type {String}
		 */
		getBlockType: function () {
			var data = this.get('data');
			return (data ? data.type : null);
		},
		
		/**
		 * Returns block title
		 * 
		 * @return Block title
		 * @type {String}
		 */
		getBlockTitle: function () {
			return this.getBlockInfo().title;
		},
		
		/**
		 * Returns block information
		 * 
		 * @return Block information
		 * @type {Object}
		 */
		getBlockInfo: function () {
			var data = this.get('data');
			return data && data.type ? Manager.Blocks.getBlock(data.type) : null;
		},
		
		/**
		 * Returns all block properties
		 * 
		 * @return List of block properties
		 * @type {Object}
		 */
		getProperties: function () {
			var block = this.getBlockInfo();
			return block ? block.properties : null;
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
			if (this.isLocked()) {
				return false;
			}
			if ('allow' in data) {
				for(var i=0, ii=data.allow.length; i<ii; i++) {
					if (data.allow[i] == type) {
						return true;
					}
				}
			} else {
				return true;
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
		
		/**
		 * Remove child
		 * 
		 * @param {Object} child
		 */
		removeChild: function (child) {
			var id = child.getId();
			if (id in this.children) {
				//Send request
				this.get('super').sendBlockDelete(child, function (data, status) {
					var node = this.getNode();
					
					this.destroy();
					if (node) node.remove();
					
					delete(this.children[id]);
					
					//Remove from order list
					var index = Y.Array.indexOf(this.children_order, String(id));
					if (index != -1) {
						this.children_order.splice(index, 1);
					}
					
				}, this);
			}
		},
		
		/**
		 * Create child block
		 * 
		 * @param {Object} data
		 * @param {Object} attrs
		 * @param {Boolean} use_only If DOM elements for content is not found, don't create them
		 */
		createChild: function (data, attrs, use_only) {
			var win = this.get('win');
			var doc = this.get('doc');
			var body = this.get('body');
			
			var type = data.type;
			var properties = Manager.Blocks.getBlock(type);
			var classname = properties && properties.classname ? properties.classname : type[0].toUpperCase() + type.substr(1);
			var html_id = '#content_' + (type || null) + '_' + (data.id || null);
			
			if (!use_only || body.one(html_id)) {
				if (classname in PageContent) {
					var block = this.children[data.id] = new PageContent[classname](SU.mix(attrs || {}, {
						'doc': doc,
						'win': win,
						'body': body,
						'data': data,
						'parent': this,
						'super': this.get('super')
					}));
					block.render();
					
					//Add to order list
					this.children_order.push(String(data.id));
				} else {
					Y.error('Class "' + classname + '" for content "' + data.id + '" is missing.');
				}
			}
			
			return block;
		},
		
		/**
		 * Returns child block by ID
		 *
		 * @param {String} block_id Block ID
		 * @return Child block
		 * @type {Object}
		 */
		getChildById: function (block_id) {
			var blocks = this.children,
				block = null;
			
			if (block_id in blocks) return blocks[block_id];
			
			for(var i in blocks) {
				block = blocks[i].getChildById(block_id);
				if (block) return block;
			}
			
			return null;
		},
		
		/**
		 * Returns all children blocks
		 *
		 * @param {Object} target Optional. Target object to add children to
		 * @return Children blocks
		 * @type {Object}
		 */
		getAllChildren: function (target) {
			var blocks = target || {},
				children = this.children;
			
			for(var child_id in children) {
				blocks[child_id] = children[child_id];
			}
			
			return blocks;
		},
		
		
		/**
		 * Process data and remove all unneeded before it's sent to server
		 * Called before save
		 * 
		 * @param {Object} data Data
		 * @return Processed data
		 * @type {Object}
		 */
		processData: function (data) {
			return data;
		},
		
		/**
		 * Render widget
		 * 
		 * @private
		 */
		render: function () {
			this.renderUI();
			this.bindUI();
			
			//Use timeout to make sure everything is styled before doing sync
			setTimeout(Y.bind(this.syncOverlayPosition, this), 1);
		},
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			if (this.get('editable') && this.overlay) {
				this.overlay.on('click', function() {
					this.get('super').set('activeChild', this);
				}, this);
				
				//Handle block save / cancel
				this.on('block:save', function () {
					// Unset active content
					if (this.get('super').get('activeChild') === this) {
						this.get('super').set('activeChild', null);
					}
				});
				this.on('block:cancel', function () {
					// Unset active content
					this.unresolved_changes = false;
					if (this.get('super').get('activeChild') === this) {
						this.get('super').set('activeChild', null);
					}
				});
			}
			
			this.before('destroy', this.beforeDestroy, this);
		},
		
		/**
		 * Update overlay position
		 * 
		 * @param {Boolean} traverse Traverse children and update their overlays
		 */
		syncOverlayPosition: function (traverse) {
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
					this.children[i].syncOverlayPosition();
				}
			}
		},
		
		/**
		 * Render UI (create nodes, widgets, etc)
		 * 
		 * @private
		 */
		renderUI: function () {
			var data = this.get('data');
			var permission_order = Supra.Authorization.isAllowed(['block', 'order'], true);
			var permission_edit = Supra.Authorization.isAllowed(['block', 'edit'], true);
			
			if ('contents' in data) {
				for(var i=0,ii=data.contents.length; i<ii; i++) {
					this.createChild(data.contents[i], {
						'dragable': !data.contents[i].locked && !this.isLocked() && permission_order,
						'editable': !data.contents[i].locked && permission_edit
					}, true);
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
			
			if (!permission_edit) {
				this.set('editable', false);
			}
			
			if (this.get('dragable')) {
				if (!this.isLocked() && permission_order) {
					this.set('dragable', true);
				} else {
					this.set('dragable', false);
				}
			}
		},
		
		/**
		 * Destructor
		 * 
		 * @private
		 */
		beforeDestroy: function () {
			if (this.get('editing')) {
				this.get('super').set('activeChild', null);
			}
			
			var children = this.children;
			this.children = {};
			for(var id in children) {
				children[id].destroy();
			}
			
			if (this.overlay) {
				this.overlay.remove();
			}
		},
		
		/**
		 * Render oberlay
		 * 
		 * @private
		 */
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
		
		/**
		 * dragable attribute setter
		 * 
		 * @param {Boolean} value
		 * @private
		 */
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
		
		/**
		 * editing attribute setter
		 * 
		 * @param {Boolean} value
		 * @private
		 */
		_setEditing: function (value) {
			if (value == (this.get('editing') || false)) return !!value;
			
			if (value) {
				if (this.overlay) this.overlay.addClass(CLASSNAME_EDITING);
				this.getNode().addClass(CLASSNAME_EDITING);
				
				//Fire editing-start event and propagate up to parent
				this.fire('editing-start');
				this.get('super').fire('editing-start', this.get('data'));
			} else {
				if (this.overlay) this.overlay.removeClass(CLASSNAME_EDITING);
				this.getNode().removeClass(CLASSNAME_EDITING);
				
				//Fire editing-end event and propagate up to parent
				this.fire('editing-end');
				this.get('super').fire('editing-end', this.get('data'));
				
				//Update overlay position
				this.syncOverlayPosition();
			}
			
			return !!value;
		},
		
		/**
		 * highlightOverlay attribute setter
		 * 
		 * @param {Boolean} value
		 * @private
		 */
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
		
		/**
		 * highlight attribute setter
		 * 
		 * @param {Boolean} value
		 * @private
		 */
		_setHighlight: function (value) {
			if (value) {
				this.getNode().addClass('yui3-highlight-' + this.getBlockType());
				if (this.get('editing')) {
					this.set('editing', false);
				}
				if (this.get('highlightOverlay')) {
					this.set('highlightOverlay', false);
				}
			} else {
				this.getNode().removeClass('yui3-highlight-' + this.getBlockType());
			}
			
			return !!value;
		},
		
		/**
		 * Changed getter
		 */
		_getChanged: function () {
			//Not editable, so nothing can change
			return false;
		}
		
	});
	
	PageContent.Proto = ContentProto;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['yui-base']});