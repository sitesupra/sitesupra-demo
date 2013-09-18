/**
 * Template selection input
 */
YUI.add("supra.datagrid-row", function (Y) {
	//Invoke strict mode
	"use strict";
	
	function DataGridRow (host, data) {
		this.host = host;
		this.data = data || {};
		this.id_components = host.get('idColumn');
		this.nodes = {};
	}
	
	var isfunc = Y.Lang.isFunction;
	var escape = Y.Escape.html;
	
	DataGridRow.prototype = {
		
		/* DataGrid instance */
		host: null,
		
		/* TR element (Y.Node) */
		node: null,
		
		/* TD elements */
		nodes: {},
		
		/* Row ID */
		id: null,
		
		/* Row data */
		data: null,
		
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
		get: function (key, strict) {
			var data = this.data;
			if (key == 'parent' && !strict) {
				return this.host;
			} else if (key == 'id' && !strict) {
				return this.getID();
			} else if (key == 'data' && !strict) {
				return data;
			} else if (key in data) {
				return data[key];
			} else {
				return null;
			}
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
			var id_components = this.id_components,
				data = this.data,
				id = [],
				i = 0,
				ii = id_components.length;
			
			for(; i<ii; i++) {
				id[i] = (id_components[i] in data ? data[id_components[i]] : '');
			}
			
			return this.id = id.join('-');
		},
		
		/**
		 * Removes row from DataGrid
		 */
		remove: function () {
			this.host.remove(this.getID(), true);
			
			this.node.remove(true);
			this.host = null;
			this.id = null;
			this.id_components = null;
		},
		
		/**
		 * Destroy row
		 */
		destroy: function () {
			this.remove();
			
			this.data = null;
			this.reference_point = null;
			
			this.node.destroy();
			this.node = null;
			
			var nodes = this.nodes;
			for(var i in nodes) if (nodes[i]) nodes[i].destroy();
			this.nodes = null;
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
					
					if (value !== null && value !== undefined) {
						td.set('innerHTML', value);
					}
					
					if (columns[i].renderer && isfunc(columns[i].renderer)) {
						columns[i].renderer.call(this, column_id, value, this.data, td);
					}
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
			
			var id = column_id.replace(/[^a-z0-9\-_]*/ig, '');
			return this.nodes[column_id] = this.node.one('td.row-' + id);
		},
		
		/**
		 * Try guessing which is title column and returns node for it if found
		 * or first column node if not found
		 * 
		 * @return Title column TD for this row
		 * @type {Y.Node}
		 */
		getTitleColumnNode: function () {
			if (!this.node) return;
			var columns = this.host.getColumns(),
				i = 0,
				ii = columns.length,
				id = null,
				title = null,
				check = ['title', 'name'];
			
			for(; i<ii; i++) {
				id = columns[i].id.toLowerCase();
				title = columns[i].title.toLowerCase();
				
				if (id.indexOf('title') != -1 || id.indexOf('name') != -1 || title.indexOf('title') != -1 || title.indexOf('name') != -1) {
					return this.getColumnNode(id);
				}
			}
			
			return this.getColumnNode(columns[0].id);
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
				html = [],
				data = this.data,
				column = null,
				column_id = null,
				classname = null,
				align = null,
				value = null,
				renderers = [];
			
			for(var i=0,ii=columns.length; i<ii; i++) {
				column = columns[i];
				column_id = column.id;
				value = (column_id in data ? data[column_id] : '');
				classname = 'row-' + column_id.replace(/[^a-z0-9\-_]*/ig, '');
				
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
				
				//Align text
				if (column.align) {
					align = ' align-' + column.align;
				} else {
					align = '';
				}
				
				if (column.renderer && isfunc(column.renderer)) {
					renderers.push({'renderer': column.renderer, 'classname': classname, 'column_id': column_id, 'value': value});
				}
				
				html[html.length] = '<td ' + (column.width ? 'width="' + column.width + '" ' : '') + 'class="' + classname + align + '">' + value + '</td>';
			}
			
			var tr = this.node = Y.Node.create('<tr>' + html.join('') + '</tr>');
			this.node.setData('rowID', this.getID());
			
			//Renderers
			var td = null,
				renderer = null;
			
			for(var i=0,ii=renderers.length; i<ii; i++) {
				renderer = renderers[i];
				td = tr.one('td.' + renderer.classname);
				if (td) {
					renderer.renderer.call(this, renderer.column_id, renderer.value, data, td);
				}
			}
			
			return this.node;
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
		},
		
		/**
		 * Returns row index
		 * 
		 * @return Row index
		 * @type {Number}
		 */
		index: function () {
			var rows = this.host.getAllRows(),
				i = 0,
				ii = rows.length;
			
			for(; i<ii; i++) if (rows[i] === this) return i;
			return -1;
		},
		
		/**
		 * Returns next row or null
		 * 
		 * @return Next row or null
		 * @type {Object}
		 */
		next: function () {
			return this.host.getRowByIndex(this.index() + 1);
		},
		
		/**
		 * Returns previous row or null
		 * 
		 * @return Next row or null
		 * @type {Object}
		 */
		previous: function () {
			return this.host.getRowByIndex(this.index() - 1);
		},
		
		/**
		 * Returns true if classname argument is DataGridRow, otherwise false
		 * For compatibility with Y.Widget classes
		 * 
		 * @param {String} classname Classname to compare to
		 * @return True if classname is DataGridRow, otherwise false
		 * @type {Boolean}
		 */
		isInstanceOf: function (classname) {
			if (classname === 'DataGridRow') {
				return this instanceof Supra.DataGridRow;
			}
			return false;
		}
	};
	
	Supra.DataGridRow = DataGridRow;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['widget']});