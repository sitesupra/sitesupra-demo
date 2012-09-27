YUI.add('supra.page-content-proto', function (Y) {
	//Invoke strict mode
	"use strict";
	
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
		CLASSNAME_OVERLAY_CLOSED = getClassName('content', 'overlay', 'closed'),	// yui3-content-overlay-closed
		CLASSNAME_OVERLAY_HOVER = getClassName('content', 'overlay', 'hover'),		// yui3-content-overlay-hover
		CLASSNAME_OVERLAY_LOADING = getClassName('content', 'overlay', 'loading'),	// yui3-content-overlay-loading
		CLASSNAME_OVERLAY_TITLE = getClassName('content', 'overlay', 'title'),		// yui3-content-overlay-title
		CLASSNAME_DRAGGABLE = getClassName('content', 'draggable'),					// yui3-content-draggable
		CLASSNAME_MARKER = getClassName('content', 'marker'),						// yui3-content-marker
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
		 * Block node
		 * @type {Object}
		 */
		node: null,
		
		/**
		 * Block overlay node
		 * @type {Object}
		 */
		overlay: null,
		
		
		/* --------------------------------- DATA / NODES ------------------------------------ */
		
		
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
		 * Render UI (create nodes, widgets, etc)
		 * 
		 * @private
		 */
		renderUI: function () {
			var data = this.get('data');
			var permission_order = true; //Supra.Permission.get('block', 'order', null, true);
			var permission_edit = true;  //Supra.Permission.get('block', 'edit', null, true);
			var node = this.getNode();
			
			if ('contents' in data) {
				for(var i=0,ii=data.contents.length; i<ii; i++) {
					this.createChild(data.contents[i], {
						'draggable': !data.contents[i].closed && !this.isClosed() && permission_order,
						'editable': !data.contents[i].closed && permission_edit
					}, true);
				}
			}
			
			if (!node) {
				var type = data.type,
					id = data.id,
					classname_type = CLASSNAME + '-' + type;
				
				node = Y.Node.create('<div id="content_' + type + '_' + id + '" class="' + CLASSNAME + ' '  + classname_type + '">' + data.value || '' + '</div>');
				node.setData('blockId', this.getId());
				
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
			var div = new Y.Node(this.get('doc').createElement('DIV')),
				title = Y.Escape.html(this.getBlockTitle());
			
			this.overlay = div;
			
			if (this.get('draggable')) {
				this.overlay.addClass(CLASSNAME_DRAGGABLE);
			}
			
			this.overlay.addClass(CLASSNAME_OVERLAY);
			
			if (this.isParentClosed()) {
				this.overlay.addClass(CLASSNAME_OVERLAY_CLOSED);
			}
			
			this.overlay.set('innerHTML', '<span></span><span class="' + CLASSNAME_OVERLAY_TITLE + '">' + title + '</span>');
			this.getNode().insert(div, 'before');
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
				node.toggleClass(CLASSNAME_DRAGGABLE, value);
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
			
			this.overlay.toggleClass(CLASSNAME_OVERLAY_HOVER, value);
			
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
			
			this.overlay.toggleClass(CLASSNAME_OVERLAY_LOADING, value);
			
			return !!value;
		},
		
		/**
		 * highlight attribute setter
		 * 
		 * @param {Boolean} value
		 * @private
		 */
		_setHighlight: function (value) {
			this.getNode().toggleClass('yui3-highlight-' + this.getBlockType(), value);
			
			if (value) {
				if (this.get('editing')) {
					this.set('editing', false);
				}
				if (this.get('highlightOverlay')) {
					this.set('highlightOverlay', false);
				}
				
				this.resetBlockPositionCache();
			}
			
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
		 */
		markDropPosition: function (e) {
			if (!e) {
				return this._markDropPosition(null, false, null);
			}
			
			var position = this.getDropPosition(e);
			this._markDropPosition(position);
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
				hasChildren = false;
			
			if (!this.blockDropCache) {
				var children = this.children;
				
				cache = this.blockDropCache = {}
				
				for (id in children) {
					region = children[id].getNode().get("region");
					cache[id] = region;
				}
				
				this.blockDropPositionId = null;
				this.blockDropPositionBefore = false;
			}
			
			for (id in cache) {
				hasChildren = true;
				region = cache[id];
				if (region.left <= xy[0] && region.right >= xy[0] && region.top <= xy[1] && region.bottom >= xy[1]) {
					positionId = id;
					positionBefore = (region.height / 2 > xy[1] - region.top);
					positionRegion = region;
				}
			}
			
			if (!hasChildren && !this.isClosed()) {
				//Drop on empty list or can't drop on any of the children
				region = this.listDropCache || (this.listDropCache = this.getNode().get('region'));
				
				if (region.left <= xy[0] && region.right >= xy[0] && region.top <= xy[1] && region.bottom >= xy[1]) {
					positionId = this.getId();
					positionRegion = region;
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
		 * @param {String} positionId Children ID or null to remove marker
		 * @param {Boolean} positionBefore Insert marker before child
		 * @param {Object} positionRegion Children block node region
		 * @private
		 */
		_markDropPosition: function (position) {
			var positionId = position ? position.id : null,
				positionBefore = position ? position.before : false,
				positionRegion = position ? position.region : null;
			
			if (this.blockDropPositionId != positionId || this.blockDropPositionBefore != positionBefore) {
				var node = this.blockDropPositionMarker;
				
				//We don't mark list, only children blocks
				if (positionId) {
					if (!node) {
						node = this.blockDropPositionMarker = Y.Node(this.get("doc").createElement("DIV")); // create using correct document object
						node.addClass(CLASSNAME_MARKER);
						this.get("body").append(node);
					}
					
					if (positionId != this.getId()) {
						//Block
						node.setStyles({
							"left": positionRegion.left + "px",
							"top": (positionBefore ? positionRegion.top + 1 : positionRegion.bottom + 1) + "px",
							"width": positionRegion.width + "px"
						});
					} else {
						//List
						node.setStyles({
							"left": positionRegion.left + 2 + "px",
							"top": positionRegion.bottom + "px",
							"width": positionRegion.width - 4 + "px"
						});
					}
				} else {
					if (node) {
						node.remove(true);
						node = this.blockDropPositionMarker = null;
					}
				}
				
				this.blockDropPositionId = positionId;
				this.blockDropPositionBefore = positionBefore;
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