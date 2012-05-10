//Invoke strict mode
"use strict";

YUI.add('supra.page-content-proto', function (Y) {
	
	//Shortcuts
	var Manager = Supra.Manager,
		PageContent = Manager.PageContent,
		getClassName = Y.bind(Y.ClassNameManager.getClassName, Y.ClassNameManager);
	
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
	var CLASSNAME = getClassName('content'),										// yui3-content
		CLASSNAME_OVERLAY = getClassName('content', 'overlay'),						// yui3-content-overlay
		CLASSNAME_OVERLAY_HOVER = getClassName('content', 'overlay', 'hover'),		// yui3-content-overlay-hover
		CLASSNAME_OVERLAY_LOADING = getClassName('content', 'overlay', 'loading'),	// yui3-content-overlay-loading
		CLASSNAME_DRAGGABLE = getClassName('content', 'draggable'),					// yui3-content-draggable
		CLASSNAME_EDITING = 'editing';												// editing
	
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
		
		/**
		 * Parent block
		 */
		'parent': {
			value: null
		},
		
		/**
		 * Supra.Manager.PageContent.IframeContents
		 */
		'super': {
			value: null
		},
		
		/**
		 * Is block being edited right now
		 */
		'editing': {
			value: false,
			setter: '_setEditing'
		},
		
		/**
		 * Is block editable
		 */
		'editable': {
			value: false,
			writeOnce: true
		},
		
		/**
		 * Is block draggable
		 */
		'draggable': {
			value: false,
			setter: '_setDraggable'
		},
		
		/**
		 * Is block highlighted right now?
		 */
		'highlight': {
			value: false,
			setter: '_setHighlight'
		},
		
		/**
		 * If block overlay highlighted right now?
		 */
		'highlightOverlay': {
			value: false,
			setter: '_setHighlightOverlay'
		},
		
		/**
		 * Display loading icon and prevent selecting
		 */
		'loading': {
			value: false,
			setter: '_setLoading'
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
		 * If is closed then child is not allowed
		 * 
		 * @param {String} type Block type
		 * @return True if child with type is allowed, otherwise false
		 * @type {Boolean}
		 */
		isChildTypeAllowed: function (type) {
			var data = this.get('data');
			if (this.isClosed()) {
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
		 * Returns if block is closed
		 * 
		 * @return True if block is closed, otherwise false
		 * @type {Boolean}
		 */
		isClosed: function () {
			var data = this.get('data');
			if ('closed' in data) {
				return data.closed;
			}
			return false;
		},
		
		/**
		 * Returns if parent is closed
		 * 
		 * @return True if parent is closed, otherwise false
		 * @type {Boolean}
		 */
		isParentClosed: function () {
			var parent = this.get('parent');
			if (parent) {
				return parent.isClosed();
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
			var id = child.getId(),
				block = null;
			if (id in this.children) {
				block = this.children[id];
				
				//Send request
				this.get('super').sendBlockDelete(child, function (data, status) {
					if (status) {

						//Discard all changes
						child.unresolved_changes = false;

						var node = block.getNode();

						//Destroy block
						block.destroy();
						if (node) node.remove();

						//Remove from child list
						delete(this.children[id]);

						//Remove from order list
						var index = Y.Array.indexOf(this.children_order, String(id));
						if (index != -1) {
							this.children_order.splice(index, 1);
						}
					} else {
						// Reopen the properties sidebar if fails
						block.properties.showPropertiesForm();
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
					var block = this.children[data.id] = new PageContent[classname](Supra.mix(attrs || {}, {
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
					if (!this.get('loading')) {
						this.get('super').set('activeChild', this);
					}
				}, this);
			}
			
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
			var permission_order = true; //Supra.Permission.get('block', 'order', null, true);
			var permission_edit = true;  //Supra.Permission.get('block', 'edit', null, true);
			
			if ('contents' in data) {
				for(var i=0,ii=data.contents.length; i<ii; i++) {
					this.createChild(data.contents[i], {
						'draggable': !data.contents[i].closed && !this.isClosed() && permission_order,
						'editable': !data.contents[i].closed && permission_edit
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
			
			if (this.get('draggable')) {
				if (!this.isClosed() && permission_order) {
					this.set('draggable', true);
				} else {
					this.set('draggable', false);
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
			var div = new Y.Node(this.get('doc').createElement('DIV'));
			
			this.overlay = div;
			
			if (this.get('draggable')) {
				this.overlay.addClass(CLASSNAME_DRAGGABLE);
			}
			
			this.overlay.addClass(CLASSNAME_OVERLAY);
			this.overlay.set('innerHTML', '<span></span>');
			this.getNode().insert(div, 'before');
		},
		
		/**
		 * draggable attribute setter
		 * 
		 * @param {Boolean} value
		 * @private
		 */
		_setDraggable: function (value) {
			var node = this.overlay;
			
			if (node) {
				node.setClass(CLASSNAME_DRAGGABLE, value);
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
				
				//Prevent user from switching to other blocks
				this.get('body').removeClass('yui3-editable');
				
				//Fire editing-start event and propagate up to parent
				this.fire('editing-start');
				this.get('super').fire('editing-start', this.get('data'));
			} else {
				if (this.overlay) this.overlay.removeClass(CLASSNAME_EDITING);
				this.getNode().removeClass(CLASSNAME_EDITING);
				
				//Prevent user from switching to other blocks
				this.get('body').addClass('yui3-editable');
				
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
			
			this.overlay.setClass(CLASSNAME_OVERLAY_HOVER, value);
			
			return !!value;
		},
		
		/**
		 * loading attribute setter
		 * 
		 * @param {Boolean} value
		 * @private
		 */
		_setLoading: function (value) {
			if (!this.overlay) return false;
			this.set('highlightOverlay', !!value);
			
			this.overlay.setClass(CLASSNAME_OVERLAY_LOADING, value);
			
			return !!value;
		},
		
		/**
		 * highlight attribute setter
		 * 
		 * @param {Boolean} value
		 * @private
		 */
		_setHighlight: function (value) {
			this.getNode().setClass('yui3-highlight-' + this.getBlockType(), value);
			
			if (value) {
				if (this.get('editing')) {
					this.set('editing', false);
				}
				if (this.get('highlightOverlay')) {
					this.set('highlightOverlay', false);
				}
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