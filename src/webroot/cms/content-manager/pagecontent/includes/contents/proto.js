YUI.add('supra.page-content-proto', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager,
		Page = Manager.Page,
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
		
		CLASSNAME_OVERLAY = 'su-overlay',
		
		CLASSNAME_OVERLAY_LIST = 'su-overlay-list',
		CLASSNAME_OVERLAY_EDITABLE = 'su-overlay-editable',
		CLASSNAME_OVERLAY_EXTERNAL = 'su-overlay-external',
		CLASSNAME_OVERLAY_CLOSED = 'su-overlay-closed',
		CLASSNAME_OVERLAY_HEIGHT = 'su-overlay-height',
		
		CLASSNAME_OVERLAY_NAME = 'su-overlay-name',
		CLASSNAME_OVERLAY_ICON = 'su-overlay-icon',
		
		//CLASSNAME_OVERLAY = getClassName('content', 'overlay'),						// yui3-content-overlay
		//CLASSNAME_OVERLAY_LIST = getClassName('content', 'overlay', 'list'),		// yui3-content-overlay-list
		//CLASSNAME_OVERLAY_CLOSED = getClassName('content', 'overlay', 'closed'),	// yui3-content-overlay-closed
		CLASSNAME_OVERLAY_HOVER = getClassName('content', 'overlay', 'hover'),		// yui3-content-overlay-hover
		
		CLASSNAME_OVERLAY_DRAGGABLE = 'su-overlay-draggable',
		
		CLASSNAME_MARKER = 'su-visual-cue',
		CLASSNAME_MARKER_NAME = 'su-visual-cue-name',
		
		CLASSNAME_HIGHLIGHT = 'su-content-highlight',
		CLASSNAME_HIGHLIGHT_NAME = 'su-content-highlight-name',
		
		CLASSNAME_EDITING = 'editing';												// editing
	
	
	var CLASSNAME_OVERLAY_MODE = {
		'overlay-visible': 'su-overlay-visible',
		'overlay-visible-hover': 'su-overlay-visible-hover',
		'overlay-hidden': '',
		'overlay-transparent': 'su-overlay-transparent',
		
		'icon-visible': 'su-overlay-icon-visible',
		'icon-visible-hover': 'su-overlay-icon-visible-hover',
		'icon-visible-loading': 'su-overlay-icon-visible-loading',
		'icon-hidden': '',
		
		'name-visible': 'su-overlay-name-visible',
		'name-hidden': ''
	};
	
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
		 * Highlight block
		 */
		'highlightMode': {
			value: null,
			setter: '_setHighlightMode'
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
		},
		
		/**
		 * This is a new block or not
		 */
		'new': {
			value: false
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
		 * Last set highlight mode
		 * @type {String|Null}
		 */
		highlight_mode: null,
		
		/**
		 * Highlight mode class names
		 * @type {String}
		 */
		highlight_mode_classname: null,
		highlight_mode_icon_classname: null,
		highlight_mode_name_className: null,
		
		/**
		 * Last known container highlight state
		 * @type {Boolean}
		 */
		highlight_container: false,
		
		/**
		 * Block is list
		 * @type {Boolean}
		 */
		is_list: false,
		
		
		/* --------------------------------- DATA / NODES ------------------------------------ */
		
		
		/**
		 * Returns block instance ID (content ID)
		 * 
		 * @return ID
		 * @type {String}
		 */
		getId: function () {
			var data = this.get('data');
			return (data ? data.id : null);
		},
		
		/**
		 * Return page id to which this block belongs to
		 * 
		 * @returns {String} Page ID
		 */
		getPageId: function () {
			var data = this.get('data');
			if (data.owner_id) {
				return data.owner_id;
			} else {
				var page_data = Page.getPageData();
				return page_data.id;
			}
		},
		
		/**
		 * Returns container node ID which is inside content
		 * 
		 * @return Container node ID
		 * @type {String}
		 */
		getNodeId: function () {
			var id = 'content_' + this.getId();
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
		 * Returns overlay node
		 * 
		 * @return Overlay node
		 * @type {Object}
		 */
		getOverlayNode: function () {
			return this.overlay;
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
			var title = '';
			if (this.isList()) {
				title = this.get('data').title;
			} else {
				title = this.getBlockInfo().title;
			}
			
			if (!title) {
				// Change ID into more readable form
				title = this.getId();
				title = title.replace(/[\-\_\.]/g, ' ');
				title = title.substr(0,1).toUpperCase() + title.substr(1);
			}
			
			return title;
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
		 * Returns current property value
		 * 
		 * @param {String} property Property name
		 */
		getPropertyValue: function (property) {
			var object = this.properties || this,
				data = object.get('data'),
				properties = data.properties,
				name = property;
			
			if (name == 'locked') {
				name = '__locked__';
			}
			
			if (properties && name in properties) {
				return properties[name].value;
			} else if (property in data) {
				return data[property]
			} else {
				return null;
			}
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
		 * If block is closed, then user is prevented from adding children and ordering this block
		 * 
		 * @returns {Boolean} True if block is closed, otherwise false
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
		 * @returns {Boolean} True if parent is closed, otherwise false
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
		 * Returns if block is list
		 * Placeholders are also lists
		 * 
		 * @returns {Boolean} True if block is list, otherwise false
		 */
		isList: function () {
			return this.is_list;
		},
		
		/**
		 * Returns if block is placeholder
		 * Placeholder is a list, which is a child of another list
		 * 
		 * @returns {Boolean} True if block is placeholder, otherwise false
		 */
		isPlaceholder: function () {
			var is_list = this.is_list,
				parent = this.get('parent');
			
			if (is_list && parent && parent.isList()) {
				return true;
			} else {
				return false;
			}
		},
		
		/**
		 * Returns true if block is placeholder, but without
		 * parent or children lists 
		 */
		isStandalonePlaceholder: function () {
			if (this.isList() && !this.isPlaceholder()) {
				// Check all children
				var id = null,
					children = this.children;
				
				for (id in children) {
					if (children[id].isList()) return false;
				}
				
				return true;
			}
		},
		
		/**
		 * Process data and remove all unneeded before it's sent to server
		 * Called before save. Overwritten in sub-classes
		 * 
		 * @param {Object} data Data
		 * @return Processed data
		 * @type {Object}
		 */
		processData: function (data) {
			return data;
		},
		
		
		/* --------------------------------- CHILDREN BLOCKS ------------------------------------ */
		
		
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
						
						//Remove from data list
						var contents = this.get('data').contents,
							i = 0,
							ii = contents.length;
						
						for (; i<ii; i++) {
							if (contents[i].id == id) {
								contents.splice(i, 1);
								break;
							}
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
		 * @param {Number} index Index where child should be inserted into, default at the end of the list
		 */
		createChild: function (data, attrs, use_only, index) {
			var win = this.get('win');
			var doc = this.get('doc');
			var body = this.get('body');
			
			var type = data.type;
			var properties = Manager.Blocks.getBlock(type);
			var classname = properties && properties.classname ? properties.classname : type[0].toUpperCase() + type.substr(1);
			var html_id = '#content_' + (data.id || null);
			
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
					if (typeof index === 'number') {
						//Move DOM node
						var next = this.children_order[index];
						if (next) {
							next = this.children[next];
							next.overlay.insert(block.overlay, 'before');
							next.overlay.insert(block.getNode(), 'before');
						}
						
						this.children_order.splice(index, 0, String(data.id));
					} else {
						this.children_order.push(String(data.id));
					}
					
					//Add to data list
					var contents = this.get('data').contents,
						i = 0,
						ii = contents.length,
						has = false;
					
					for (; i<ii; i++) {
						if (contents[i].id == data.id) {
							has = true;
						}
					}
					
					if (!has) {
						contents.push(data);
					}
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
		 * Returns direct descendent children blocks
		 *
		 * @param {Object} target Optional. Target object to add children to
		 * @return Children blocks
		 * @type {Object}
		 */
		getChildren: function (target) {
			return Supra.mix(target || {}, this.children);
		},
		
		/**
		 * Returns all children blocks, including children children
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
				children[child_id].getAllChildren(blocks);
			}
			
			return blocks;
		},
		
		
		/**
		 * Remove child only from list without deleting it
		 * 
		 * @param {Object} child
		 */
		removeChildFromList: function (block) {
			var id = block.getId();
			
			if (id in this.children) {
				//Remove from child list
				delete(this.children[id]);
				
				//Remove from order list
				var index = Y.Array.indexOf(this.children_order, String(id));
				if (index != -1) {
					this.children_order.splice(index, 1);
				}
				
				//Remove from data list
				var contents = this.get('data').contents,
					i = 0,
					ii = contents.length;
				
				for (; i<ii; i++) {
					if (contents[i].id == id) {
						contents.splice(i, 1);
						break;
					}
				}
			}
		},
		
		/**
		 * Add existing block to the children list
		 * 
		 * @param {Object} block Block to add
		 * @param {Number} index Optional index where to insert
		 */
		addChildToList: function (block, index) {
			var id = block.getId(),
				oldIndex = -1,
				
				children = this.children,
				children_order = this.children_order;
			
			if (id in children) {
				oldIndex = Y.Array.indexOf(children_order, String(id));
				if (oldIndex != -1) {
					children_order.splice(oldIndex, 1);
				}
			}
			
			//Add to child list
			children[id] = block;
			
			//Add to order list
			if (typeof index != 'number' || index < 0) index = children_order.length;
			
			if (index >= children_order.length) {
				children_order.push(id);
			} else {
				children_order.splice(index, 0, id);
			}
			
			//Add to data list
			var contents = this.get('data').contents;
			contents.push(block.get('data'));
			
			//Update parent attribute
			block.set('parent', this);
		},
		
		
		/* --------------------------------- CONTENT MANAGEMENT ------------------------------------ */
		
		
		/**
		 * Trigger event in content
		 * If jQuery.refresh is available it is used, if not then jQuery event is triggered
		 */
		fireContentEvent: function (event_name, node, data) {
			//Call cleanup before proceeding
			var win = this.get('win'),
				jQuery = win.jQuery;
			
			if (jQuery && jQuery.refresh) {
				var fn = event_name,
					jquery_element = jQuery(node),
					args = [jquery_element];
				
				if (event_name == 'refresh') {
					fn = 'init';
				} else if (event_name == 'update') {
					fn = 'trigger';
					args = [event_name, jquery_element, data];
				}
				
				if (jQuery.refresh[fn]) {
					return jQuery.refresh[fn].apply(jQuery.refresh, args);
				}
			} else if (jQuery) {
				var event_object = jQuery.Event(event_name);
				jQuery(node).trigger(event_object, data);
				
				return !event_object.isDefaultPrevented();
			}
		},
		
		
		/* --------------------------------- LIFE CYCLE ------------------------------------ */
		
		
		/**
		 * Render widget
		 * 
		 * @private
		 */
		render: function () {
			this.renderUI();
			this.bindUI();
			
			//Delay to make sure everything is styled before doing sync
			Supra.immediate(this, this.syncOverlayPosition);
		},
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			if (this.overlay) {
				this.overlay.on('click', this.handleLayoutClick, this);
			}
			
			//Handle block save / cancel
			this.on('block:save', function () {
				// Update overlay
				this.updateOverlayClassNames();
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
		
		handleLayoutClick: function () {
			if (this.get('editable')) {
				if (!this.get('loading') && this.highlight_mode != 'loading' && this.highlight_mode != 'insert' && this.highlight_mode != 'disabled') {
					// Don't edit if loading or is in insert mode
					this.get('super').set('activeChild', this);
				}
			} else {
				var owner_id = this.get('data').owner_id;
				if (owner_id) {
					// User has permissions to edit template, open that page
					// Stop editing
					PageContent.stopEditing();

					//Change path
					var Root = Manager.getAction('Root');
					Root.router.save(Root.ROUTE_PAGE.replace(':page_id', owner_id));
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
			var permission_block_order = true;
			var permission_block_edit = true;
			var node = this.getNode();
			
			if ('contents' in data) {
				for(var i=0,ii=data.contents.length; i<ii; i++) {
					permission_block_edit = true;
					permission_block_order = true;
					
					if (data.contents[i].closed && !data.contents[i].owner_id) {
						permission_block_edit = false;
					}
					if (data.contents[i].closed) {
						permission_block_order = false;
					}
					
					this.createChild(data.contents[i], {
						'draggable': !this.isClosed() && permission_order && permission_block_order,
						'editable':  permission_edit && permission_block_edit && data.contents[i].editable !== false
					}, true);
				}
			}
			
			if (!node) {
				var type = data.type,
					id = data.id,
					classname_type = CLASSNAME + '-' + type;
				
				node = Y.Node.create('<div id="content_' + id + '" class="' + CLASSNAME + ' '  + classname_type + '">' + data.value || '' + '</div>');
				node.setData('blockId', this.getId());
				
				this.node = node;
				this.get('parent').getNode().append(node);
				
				//Trigger refresh
				this.fireContentEvent('refresh', node.getDOMNode(), {'supra': Supra});
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
		
		
		/* --------------------------------- OVERLAY ------------------------------------ */
		
		
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
		 * Render oberlay
		 * 
		 * @private
		 */
		renderOverlay: function () {
			if (this.overlay) {
				if (!this.overlay.inDoc()) {
					this.getNode().insert(this.overlay, 'before');
				}
				return;
			}
			
			var div = new Y.Node(this.get('doc').createElement('DIV')),
				title = Y.Escape.html(this.getBlockTitle()),
				html = '',
				locked = null;
			
			this.overlay = div;
			
			if (this.get('draggable')) {
			 	this.overlay.addClass(CLASSNAME_OVERLAY_DRAGGABLE);
			}
			
			this.overlay.addClass(CLASSNAME_OVERLAY);
			
			if (this.isList()) {
				this.overlay.addClass(CLASSNAME_OVERLAY_LIST);
				html = '<span class="' + CLASSNAME_OVERLAY_ICON + '"></span><span class="' + CLASSNAME_OVERLAY_NAME + '">' + title + '</span>';
			} else {
				if (this.get('editable')) {
					this.overlay.addClass(CLASSNAME_OVERLAY_EDITABLE);
					
					locked = this.getPropertyValue('locked');
					if (locked || this.isClosed()) {
						// Global block, but editable
						this.overlay.addClass(CLASSNAME_OVERLAY_EXTERNAL);
					}
				} else {
					// User has permissions to edit template?
					var has_permissions = !!this.get('data').owner_id;
					if (has_permissions) {
						this.overlay.addClass(CLASSNAME_OVERLAY_EXTERNAL);
					}
				}
				
				if (!this.get('editable')) {
					title = 'Global Block<br /><small>Click to edit on Template</small>';
				} else if (this.isClosed()) {
					title = 'Global Block<br /><small>Changes affect all pages</small>';
				} else if (this.isParentClosed()) {
					title += '<br /><small>' + Supra.Intl.get(['page', 'click_to_edit']) + '</small>';
				} else {
					title += '<br /><small>' + Supra.Intl.get(['page', 'click_to_edit_drag_to_move']) + '</small>';
				}
				
				html = '<span class="' + CLASSNAME_OVERLAY_NAME + '">' + title + '<span class="' + CLASSNAME_OVERLAY_ICON + '"></span></span><span class="' + CLASSNAME_OVERLAY_ICON + '"></span>';
			}
			
			if (this.isParentClosed()) {
				this.overlay.addClass(CLASSNAME_OVERLAY_CLOSED);
			}
			
			this.overlay.set('innerHTML', html);
			this.getNode().insert(div, 'before');
		},
		
		/**
		 * Update overlay classname to reflect "locked" state
		 * 
		 * @private
		 */
		updateOverlayClassNames: function () {
			var locked = this.getPropertyValue('locked'),
				overlay = this.overlay;
			
			if (!this.isList() && overlay) {
				if (this.get('editable')) {
					if (locked || this.isClosed()) {
						overlay.addClass(CLASSNAME_OVERLAY_EXTERNAL);
					} else {
						overlay.removeClass(CLASSNAME_OVERLAY_EXTERNAL);
					}
				}
			}
		},
		
		/**
		 * Highlight mode attribute setter
		 * 
		 * @param {String|Null} mode Highlight mode
		 * @returns {String} New mode value
		 * @private
		 */
		_setHighlightMode: function (mode) {
			this.setHighlightMode(mode, true);
			return mode;
		},
		
		/**
		 * Change highlight mode
		 * 
		 * @param {String|Null} mode New mode
		 * @param {Boolean} overwrite If true attribute value will be ignored
		 */
		setHighlightMode: function (mode, overwrite) {
			
			// Is this block a list?
			var is_list = this.isList(),
				is_placeholder = this.isPlaceholder(),
				is_editable = this.get('editable'),
				
				parent = this.get('parent'),
				
				// Highlight mode
				attr_mode = overwrite ? mode : this.get('highlightMode'),
				old_mode = this.highlight_mode,
				mode = attr_mode || mode || (parent ? parent.get('highlightMode') : null) || this.get('super').get('highlightMode'),
				
				// Children
				children_mode = mode,
				children = this.children,
				id = null,
				filter = null,
				
				// Nodes
				overlay = this.getOverlayNode(),
				node = this.getNode(),
				
				// Node highlight
				old_highlight_container = this.highlight_container,
				highlight_container = false;
			
			// If overlay is missing only apply styles for node
			if (!overlay) {
				
				var children = this.children,
					id = null;
				
				// Only 'insert' and 'order' modes style node, all other style overlay
				if (mode == 'insert' || mode == 'order') {
					filter = this.get('super').get('highlightModeFilter') || '_undefined';
					
					if (is_list && this.isChildTypeAllowed(filter)) {
						// This list can have this child
						highlight_container = true;
					}
				}
				
				// Highlight container itself
				if (old_highlight_container != highlight_container) {
					node.toggleClass(CLASSNAME_HIGHLIGHT, highlight_container);
					this.highlight_container = highlight_container;
				}
				
				this.highlight_mode = mode;
				
				// Apply to children
				for (id in children) {
					children[id].setHighlightMode(mode);
				}
				
				return;
			}
			
			// Overlay highlight classnames
			var classnames = CLASSNAME_OVERLAY_MODE,
				
				old_overlay_classname = this.highlight_mode_classname,
				old_icon_classname = this.highlight_mode_icon_classname,
				old_name_classname = this.highlight_mode_name_classname,
				
				overlay_classname = old_overlay_classname,
				icon_classname = old_icon_classname,
				name_classname = old_name_classname;
			
			// Only if changed
			if (old_mode == mode) return;
			
			switch (mode) {
				case 'edit':
					// Normal edit mode, any non-list content can be edited
					// - non-list overlays are visible on hover, icon is shown
					// - list overlays are hidden
					
					if (is_list) {
						overlay_classname = 'overlay-hidden';
					} else {
						if (is_editable) {
							overlay_classname = 'overlay-visible-hover';
							icon_classname = 'icon-visible';
							name_classname = 'name-visible';
						} else {
							overlay_classname = 'overlay-visible-hover';
							icon_classname = 'icon-hidden';
							name_classname = 'name-visible';
						}
					}
					
					break;
				case 'insert':
				case 'order':
					// Page blocks are reordered or block is dragged for insertion
					// - non-list overlays are visible with name
					// - list overlays are hidden, but container itself is highlighted
					
					filter = this.get('super').get('highlightModeFilter') || '_undefined';
					
					// Only placeholders which can have child with given classname/type
					if (is_list) {
						if (this.isChildTypeAllowed(filter)) {
							// This list can have this child
							highlight_container = true;
							
							if (is_placeholder) {
								overlay_classname = 'overlay-transparent';
								icon_classname = 'icon-hidden';
								name_classname = 'name-visible';
							} else {
								overlay_classname = 'overlay-hidden';
							}
						} else {
							overlay_classname = 'overlay-hidden';
						}
					} else {
						if (is_editable && this.get('parent').isChildTypeAllowed(filter)) {
							// Parent can have that child, show overlay to allowed order, insert
							// before or after this one
							overlay_classname = 'overlay-visible';
							icon_classname = 'icon-hidden';
							name_classname = 'name-visible';
						} else {
							overlay_classname = 'overlay-hidden';
						}
					}
					
					break;
				case 'blocks':
					// Page blocks are viewed
					// - non-list overlays are visible with name; icon is shown on hover
					// - list blocks are hidden
					
					if (is_list) {
						overlay_classname = 'overlay-hidden';
					} else {
						if (is_editable) {
							overlay_classname = 'overlay-visible';
							icon_classname = 'icon-visible-hover';
							name_classname = 'name-visible';
						} else {
							overlay_classname = 'overlay-visible';
							icon_classname = 'icon-hidden';
							name_classname = 'name-visible';
						}
					}
					
					break;
				case 'blocks-hover':
					// Page blocks are viewed and this block is hovered
					// - non-list overlays are visible with name and icon
					// - list blocks are hidden
					
					if (is_list) {
						overlay_classname = 'overlay-hidden';
					} else {
						if (is_editable) {
							overlay_classname = 'overlay-visible';
							icon_classname = 'icon-visible';
							name_classname = 'name-visible';
						} else {
							overlay_classname = 'overlay-visible';
							icon_classname = 'icon-hidden';
							name_classname = 'name-visible';
						}
					}
					
					break;
				case 'placeholders':
					// Placeholder blocks are viewed
					// - non-list overlays are hidden
					// - list overlays (top level) are visible with name; icon is shown on hover
					
					if (is_list && !is_placeholder) {
						overlay_classname = 'overlay-visible';
						icon_classname = 'icon-visible-hover';
						name_classname = 'name-visible';
					} else {
						overlay_classname = 'overlay-hidden';
					}
					
					break;
				case 'placeholders-hover':
					// Placeholder blocks are viewed
					// - non-list overlays are hidden
					// - list overlays (top level) are visible with name and icon
					
					if (is_list && !is_placeholder) {
						overlay_classname = 'overlay-visible';
						icon_classname = 'icon-visible';
						name_classname = 'name-visible';
					} else {
						overlay_classname = 'overlay-hidden';
					}
					
					break;
				case 'loading':
					// Block or placeholder content is loading
					// - overlay is visible with loading icon
					// - all children overlays are hidden
					
					overlay_classname = 'overlay-visible';
					icon_classname = 'icon-visible-loading';
					name_classname = 'name-hidden';
					
					for (id in children) {
						children[id].set('highlightMode', 'disabled');
					}
					children_mode = null;
					
					break;
				case 'editing-list':
					// List edit mode
					
					if (is_placeholder) {
						overlay_classname = 'overlay-visible';
						icon_classname = 'icon-hidden';
						name_classname = 'name-hidden';
					} else if (this.isStandalonePlaceholder()) {
						overlay_classname = 'overlay-visible';
						icon_classname = 'icon-hidden';
						name_classname = 'name-hidden';
					} else {
						overlay_classname = 'overlay-hidden';
					}
					
					break;
				case 'disabled':
				case 'editing':
				default:
					// Editing content or disabled
					// Remove all highlights
					
					mode = 'disabled';
					
					overlay_classname = 'overlay-hidden';
					icon_classname = 'icon-hidden';
					name_classname = 'name-hidden';
					
					break;
			}
			
			if (overlay_classname != old_overlay_classname) {
				overlay.replaceClass(classnames[old_overlay_classname], classnames[overlay_classname]);
				this.highlight_mode_classname = overlay_classname;
			}
			if (icon_classname != old_icon_classname) {
				overlay.replaceClass(classnames[old_icon_classname], classnames[icon_classname]);
				this.highlight_mode_icon_classname = icon_classname;
			}
			if (name_classname != old_name_classname) {
				overlay.replaceClass(classnames[old_name_classname], classnames[name_classname]);
				this.highlight_mode_name_classname = name_classname;
			}
			
			this.highlight_mode = mode;
			
			// Highlight container itself
			if (old_highlight_container != highlight_container) {
				node.toggleClass(CLASSNAME_HIGHLIGHT, highlight_container);
				this.highlight_container = highlight_container;
			}
			
			// Reset overlay cache
			if (mode != 'disabled') {
				this.resetBlockPositionCache();
			}
			
			// Apply to children
			if (children_mode) {
				for (id in children) {
					if (old_mode === 'loading') {
						// Children were disabled because of 'loading', re-enable them
						children[id].set('highlightMode', null);
					}
					children[id].setHighlightMode(children_mode);
				}
			}
		},
		
		
		/* --------------------------------- ATTRIBUTES ------------------------------------ */
		
		
		/**
		 * draggable attribute setter
		 * 
		 * @param {Boolean} value
		 * @private
		 */
		_setDraggable: function (value) {
			var node = this.overlay;
			
			if (node) {
				node.toggleClass(CLASSNAME_OVERLAY_DRAGGABLE, value);
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
				this.getNode().addClass(CLASSNAME_EDITING);
				
				//Prevent user from switching to other blocks
				this.get('body').removeClass('yui3-editable');
				
				//Fire editing-start event and propagate up to parent
				this.fire('editing-start');
				this.get('super').fire('editing-start', this.get('data'));
			} else {
				this.getNode().removeClass(CLASSNAME_EDITING);
				
				//Prevent user from switching to other blocks
				this.get('body').addClass('yui3-editable');
				
				//Fire editing-end event and propagate up to parent
				this.fire('editing-end');
				this.get('super').fire('editing-end', this.get('data'));
				
				//Block is not new anymore
				this.set('new', false);
				
				//Update overlay position
				this.syncOverlayPosition();
			}
			
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
			this.set('highlightMode', value ? 'loading' : null);
			
			return !!value;
		},
		
		/**
		 * Changed getter
		 */
		_getChanged: function () {
			//Not editable, so nothing can change
			return false;
		},
		
		
		/* ------------------------------------ BLOCK DROP -------------------------------------- */
		
		
		/**
		 * Children position cache
		 * @type {Array}
		 */
		blockDropCache: null,
		
		/**
		 * Self position cache
		 * @type {Object}
		 */
		listDropCache: null,
		
		/**
		 * Drop target ID, block ID
		 * @type {Number}
		 */
		blockDropPositionId: null,
		
		/**
		 * Drop before target?
		 * @type {Boolean}
		 */
		blockDropPositionBefore: false,
		
		/**
		 * Drop marker node
		 * @type {Object}
		 */
		blockDropPositionMarker: null,
		
		/**
		 * Mark drop position
		 * If event object is not passed, then removes marker
		 * 
		 * @param {Object} e Event facade object, optional
		 * @param {String} title Block title
		 */
		markDropPosition: function (e, title) {
			if (!e) {
				return this._markDropPosition(null, false, null);
			}
			
			var position = this.getDropPosition(e);
			this._markDropPosition(position, title);
		},
		
		/**
		 * Returns drop position by event
		 * If event object is not passed, then returns last known position
		 * 
		 * @param {Object} e Event facade object, optional
		 */
		getDropPosition: function (e) {
			if (!e) {
				return {
					"id": this.blockDropPositionId,
					"before": this.blockDropPositionBefore,
					"region": null
				};
			}
			
			var cache = this.blockDropCache,
				region = null,
				id = null,
				xy = e.position,
				positionId = null,
				positionBefore = false,
				positionRegion = null,
				hasChildren = false,
				matched = false;
			
			if (!this.blockDropCache) {
				var children = this.children;
				
				cache = this.blockDropCache = {}
				
				for (id in children) {
					region = children[id].getNode().get("region");
					cache[id] = region;
				}
			}
			
			// Check if hovering any child block
			for (id in cache) {
				hasChildren = true;
				region = cache[id];
				if (region.left <= xy[0] && region.right >= xy[0] && region.top <= xy[1] && region.bottom >= xy[1]) {
					positionId = id;
					positionBefore = (region.height / 2 > xy[1] - region.top);
					positionRegion = region;
					matched = true;
				}
			}
			
			// Check if hovering this list at all
			if (!matched && !this.isClosed()) {
				region = this.listDropCache || (this.listDropCache = this.getNode().get('region'));
				
				if (region.left <= xy[0] && region.right >= xy[0] && region.top <= xy[1] && region.bottom >= xy[1]) {
					
					if (!hasChildren) {
						// There are no hoverable items inside the list
						// Report list itself
						positionId = this.getId();
						positionRegion = region;
					} else {
						// Most likely still hovered over drop marker, so we report
						// same item 
						positionId = this.blockDropPositionId;
						positionBefore = this.blockDropPositionBefore;
					}
				}
			}
			
			return {
				"id": positionId,
				"before": positionBefore,
				"region": positionRegion
			};
		},
		
		/**
		 * Reset block position cache
		 */
		resetBlockPositionCache: function () {
			this.blockDropCache = null;
			this.listDropCache = null;
			
			//Children cache
			var children = this.children,
				id;
			
			for (id in children) {
				children[id].resetBlockPositionCache();
			}
		},
		
		/**
		 * Show marker at specific position
		 * 
		 * @param {Object} position Object with children ID, position and region
		 * @param {String} title Block title which is inserted
		 * @private
		 */
		_markDropPosition: function (position, title) {
			var positionId = position ? position.id : null,
				positionBefore = position ? position.before : false;
			
			if (this.blockDropPositionId != positionId || this.blockDropPositionBefore != positionBefore) {
				var node = this.blockDropPositionMarker,
					reference = null;
				
				//We don't mark list, only children blocks
				if (positionId) {
					
					if (!node) {
						var title = Supra.Intl.get(["insertblock", "drop_to_insert"]).replace("{block}", title || "");
						
						node = this.blockDropPositionMarker = Y.Node(this.get("doc").createElement("DIV")); // create using correct document object
						node.addClass(CLASSNAME_MARKER);
						node.set("innerHTML", '<span class="' + CLASSNAME_MARKER_NAME + '">' + title + '</span>');
					}
					
					if (positionId != this.getId()) {
						// Block
						if (positionBefore) {
							reference = this.children[positionId].getOverlayNode();
							reference.insert(node, "before");
						} else {
							reference = this.children[positionId].getNode();
							reference.insert(node, "after");
						}
					} else {
						// List
						reference = this.getNode();
						reference.appendChild(node);
					}
					
				} else {
					if (node) {
						node.remove(true);
						node = this.blockDropPositionMarker = null;
					}
				}
				
				this.blockDropPositionId = positionId;
				this.blockDropPositionBefore = positionBefore;
				
				// Reset cache for all blocks because order has changed
				var blocks = this.get("super").getChildren(),
					id = null;
				
				for (id in blocks) {
					blocks[id].resetBlockPositionCache();
				}
			}
		},
		
		/* ------------------------------------ CONTENT MANIPULATION -------------------------------------- */
		
		
		/**
		 * Returns stylesheet parser,
		 * Supra.IframeStylesheetParser instance
		 * 
		 * @type {Object}
		 */
		getStylesheetParser: function () {
			return this.get("super").get("iframe").get("stylesheetParser");
		}
		
	});
	
	PageContent.Proto = ContentProto;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['yui-base']});
