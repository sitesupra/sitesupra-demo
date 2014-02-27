/*
 * Supra.Manager.PageContent.Iframe
 */
YUI.add('supra.iframe-contents', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.PageContent,
		Root = Manager.getAction('Root');
		
	//Classname to add to blocks while inserting new block
	var CLASSNAME_INSERT = Y.ClassNameManager.getClassName('content', 'insert');		//yui3-content-insert
		
	/*
	 * Editable content
	 */
	function IframeContents (config) {
		IframeContents.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	IframeContents.NAME = 'page-iframe-contents';
	IframeContents.CLASS_NAME = Y.ClassNameManager.getClassName(IframeContents.NAME);
	IframeContents.ATTRS = {
		'iframe': {
			value: null
		},
		'win': {
			value: null,
		},
		'doc': {
			value: null,
		},
		'body': {
			value: null
		},
		'contentData': {
			value: null
		},
		'disabled': {
			value: false,
			setter: '_setDisabled'
		},
		'activeChild': {
			value: null
		},
		
		/*
		 * Highlight list nodes
		 */
		'highlightMode': {
			value: 'disabled',
			setter: '_setHighlightMode'
		},
		
		'highlightModeFilter': {
			value: ''
		}
	};
	
	Y.extend(IframeContents, Y.Base, {
		children: {},
		
		/**
		 * On URI change to /22/edit unset active content
		 *
		 * @param {Object} req Routing request
		 */
		routeMain: function (req) {
			if (this.get('activeChild')) {
				this.set('activeChild', null);
			}
			
			if (req && req.next) req.next();
		},
		
		/**
		 * On URI change to /22/edit/111 set active content
		 *
		 * @param {Object} req Routing request
		 */
		routeBlock: function (req) {
			var block_id = req.params.block_id;
			var block_old = this.get('activeChild');
			var block_new = block_id ? this.getChildById(block_id) : null;
			
			if (block_old && block_old.get('data').id != block_id) {
				this.set('activeChild', block_new);
			} else if (!block_old && block_new) {
				this.set('activeChild', block_new);
			}
			
			if (req && req.next) req.next();
		},
		
		
		bindUI: function () {
			
			//Set 'editing' attribute after content changes
			this.after('activeChildChange', function (evt) {
				if (evt.newVal !== evt.prevVal) {
					if (evt.prevVal && evt.prevVal.get('editing')) {
						evt.prevVal.set('editing', false);
						
						//Route to /22/edit
						if (!evt.newVal || !evt.newVal.get('editable')) {
							var uri = Root.ROUTE_PAGE_EDIT.replace(':page_id', Manager.Page.getPageData().id);
							
							//Change path only if /page/.../edit is opened
							if (document.location.pathname.indexOf(uri) != -1) {
								Root.router.save(uri);
							}
						}
						this.set('highlightMode', 'edit');
					}
					if (evt.newVal && !evt.newVal.get('editing') && evt.newVal.get('editable')) {
						evt.newVal.set('editing', true);
						
						//Route to /22/edit/111
						var uri = Root.ROUTE_PAGE_CONT.replace(':page_id', Manager.Page.getPageData().id)
													  .replace(':block_id', evt.newVal.get('data').id);
						
						Root.router.save(uri);
						
						if (evt.newVal.isList()) {
							this.set('highlightMode', 'editing');
							evt.newVal.set('highlightMode', 'editing-list');
						} else {
							this.set('highlightMode', 'editing');
						}
					}
				}
			}, this);
			
			//Update children highlightMode after it has changed
			this.after('highlightModeChange', this._afterHighlightModeChange, this);
			
			//Routing
			Root.router.route(Root.ROUTE_PAGE_EDIT, Y.bind(this.routeMain, this));
			Root.router.route(Root.ROUTE_PAGE_CONT, Y.bind(this.routeBlock, this));
			
			//Restore state
			this.get('iframe').on('ready', this.onIframeReady, this);
			
			
			//Bind block D&D
			this.on('block:dragend:hit', function (e) {
				if (e.block) {
					var children = this.getAllChildren();
					
					for(var i in children) {
						if (children[i].isList()) {
							//Check if it was dropped on this child
							var position = children[i].getDropPosition();
							
							//Remove mark and saved drop position
							children[i].markDropPosition(null);
							
							if (position.id) {
								//If droped on the list but not children block then there is no need to send reference
								if (position.id == children[i].getId()) {
									position.id = null;
								}
								
								//Was dropped on block
								return children[i].fire('dragend:hit', {dragnode: e.dragnode, block: e.block, insertReference: position.id, insertBefore: position.before});
							}
						}
					}
				}
			}, this);
			
			this.on('block:dragstart', function (e) {
				//Only if dragging block
				if (e.block) {
					this.set('highlightModeFilter', e.block.id);
					this.set('highlightMode', 'insert');
				}
			}, this);
			
			this.on('block:dragmove', function (e) {
				//Only if dragging block
				if (e.block) {
					var type = e.block.id,
						children = children = this.getAllChildren(),
						title = e.block.title;
					
					for(var i in children) {
						if (children[i].isList() && children[i].isChildTypeAllowed(type)) {
							children[i].markDropPosition(e, title);
						}
					}
				}
			}, this);
			
			this.on('block:dragend:miss', function (e) {
				// Remove highlight
				var children = this.getAllChildren();
				
				for(var i in children) {
					if (children[i].isList()) {
						children[i].markDropPosition(null);
					}
				}
			}, this);
			
			this.once('destroy', this.beforeDestroy, this);
			
			//On block order change save
			this.order.on("orderChange", this._onBlockOrderChange, this);
			this.order.on("listChange", this._onBlockOrderListChange, this);
			
			
			//Fix context
			var win = this.get('iframe').get('win');
			this.resizeOverlays = Supra.throttle(this.resizeOverlays, 50, this);
			Y.on('resize', this.resizeOverlays, win);
		},
		
		/**
		 * On iframe ready restore editing state
		 * 
		 * @private
		 */
		onIframeReady: function () {
			var match = Root.getRoutePath().match(Root.ROUTE_PAGE_CONT_R);
			if (match) {
				var block = this.getChildById(match[1]);
				if (block) {
					//Need delay to make sure editing state is correctly set
					//needed only if settings immediately after load
					Y.later(16, this, function () {
						//Can't use supra.immediate, because in that case layout for toolbar is not synced
						this.set('activeChild', block);
					});
				}
			}
			
			this.resizeOverlays();
		},
		
		/**
		 * Create children
		 * 
		 * @param {Object} data
		 * @param {Boolean} use_only If DOM elements for content is not found, don't create them
		 * @private
		 */
		createChildren: function (data, use_only) {
			var data = data || this.get('contentData');
			
			if (data) {
				var body = this.get('body');
				var doc = this.get('doc');
				var win = this.get('win');
				
				for(var i=0,ii=data.length; i<ii; i++) {
					
					var type = data[i].type;
					var properties = Manager.Blocks.getBlock(type);
					var classname = properties && properties.classname ? properties.classname : type[0].toUpperCase() + type.substr(1);
					var html_id = '#content_' + (data[i].id || null);
					
					if (!use_only || body.one(html_id)) {
						if (classname in Action) {
							var block = this.children[data[i].id] = new Action[classname]({
								'doc': doc,
								'win': win,
								'body': body,
								'data': data[i],
								'parent': null,
								'super': this,
								'draggable': !data[i].closed,
								// Can edit if 'editable' and either not 'closed' or has 'owner_id', which means
								// we can edit this block in this page even though this block doesn't belong to this page 
								'editable': (!data[i].closed || data[i].owner_id) && data[i].editable !== false
							});
							block.render();
						} else {
							Y.error('Class "' + classname + '" for content "' + data[i].id + '" is missing.');
						}
					}
				}
			}
		},
		
		renderUI: function () {
			//Allow ordering
			this.plug(Action.PluginOrdering);
			
			this.createChildren(null, true);
			this.get('body').addClass('yui3-editable');
		},
		
		render: function () {
			this.renderUI();
			this.bindUI();
		},
		
		/**
		 * Loads and returns block data
		 * 
		 * @param {Object} data Block information
		 * @param {Function} callback Callback function
		 * @param {Object} context
		 */
		getBlockInsertData: function (data, callback, context) {
			var url = Manager.PageContent.getDataPath('insertblock');
			var page_info = Manager.Page.getPageData();
			
			data = Supra.mix({
				'page_id': page_info.id,
				'locale': Supra.data.get('locale')
			}, data);
			
			Supra.io(url, {
				'data': data,
				'method': 'post',
				'on': {
					'success': function (data) {
						callback.call(this, data);
						
						//Change page version title
						Manager.getAction('PageHeader').setVersionTitle('autosaved');
					}
				},
				'context': context
			});
		},
		
		/**
		 * Send block delete request
		 * 
		 * @param {Object} block
		 */
		sendBlockDelete: function (block, callback, context) {
			var url = Manager.PageContent.getDataPath('deleteblock');
			var page_info = Manager.Page.getPageData();
			var data = {
				'page_id': page_info.id,
				'owner_page_id': block.getPageId(),
				'block_id': block.getId(),
				'locale': Supra.data.get('locale')
			};

			Supra.io(url, {
				'data': data,
				'method': 'post',
				'on': {
					'complete': function (data, status) {
						callback.call(this, data, status);

						if (status) {
							//Change page version title
							Manager.getAction('PageHeader').setVersionTitle('autosaved');
						}
					}
				},
				'context': context
			});
			
			//Global activity
			Supra.session.triggerActivity();
		},
		
		
		/* --------------------------- HANDLE ORDER CHANGE --------------------------- */
		
		
		/**
		 * Save block order request
		 * 
		 * @param {Object} block
		 * @param {Object} order
		 */
		sendBlockOrder: function (block, order) {
			var url = Manager.PageContent.getDataPath('orderblocks');
			var page_info = Manager.Page.getPageData();
			var data = {
				'page_id': page_info.id,
				'owner_page_id': block.getPageId(),
				
				'place_holder_id': block.getId(),
				'order': order,
				
				'locale': Supra.data.get('locale')
			};
			
			Supra.io(url, {
				'data': data,
				'method': 'post'
			});
						
			//Change page version title
			Manager.getAction('PageHeader').setVersionTitle('autosaved');
			
			//Global activity
			Supra.session.triggerActivity();
		},
		
		/**
		 * Handle block order change event
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		_onBlockOrderChange: function (e) {
			this.sendBlockOrder(e.block, e.order);
			this.resizeOverlays();
		},
		
		/**
		 * Save block order request
		 * 
		 * @param {Object} block
		 * @param {Object} order
		 */
		sendBlockListChange: function (block, order) {
			var url = Manager.PageContent.getDataPath('moveblocks');
			var page_info = Manager.Page.getPageData();
			var data = {
				'page_id': page_info.id,
				'owner_page_id': block.getPageId(),
				
				'place_holder_id': block.get('parent').getId(),
				'order': order,
				'block_id': block.getId(),
				
				'locale': Supra.data.get('locale')
			};
			
			Supra.io(url, {
				'data': data,
				'method': 'post'
			});
						
			//Change page version title
			Manager.getAction('PageHeader').setVersionTitle('autosaved');
			
			//Global activity
			Supra.session.triggerActivity();
		},
		
		/**
		 * Handle block order change event
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		_onBlockOrderListChange: function (e) {
			this.sendBlockListChange(e.block, e.order);
			this.resizeOverlays();
		},
		
		
		/* --------------------------- SAVE DATA --------------------------- */
		
		
		/**
		 * Save placeholder properties
		 * 
		 * @param {Object} placeholder Supra.Manager.PageContent.List instance
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback context
		 */
		sendPlaceHolderProperties: function (placeholder, callback, context) {
			var url = Manager.PageContent.getDataPath('save-placeholder'),
				property = 'place_holder_id';
			
			this.sendObjectProperties(placeholder, property, callback, context, url);
		},
		
		/**
		 * Save placeholder properties from within a page
		 * 
		 * @param {Object} placeholder Supra.Manager.PageContent.List instance
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback context
		 */
		sendPagePlaceHolderProperties: function (placeholder, callback, context) {
			var url = Manager.PageContent.getDataPath('save-page-placeholder'),
				property = 'place_holder_id';
			
			this.sendObjectProperties(placeholder, property, callback, context, url);
		},
		
		/**
		 * Save block properties
		 * 
		 * @param {Object} block Block
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback context
		 */
		sendBlockProperties: function (block, callback, context) {
			var url = Manager.PageContent.getDataPath('save'),
				property = 'block_id';
			
			this.sendObjectProperties(block, property, callback, context, url);
		},
		
		/**
		 * Save block or placeholder properties
		 * 
		 * @param {Object} object Block or list instance
		 * @param {String} object_property_name Post variable in which to send block or placeholder id
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback context
		 * @param {STring} url Url to which send the request
		 * @private
		 */
		sendObjectProperties: function (object, object_property_name, callback, context, url) {
			var page_data = Manager.Page.getPageData(),
				values = object.properties.getValues();
			
			var save_values = object.properties.getSaveValues(),
				locked = false;
			
			//Allow block to modify data before saving it
			save_values = object.processData(save_values);
			
			var post_data = {
				'page_id': page_data.id,
				'owner_page_id': object.getPageId(),
				
				'locale': Supra.data.get('locale'),
				'properties': save_values
			};
			
			post_data[object_property_name] = object.getId();
			
			//If editing template, then send also "locked", but not as part
			//of properties
			if (page_data.type != 'page') {
				if (typeof save_values.__locked__ !== 'undefined') {
					post_data.locked = save_values.__locked__;
					object.properties.get('data').locked = post_data.locked;
					delete(save_values.__locked__);
				}
			}
			
			//Remove __locked__ from values which are sent to backend
			if ('__locked__' in save_values) {
				delete(save_values.__locked__);
			}
			
			Supra.io(url, {
				'data': post_data,
				'method': 'post',
				'on': {'complete': function () {
					callback.apply(this, arguments);
					
					if (status) {
						//Change page version title
						Manager.getAction('PageHeader').setVersionTitle('autosaved');
					}
				}}
			}, context);
			
			//Global activity
			Supra.session.triggerActivity();
		},
		
		/**
		 * Disable editing
		 */
		_setDisabled: function (value) {
			this.get('body').toggleClass('yui3-editable', !value);
			
			if (value) {
				this.set('activeChild', null);
			}
			
			return !!value;
		},
		
		
		/* --------------------------- HIGHLIGHTING --------------------------- */
		
		
		/**
		 * Highlight mode attribute setter
		 * 
		 * @param {String} mode Higlight mode value
		 * @returns {String} New highlight mode value
		 * @private
		 */
		_setHighlightMode: function (mode) {
			var old_mode = this.get('highlightMode'),
				mode = mode || 'disabled',
				node = this.get('body');
			
			if (node && old_mode != mode) {
				this.get('body').replaceClass('su-highlight-' + old_mode, 'su-highlight-' + mode);
			}
			
			return mode;
		},
		
		/**
		 * Highlight children blocks
		 * 
		 * @param {Object} evt Event facade object for highlight mode change
		 * @private
		 */
		_afterHighlightModeChange: function (evt) {
			var children = this.children,
				id = null,
				mode = evt.newVal,
				old_mode = evt.prevVal;
			
			if (mode != old_mode) {
				
				for (id in children) {
					children[id].set('highlightMode', mode);
				}
			}
		},
		
		/**
		 * Resize and reposition overlays
		 */
		resizeOverlays: function () {
			for(var i in this.children) {
				this.children[i].syncOverlayPosition();
			}
		},
		
		
		/* --------------------------- CHILDREN --------------------------- */
		
		
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
		 * Returns children blocks
		 *
		 * @return Children blocks
		 * @type {Object}
		 */
		getChildren: function () {
			return Supra.mix({}, this.children);
		},
		
		/**
		 * Returns all children blocks
		 *
		 * @return All children blocks
		 * @type {Object}
		 */
		getAllChildren: function () {
			var blocks = {},
				children = this.children;
			
			for(var child_id in children) {
				blocks[child_id] = children[child_id];
				children[child_id].getAllChildren(blocks);
			}
			
			return blocks;
		},
		
		beforeDestroy: function () {
			//Remove ordering
			if (this.order) {
				this.order.destroy();
			}
			
			//Destroy children
			var child = null,
				blocks = this.children;
			
			for(var i in blocks) {
				child = blocks[i];
				delete(blocks[i]);
				child.destroy();
			}
			
			//Unsubscribe resize
			var win = this.get('iframe').get('win');
			Y.unsubscribe('resize', this.resizeOverlays, win);
		}
	});
	
	
	Manager.PageContent.IframeContents = IframeContents;
	
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: (function () {
	var blocks = Supra.Manager.getAction('PageContent').BLOCK_PROTOTYPES,
		list = ['widget', 'supra.help', 'supra.page-content-ordering'];
	
	for(var i=0,ii=blocks.length; i<ii; i++) {
		list.push('supra.page-content-' + blocks[i].toLowerCase());
	}
	
	return list;
})()});