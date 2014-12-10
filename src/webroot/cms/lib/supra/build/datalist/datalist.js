YUI.add('supra.datalist', function (Y) {
	//Invoke strict mode
	"use strict";
	
	// Will use this value as default item height, until
	// actual height can be determined
	var ITEM_HEIGHT_DEFAULT = 32;
	
	// Number of items which to load which will be out of view
	var ITEM_LOAD_OFFSET_COUNT = 10;
	
	
	/**
	 * DataList is a widget which takes data or optionally loads it from URL
	 * and renders a list of items.
	 *
	 * If list is scrollable then uses Supra.Scrollable and loads and renders
	 * only data which is visible
	 */
	function DataList (config) {
		this.items = [];
		DataList.superclass.constructor.apply(this, arguments);
	}
	
	DataList.NAME = 'datalist';
	
	DataList.CSS_PREFIX = 'su-' + DataList.NAME;
	DataList.CLASS_NAME = 'su-' + DataList.NAME;
	DataList.ATTRS = {
		
		/*
		 * Wrapper template, either string or function
		 */
		'wrapperTemplate': {
			value: '<ul></ul>'
		},
		
		/*
		 * CSS selector to find list item
		 * If ommited, then wrapper element is list element
		 */
		'listSelector': {
			value: ''
		},
		
		/*
		 * Item template, either string or function
		 */
		'itemTemplate': {
			value: ''
		},
		
		/*
		 * Item template for empty message, either string or function
		 */
		'itemEmptyTemplate': {
			value: ''
		},
		
		/*
		 * Message when there are no results
		 */
		'messageNoResults': {
			value: '{# inputs.autocomplete_empty #}'
		},
		
		/*
		 * Item height, used when lazy loading is done
		 */
		'itemHeight': {
			value: null
		},
		
		/*
		 * Data
		 */
		'data': {
			value: null,
			setter: '_attrSetData',
			getter: '_attrGetData'
		},
		
		'dataSource': {
			value: null
		},
		
		'dataTransform': {
			value: null
		},
		
		/*
		 * Animation when adding new item
		 */
		'animationAdd': {
			value: 'appear'
		},
		
		/*
		 * Animation when removing item
		 */
		'animationRemove': {
			value: 'disappear'
		},
		
		/*
		 * Use Supra.Scrollable for content
		 */
		'scrollable': {
			value: false,
			writeOnce: true
		},
		
		/*
		 * Style
		 */
		'style': {
			value: null
		}
		
	};
	
	DataList.HTML_PARSER = {};
	
	DataList.NODE_ATTRIBUTE_NAME = 'data-datalist-item-id';
	
	Y.extend(DataList, Y.Widget, {
		
		/**
		 * Data cache
		 * @type {Array}
		 * @private
		 */
		items: null,
		
		/**
		 * Unique ID counter
		 * used to generate item ids
		 * @type {Number}
		 * @private
		 */
		guid: 1,
		
		/**
		 * Nodes and widgets
		 * @type {Object}
		 * @private
		 */
		nodes: null,
		
		/**
		 * Source data
		 */
		source: null,
		
		/**
		 * Empty message visible
		 */
		emptyMessageVisible: false,
		
		
		/**
		 * Render UI
		 * 
		 * @private
		 */
		renderUI: function () {
			// Render wrapper
			var bounding = this.get('boundingBox'),
				content  = this.get('contentBox'),
				wrapper  = Y.Node.create(this._renderTemplate(this.get('wrapperTemplate'), {}) || '<ul></ul>'),
				selector = this.get('listSelector'),
				style    = this.get('style'),
				list     = null,
				scrollable = null;
			
			if (wrapper.get('children').size() == 0 && wrapper.get('tagName') == content.get('tagName')) {
				// Wrapper is the same as content
				wrapper.remove(true);
				wrapper = content;
			} else {
				content.append(wrapper);
			}
			
			// Style
			if (style) {
				bounding.addClass(this.getClassName(style));
			}
			
			// Find list
			if (selector) {
				list = wrapper.one(this.get('listSelector'));
			} else {
				this.set('listSelector', wrapper.get('tagName'));
			}
			
			// Add scroll widget
			if (this.get('scrollable')) {
				bounding.addClass(this.getClassName('scrollable'));
				
				scrollable = new Supra.Scrollable();
				scrollable.render(bounding);
				scrollable.get('contentBox').append(content);
				scrollable.on('sync', this.check, this);
			}
			
			// Throttle change call
			this._onContentChange = Supra.throttle(this._onContentChange, 50, this, true);
			
			// Cache
			this.nodes = {
				'wrapper': wrapper,
				'list': list || wrapper,
				'scrollable': scrollable,
				'empty': null
			};
			
			// Show message
			this._showEmptyListMessage();
			
			
			if (this.get('dataSource')) {
				this.reload();
			} else if (this.get('data')) {
				Supra.immediate(this, this.syncUI);
			}
		},
		
		
		/**
		 * Attach event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			this.after('dataSourceChange', this._attrHandleDataSourceChange, this);
			this.after('styleChange', this._attrHandleStyleChange, this);
		},
		
		/**
		 * Sync UI
		 * 
		 * @private
		 */
		syncUI: function () {
			var items = this.items,
				i     = 0,
				ii    = items.length;
			
			for (; i<ii; i++) {
				this._addItem(items[i]);
			}
		},
		
		/**
		 * Re-render item
		 * 
		 * @param {Object} item Item or item data
		 */
		syncItem: function (item) {
			this._addItem(item);
		},
		
		/**
		 * Destructor
		 * Clean up
		 * 
		 * @private
		 */
		destructor: function () {
			if (this.nodes.list) {
				this.nodes.list.remove(true);
			}
			if (this.nodes.scrollable) {
				this.nodes.scrollable.destroy();
				this.nodes.scrollable = null;
			}
			if (this.xhr) {
				this.xhr.abort();
				this.xhr = null;
			}
			
			this._onContentChange = null;
			this.items = null;
			this.nodes = null;
			this.source = null;
		},
		
		
		/* --------------------------- Renderer --------------------------- */
		
		
		/**
		 * Render template
		 * 
		 * @param {String|Function} template Template or template id
		 * @param {Object} data Template data
		 * @returns {String|Null} Rendered HTML or null if there was a problem
		 * @private
		 */
		_renderTemplate: function (template, data) {
			var fn;
			
			if (typeof template === 'function') {
				return template(data);
			} else if (template && typeof template === 'string') {
				fn = Supra.Template(template);
				if (fn) {
					return fn(data);
				} else {
					return template;
				}
			}
			
			return null;
		},
		
		/**
		 * Render item
		 * 
		 * @param {Object} data Item data
		 * @returns {Object} Item node
		 * @private
		 */
		_renderItemNode: function (data, animate) {
			var item    = this._getItemByInternalId(data.id),
				type    = typeof data.data,
				tpldata = (type === 'string' || type === 'number' ? {'title': data.data} : data.data),
				node    = Y.Node.create(this._renderTemplate(this.get('itemTemplate'), tpldata));
			
			node.setAttribute(DataList.NODE_ATTRIBUTE_NAME, data.id);
			
			if (item && item.node) {
				// Replace existing item
				item.node.insert(node, 'before');
				this._removeItemNode(data);
			} else {
				// Add
				this.nodes.list.append(node);
			}
			
			if (this.nodes.scrollable && !this.get('itemHeight')) {
				// Save item height
				this.set('itemHeight', node.get('offsetHeight'));
			}
			
			return node;
		},
		
		/**
		 * Remove item
		 * 
		 * @param {Number|Object} data Item or item id
		 * @param {Boolean|String} animate Animate node removal
		 * @private
		 */
		_removeItemNode: function (data, animate) { 
			var id   = typeof data === 'number' ? data : data.id,
				item = typeof data === 'number' ? this._getItemByInternalId(id) : data,
				key  = null;
			
			if (animate) {
				var animation = (animate && typeof animate === 'string' ? animate : this.get('animationRemove'));
				this._animate(item.node, animation).always(function () {
					this._removeItemNode(item, false);
				}, this)
				
				return;
			}
			
			// Remove widgets
			for (key in item.widgets) {
				item.widgets[key].destroy();
			}
			
			item.widgets = {};
			
			// Remove events
			for (key in item.events) {
				item.events[key].detach();
			}
			
			item.events = {};
			
			// Trigger event
			this.fire('itemDestroy', {
				'node': item.node,
				'data': item.data,
				'item': item,
				'widgets': {},
				'events': {}
			});
			
			// Remove node
			if (item.node) {
				item.node.remove(true);
				item.node = null;
				
				// Update scrollable
				this._onContentChange();
			}
		},
		
		/**
		 * Returns list node
		 * 
		 * @returns {Object|Null} List node or null if it's not yet rendered
		 */
		getListNode: function () {
			return this.nodes ? this.nodes.list : null;
		},
		
		/**
		 * Returns wrapper node
		 * 
		 * @returns {Object|Null} Wrapper node or null if it's not yet rendered
		 */
		getWrapperNode: function () {
			return this.nodes ? this.nodes.wrapper : null;
		},
		
		
		/* ------------------------ Data loading ------------------------ */
		
		
		/**
		 * Load and draw records if needed
		 */
		check: function () {
			var source = this.source,
				url = this.get('dataSource'),
				resultsPerRequest = null,
				resultsOffset,
				itemHeight;
			
			if (url && (source.total === null || source.total > source.loaded)) {
				if (this.nodes.scrollable) {
					itemHeight = this.get('itemHeight') || ITEM_HEIGHT_DEFAULT;
					resultsPerRequest = this.getItemsPerView();
					resultsOffset = Math.ceil(this.nodes.scrollable.getScrollPosition() / itemHeight) + 5;
					
					if (resultsOffset + resultsPerRequest > source.loaded) {
						this._loadRecords(source.loaded, resultsPerRequest);
						return true;
					}
				} else {
					// Load all records
					this._loadRecords();
					return true;
				}
			}
			
			// No need to load more
			return false;
		},
		
		/**
		 * Reload and redraw all data
		 */
		reload: function () {
			if (this.xhr) {
				this.xhr.abort();
			}
			
			this.removeAll();
			
			this.source = {
				'total': null,
				'loaded': 0
			};
			
			this.check();
		},
		
		/**
		 * Returns how many items can fit into view
		 *
		 * @returns {Number} Number of items per view
		 */
		getItemsPerView: function () {
			var list_height = this.get('boundingBox').get('offsetHeight'),
				item_height = this.get('itemHeight') || ITEM_HEIGHT_DEFAULT;
			
			return Math.ceil(list_height / item_height);
		},
		
		/**
		 * Load and render records
		 */
		_loadRecords: function (offset, resultsPerRequest) {
			// Wait till request completes
			if (this.xhr) return;
			var url = this.get('dataSource');
			
			this.xhr = Supra.io(url, {
				data: {
					offset: offset,
					resultsPerRequest: resultsPerRequest
				}
			}).always(function () {
				this.xhr = null;
			}, this).done(function (data) {
				var transform = this.get('dataTransform'),
					total = 0,
					items = [],
					i     = 0,
					ii    = null,
					
					listNode = null,
					itemHeight = null;
				
				if (Y.Lang.isArray(data)) {
					// Received as array, assume there are no more records
					total = data.length;
					items = data;
				} else if (data && 'results' in data) {
					items = data.results || [];
					total = data.total || items.length;
				}
				
				if (typeof transform !== 'function') {
					transform = null;
				}
				
				this.source.loaded += items.length;
				this.source.total = total;
				
				for (ii=items.length; i<ii; i++) {
					if (transform) {
						this._addItem(transform(items[i]));
					} else {
						this._addItem(items[i]);
					}
				}
				
				// Update list height if scrolling can trigger another .check
				if (this.nodes.scrollable) {
					listNode = this.getListNode();
					
					if (this.source.loaded < this.source.total) {
						itemHeight = this.get('itemHeight') || ITEM_HEIGHT_DEFAULT;
						listNode.setStyle('minHeight', itemHeight * this.source.total + 'px');
					} else {
						listNode.setStyle('minHeight', '0px');
					}
				}
				
				// Check if we need to load more
				this.check();
			}, this);
		},
		
		
		/* ------------------------- Events ------------------------- */
		
		
		/**
		 * When content change has completed update scrollbars and
		 * trigger an event
		 *
		 * @private
		 */
		_onContentChange: function () {
			// Update scrollbars
			var scrollable = this.nodes.scrollable;
			
			if (scrollable) {
				scrollable.syncUI();
			}
			
			// Trigger change event
			this.fire('redraw');
		},
		
		
		/* --------------------------- Items --------------------------- */
		
		
		/**
		 * Add item
		 * 
		 * @param {Object} data Item data
		 * @param {Boolean} animate Animate, default is false
		 * @private
		 */
		_addItem: function (data, animate) {
			data = this._normalizeData([data])[0];
			
			if (!this.get('rendered')) {
				// We will have to wait till syncUI call
				if (!this._getItemByInternalId(data.id)) {
					this.items.push(data);
				}
				
				return data;
			}
			
			var item = this._getItemByInternalId(data.id),
				node = item ? item.node : null,
				evt  = null;
			
			if (item) {
				// Update data
				if (item !== data) {
					Supra.mix(item.data, data.data);
				}
			} else {
				// Add data
				item = {
					'_datalist_item': true,
					'id': (this.guid++),
					'data': data.data,
					'node': null,
					'widgets': {},
					'events': {}
				};
				
				this.items.push(item);
			}
			
			this._hideEmptyListMessage();
			item.node = this._renderItemNode(item);
			
			// Trigger event to allow to decorate this item
			evt = {
				'node': item.node,
				'data': item.data,
				'item': item,
				'widgets': {},
				'events': {}
			};
			
			this.fire('itemRender', evt);
			
			item.widgets = evt.widgets;
			item.events = evt.events;
			
			if (animate) {
				var animation = (animate && typeof animate === 'string' ? animate : this.get('animationAdd'));
				this._animate(item.node, animation);
			}
			
			// Update scrollable
			this._onContentChange();
			
			return item;
		},
		
		/**
		 * Returns number of items in the list
		 * 
		 * @returns {Number} Number of items
		 */
		size: function () {
			return this.items ? this.items.length : 0;
		},
		
		/**
		 * Add item
		 * 
		 * @param {Object} data Item data
		 * @param {Boolean} animate Animate, default is false
		 * @returns {Object} Item
		 */
		addItem: function (data, animate) {
			return this._addItem(data, animate);
		},
		
		/**
		 * Update item data
		 * 
		 * @param {Object} item Item
		 * @param {Object} data Item data
		 */
		updateItem: function (item, data) {
			item = typeof item === 'number' ? this._getItemByInternalId(item) : item;
			
			if (item) {
				Supra.mix(item.data, data);
				this._addItem(item);
			}
		},
		
		/**
		 * Remove item
		 * 
		 * @param {Nunber|Object} data Item ID or item data
		 * @param {Boolean} remove_data Remove also data
		 * @param {Boolean} animate Animate, default is false
		 * @private 
		 */
		_removeItem: function (data, remove_data, animate) {
			var id   = typeof data === 'number' ? data : data.id,
				item = this._getItemByInternalId(id),
				items, i, ii;
			
			this._removeItemNode(data, animate);
			
			if (remove_data) {
				items = this.items;
				
				for (i=0, ii=items.length; i<ii; i++) {
					if (items[i].id == id) {
						items.splice(i, 1);
						break;
					}
				}
				
				if (!items.length) {
					// Last item, show message
					this._showEmptyListMessage();
				}
			}
		},
		
		/**
		 * Remove item
		 * 
		 * @param {Nunber|Object} data Item ID or item data
		 * @param {Boolean} remove_data Remove also data
		 * @param {Boolean} animate Animate, default is false
		 * @param {Boolean|String} animate Animate node removal
		 */
		removeItem: function (item, animate) {
			this._removeItem(item, true, animate);
		},
		
		/**
		 * Remove all items
		 */
		removeAll: function () {
			var items = this.items,
				i     = items.length - 1;
			
			for (; i>=0; i--) {
				this._removeItem(items[i], true);
			}
			
			this._onContentChange();
		},
		
		/**
		 * Remove item by property name and property value
		 * or searches using filter function
		 * 
		 * @param {String|Function} name Property name or function
		 * @param {Object|Function} value Property value or function
		 * @param {Boolean} animate Animate, default is false
		 * @returns {Boolean} True on success or false if item was not found
		 */
		removeItemByProperty: function (property_name, property_value, animate) {
			var item = this.getItemByProperty(property_name, property_value);
			if (item) {
				this._removeItem(item, true, animate);
				return true;
			} else {
				return false;
			}
		},
		
		/**
		 * Returns single item from data by property name and property value
		 * or searches using filter function
		 * 
		 * If as first argument is passed a function then it will act as filter
		 * and will return first result for which filter returns true,
		 * to filter function is passed item
		 * 
		 * If as second argument is function then it will act as filter
		 * and will return first resupt for which filter returns true,
		 * to filter function is passed item property `property_name` value
		 * 
		 * @param {String|Function} name Property name or function
		 * @param {Object|Function} value Property value or function
		 * @returns {Object|Null} Matching object
		 */
		getItemByProperty: function (property_name, property_value) {
			var data = this.items,
				item = null,
				i    = 0,
				ii   = data ? data.length : 0,
				test = null,
				name = null,
				value;
			
			if (typeof property_name === 'function') {
				test = property_name;
				property_name = null;
			}
			if (typeof property_value === 'function' && !test) {
				test = property_value;
				property_value = null;
			}
			
			if (!property_name && !test) {
				// Will not find anything, since there is no filter and no
				// property to check
				return null;
			}
			
			if (property_name === 'index' && typeof property_value === 'number') {
				// By index
				return property_value >= 0 && property_value < ii ? data[property_value] : null;
			}
			
			for (; i<ii; i++) {
				item = data[i].data;
				
				if (property_name && !(property_name in item)) continue;
				
				// if getItemByProperty(function () {...}) then test item
				// otherwise test value
				value = property_name ? item[property_name] : item;
				
				if (test) {
					if (test(value, i) === true) {
						return data[i];
					}
				} else {
					if (value === property_value) {
						return data[i];
					}
				}
			}
			
			return null;
		},
		
		/**
		 * Returns single item data from data by property name and property value
		 * or searches using filter function
		 * 
		 * @param {String|Function} name Property name or function
		 * @param {Object|Function} value Property value or function
		 * @returns {Object|Null} Matching object
		 */
		getItemDataByProperty: function (property_name, property_value) {
			var item = this.getItemByProperty(property_name, property_value);
			return item ? item.data : null;
		},
		
		/**
		 * Returns item node by property name and property value
		 * or searches using filter function
		 * 
		 * @param {String|Function} name Property name or function
		 * @param {Object|Function} value Property value or function
		 * @returns {Object|Null} Matching node
		 */
		getItemNodeByProperty: function (property_name, property_value) {
			var item = this.getItemByProperty(property_name, property_value);
			return item ? item.node : null;
		},
		
		/**
		 * Returns item by node
		 * 
		 * @param {Object} node Node
		 * @returns {Object|Null} Item 
		 */
		getItemByNode: function (node) {
			var attr,
				node = this.getItemNodeByNode(node),
				item = null;
			
			if (node) {
				attr = node.getAttribute(DataList.NODE_ATTRIBUTE_NAME);
				if (attr) {
					item = this._getItemByInternalId(attr);
					return item ? item : null;
				}
			}
			
			return null;
		},
		
		/**
		 * Returns item data by node
		 * 
		 * @param {Object} node Node
		 * @returns {Object|Null} Item 
		 */
		getItemDataByNode: function (node) {
			var item = this.getItemByNode(node);
			return item ? item.data : null;
		},
		
		/**
		 * Returns item node by descendant node
		 * 
		 * @param {Object} node Node
		 * @returns {Object|Null} Item node or null if node is not any items descendant
		 */
		getItemNodeByNode: function (node) {
			if (node) {
				return node.closest(this.get('listSelector') + ' > *');
			}
			return null;
		},
		
		/**
		 * Returns item by internal datalist id
		 * 
		 * @param {Number} id Internal id
		 * @returns {Object|Null} Item data or null if item is not found
		 * @private
		 */
		_getItemByInternalId: function (id) {
			var data = this.items || [],
				i    = 0,
				ii   = data.length;
			
			for (; i<ii; i++) {
				if (data[i].id == id) return data[i];
			}
			
			return null;
		},
		
		/**
		 * Returns item node by internal datalist id
		 * 
		 * @param {Number} id Internal id
		 * @returns {Object|Null} Node or null if item is not found
		 * @private
		 */
		_getNodeByInternalId: function (id) {
			var item = this._getItemByInternalId(id);
			return item ? item.node : null;
		},
		
		
		/**
		 * Returns full data for value
		 * If value is an array of values then returns array of data
		 * 
		 * @param {String} value Optional, value for which to return full data
		 * @returns {Object} Value data
		 */
		getValueData: function (value) {
			if (Y.Lang.isArray(value)) {
				var i  = 0,
					ii = value.length,
					result,
					results = [];
				
				for (; i<ii; i++) {
					result = this.getItemByProperty('id', value[i]) || this._getItemByInternalId(value[i]);
					
					if (result) {
						results.push(result);
					}
				}
				
				return results;
			} else {
				return (this.getItemByProperty('id', id) || this._getItemByInternalId(id));
			}
		},
		
		/**
		 * Returns true if list has options with given id
		 * 
		 * @param {String} id Option ID
		 * @return True if has option with given id, otherwise false
		 * @type {Boolean}
		 */
		hasValue: function (id) {
			return !!(this.getItemByProperty('id', id) || this._getItemByInternalId(id));
		},
		
		
		/* ----------------------- Empty list message ----------------------- */
		
		
		/**
		 * Create and show message about empty list if possible and needed
		 * 
		 * @private
		 */
		_showEmptyListMessage: function () {
			if (this.emptyMessageVisible) {
				return;
			}
			
			var template = this.get('itemEmptyTemplate'),
				message = Supra.Intl.replace(this.get('messageNoResults')),
				node = null;
			
			if (template) {
				node = this.nodes.empty;
				
				if (!node) {
					node = this.nodes.empty = Y.Node.create(this._renderTemplate(template, {'message': message}));
				}
				
				this.getListNode().append(node);
				this.emptyMessageVisible = true;
			}
		},
		
		/**
		 * Hide message about empty list
		 * 
		 * @private
		 */
		_hideEmptyListMessage: function () {
			if (!this.emptyMessageVisible) {
				return;
			}
			
			var node = this.nodes.empty;
			
			if (node) {
				node.remove();
			}
			
			this.emptyMessageVisible = false;
		},
		
		
		/* --------------------------- Animations --------------------------- */
		
		
		/**
		 * Animate a node
		 * 
		 * @param {Object} node Node which to animate
		 * @param {String} animation Animation name
		 * @returns {Object} Deferred
		 */
		_animate: function (node, animation) {
			var deferred = new Supra.Deferred();
			
			if (animation in DataList.ANIMATIONS) {
				DataList.ANIMATIONS[animation](node, deferred);
			} else {
				deferred.reject();
			}
			
			return deferred.promise();
		},
		
		
		/* --------------------------- Attributes --------------------------- */
		
		
		/**
		 * Normalize data
		 * 
		 * @param {Object} data Data
		 * @returns {Array} Array of data
		 */
		_normalizeData: function (data) {
			if (!Y.Lang.isArray(data)) {
				data = [];
			} else {
				var i = 0,
					ii = data.length,
					out = [];
				
				for (; i<ii; i++) {
					if (data[i] || data[i] === '' || data[i] === 0) {
						if (typeof data[i] === 'object' && '_datalist_item' in data[i] && data[i].id) {
							out.push(data[i]);
						} else {
							out.push({
								'_datalist_item': true,
								'id': (this.guid++),
								'data': data[i],
								'node': null,
								'widgets': {},
								'events': {}
							});
						}
					}
				}
				
				data = out;
			}
			
			return data;
		},
		
		/**
		 * Data attribute setter
		 * 
		 * @param {Array} data Data
		 * @private
		 */
		_attrSetData: function (data) {
			// Remove all old items first
			this.removeAll();
			
			data = this.items = this._normalizeData(data);
			
			if (this.get('rendered')) {
				this.syncUI();
			}
			
			return data;
		},
		
		/**
		 * Data attribute getter
		 * 
		 * @returns {Array} data
		 * @private
		 */
		_attrGetData: function () {
			var data = this.items || [],
				out  = [],
				i    = 0,
				ii   = data.length;
			
			for (; i<ii; i++) {
				out.push(data[i].data);
			}
			
			return out;
		},
		
		/**
		 * Data source attribute change handler
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		_attrHandleDataSourceChange: function (e) {
			// Remove all old items first
			this.removeAll();
			
			if (this.get('rendered')) {
				this.reload();
			}
		},
		
		/**
		 * Style attribute change handler
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		_attrHandleStyleChange: function (e) {
			if (this.get('rendered')) {
				var box = this.get('boundingBox');
				
				if (e.prevVal) {
					box.removeClass(this.getClassName(e.prevVal));
				}
				if (e.newVal) {
					box.addClass(this.getClassName(e.newVal));
				}
			}
		}
		
	});
	
	DataList.ANIMATIONS = {
		
		/**
		 * Hide node
		 */
		'disappear': function (node, deferred) {
			node.transition({
				'duration': 0.3,
				'opacity': '0',
				'paddingTop': '0px',
				'paddingBottom': '0px',
				'height': '0px'
			}, function () {
				node.addClass('hidden');
			});
			
			Y.later(320, deferred, deferred.resolve);
		},
		
		'appear': function (node, deferred) {
			node.removeClass('hidden');
			deferred.resolve();
		}
		
	};
	
	Supra.DataList = DataList;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['widget', 'transition', 'supra.template', 'supra.scrollable']});
