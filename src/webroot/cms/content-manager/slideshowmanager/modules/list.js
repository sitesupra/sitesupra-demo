YUI.add('slideshowmanager.list', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.PageContent;
	
	//Classnames
	var CLASSNAME_ACTIVE = 'active';
	
	//Templates
	var LIST_TEMPLATE =
		'<ul class="clearfix"></ul>';
	
	var ITEM_TEMPLATE = Supra.Template.compile(
		'<li>\
			<div class="background"></div>\
			<div class="marker"></div>\
			<div class="content click-target">\
				{% if icon %}\
					<img src="{{ icon }}" alt="" />\
				{% else %}\
					<span class="center"></span><span class="title">{{ title }}</span>\
				{% endif %}\
			</div>\
		 </li>');
	
	var NEW_ITEM_TEMPLATE = Supra.Template.compile(
		'<li class="new-item {% if !visible %} hidden{% endif %}">\
			<div class="background"></div>\
			<div class="content"></div>\
		 </li>');
	
	/**
	 * Slide list
	 */
	function SlideList (config) {
		SlideList.superclass.constructor.apply(this, arguments);
	}
	
	SlideList.NAME = 'slideshowmanager-list';
	SlideList.NS = 'list';
	
	SlideList.ATTRS = {
		
		// Container node
		'containerNode': {
			value: null
		},
		
		// List container node
		'listNode': {
			value: null
		},
		
		// Show insert button
		'showInsertControl': {
			value: true,
			setter: '_setShowInsertControl'
		},
		
		// Insert button
		'newItemControl': {
			value: null
		},
		
		// Active item id
		'activeItemId': {
			value: null,
			setter: '_setActiveItemId'
		}
	};
	
	Y.extend(SlideList, Y.Plugin.Base, {
		
		/**
		 * List of item nodes
		 * @type {Object}
		 * @private
		 */
		_items: null,
		
		/**
		 * Item count
		 * @type {Number}
		 * @private
		 */
		_count: 0,
		
		/**
		 * Scrollable widget
		 * @type {Obejct}
		 * @private
		 */
		_scrollable: null,
		
		
		/**
		 * Automatically called by Base, during construction
		 * 
		 * @param {Object} config
		 * @private
		 */
		initializer: function(config) {
			var container = this.get('containerNode'),
				list_node = null,
				new_item = null,
				button = null,
				scrollable = null;
			
			// Scrollable
			scrollable = new Supra.Scrollable({
				'axis': 'x'
			});
			scrollable.render(container);
			
			// List
			list_node = Y.Node.create(LIST_TEMPLATE);
			scrollable.get('contentBox').append(list_node);
			list_node.delegate('click', this.fireItemClickEvent, '.click-target', this);
			
			// New item
			new_item = Y.Node.create(NEW_ITEM_TEMPLATE({
				'visible': this.get('showInsertControl')
			}));
			list_node.append(new_item);
			
			this.set('listNode', list_node);
			this.set('newItemControl', new_item);
			
			// Handle new item click
			new_item.one('.content').on('click', this.fireNewItemEvent, this);
			
			this._items = {};
			this._scrollable = scrollable;
		},
		
		/**
		 * Automatically called by Base, during destruction
		 */
		destructor: function () {
			this.resetAll();
			this.get('listNode').remove(true);
		},
		
		/**
		 * Reset cache, clean up
		 */
		resetAll: function () {
			var items = this._items,
				id = null;
			
			for (id in items) {
				items[id].remove(true);
			}
			
			this._items = {};
		},
		
		/**
		 * Fire new item event
		 * 
		 * @private
		 */
		fireNewItemEvent: function () {
			this.fire('addClick');
		},
		
		/**
		 * Fire item click event
		 * 
		 * @private
		 */
		fireItemClickEvent: function (event) {
			var target = event.target.closest('li'),
				id = target.getData('itemId');
			
			if (id) {
				this.fire('itemClick', {'data': {'id': id}});
			}
		},
		
		/* ---------------------------- Items --------------------------- */
		
		
		/**
		 * Add new item
		 * 
		 * @param {Object} data Item data
		 */
		addItem: function (data) {
			var id = data.id,
				list = this.get('listNode'),
				new_item = this.get('newItemControl'),
				node = null,
				layout = this.get('host').layouts.getLayoutById(data.layout);
			
			node = Y.Node.create(ITEM_TEMPLATE(
				Supra.mix({}, data, {
					'icon': layout.icon,
					'title': layout.label
				})
			));
			
			list.setStyles({
				// +2 because we have "+" and newly created item
				'width': (this._count + 2) * 110 + 'px'
			});
			list.append(node);
			list.append(new_item); // new item always last
			
			node.setStyles({
				'opacity': 0
			});
			new_item.setStyles({
				'left': '-110px'
			});
			
			node.transition({
				'opacity': 1,
				'duration': 0.35
			});
			new_item.transition({
				'left': '0px',
				'duration': 0.35
			});
			
			// Data is used to identify on click event
			node.setData('itemId', id);
			
			this._count++;
			this._items[id] = node;
			this._scrollable.syncUI();
		},
		
		/**
		 * Remove item
		 * 
		 * @param {String} id Item id
		 */
		removeItem: function (id) {
			var list = this.get('listNode'),
				node = this._items[id],
				next = null;
			
			if (node) {
				this._count--;
				delete(this._items[id]);
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
					
					// Adjust list width to allow scrollbar to detect
					// correct content width
					list.setStyles({
						// +1 because we have "+" item
						'width': (this._count + 1) * 110 + 'px'
					});
					
					// Scrollbar
					this._scrollable.syncUI();
					
				}, this));
			}
		},
		
		/**
		 * Redraw item
		 * 
		 * @param {String} id Item id
		 * @param {Object} data Item data
		 */
		redrawItem: function (id) {
			
		},
		
		/**
		 * Returns item count
		 * 
		 * @returns {Number} Item count
		 */
		getItemCount: function () {
			return this._count;
		},
		
		
		/* ---------------------------- ATTRIBUTES --------------------------- */
		
		
		/**
		 * showInsertControl attribute setter
		 * 
		 * @param {Boolean} value Attribute value
		 * @returns {Boolean} New attribute value
		 * @private
		 */
		_setShowInsertControl: function (value) {
			var control = this.get('newItemControl');
			if (control) {
				// We are making assumtion that 'hidden' class is defined
				if (value) {
					control.addClass('hidden');
				} else {
					control.removeClass('hidden');
				}
			}
			
			return !!value;
		},
		
		/**
		 * Active item id attribute change
		 * 
		 * @param {String} id Active item id
		 */
		_setActiveItemId: function (id) {
			var old_id = this.get('activeItemId'),
				items = this._items;
			
			if (old_id && items[old_id]) {
				items[old_id].removeClass(CLASSNAME_ACTIVE);
			}
			if (id && items[id]) {
				items[id].addClass(CLASSNAME_ACTIVE);
			}
			
			return id;
		}
		
	});
	
	Supra.SlideshowManagerList = SlideList;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'supra.template']});