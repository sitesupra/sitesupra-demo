/**
 * Continuous loader plugin
 */
YUI.add('supra.datagrid-sortable', function (Y) {
	//Invoke strict mode
	"use strict";
	
	function SortablePlugin (config) {
		SortablePlugin.superclass.constructor.apply(this, arguments);
	}

	// When plugged into a DataGrid instance, the plugin will be 
	// available on the "loader" property.
	SortablePlugin.NS = 'sortable';
	
	// Attributes
	SortablePlugin.ATTRS = {
		'columns': {
			'value': null
		},
		
		'column': {
			'value': null,
			'setter': '_setColumn'
		},
		'order': {
			'value': 'asc',
			'setter': '_setOrder'
		},
		
		'disabled': {
			'value': false,
			'setter': '_setDisabled'
		},
		
		'requestParamColumn': {
			'value': 'sort_by'
		},
		'requestParamOrder': {
			'value': 'sort_order'
		}
	};
	
	// Extend Plugin.Base
	Y.extend(SortablePlugin, Y.Plugin.Base, {
		
		
		/**
		 * Column by which data is sorted
		 * @type {String}
		 * @private
		 */
		'sortingColumn': null,
		
		/**
		 * Order 'asc' or 'desc' in which data is sorted
		 * @type {String}
		 * @private
		 */
		'sortingOrder': null,
		
		
		/**
		 * Load records, which should be in view now
		 * 
		 * @private
		 */
		'setSorting': function (id, order) {
			if (this.sortingColumn != id || this.sortingOrder != order) {
				var host = this.get('host'),
					old_node = null,
					new_node = null;
				
				if (host.get('rendered')) {
					old_node = host.get('tableHeadingNode').one('.col-' + this.sortingColumn),
					new_node = host.get('tableHeadingNode').one('.col-' + id);
					
					if (old_node) old_node.removeClass('sort-' + this.sortingOrder);
					if (new_node) new_node.addClass('sort-' + order);
				}
				
				this.sortingColumn = id;
				this.sortingOrder = order;
				
				if (!this.get('disabled')) {
					host.requestParams.set(this.get('requestParamColumn'), id);
					host.requestParams.set(this.get('requestParamOrder'), order);
					
					if (host.get('rendered')) {
						host.reset();
					}
				}
			}
		},
		
		/**
		 * Disabled attribute setter
		 * 
		 * @param {Boolean} disabled Attribute value
		 * @returns {Boolean} Attribute value
		 * @private
		 */
		'_setDisabled': function (disabled) {
			var host = this.get('host'),
				node = host.get('boundingBox');
			
			if (node) {
				node.toggleClass(host.getClassName('sortable'), !disabled);
			}
			
			if (disabled) {
				host.requestParams.remove(this.get('requestParamColumn'));
				host.requestParams.remove(this.get('requestParamOrder'));
			} else {
				host.requestParams.set(this.get('requestParamColumn'), this.sortingColumn);
				host.requestParams.set(this.get('requestParamOrder'), this.sortingOrder);
			}
			
			return !!disabled;
		},
		
		/**
		 * On heading click change sorting
		 * 
		 * @private
		 */
		'_handleHeadingClick': function (event, id) {
			if (this.sortingColumn == id) {
				this.setSorting(id, this.sortingOrder == 'asc' ? 'desc' : 'asc');
			} else {
				this.setSorting(id, 'asc');
			}
		},
		
		/**
		 * Sorting column attribute setter
		 * 
		 * @param {String} column Column ID
		 * @returns {String} New attribute value
		 * @private
		 */
		'_setColumn': function (column) {
			this.setSorting(column, this.get('order'));
			return column;
		},
		
		/**
		 * Sorting order attribute setter
		 * 
		 * @param {String} order Order 'asc' or 'desc'
		 * @returns {String} New attribute value
		 * @private
		 */
		'_setOrder': function (order) {
			this.setSorting(this.get('column'), order);
			return order;
		},
		
		'_afterRender': function () {
			var host = this.get('host'),
				columns = this.get('columns') || host.get('columns'),
				id = null,
				i = 0,
				ii = columns.length,
				
				heading = host.get('tableHeadingNode'),
				node = null,
				
				column = this.sortingColumn,
				order  = this.sortingOrder;
			
			if (!heading) return;
			
			// Add classname to all column headings which are sortable
			for (; i<ii; i++) {
				id = columns[i] && typeof columns[i] === 'object' ? columns[i].id : columns[i];
				node = heading.one('.col-' + id);
				
				if (node) {
					node.addClass('sort');
					node.on('click', this._handleHeadingClick, this, id);
					
					if (id == column) {
						node.addClass('sort-' + order);
					}
				}
			}
		},
		
		/**
		 * Constructor
		 */
		'initializer': function () {
			// Set column, etc
			var host = this.get('host'),
				column = this.get('column'),
				order  = this.get('order') || 'asc',
				
				columns = null;
			
			if (!column) {
				columns = this.get('columns') || host.get('columns');
				column = (columns[0] && typeof columns[0] === 'object' ? columns[0].id : columns[0]) || '';
			}
			
			host.requestParams.set(this.get('requestParamColumn'), column);
			host.requestParams.set(this.get('requestParamOrder'), order);
			
			this.sortingColumn = column;
			this.sortingOrder = order;
			
			// Wait till is rendered
			if (host.get('rendered')) {
				this._afterRender();
			} else {
				host.after('render', this._afterRender, this);
			}
		},
		
		/**
		 * Destructor
		 */
		'destructor': function () {
			var host = this.get('host'),
				columns = this.get('columns') || host.get('columns'),
				id = null,
				i = 0,
				ii = columns.length,
				heading = host.get('tableHeadingNode');
			
			// Add classname to all column headings which are sortable
			for (; i<ii; i++) {
				id = columns[i] && typeof columns[i] === 'object' ? columns[i].id : columns[i];
				node = heading.one('.col-' + id);
				
				if (node) {
					node.detach('click');
				}
			}
		}
		
	});
	
	Supra.DataGrid.SortablePlugin = SortablePlugin;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'supra.datagrid']});