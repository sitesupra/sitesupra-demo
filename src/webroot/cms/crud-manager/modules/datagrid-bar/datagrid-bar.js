//Invoke strict mode
"use strict";

/**
 * Template selection input
 */
YUI.add("website.datagrid-bar", function (Y) {
	
	function DataGridBar (config) {
		DataGridBar.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	DataGridBar.NAME = 'datagrid-bar';
	
	DataGridBar.ATTRS = {
		'new-item': {
			value: true
		},
		'recycle-bin': {
			value: true
		}
	};
	
	Y.extend(DataGridBar, Y.Widget, {
		/**
		 * Y.Drag instance for new item
		 * @type {Y.DD.Drag}
		 * @private
		 */
		new_item_drag: null,
		
		/**
		 * Recycle bin drop instance
		 * @type {Y.DD.Drop}
		 * @private
		 */
		recycle_bin_drop: null,
		
		
		
		/**
		 * Delete item which was dropped in recycle bin
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		recycleItem: function (e) {
			var node = e.drag.get('node'),
				provider = Supra.CRUD.Providers.getActiveProvider(),
				data_grid = provider.getDataGrid(),
				row = data_grid.getRowByNode(node);
			
			provider.deleteRecord(row.getID());
		},
		
		
		/**
		 * Add class to proxy element for custom style
		 * 
		 * @private
		 */
		decorateNewItemProxy: function (e) {
			var drag_node = e.target.get('dragNode');
			
			//Set offset from mouse
			e.target.deltaXY = [-16, 16];
			
			drag_node.empty().append(Y.Node.create(Supra.Intl.get(['crud', 'new_item'])));
			drag_node.setStyles({'width': '300px'});
			drag_node.addClass('yui3-datagrid-proxy');
		},
		
		/**
		 * Remove class from proxy to make sure we don't break
		 * proxy for other drag and drops
		 * 
		 * @private
		 */
		undecorateNewItemProxy: function (e) {
			e.target.get('dragNode').removeClass('yui3-datagrid-proxy');
		},
		
		/**
		 * On new item click trigger event
		 * 
		 * @private
		 */
		newItemClick: function () {
			this.fire('insert:click');
		},
		
		/**
		 * Add needed elements
		 * 
		 * @private
		 */
		renderUI: function () {
			DataGridBar.superclass.renderUI.apply(this, arguments);
			var node = null;
			
			if (this.get('new-item')) {
				node = Y.Node.create('<span class="new-item"><img src="/cms/lib/supra/img/crud/new-item-icon.png" alt="" /></span>')
				
				this.get('contentBox').append(node);
				node.on('click', this.newItemClick, this);
				
				this.new_item_drag = new Y.DD.Drag({
					node: node,
					//Can be droped only on datagrid items, not in recycle bin
					groups: ['datagrid', 'new-item']
				});
				this.new_item_drag.plug(Y.Plugin.DDProxy, {
					moveOnEnd: false,
					cloneNode: true
				});
				
				this.new_item_drag.on('drag:start', this.decorateNewItemProxy, this);
				this.new_item_drag.on('drag:end', this.undecorateNewItemProxy, this);
				
				/*
				this.new_item_drag.on('drag:end', this.dropNewItem, this);
				*/
			}
			
			if (this.get('recycle-bin')) {
				node = Y.Node.create('<span class="recycle-bin"><img src="/cms/lib/supra/img/crud/recycle-bin-icon.png" alt="" /></span>')
				this.get('contentBox').append(node);
				
				this.recycle_bin_drop = new Y.DD.Drop({
					node: node,
					//Only items for recycle bin can be dropped
					groups: ['recycle-bin']
				});
				
				this.recycle_bin_drop.on('drop:hit', this.recycleItem, this);
			}
		},
		
		/**
		 * Attach event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			DataGridBar.superclass.bindUI.apply(this, arguments);
			
			var drag = this.new_item_drag;
			
		}
	});
	
	Supra.DataGridBar = DataGridBar;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['widget', 'dd-drag', 'dd-drop']});