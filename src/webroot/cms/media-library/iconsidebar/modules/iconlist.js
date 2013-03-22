//Invoke strict mode
"use strict";
	
YUI.add("iconsidebar.iconlist", function (Y) {
	
	function List(config) {
		List.superclass.constructor.apply(this, arguments);
	}
	
	List.NAME = 'icon-list';
	List.CSS_PREFIX = 'su-icon-list';
	
	List.TEMPLATE_LIST = Supra.Template.compile('\
			<ul></ul>\
		');
	
	List.TEMPLATE_ITEM = Supra.Template.compile('\
			<li data-id="{{ id }}" class="item {% if type == "page" %}item-page{% endif %}">\
				<img src="{{ icon_path }}" alt="" />\
				<label>{{ title }}</label>\
			</LI>\
		');
	
	List.ATTRS = {
		/**
		 * Data object
		 */
		'data': {
			'value': null
		},
		
		/**
		 * Active icon ID
		 */
		'active': {
			'value': null,
			'setter': '_uiSetActive'
		},
		
		/**
		 * Category by which list is filtered
		 */
		'categoryFilter': {
			'value': ''
		},
		
		/**
		 * Keyword by which list is filtered
		 */
		'keywordFilter': {
			'value': ''
		},
		
		/**
		 * Number of items per row
		 */
		'itemsPerRow': {
			'value': 4
		},
		
		/**
		 * Single item height
		 */
		'itemHeight': {
			'value': 90
		}
	};
	
	Y.extend(List, Y.Widget, {
		
		/**
		 * List of all items which should be rendered
		 * @type {Array}
		 * @private
		 */
		items: null,
		
		/**
		 * Number of items already rendered
		 * @type {Number}
		 * @private
		 */
		itemsRendered: 0,
		
		/**
		 * Number of items left to render
		 * @type {Number}
		 * @private
		 */
		itemsLeft: 0,
		
		/**
		 * Content scrollable object
		 * @type {Object}
		 * @private
		 */
		scrollable: null,
		
		/**
		 * List node
		 * @type {Object}
		 * @private
		 */
		listNode: null,
		
		/**
		 * Silent update
		 * @type {Boolean}
		 */
		silent: false,
		
		
		/**
		 * Render UI, etc.
		 */
		renderUI: function () {
			// Render list
			this.renderList();
			
			// Render items
			if (this.get('visible')) {
				this.reset();
			}
		},
		
		bindUI: function () {
			// Attach listeners
			this.after('categoryFilterChange', this._resetDelayed, this);
			this.after('keywordFilterChange', this._resetDelayed, this);
			
			this.get('contentBox').delegate('click', this._uiItemClick, 'li', this);
			
			this.after('visibleChange', this._uiVisibleChange, this);
		},
		
		
		/**
		 * ------------------------------ Events ------------------------------
		 */
		
		
		/**
		 * Active attribute setter
		 * 
		 * @param {String} id Active icon ID
		 * @private
		 */
		_uiSetActive: function (id) {
			var list = this.listNode,
				node = null,
				prev = this.get('active');
			
			if (list && prev != id) {
				if (prev) {
					node = list.one('li[data-id="' + prev + '"]');
					if (node) node.removeClass('active');
				}
				if (id) {
					node = list.one('li[data-id="' + id + '"]');
					if (node) node.addClass('active');
				}
			}
			
			return id;
		},
		
		/**
		 * On item click fire event
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		_uiItemClick: function (event) {
			var target = event.target.closest('li'),
				id     = target.getAttribute('data-id'),
				icon   = this.get('data').getIcon(id);
			
			if (icon) {
				this.fire('select', {'icon': icon});
			}
		},
		
		/**
		 * After visible change remove item elements
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		_uiVisibleChange: function (event) {
			if (event.newVal != event.prevVal && !event.newVal) {
				// Purge cache
				this.items = null;
				this.itemsRendered = 0;
				this.itemsLeft = 0;
				
				// Remove old items
				if (this.listNode) {
					this.listNode.empty();
				}
			}
		},
		
		
		/**
		 * ------------------------------ Render ------------------------------
		 */
		
		
		/**
		 * Render list node
		 */
		renderList: function () {
			if (this.listNode) return;
			
			var scrollable = this.scrollable = new Supra.Scrollable();
			scrollable.render(this.get('contentBox'));
			
			var fn = Supra.throttle(function () {
				if (!this.silent) {
					this.silent = true;
					this.renderItems();
					this.silent = false;
				}
			}, 60, this);
			
			scrollable.after('sync', fn);
			scrollable.after('drag', fn);
			
			var content_node = scrollable.get('contentBox'),
				node = this.listNode = Y.Node.create(List.TEMPLATE_LIST());
			
			content_node.append(node);
		},
		
		/**
		 * Render items
		 */
		renderItems: function () {
			if (!this.get('visible')) return false;
			var items = this.getItems(),
				range = this.getItemRenderRange(),
				i     = 0,
				ii    = 0,
				count = items.length,
				
				node  = null,
				list  = this.listNode,
				
				rendered = range[1],
				left = count - range[1],
				
				active = this.get('active');
			
			if (this.itemsRendered != rendered && this.itemsLeft != left) {
				items = items.slice(range[0], range[1]);
				
				if (items.length) {
					for (ii=items.length; i<ii; i++) {
						node = Y.Node.create(List.TEMPLATE_ITEM(items[i]));
						
						if (items[i].id == active) {
							node.addClass('active');
						}
						
						list.append(node);
					}
				}
				
				this.itemsRendered = rendered;
				this.itemsLeft = left;
				
				if (!this.silent) {
					list.setStyle('height', Math.ceil(count / this.get('itemsPerRow')) * this.get('itemHeight') + 'px');
					this.scrollable.syncUI();
				}
			}
		},
		
		/**
		 * Returns range of which items needs to be rendered
		 * 
		 * @returns {Number} Items per view
		 */
		getItemRenderRange: function () {
			var view_scroll   = this.scrollable.getScrollPosition(),
				view_height   = this.scrollable.getViewSize(),
				item_height   = this.get('itemHeight'),
				
				rows          = Math.ceil((view_scroll + view_height) / item_height),
				count         = rows * this.get('itemsPerRow'),
				
				rendered      = this.itemsRendered,
				left          = this.itemsLeft;
			
			return [
				rendered,
				Math.max(rendered, Math.min(rendered + left, count))
			];
		},
		
		
		/**
		 * ------------------------------ Data ------------------------------
		 */
		
		
		/**
		 * Returns items filtered by category and keywords
		 * 
		 * @param {Boolean} reset Purge cache
		 * @returns {Array} Items
		 */
		getItems: function (reset) {
			if (this.items && reset !== true) return this.items;
			
			var items    = [],
				data     = this.get('data'),
				keyword  = null,
				category = null;
			
			if (data) {
				keyword = this.get('keywordFilter') || '';
				category = this.get('categoryFilter') || '';
				
				if (keyword) {
					items = data.getFilteredByKeyword(keyword, category);
				} else {
					items = data.getFilteredByCategory(category);
				}
			}
			
			this.itemsRendered = 0;
			this.itemsLeft = items.length;
			
			return this.items = items;
		},
		
		/**
		 * Reset all list
		 */
		reset: function () {
			if (this.get('visible')) {
				// Purge cache
				this.items = null;
				this.itemsRendered = 0;
				this.itemsLeft = 0;
				
				// Remove old items
				if (this.listNode) {
					this.listNode.empty();
				}
				
				// Update view
				this.silent = true;
				this.scrollable.syncUI();
				//this.scrollable.animateTo(0);
				this.silent = false;
				
				// Render
				this.renderItems();
			}
		},
		
		/**
		 * Reset list with small delay
		 * Needed to prevent double reset when changing keyword
		 * and category at the same time
		 * 
		 * @private
		 */
		_resetDelayed: function () {
			if (this._resetTimer) {
				this._resetTimer.cancel();
			}
			this._resetTimer = Y.later(1, this, this.reset);
		}
		
		
	});
	
	Supra.IconSidebarIconList = List;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['widget', 'supra.scrollable']});

		