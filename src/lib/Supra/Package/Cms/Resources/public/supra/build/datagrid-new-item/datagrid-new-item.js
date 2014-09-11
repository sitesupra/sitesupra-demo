YUI().add('supra.datagrid-new-item', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Data grid new item
	 */
	function DataGridNewItem(config) {
		DataGridNewItem.superclass.constructor.apply(this, arguments);
	}
	
	DataGridNewItem.NAME = 'DataGridNewItem';
	DataGridNewItem.CSS_PREFIX = 'su-datagrid-new-item';
	
	DataGridNewItem.TEMPLATE = Supra.Template.compile('\
			<div class="item">\
				{% if icon %}<img src="{{ icon }}" alt="" />{% else %}<div class="img"></div>{% endif %}\
				<label>{{ title }}</label>\
			</div>\
		');
	
	DataGridNewItem.ATTRS = {
		
		'newItemLabel': {
			'value': 'New'
		},
		
		'draggable': { 
			'value': true
		}
		
	};
	
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
				var html = DataGridNewItem.TEMPLATE({'title': this.get('newItemLabel')});
				contentBox.set('innerHTML', html);
			
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
			if (this.get('draggable')) {
				
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
				
			} else {
				this.get('contentBox').addClass('not-draggable');
			}
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
			
			drag_node.empty().append(Y.Node.create(this.get('newItemLabel')));
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