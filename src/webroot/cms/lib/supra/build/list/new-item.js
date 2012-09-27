YUI().add('supra.list-new-item', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	
	function ListNewItem(config) {
		ListNewItem.superclass.constructor.apply(this, arguments);
	}
	
	ListNewItem.NAME = 'ListNewItem';
	ListNewItem.CSS_PREFIX = 'su-list-new-item';
	
	ListNewItem.TEMPLATE = '\
			<div class="deco"><img src="/cms/lib/supra/build/list/assets/skins/{{ skin }}/list-new-item-icon.png" alt="" /></div>\
			<div class="item">\
				<div class="img"></div>\
				<label>{{ title }}</label>\
			</div>\
		';
	
	ListNewItem.ATTRS = {
		'title': {
			'value': '',
			'setter': '_setTitle'
		},
		'proxyClassName': {
			'value': ''
		},
		
		'dndGroups': {
			'value': ['new-item']
		},
		
		'nodeTitle': {
			'value': null
		},
		'nodeItem': {
			'value': null
		}
	};
	
	ListNewItem.HTML_PARSER = {
		"nodeTitle": function (srcNode) {
			return srcNode.one("label");
		},
		"nodeItem": function (srcNode) {
			return srcNode.one('.item') || srcNode;
		}
	};
	
	Y.extend(ListNewItem, Y.Widget, {
		
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
			var title = Supra.Intl.replace(this.get('title'));
			
			//Create nodes if they doesn't exist
			if (!this.get('nodeTitle')) {
				//Render template
				var template = Supra.Template.compile(ListNewItem.TEMPLATE, 'ListNewItem'),
					contentBox = this.get('contentBox');
				
				contentBox.set('innerHTML', template({
					'skin': Y.config.skin.defaultSkin,
					'title': title
				}));
				
				this.set('nodeItem', contentBox.one('.item'));
				this.set('nodeTitle', contentBox.one('label'));
			} else {
				this.get('nodeTitle').set('text', title);
			}
			
		},
		
		/**
		 * Bind UI events
		 * 
		 * @private
		 */
		'bindUI': function () {
			this.get('nodeItem').on('click', function () {
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
			this._setTitle(this.get('title'));
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
				node: this.get('nodeItem'),
				//Can be droped only on datagrid items, not in recycle bin
				groups: this.get('dndGroups')
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
			var drag_node = e.target.get('dragNode'),
				classname = this.get('proxyClassName');
			
			//Set offset from mouse
			e.target.deltaXY = [-16, 16];
			
			if (classname) {
				drag_node.addClass(classname);
			}
			
			this.fire('proxy:decorate', {
				'target': e.target,
				'node': drag_node
			});
		},
		
		/**
		 * Remove class from proxy to make sure we don't break
		 * proxy for other drag and drops
		 * 
		 * @private
		 */
		'undecorateNewItemProxy': function (e) {
			var classname = this.get('proxyClassName');
			
			if (classname) {
				e.target.get('dragNode').removeClass(classname);
			}
		},
		
		/**
		 * Title attribute setter
		 * 
		 * @param {String} title Title attribute value
		 * @return New title attribute value
		 * @type {String}
		 * @private
		 */
		'_setTitle': function (title) {
			title = String(title || '');
			
			var title_node = this.get('titleNode');
			if (title_node) {
				title_node.set('text', Supra.Intl.replace(title));
			}
			
			return title;
		},
		
		/**
		 * ------------------------------ API ------------------------------
		 */
		
		/**
		 * Returns Y.DD.Drag instance for this item
		 * 
		 * @return Y.DD.Drag instance
		 * @type {Object}
		 */
		'getDrag': function () {
			return this.new_item_drag;
		}
		
		
	});
	
	
	Supra.ListNewItem = ListNewItem;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['widget', 'dd-drag']});