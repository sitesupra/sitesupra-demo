//Invoke strict mode
"use strict";

YUI.add('supra.datatable', function (Y) {
	
	var COLUMN_DEFINITION = {
		'id': null,
		'title': '',
		'formatter': null,
		'escape': true
	};
	
	function DataTable (config) {
		DataTable.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
		
		//Empty params
		this.requestParams.data = this.get('requestParams');
	}
	
	DataTable.NAME = 'datatable';
	
	DataTable.ATTRS = {
		'tableNode': {
			'value': null,
			'writeOnce': true
		},
		'tableHeadingNode': {
			'value': null,
			'writeOnce': true
		},
		'columns': {
			'value': [],
			'setter': '_setColumns'
		},
		'idColumn': {
			'value': null,
			'getter': '_getIDColumn',
			'setter': '_setIDColumn'
		},
		'requestURI': {
			'value': null,
			'setter': 'setRequestURI'
		},
		'requestParams': {
			'value': {}
		},
		'requestDataLocator': {
			'value': 'results'
		},
		'dataSource': {
			'value': null,
			'setter': 'setDataSource'
		}
	};
	
	DataTable.HTML_PARSER = {
		'tableNode': function (srcNode) {
			var node = srcNode.one('table');
			if (!node) {
				node = Y.Node.create('<table></table>');
				srcNode.append(node);
			}
			this.tableNode = node;
			return node;
		},
		'tableHeadingNode': function (srcNode) {
			var table = this.tableNode;
			var node = table.one('tr');
			if (!node) {
				node = Y.Node.create('<thead><tr></tr></thead>');
				table.append(node);
				node = table.one('tr');
			}
			this.tableHeadingNode = node;
			return node;
		}
	};
	
	Y.extend(DataTable, Y.Widget, {
		
		/**
		 * Reference for beginChange() and endChange()
		 */
		reference_point: null,
		
		tableNode: null,
		tableHeadingNode: null,
		tableBodyNode: null,
		
		/**
		 * Rows
		 */
		rows: [],
		
		/*
		 * Request params
		 */
		requestParams: {
			/**
			 * Request parameter data
			 * @type {Object}
			 */
			data: {},
			
			/**
			 * Remove all parameters
			 */
			removeAll: function () {
				this.data = {};
				return this;
			},
			
			/**
			 * Set parameter
			 * 
			 * @param {String} key
			 * @param {String} value
			 */
			set: function (key, value) {
				if (Y.Lang.isObject(key)) {
					for(var i in key) {
						this.set(i, key[i]);	
					}
				} else {
					if (value !== null) {
						this.data[key] = value;
					} else {
						delete(this.data[key]);
					}
				}
				
				return this;
			},
			
			/**
			 * Returns parameter value
			 * 
			 * @param {String} key
			 * @return Parameter value
			 * @type {String}
			 */
			get: function (key) {
				return (key in this.data ? this.data[key] : null);
			},
			
			/**
			 * Remove parameter
			 * 
			 * @param {String} key
			 */
			remove: function (key) {
				this.set(key, null);
				return this;
			},
			
			/**
			 * Converts all parameters into query string
			 * 
			 * @private
			 */
			toString: function () {
				return Y.QueryString.stringify(this.data);
			}
		},
		
		renderUI: function () {
			DataTable.superclass.renderUI.apply(this, arguments);
			
			//Create table body
			this.tableBodyNode = Y.Node.create('<tbody></tbody>');
			this.tableNode.append(this.tableBodyNode);
			
			//Create headings
			var fields = [],
				heading = this.get('tableHeadingNode'),
				columns = this.get('columns'),
				column = null,
				id = null,
				node = null;
			
			for(var i=0,ii=columns.length; i<ii; i++) {
				column = columns[i];
				
				//If column doesn't have data received from server
				//then don't add this column to fields, otherwise
				//data will be "undefined"
				if (column.hasData !== false) {
					fields.push(column.id);
				}
				
				id = column.id.replace(/[^a-z0-9\-_]*/g, '');
				node = Y.Node.create('<th class="col-' + id + '">' + (column.title || '') + '</th>');
				heading.append(node);
			}
			
			//Add session ID to request params
			var sid_name = SU.data.get('sessionName', null),
				sid_id = SU.data.get('sessionId', null);
			
			if (sid_name && sid_id) {
				this.requestParams.set(sid_name, sid_id);
			}
			
			//Create datasource
			var datasource = this.get('dataSource');
			if (!datasource) {
				datasource = new Y.DataSource.IO({
					'source': this.get('requestURI')
				});
				
				//We expect JSON
				datasource.plug(Y.Plugin.DataSourceJSONSchema, {
					'schema': {
						'resultListLocator': this.get('requestDataLocator'),	// default is 'records'
						'resultFields': fields
					}
				});
				
				this.set('dataSource', datasource);
			}
			
			this.after('render', function () {
				this.reset();
			}, this);
		},
		
		bindUI: function () {
			DataTable.superclass.bindUI.apply(this, arguments);
		},
		
		syncUI: function () {
			DataTable.superclass.syncUI.apply(this, arguments);
		},
		
		/**
		 * Reload data
		 */
		reset: function () {
			this.fire('reset');
			
			var datasource = this.get('dataSource');
			
			datasource.sendRequest({
				'request': this.requestParams.toString(),
				'callback': {
					'success': Y.bind(this._dataReceivedSuccess, this),
					'failure': Y.bind(this._dataReceivedFailure, this)
				}
			});
		},
		
		/**
		 * Add even/odd classes to rows
		 * @private
		 */
		_renderEvenOddRows: function () {
			var rows = this.rows;
			for(var i=0,ii=rows.length; i<ii; i++) {
				rows[i].getNode().addClass('yui3-datatable-' + (i % 2 ? 'odd' : 'even'));
			}
		},
		
		/**
		 * Handle received data
		 * 
		 * @param {Object} e
		 */
		_dataReceivedSuccess: function (e) {
			var response = e.response;
			this.beginChange();
			
			//Don't need old data
			this.removeAllRows();
			
			//Add new rows
			var results = response.results, i = null;
			for(i in results) {
				if (results.hasOwnProperty(i)) {
					this.addRow(results[i]);
				}
			}
			
			this._renderEvenOddRows();
			
			this.fire('reset:success', {'results': results});
			
			this.endChange();
		},
		
		/**
		 * Handle request error
		 * 
		 * @param {Object} e
		 */
		_dataReceivedFailure: function (e) {
			Y.log(e, 'debug');
			
			//Don't need old data
			this.removeAllRows();
			
		},
		
		/**
		 * Returns all rows
		 * 
		 * @return Array of rows
		 * @type {Array}
		 */
		getAllRows: function () {
			return this.rows;
		},
		
		/**
		 * Returns row by ID
		 * 
		 * @param {String} row_id
		 * @return Rows DataTableRow instance
		 * @type {Object}
		 */
		getRowByID: function (row_id) {
			var rows = this.rows;
			for(var i=0,ii=rows.length; i<ii; i++) {
				if (rows[i].getID() == row_id) return rows[i];
			}
			return null;
		},
		
		/**
		 * Returns row by index
		 * 
		 * @param {Number} index Row index
		 * @return Rows DataTableRow instance
		 * @type {Object}
		 */
		getRowByIndex: function (index) {
			var rows = this.rows;
			if (index >= 0 && index < rows.length) {
				return rows[index];
			}
			return null;
		},
		
		/**
		 * Removes row by ID and returns removed row data
		 * 
		 * @param {String} row_id
		 * @return Removed row data
		 * @type {Object}
		 */
		removeRow: function () {
			var rows = this.rows;
			for(var i=0,ii=rows.length; i<ii; i++) {
				if (rows[i].getID() == row_id) {
					var row = rows[i];
					var data = row.getData();
					this.rows = rows.slice(0,i).concat(rows.slice(i+1));
					row.destroy();
					
					return data;
				}
			}
			return null;
		},
		
		/**
		 * Remove all rows from DataTable
		 */
		removeAllRows: function () {
			//If beginChange was already called before removeAllRows() then skip it
			var changing = this.reference_point ? true : false;
			if (!changing) this.beginChange();
			
			var rows = this.rows;
			for(var i=0,ii=rows.length; i<ii; i++) {
				rows[i].destroy();
			}
			this.rows = [];
			
			if (!changing) this.endChange();
		},
		
		/**
		 * Add row to DataTable
		 * 
		 * @param {Object} row Row data
		 * @param {Object} where Place where to insert row, by default at the end
		 * @return Row object
		 * @type {Object}
		 */
		addRow: function (row, where) {
			var rows = this.rows;
			
			//If not DataTableRow object, then create it
			if (!(row instanceof Supra.DataTableRow)) {
				row = new Supra.DataTableRow(this, row, rows.length);
			}
			
			//Add to row list
			rows.push(row);
			
			//Insert into DOM
			this.tableBodyNode.append(row.getNode());
			
			return row;
		},
		
		/**
		 * Returns all column information
		 * 
		 * @return Column information
		 * @type {Array}
		 */
		getColumns: function () {
			return this.get('columns');
		},
		
		/**
		 * Add column
		 * 
		 * @param {String} id
		 * @param {String} title
		 */
		addColumn: function (id, title) {
			var data = id;
			if (!Y.Lang.isObject(id)) {
				data = {'id': id, 'title': title};
			}
			
			data = Supra.mix({}, COLUMN_DEFINITION, data);
			
			if (!('id' in data) || !data.id) {
				Y.log('All DataTable column must have an ID', 'debug');
				return this;
			}
			
			//Add to column list
			var columns = this.get('columns');
			columns.push(data);
			this.set('columns', columns);
			
			return this;
		},
		
		/**
		 * Add several columns to DataTable
		 * 
		 * @param {Object} data
		 */
		addColumns: function (data) {
			var columns = this.get('columns').concat(data);
			this.set('columns', columns);
			return this;
		},
		
		/**
		 * Set columns
		 * 
		 * @param {Object} value
		 */
		setColumns: function (value) {
			this.set('columns', value);
			return this;
		},
		
		/**
		 * Set columns
		 * 
		 * @param {Object} value
		 * @return Normalized value
		 * @type {Object}
		 * @private
		 */
		_setColumns: function (value) {
			var columns = [],
				data = null,
				id_col = null;
				
			for(var i in value) {
				if (value.hasOwnProperty(i)) {
					data = Supra.mix({}, COLUMN_DEFINITION, value[i]);
					if (!('id' in data) || !data.id) {
						Y.log('All DataTable columns must have an ID', 'debug');
						continue;
					}
					
					if (data.id == 'id' || (!id_col && String(data.id).indexOf('id') != -1)) {
						id_col = data.id;
					}
					
					columns.push(data);
				}
			}
			
			if (id_col) {
				this.set('idColumn', id_col);
			}
			
			return columns;
		},
		
		/**
		 * Returns array of column IDs from which are made unique row ID
		 * 
		 * @return Array of column IDs
		 * @type {Array}
		 */
		getIDColumn: function () {
			return this.get('idColumn');
		},
		
		/**
		 * Set ID column, which will be used to create unique row IDs
		 * 
		 * @param {Array} val
		 * @return Array of column IDs
		 * @type {Array}
		 */
		_setIDColumn: function (val) {
			return (!Y.Lang.isArray(val) ? [val] : val); 
		},
		
		/**
		 * Attribute "idColumn" getter 
		 * @private
		 */
		_getIDColumn: function (val) {
			if (val) return val;
			var columns = this.get('columns');
			var val = [columns[0].id];
			this.set('idColumn', val);
			return val;
		},
		
		/**
		 * Set request URI
		 * 
		 * @param {String} uri
		 * @return Request URI
		 * @type {String}
		 */
		setRequestURI: function (uri) {
			var uri = String(uri);
			var lastchar = uri.substr(-1);
			
			if (lastchar != '?' && lastchar != '&') {
				uri += (uri.indexOf('?') == -1 ? '?' : '&');
			}
			
			if (this.get('rendered')) {
				this.get('dataSource').set('source', uri);
			}
			
			return uri;
		},
		
		/**
		 * Returns request URI
		 * 
		 * @return Request URI
		 * @type {String}
		 */
		getRequestURI: function () {
			return this.get('requestURI');
		},
		
		/**
		 * Set DataSource
		 * 
		 * @param {Object} datasource
		 * @return DataSource instance
		 * @type {Object}
		 */
		setDataSource: function (datasource) {
			return datasource;
		},
		
		/**
		 * Returns DataSource instance
		 * 
		 * @return DataSource
		 * @type {Object}
		 */
		getDataSource: function () {
			return this.get('dataSource');
		},
		
		/**
		 * Remove table from DOM to do manipulation with it
		 * Needed, because single cell manipulation while table is in
		 * DOM is slow
		 */
		beginChange: function () {
			if (this.reference_point) return this;
			this.reference_point = Y.DOM.removeFromDOM(this.tableNode);
			return this;
		},
		
		/**
		 * Restore tables position in DOM after manipulation has done
		 */
		endChange: function () {
			if (!this.reference_point) return this;
			Y.DOM.restoreInDOM(this.reference_point);
			this.reference_point = null;
			return this;
		}
	});
	
	Supra.DataTable = DataTable;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['widget', 'datasource', 'dataschema', 'datatype', 'querystring', 'supra.datatable-row']});