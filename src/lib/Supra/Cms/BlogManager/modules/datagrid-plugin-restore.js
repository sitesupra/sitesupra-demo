/**
 * Continuous loader plugin
 */
YUI.add('blog.datagrid-restore', function (Y) {
	//Invoke strict mode
	"use strict";
	
	function RestorePlugin (config) {
		RestorePlugin.superclass.constructor.apply(this, arguments);
	}

	// When plugged into a DataGrid instance, the plugin will be 
	// available on the "loader" property.
	RestorePlugin.NS = 'restore';
	
	// Attributes
	RestorePlugin.ATTRS = {};
	
	// Extend Plugin.Base
	Y.extend(RestorePlugin, Y.Plugin.Base, {
		
		/**
		 * Temporary new item node
		 */
		temp_node: null,
		
		/**
		 * Constructor
		 * 
		 * @private
		 */
		initializer: function () {
			var host = this.get('host');
			host.after('render', this.bindUI, this);
		},
		
		/**
		 * On drag over main element add item to the top
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		_dragOverMain: function (e) {
			var drag = this.temp_node,
				body = null;
			
			//Create element
			if (!drag) {
				this.temp_node = drag = Y.Node.create('<tr class="yui3-dd-dragging"><td colspan="' + this.get('host').getColumns().length + '"><span>' + Supra.Intl.get(['blog', 'posts', 'restore']) + '</span></td></tr>');
				body = this.get('host').tableBodyNode;
				body.prepend(drag);
			}
		},
		
		/**
		 * On drag exit remove "New item" element from data grid
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		_dragExitMain: function (e) {
			if (this.temp_node) {
				this.temp_node.remove(true);
				this.temp_node = null;
			}
		},
		
		/**
		 * Handle new item drop
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		_dragDrop: function (e) {
			var data = e.drag.get('data');
			if (!data || !this.temp_node) return;
			
			this.get('host').fire('datagrid:restore', {
				'data': data,
				'node': null
			});
			
			//Remove element from DOM
			this.temp_node.remove(true);
			this.temp_node = null;
		},
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			var host = this.get('host'),
				container = host.tableBodyNode,
				del = null;
			
			this.drop = new Y.DD.Drop({
				'node': container.closest('.su-datagrid-content'),
				'groups': ['restore-page']
			});
			
			this.drop.on('drop:hit', this._dragDrop, this);
			this.drop.on('drop:exit', this._dragExitMain, this);
			this.drop.on('drop:over', this._dragOverMain, this);
		},
		
		/**
		 * Destructor
		 * 
		 * @private
		 */
		destructor: function () {
			if (this.drop) {
				this.drop.destroy();
			}
			if (this.temp_node) {
				this.temp_node.remove(true);
				this.temp_node = null;
			}
		}
		
	});
	
	Supra.DataGrid.RestorePlugin = RestorePlugin;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'dd-drop', 'supra.datagrid']});