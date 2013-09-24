YUI.add('supra.input-image-list', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	var ITEM_TEMPLATE = Supra.Template.compile(
			'<li data-id="{{ id }}" {% if temporary %}class="blank"{% endif %}>\
				<div class="background"></div>\
				<div class="marker"></div>\
				<div class="remove">\
					<button class="su-button-fill">{{ "buttons.delete"|intl }}</button>\
				</div>\
				<div class="content click-target" {% if size %}style="background-image: url({{ sizes[size].external_path }})"{% endif %}></div>\
			 </li>'
		),
		NEW_ITEM_TEMPLATE = Supra.Template.compile(
			'<li class="new-item">\
				<div class="background"></div>\
				<div class="content"></div>\
			 </li>'
		);
	
	/**
	 * Image list input
	 * 
	 * @param {Object} config Configuration
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'input-image-list';
	Input.CLASS_NAME = Input.CSS_PREFIX = 'su-' + Input.NAME;
	
	Input.ATTRS = {
		/*
		 * Active item
		 */
		'activeItem': {
			value: null
		},
		
		/*
		 * List node
		 */
		'listNode': {
			value: null
		},
		
		'newItemControls': {
			value: null
		},
		
		/*
		 * List is ordered and items can be dragged and dropped
		 * to change order
		 */
		'ordered': {
			value: true
		}
	};
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		
		/**
		 * Content template
		 */
		CONTENT_TEMPLATE: '<ul></ul>',
		
		/**
		 * Button is used instead of input
		 */
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		
		/**
		 * Last known value, needed, because .get('value') is using
		 * getter
		 * @private
		 */
		value: null,
		
		/**
		 * Item list
		 * @private
		 */
		items: null,
		
		
		
		renderUI: function () {
			// Parent constructor
			this.items = [];
			Input.superclass.renderUI.apply(this, arguments);
			
			
			var content_box = this.get('contentBox'),
				list_node = null,
				node = null;
			
			// Change content box from input into actual element
			if (content_box.test('input')) {
				list_node = Y.Node.create(this.CONTENT_TEMPLATE);
				list_node.addClass(this.getClassName('content'));
				content_box.insert(list_node, 'after');
				
				this.set('listNode', list_node);
			} else {
				this.set('listNode', content_box);
				list_node = content_box;
			}
			
			// New item
			node = Y.Node.create(NEW_ITEM_TEMPLATE({}));
			list_node.append(node);
			list_node.addClass('clearfix');
			
			this.set('newItemControls', node);
			
			if (this.value) {
				this._uiRenderItems();
			}
		},
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			var list_node = this.get('listNode'),
				new_node  = this.get('newItemControls');
			
			new_node.on('click', this._onAddNewItem, this);
			list_node.delegate('click', this._onItemClick, '.click-target', this);
			
			this.after('valueChange', this._uiRenderItems, this);
			this.after('activeItemChange', this._uiSetActiveItem, this);
			
			if (this.get('ordered')) {
				this._initDnD();
			}
		},
		
		syncUI: function () {
			Input.superclass.syncUI.apply(this, arguments);
			
			if (this.get('ordered')) {
				this.orderDelegate.syncTargets();
			}
		},
		
		
		/* ------------------------------ Item list ordering -------------------------------- */
		
		
		/**
		 * Initialize drag and drop
		 * 
		 * @private
		 */
		_initDnD: function () {
			var list_node = this.get('listNode'),
				del = null,
				
				fnDragDrop = Y.bind(this._onDragDrop, this),
				fnDragStart = Y.bind(this._onDragStart, this),
				fnDropOver = Y.bind(this._onDropOver, this);
			
			this.orderDelegate = del = new Y.DD.Delegate({
				'container': list_node,
				'nodes': 'li',
				'target': true,
				'invalid': '.new-item',
				'dragConfig': {
					'haltDown': false,
					'clickTimeThresh': 1000
				}
			});
			
			del.dd.addInvalid('.new-item');
			
			del.dd.plug(Y.Plugin.DDProxy, {
				'moveOnEnd': false,
				'cloneNode': true,
				'resizeFrame': false
			});
			
			del.on('drag:start', fnDragStart);
			del.on('drag:over', fnDropOver);
			del.on('drag:end', fnDragDrop);
		},
		
		/**
		 * Handle drag:start event
		 * 
		 * @param {Object} evt Event
		 * @private
		 */
		_onDragStart: function (evt) {
			//Get our drag object
	        var drag = evt.target,
	        	proxy = drag.get('dragNode'),
	        	node = drag.get('node');
			
	        //Set proxy styles
	        proxy.addClass('proxy');
	        
	        this.originalDragIndex = node.get('parentNode').get('children').indexOf(node);
	        this.lastDragIndex = this.originalDragIndex;
		},
		
		/**
		 * Handle drop:over event
		 * 
		 * @param {Object} evt Event
		 * @private
		 */
		_onDropOver: function (evt) {
			//Get a reference to our drag and drop nodes
		    var drag = evt.drag.get('node'),
		        drop = evt.drop.get('node'),
		        selector = 'li',
		        invalid = this.orderDelegate.get('invalid'),
		        index = 0,
		        dragGoingUp = false,
		        indexFrom = 0,
		        indexTo = 0;
			
		    //Are we dropping on a li node?
		    if (drop.test(selector) && !drop.test(invalid)) {
			    index = drop.get('parentNode').get('children').indexOf(drop);
			    dragGoingUp = index < this.lastDragIndex;
			    
			    indexFrom = Math.min(index, this.lastDragIndex);
			    indexTo = Math.max(index, this.lastDragIndex);
			    this.lastDragIndex = index;
			    
			    //Are we not going up?
		        if (!dragGoingUp) {
		            drop = drop.get('nextSibling');
		        }
		        
				if (!dragGoingUp && !drop) {
			        //evt.drop.get('node').get('parentNode').append(drag);
			        evt.drop.get('node').get('parentNode').insertBefore(drag, this.get('newItemControl'));
				} else {
			        evt.drop.get('node').get('parentNode').insertBefore(drag, drop);
				}
				
		        //Resize node shims, so we can drop on them later since position may
		        //have changed
		        var nodes = drop.get('parentNode').get('children'),
		        	dropObj = null;
		        
		        for (var i=indexFrom; i<= indexTo; i++) {
		        	dropObj = nodes.item(i).drop;
		        	if (dropObj) {
		        		dropObj.sizeShim();
		        	}
		        }
		    }
		},
		
		/**
		 * Handle drag:drop event
		 * 
		 * @param {Object} evt Event
		 * @private
		 */
		_onDragDrop: function () {
			if (this.originalDragIndex != this.lastDragIndex) {
				this._uiFreeze();
				this.set('value', this.get('value'));
				this._uiUnfreeze();
			}
		},
		
		/**
		 * Returns list item order  based on DOM order
		 * 
		 * @returns {Object} List of item ids and their indexes
		 */
		_getDOMOrder: function () {
			if (!this.get('rendered')) return;
			
			var order = {},
				list_node = this.get('listNode'),
				item_nodes = list_node.get('children'),
				i = 0,
				ii = item_nodes.size(),
				id = null;
			
			for (; i<ii; i++) {
				id = item_nodes.item(i).getAttribute('data-id');
				
				if (id) {
					order[id] = i;
				}
			}
			
			return order;
		},
		
		
		/* ------------------------------ Media sidebar -------------------------------- */
		
		
		/**
		 * Open link manager for redirect
		 */
		openMediaSidebar: function (value) {
			var path = value && !value.temporary ? [].concat(value.path).concat(value.id) : 0;
			
			this.image_was_selected = false;
			
			//Save previous right layout container action to restore
			//it after 
			this.restore_action = null;
			if (Manager.Loader.isLoaded('LayoutRightContainer')) {
				
				var action_name = Manager.LayoutRightContainer.getActiveAction();
				if (action_name && Manager.Loader.isLoaded(action_name)) {
					var action = Manager.getAction(action_name);
					
					if (action_name == 'PageContentSettings') {
						this.restore_action = {
							'action': action,
							'args': [action.form, action.options]
						};
					} else if (action_name == 'PageSettings') {
						this.restore_action = {
							'action': action,
							'args': [true]
						};
					}
					
				}
			}
			
			Manager.executeAction('MediaSidebar', {
				'item': path,
				'dndEnabled': false,
				'onselect': Y.bind(this.onMediaSidebarImage, this),
				'onclose': Y.bind(this.onMediaSidebarClose, this)
			});
		},
		
		/**
		 * Update value on change
		 *
		 * @param {Object} data
		 */
		onMediaSidebarImage: function (data) {
			this._uiUpdateItem(null, data.image);
			this.image_was_selected = true;
			
			if (this.restore_action) {
				var conf = this.restore_action;
				conf.action.execute.apply(conf.action, conf.args);
			}
		},
		
		/**
		 * Update value on change
		 *
		 * @param {Object} data
		 */
		onMediaSidebarClose: function () {
			var item_id = this.get('activeItem'),
				item    = item_id ? this._listGetItem(item_id) : null;
			
			if (!this.image_was_selected) {
				if (item && item.data.temporary) {
					this._uiRemoveItem(null, true);
				} else{
					this.set('activeItem', null);
				}
			}
			
			if (this.restore_action) {
				var conf = this.restore_action;
				conf.action.execute.apply(conf.action, conf.args);
			}
		},
		
		
		/* ------------------------------ Item list -------------------------------- */
		
		
		/**
		 * Add item to the list
		 * 
		 * @param {Object} item Item
		 * @private
		 */
		_listAddItem: function (item) {
			var items = this.items;
			
			if (item) {
				items.push(item);
				return item;
			} else {
				return null;
			}
		},
		
		/**
		 * Remove item from the list
		 * 
		 * @param {String} item_id Item ID
		 * @private
		 */
		_listRemoveItem: function (item_id) {
			var items = this.items,
				i     = 0,
				ii    = items.length;
			
			for (; i<ii; i++) {
				if (items[i].data.id == item_id) {
					items.splice(i, 1);
					return;
				}
			}
		},
		
		/**
		 * Returns list item by ID
		 * 
		 * @param {String} item_id Item ID
		 * @private
		 */
		_listGetItem: function (item_id) {
			var items = this.items,
				i     = 0,
				ii    = items.length;
			
			for (; i<ii; i++) {
				if (items[i].data.id == item_id) {
					return items[i];
				}
			}
			
			return null;
		},
		
		/**
		 * Update item data
		 * 
		 * @param {String} item_id Item id
		 * @param {Object} data New item data
		 * @private
		 */
		_listUpdateItem: function (item_id, data) {
			var item = this._listGetItem(item_id);
			
			if (item && data) {
				item.data = data;
			}
		},
		
		
		/* ------------------------------ Events -------------------------------- */
		
		
		/**
		 * Handle "Remove" button click
		 * 
		 * @param {Object} e Event facade object
		 * @param {Object} node Item node
		 * @private
		 */
		_onRemoveClick: function (e, node) {
			// Note: node is passed instead of ID, because item ID may change
			var id = node.getAttribute('data-id');
			
			if (id) {
				this._uiRemoveItem(id, true);
			}
		},
		
		/**
		 * Handle new item click
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		_onAddNewItem: function (e) {
			if (this.get('disabled')) return;
			
			var item = this._uiAddItem(null, true);
			
			if (item) {
				this.set('activeItem', item.data.id);
				this.openMediaSidebar(item.data);
			}
			
			e.preventDefault();
		},
		
		/**
		 * Handle item click
		 */
		_onItemClick: function (e) {
			if (this.get('disabled')) return;
			
			var node = e.target.closest('li'),
				item_id = node.getAttribute('data-id'),
				item = this._listGetItem(item_id);
				
			if (item_id && item) {
				this.set('activeItem', item_id);
				this.openMediaSidebar(item.data);
			}
			
			e.preventDefault();
		},
		
		
		/* ------------------------------ UI -------------------------------- */
		
		
		/**
		 * Freeze UI
		 * While frozen value change will not affect UI
		 * 
		 * @private
		 */
		_uiFreeze: function () {
			this._uiFrozen = true;
		},
		
		/**
		 * Unfreeze UI
		 * 
		 * @private
		 */
		_uiUnfreeze: function () {
			this._uiFrozen = false;
		},
		
		
		/**
		 * Render image list
		 * 
		 * @private
		 */
		_uiRenderItems: function (e) {
			if (this._uiFrozen) return;
			
			var value = this.value,
				i     = 0,
				ii    = value ? value.length : 0;
			
			this._uiRemoveItems();
			
			for (; i<ii; i++) {
				this._uiAddItem(value[i]);
			}
		},
		
		/**
		 * Remove all items
		 * 
		 * @private
		 */
		_uiRemoveItems: function () {
			if (this._uiFrozen) return;
			
			var items = this.items,
				i     = 0,
				ii    = items ? items.length : 0;
			
			for (; i<ii; i++) {
				items[i].node.remove(true);
			}
			
			this.items = [];
		},
		
		/**
		 * Add new item
		 * 
		 * @param {Object} data Optional image data
		 * @param {Boolean} animate Animate
		 * @private
		 */
		_uiAddItem: function (data, animate) {
			if (this._uiFrozen) return;
			
			if (!data) {
				data = {
					'id': Y.guid(),
					'temporary': true,
					'size': null
				};
			} else {
				data.size = Y.DataType.Image.getSizeName(data, {
					'minWidth': 100,
					'minHeight': 100
				}) || Y.DataType.Image.getSizeName(data, {
					'width': 100,
					'height': 100
				});
			}
			
			var node = Y.Node.create(ITEM_TEMPLATE(data)),
				node_new_item = this.get('newItemControls'),
				item = null,
				button = new Supra.Button({'srcNode': node.one('button'), 'style': 'small-red'});
			
			node_new_item.insert(node, 'before');
			
			button.render();
			button.on('click', this._onRemoveClick, this, node);
			
			item = this._listAddItem({'node': node, 'data': data});
			
			if (item && animate) {
				node.setStyles({
					'opacity': 0
				});
				node_new_item.setStyles({
					'left': '-110px'
				});
				
				node.transition({
					'opacity': 1,
					'duration': 0.35
				});
				node_new_item.transition({
					'left': '0px',
					'duration': 0.35
				});
			}
			
			this.syncUI();
			
			return item;
		},
		
		/**
		 * Remove item
		 * 
		 * @private
		 */
		_uiRemoveItem: function (item_id, animate) {
			this._uiFreeze();
			
			var item = null,
				active_id = this.get('activeItem');
			
			if (!item_id) {
				item_id = active_id;
			}
			if (!item_id || !(item = this._listGetItem(item_id))) {
				return;
			}
			
			// Trigger value change
			this.set('value', this.get('value'));
			
			if (animate) {
				var node = item.node,
					next = node.next();
				
				node.transition({
					'opacity': 0,
					'duration': 0.35
				});
				
				next.transition({
					'marginLeft': '-110px',
					'duration': 0.35
				}, Y.bind(function () {
					
					node.remove(true);
					next.setStyles({
						'marginLeft': '0px'
					});
					
					this._uiRemoveItemAfter(item);
				}, this));
			} else {
				this._uiRemoveItemAfter(item);
			}
			
			this._uiUnfreeze();
		},
		
		/**
		 * After item remove sync UI
		 * 
		 * @private
		 */
		_uiRemoveItemAfter: function (item) {
			var active_id = this.get('activeItem');
			
			item.node.remove(true);
			
			this._listRemoveItem(item.data.id);
			
			if (item.data.id == active_id) {
				this.set('activeItem', null);
			}
			
			this.syncUI();
		},
		
		/**
		 * Update item
		 * 
		 * @private
		 */
		_uiUpdateItem: function (item_id, data) {
			this._uiFreeze();
			
			var item = null,
				size = null,
				active_id = this.get('activeItem');
			
			if (!item_id) {
				item_id = active_id;
			}
			if (!item_id || !(item = this._listGetItem(item_id))) {
				return;
			}
			
			if (item.id == data.id) {
				// Nothing has changed
				this.set('activeItem', null);
				return;
			}
			if (this._listGetItem(data.id)) {
				// Image user selected already exists in the list
				// remove this item
				this._uiRemoveItem(null, true);
				return;
			}
			
			if (item.data.temporary) {
				item.node.removeClass('blank');
			}
			
			// Update node attribute which is unique item id
			item.node.setAttribute('data-id', data.id);
			
			// Set image
			size = Y.DataType.Image.getSizeName(data, {
				'minWidth': 100,
				'minHeight': 100
			}) || Y.DataType.Image.getSizeName(data, {
				'width': 100,
				'height': 100
			});
			
			item.node.one('.content').setStyle('backgroundImage', 'url(' + data.sizes[size].external_path + ')');
			
			// Unmark active item
			if (item_id == active_id) {
				this.set('activeItem', null);
			}
			
			this._listUpdateItem(item_id, data);
			
			// Trigger value change
			this.set('value', this.get('value'));
			
			this.syncUI();
			this._uiUnfreeze();
		},
		
		/**
		 * Mark active item 
		 */
		_uiSetActiveItem: function (e) {
			var old_item = this._listGetItem(e.prevVal),
				new_item = this._listGetItem(e.newVal);
			
			if (old_item) {
				old_item.node.removeClass('active');
			}
			if (new_item) {
				new_item.node.addClass('active');
			}
		},
		
		
		
		/* ------------------------------ Attributes -------------------------------- */
		
		
		/**
		 * Value attribute setter
		 * 
		 * @param {Object} value Attribute value
		 * @returns {Object} Attribute value
		 * @private
		 */
		_setValue: function (value) {
			this.value = value;
			return value;
		},
		
		/**
		 * Value attribute getter
		 * 
		 * @returns {Object} Attribute value
		 * @private
		 */
		_getValue: function (value) {
			if (!this.items) return this.value;
			
			var items = this.items,
				i     = 0,
				ii    = items.length,
				value = [],
				order = this._getDOMOrder();
			
			for (; i<ii; i++) {
				if (!items[i].data.temporary) {
					value.push(items[i].data);
				}
			}
			
			if (order) {
				value.sort(function (a, b) {
					var index_a = order[a.id],
						index_b = order[b.id];
					
					return index_a == index_b ? 0 : index_a > index_b ? 1 : -1;
				});
			}
			
			return value;
		},
		
		/**
		 * Return only IDs, all other information is already known on server
		 * 
		 * @returns {Array} Data which will be sent to server, image IDs or empty array
		 * @private
		 */
		_getSaveValue: function () {
			var value = this.get('value'),
				i     = 0,
				ii    = value.length,
				out   = [];
			
			for (; i<ii; i++) {
				out.push(value[i].id);
			}
			
			return out;
		},
		
		/**
		 * Disabled attribute setter
		 * 
		 * @param {Boolean} disabled Attribute value
		 * @returns {Boolean} Attribute value
		 */
		_setDisabled: function (disabled) {
			Input.superclass._setDisabled.apply(this, arguments);
			
			if (this.orderDelegate) {
				this.orderDelegate.dd.set('lock', !!disabled);
			}
			
			return disabled;
		}
		
		
	});
	
	Supra.Input.ImageList = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-image', 'supra.datatype-image']});