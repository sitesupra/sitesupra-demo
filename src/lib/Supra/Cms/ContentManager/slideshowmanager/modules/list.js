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
			<div class="status {% if inactive %}status-inactive{% endif %} {% if period_from or period_to %}status-scheduled{% endif %}"></div>\
			<div class="content click-target {% if !background %}blank{% endif %}" {% if background %}style="background-image: {{ background }};"{% endif %}>\
				{% if !background %}\
					<span class="center"></span><span class="title">{{ title }}</span>\
				{% endif %}\
			</div>\
			<label>{{ label }}</label>\
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
			
			this.plug(Supra.SlideshowManagerListOrder);
			
			// After slide order update labels
			this.after('order', this.redrawItemLabels, this);
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
			this._count = 0;
			this.order.update();
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
				layout = this.get('host').layouts.getLayoutById(data.layout) || this.get('host').layouts.getDefaultLayout(),
				image_bg = null,
				image_img = null,
				background = null,
				inactive = false;
			
			image_bg  = Supra.getObjectValue(data, ['background', 'image', 'image', 'sizes', 'original', 'external_path']);
			image_img = Supra.getObjectValue(data, ['media', 'image', 'sizes', 'original', 'external_path']);
			
			if (image_bg || image_img) {
				background = (image_img ? 'url(' + image_img + ')' : 'none') + ', ' + (image_bg ? 'url(' + image_bg + ')' : 'none');
			}
			
			if ('active' in data) {
				if (!data.active || data.active === '0' || data.active === 'false') {
					inactive = true;
				}
			}
			
			node = Y.Node.create(ITEM_TEMPLATE(
				Supra.mix({}, data, {
					'background': background,
					'title': layout.label || '',
					'label': Supra.Intl.get(['slideshowmanager', 'slide_label']).replace('{nr}', this._count + 1),
					'inactive': inactive,
					'period_from': data.period_from || null,
					'period_to': data.period_to || null
				})
			));
			
			list.setStyles({
				// +2 because we have "Add item" and newly created item
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
			
			this.order.update();
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
					this.order.update();
					
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
			var data = this.get('host').data.getSlideById(id),
				node = this._items[id],
				node_bg = node.one('.content'),
				node_status = node.one('.status'),
				
				image_bg = null,
				image_img = null,
				background = null;
			
			// Background
			image_bg  = Supra.getObjectValue(data, ['background', 'image', 'image', 'sizes', 'original', 'external_path']);
			image_img = Supra.getObjectValue(data, ['media', 'image', 'sizes', 'original', 'external_path']);
			
			if (image_bg || image_img) {
				background = (image_img ? 'url(' + image_img + ')' : 'none') + ', ' + (image_bg ? 'url(' + image_bg + ')' : 'none');
			}
			
			node_bg.setStyle('backgroundImage', background || 'none');
			
			// Status message
			if ('active' in data) {
				if (!data.active || data.active === '0' || data.active === 'false') {
					node_status.addClass('status-inactive');
				} else {
					node_status.removeClass('status-inactive');
				}
			}
			
			if (data.period_from || data.period_to) {
				node_status.addClass('status-scheduled');
			} else {
				node_status.removeClass('status-scheduled');
			}
		},
		
		/**
		 * Redraw all item labels
		 */
		redrawItemLabels: function () {
			var data  = this.get('host').data.get('data'),
				nodes = this._items,
				node  = null,
				i     = 0,
				ii    = data.length,
				label = '';
			
			for (; i<ii; i++) {
				node = nodes[data[i].id];
				if (node) {
					node = node.one('label');
					label = Supra.Intl.get(['slideshowmanager', 'slide_label']).replace('{nr}', i + 1);
					node.set('text', label);
				}
			}
		},
		
		/**
		 * Returns item count
		 * 
		 * @returns {Number} Item count
		 */
		getItemCount: function () {
			return this._count;
		},
		
		/**
		 * Update scroll position
		 */
		syncScroll: function () {
			this._scrollable.syncUI();
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