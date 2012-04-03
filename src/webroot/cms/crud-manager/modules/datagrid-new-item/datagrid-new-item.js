//Invoke strict mode
"use strict";

YUI().add('website.datagrid-new-item', function (Y) {
	
	//Shortcuts
	var CRUD = Supra.CRUD;
	
	
	function DataGridNewItem(config) {
		DataGridNewItem.superclass.constructor.apply(this, arguments);
	}
	
	DataGridNewItem.NAME = 'DataGridNewItem';
	DataGridNewItem.CSS_PREFIX = 'su-datagrid-new-item';
	
	
	DataGridNewItem.TEMPLATE_ITEM = Supra.Template.compile('\
			<div class="item">\
				{% if icon %}<img src="{{ icon }}" alt="" />{% else %}<div class="img"></div>{% endif %}\
				<label>{{ title }}</label>\
			</div>\
		');
	
	DataGridNewItem.TEMPLATE = '\
			<div class="deco"><img src="/cms/crud-manager/images/datagrid-new-item-icon.png" alt="" /></div>\
			' + DataGridNewItem.TEMPLATE_ITEM({"title": Supra.Intl.get(["crud", "new_item"])}) + '\
		';
	
	DataGridNewItem.ATTRS = {};
	
	Y.extend(DataGridNewItem, Y.Widget, {
		
		/**
		 * New item Y.DD.Drag instance
		 * @type {Object}
		 * @private
		 */
		'new_item_drag': null,
		
		
		
		/**
		 * Render UI
		 * 
		 * @private
		 */
		'renderUI': function () {
			var contentBox = this.get('contentBox');
			
			//Render template
				contentBox.set('innerHTML', DataGridNewItem.TEMPLATE);
			
		},
		
		/**
		 * Bind UI events
		 * 
		 * @private
		 */
		'bindUI': function () {
			
			this.get('contentBox').one('div.item').on('click', function () {
				this.fire('insert:click');
			}, this);
			
			this._bindDnD();
		},
		
		/**
		 * Sync UI state with widget attribute states
		 * 
		 * @private
		 */
		'syncUI': function () {
			
		},
		
		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		/**
		 * Add drag and drop support
		 * 
		 * @private
		 */
		'_bindDnD': function (apps) {
			var drag = this.new_item_drag = new Y.DD.Drag({
				node: this.get('contentBox').one('div.item'),
				//Can be droped only on datagrid items, not in recycle bin
				groups: ['datagrid', 'new-item']
			});
			
			drag.plug(Y.Plugin.DDProxy, {
				moveOnEnd: false,
				cloneNode: true
			});
			
			drag.on('drag:start', this.decorateNewItemProxy, this);
			drag.on('drag:end',   this.undecorateNewItemProxy, this);
		},
		
		/**
		 * Add class to proxy element for custom style
		 * 
		 * @private
		 */
		'decorateNewItemProxy': function (e) {
			var drag_node = e.target.get('dragNode');
			
			//Set offset from mouse
			e.target.deltaXY = [-16, 16];
			
			drag_node.empty().append(Y.Node.create(Supra.Intl.get(['crud', 'new_item'])));
			drag_node.setStyles({'width': '300px', 'height': 'auto'});
			drag_node.addClass('su-datagrid-proxy');
		},
		
		/**
		 * Remove class from proxy to make sure we don't break
		 * proxy for other drag and drops
		 * 
		 * @private
		 */
		'undecorateNewItemProxy': function (e) {
			e.target.get('dragNode').removeClass('su-datagrid-proxy');
		}
		
		/**
		 * ------------------------------ API ------------------------------
		 */
		
		
	});
	
	
	Supra.DataGridNewItem = DataGridNewItem;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['widget', 'dd-drag']});