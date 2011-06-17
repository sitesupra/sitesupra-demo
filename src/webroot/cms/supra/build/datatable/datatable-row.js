YUI.add('supra.datatable-row', function (Y) {
	
	function DataTableRow (host, data, index) {
		this.index = index;
		this.host = host;
		this.data = data || {};
		this.id_components = host.get('idColumn');
		this.nodes = {};
	}
	
	var isfunc = Y.Lang.isFunction;
	var escape = Y.Lang.escapeHTML;
	
	DataTableRow.prototype = {
		/* DataTable instance */
		host: null,
		
		/* TR element (Y.Node) */
		node: null,
		
		/* TD elements */
		nodes: {},
		
		/* Row ID */
		id: null,
		
		/* Row data */
		data: null,
		
		/* Row index */
		index: 0,
		
		/* Columns which make ID */
		id_components: null,
		
		/* Reference for beginChange / endChange */
		reference_point: null,
		
		/**
		 * Returns column value
		 * 
		 * @param {String} key
		 * @return Column value
		 * @type {Object}
		 */
		get: function (key) {
			var data = this.data;
			return (key in data ? data[key] : (key == 'id' ? this.getID() : null));
		},
		
		/**
		 * Set column value
		 * 
		 * @param {String} key
		 * @param {Object} value
		 */
		set: function (key, value) {
			if (key in this.data && this.data[key] == value) return this;
			
			this.data[key] = value;
			this.update(key, value);
		},
		
		/**
		 * Returns all row data as object
		 * 
		 * @return Row data
		 * @type {Object}
		 */
		getData: function () {
			return this.data;
		},
		
		/**
		 * Returns possibly unique row ID
		 * 
		 * @return Row ID
		 * @type {Object}
		 */
		getID: function () {
			if (this.id !== null) return this.id;
			var id_components = this.id_components;
			var data = this.data;
			var id = [];
			
			for(var i=0,ii=id_components.length; i<ii; i++) {
				id[i] = (id_components[i] in data ? data[id_components[i]] : '');
			}
			
			return this.id = id.join('-');
		},
		
		/**
		 * Removes row from DataTable
		 */
		remove: function () {
			this.node.remove();
			this.host = null;
			this.id = null;
			this.id_components = null;
		},
		
		/**
		 * Destroy row
		 */
		destroy: function () {
			this.remove();
		},
		
		/**
		 * Update row data
		 */
		update: function (column_id, value) {
			var columns = this.host.getColumns();
			
			for(var i=0,ii=columns.length; i<ii; i++) {
				if (columns[i].id == column_id) {
					var td = this.getColumnNode(column_id);
					
					if (value === undefined || value === null) {
						value = '';
					}
					
					if (columns[i].formatter && isfunc(columns[i].formatter)) {
						value = columns[i].formatter.call(this, column_id, value, this.data);
					} else if (columns[i].escape) {
						//Formatter should handle escaping itself
						value = escape(String(value));
					}
					
					td.set('innerHTML', value);
				}
			}
		},
		
		/**
		 * Returns row TD for given column or null
		 * 
		 * @param {String} column_id
		 * @return Row TD (Y.Node)
		 * @type {Object}
		 */
		getColumnNode: function (column_id) {
			if (!this.node) return;
			if (column_id in this.nodes) return this.nodes[column_id];
			
			var id = column_id.replace(/[^a-z0-9\-_]*/g, '');
			return this.nodes[column_id] = this.node.one('td.' + id);
		},
		
		/**
		 * Create row HTML or returns existing one
		 * 
		 * @return Rows node (Y.Node)
		 * @type {Object}
		 */
		getNode: function () {
			//If already was created, return it
			if (this.node) return this.node;
			
			var columns = this.host.getColumns(),
				html = [];
				data = this.data,
				column = null,
				column_id = null,
				value = null;
			
			for(var i=0,ii=columns.length; i<ii; i++) {
				column = columns[i];
				if (column.title !== null) {
					column_id = column.id;
					value = (column_id in data ? data[column_id] : '');
					
					if (value === undefined || value === null) {
						value = '';
					}
					
					//Format data if needed
					if (column.formatter && isfunc(column.formatter)) {
						value = column.formatter.call(this, column_id, value, data);
					} else if (column.escape) {
						//Formatter should handle escaping
						value = escape(String(value));
					}
					
					html[html.length] = '<td class="row-' + column_id.replace(/[^a-z0-9\-_]*/g, '') + '">' + value + '</td>';
				}
			}
			
			return this.node = Y.Node.create('<tr>' + html.join('') + '</tr>');
		},
		
		/**
		 * Remove element from DOM to do manipulation with it
		 * Needed, because single cell manipulation while table is in
		 * DOM is slow
		 */
		beginChange: function () {
			if (this.reference_point) return this;
			this.reference_point = Y.DOM.removeFromDOM(this.node);
			return this;
		},
		
		/**
		 * Restore elements position in DOM after manipulation has done
		 */
		endChange: function () {
			if (!this.reference_point) return this;
			Y.DOM.restoreInDOM(this.reference_point);
			this.reference_point = null;
			return this;
		}
	};
	
	Supra.DataTableRow = DataTableRow;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version);