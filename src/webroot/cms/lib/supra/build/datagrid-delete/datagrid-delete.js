YUI().add('supra.datagrid-delete', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * Recycle bin with drag and drop support
	 */
	function DataGridDelete(config) {
		DataGridDelete.superclass.constructor.apply(this, arguments);
	}
	
	DataGridDelete.NAME = 'DataGridDelete';
	DataGridDelete.CSS_PREFIX = 'su-datagrid-delete';
	DataGridDelete.ATTRS = {
		'dataGrid': {
			value: null
		}
	};
	
	Y.extend(DataGridDelete, Y.Widget, {
		
		/**
		 * Drop target
		 * @type {Object}
		 * @private
		 */
		'_dnd': null,
		
		
		/**
		 * Render UI
		 * 
		 * @private
		 */
		'renderUI': function () {
			this.get('boundingBox').addClass('block-inset');
		},
		
		/**
		 * Bind UI events
		 * 
		 * @private
		 */
		'bindUI': function () {
			var dnd = this._dnd = new Y.DD.Drop({
				'node': this.get('boundingBox'),
				'groups': ['recycle-bin']
			});
			
			dnd.on('drop:hit', this._nodeDrop, this);
		},
		
		/**
		 * Sync UI state with widget attribute states
		 * 
		 * @private
		 */
		'syncUI': function () {},
		
		/**
		 * Clean up
		 */
		'destructor': function () {
			if (this._dnd) {
				this._dnd.destroy();
				this._dnd = null;
			}
		},
		
		
		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		
		/**
		 * On drop delete page
		 * 
		 * @private
		 */
		'_nodeDrop': function (e) {
			var node = e.drag.get('node'),
				data_grid = this.get('dataGrid'),
				row = data_grid.getRowByNode(node);
			
			this.fire('delete', {
				'id': row.getID(),
				'row': row
			});
			
			e.halt();
		}
		
		
	});
	
	
	Supra.DataGridDelete = DataGridDelete;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['widget', 'dd-drop']});
