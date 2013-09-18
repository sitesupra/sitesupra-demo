/**
 * Data Grid
 */
YUI.add("supra.datagrid", function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Column definition prototype
	var COLUMN_DEFINITION = {
		'id': null,
		'title': '',
		'formatter': null,
		'escape': true
	};
	
	//Built-in formatting functions
	var FORMATTERS = {
		'dateShort': function (col_id, value, data) {
			if (!value) return '';
			return Y.DataType.Date.reformat(value, 'in_date', '<span class="format-date"><small>%b</small>%d</span>');
		},
		'ellipsis': function (col_id, value, data) {
			return '<div class="ellipsis">' + value + '</div>';
		}
	};
	
	
	
	function DataGrid (config) {
		DataGrid.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
		
		//Empty params
		this.requestParams.data = this.get('requestParams');
		
		//Set initial property values
		this.rows = [];
	}
	
	DataGrid.NAME = 'datagrid';
	DataGrid.CSS_PREFIX = 'su-' + DataGrid.NAME;
	
	DataGrid.ATTRS = {
		'tableNode': {
			'value': null,
			'writeOnce': true
		},
		'tableHeadingNode': {
			'value': null,
			'writeOnce': true
		},
		'tableHeadingFixed': {
			'value': false,
			'writeOnce': true
		},
		'tableHeadingVisible': {
			'value': true,
			'setter': '_setTableHeadingVisible'
		},
		'columns': {
			'value': [],
			'setter': '_setColumns'
		},
		'dataColumns': {
			'value': []
		},
		'idColumn': {
			'value': null,
			'getter': '_getIDColumn'
		},
		'requestURI': {
			'value': null,
			'setter': 'setRequestURI'
		},
		'requestParams': {
			'value': {}
		},
		'requestMetaLocator': {
			'value': {
				total: 'data.total',
				offset: 'data.offset'
			}
		},
		'requestDataLocator': {
			'value': 'data.results'
		},
		'requestTotalRecords': {
			'value': null
		},
		
		'dataSource': {
			'value': null,
			'setter': 'setDataSource'
		},
		'nodeScrollable': {
			'value': null
		},
		
		/**
		 * Automatically start loading data when craeted
		 */
		'autoLoad': {
			'value': true
		},
		
		/**
		 * Children attribute, returns all rows
		 */
		'children': {
			'value': null,
			'getter': 'getAllRows'
		},
		
		/**
		 * Style, values are 'grid' and 'list' 
		 */
		'style': {
			'value': 'grid',
			'setter': '_setStyle'
		},
		
		/**
		 * Loading style
		 */
		'loading': {
			'value': false,
			'setter': '_setLoading'
		},
		
		/**
		 * Rows are clickable
		 */
		'clickable': {
			'value': true,
			'setter': '_setClickable'
		},
		
		/**
		 * Datagrid is scrollable
		 */
		'scrollable': {
			'value': true
		}
	};
	
	DataGrid.HTML_PARSER = {
		'tableNode': function (srcNode) {
			var node = srcNode.one('table');
			if (!node) {
				node = Y.Node.create('<table></table>');
				srcNode.append(node);
			}
			
			if (node) {
				this.tableNode = node;
				return node;
			}
		},
		'tableHeadingNode': function (srcNode) {
			var table = this.tableNode;
			var node = table.one('tr');
			if (!node) {
				node = Y.Node.create('<thead><tr></tr></thead>');
				table.append(node);
				node = table.one('tr');
			}
			
			if (node) {
				this.tableHeadingNode = node;
				return node;
			}
		},
		'nodeScrollable': function (srcNode) {
			return this.get('scrollable') ? srcNode.closest('.su-scrollable') : null;
		}
	};
	
	Y.extend(DataGrid, Y.Widget, {
		
		/**
		 * Reference for beginChange() and endChange()
		 * @type {Object}
		 * @private
		 */
		'reference_point': null,
		
		/**
		 * Saved scroll offset before change
		 * @type {Number}
		 * @private
		 */
		'scroll_offset': null,
		
		/**
		 * Table node
		 * @type {Y.Node}
		 * @private
		 */
		'tableNode': null,
		
		/**
		 * Table heading node
		 * @type {Y.Node}
		 * @private
		 */
		'tableHeadingNode': null,
		
		/**
		 * Table body node
		 * @type {Y.Node}
		 * @private
		 */
		'tableBodyNode': null,
		
		/**
		 * Rows, instances of DataGridRow
		 * @type {Array}
		 * @private
		 */
		'rows': [],
		
		/**
		 * Scrollable object, instance of Supra.Scrollable
		 * @type {Object}
		 * @private
		 */
		'scrollable': null,
		
		
		
		
		/*
		 * Request params
		 */
		'requestParams': {
			/**
			 * Request parameter data
			 * @type {Object}
			 */
			'data': {},
			
			/**
			 * Remove all parameters
			 */
			'removeAll': function () {
				this.data = {};
				return this;
			},
			
			/**
			 * Set parameter
			 * 
			 * @param {String} key
			 * @param {String} value
			 */
			'set': function (key, value) {
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
			'get': function (key) {
				return (key in this.data ? this.data[key] : null);
			},
			
			/**
			 * Remove parameter
			 * 
			 * @param {String} key
			 */
			'remove': function (key) {
				this.set(key, null);
				return this;
			},
			
			/**
			 * Converts all parameters into query string
			 * 
			 * @return Query string
			 * @type {String}
			 * @private
			 */
			'toString': function () {
				return Y.QueryString.stringify(this.data);
			}
		},
		
		
		'initializer': function () {
			this.rows = [];
			
			var scrollable = this.get('scrollable');
			if (scrollable) {
				this.scrollable = new Supra.Scrollable({
					'srcNode': this.get('contentBox'),
					'axis': scrollable == 'x' ? 'x' : 'y' 
				});
			}
		},
		
		/**
		 * Add needed elements
		 * 
		 * @private
		 */
		'renderUI': function () {
			DataGrid.superclass.renderUI.apply(this, arguments);
			
			//Create table body
			this.tableBodyNode = Y.Node.create('<tbody></tbody>');
			this.tableNode.append(this.tableBodyNode);
			
			//Create scrollable
			if (this.scrollable) {
				this.scrollable.render();
			}
			
			//Create headings
			var fields = [],
				heading = this.get('tableHeadingNode'),
				columns = this.get('columns'),
				data_columns = this.get('dataColumns'),
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
				
				id = column.id.replace(/[^a-z0-9\-_]*/ig, '');
				node = Y.Node.create('<th ' + (column.width ? 'width="' + column.width + '" ' : '') + 'class="col-' + id + '"><span>' + (column.title || '') + '</span></th>');
				heading.append(node);
			}
			
			//Heading visibility
			if (!this.get('tableHeadingVisible')) {
				heading.ancestor().addClass('hidden');
			}
			
			//Data columns are not rendered
			for(var i=0,ii=data_columns.length; i<ii; i++) {
				fields.push(data_columns[i].id);
			}
			
			//Add ID column to data column list
			column = this.get('idColumn');
			if (column.length) {
				fields.push(column[0]);
			}
			
			//Fixed heading
			if (this.get('tableHeadingFixed')) {
				this.get('boundingBox').addClass('fixed');
			}
			
			//Add session ID to request params
			var sid_name = Supra.data.get('sessionName', null),
				sid_id = Supra.data.get('sessionId', null);
			
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
						'metaFields': this.get('requestMetaLocator'),			// default is 'data.total'
						'resultListLocator': this.get('requestDataLocator'),	// default is 'data.records'
						'resultFields': fields
					}
				});
				
				this.set('dataSource', datasource);
			}
		},
		
		/**
		 * Attach event listeners
		 * 
		 * @private
		 */
		'bindUI': function () {
			DataGrid.superclass.bindUI.apply(this, arguments);
			
			this.tableBodyNode.delegate('click', this._handleRowClick, 'tr', this);
			
			//When rendering is done start loading data
			if (this.get('autoLoad')) {
				this.after('render', this.reset, this);
			}
		},
		
		/**
		 * Update UI state to match attributes
		 * 
		 * @private
		 */
		'syncUI': function () {
			this.set('style', this.get('style'));
			this.set('clickable', this.get('clickable'));
		},
		
		
		
		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		
		
		/**
		 * On row click fire row:click event with row
		 * 
		 * @param {Event} e Event
		 */
		'_handleRowClick': function (e) {
			if (this.get('disabled')) return;
			
			var target = e.target.closest('TR'),
				row_id = target ? target.getData('rowID') : null;
			
			if (row_id) {
				var row = this.getRowByID(row_id),
					data = row.getData();
				
				this.fire('row:click', {'data': data, 'row': row, 'element': e.target});
			}
		},
		
		/**
		 * Handle received data
		 * 
		 * @param {Object} e
		 * @private
		 */
		'_dataReceivedSuccess': function (e) {
			var response = e.response;
			this.beginChange();
			
			//Don't need old data
			this.removeAllRows();
			
			//Add new rows
			var results = response.results, i = null;
			for(i in results) {
				if (results.hasOwnProperty(i)) {
					this.add(results[i], null, true);
				}
			}
			
			//Event
			this.fire('load:success', {'results': results});
			
			//Remove loading style
			this.set('loading', false);
			
			this.endChange();
		},
		
		/**
		 * Handle request error
		 * 
		 * @param {Object} e
		 * @private
		 */
		'_dataReceivedFailure': function (e) {
			//Y.log(e, 'error');
			this.fire('load:failure');
			
			//Remove loading style
			this.set('loading', false);
			
			//Don't need old data
			this.removeAllRows();
		},
		
		
		
		/**
		 * ------------------------------ API ------------------------------
		 */
		
		
		
		/**
		 * Remove existing data and reload using offset 0
		 */
		'reset': function () {
			this.fire('reset');
			this.requestParams.set('offset', 0);
			this.removeAllRows();
			this.load();
			this.handleChange();
		},
		
		/**
		 * Load data
		 */
		'load': function () {
			//Event
			this.fire('load');
			
			//Style
			this.set('loading', true);
			
			this.get('dataSource').sendRequest({
				'request': this.requestParams.toString(),
				'callback': {
					'success': Y.bind(this._dataReceivedSuccess, this),
					'failure': Y.bind(this._dataReceivedFailure, this)
				}
			});
		},
		
		/**
		 * Returns all rows
		 * 
		 * @return Array of rows, Supra.DataGridRow instances
		 * @type {Array}
		 */
		'getAllRows': function () {
			return this.rows;
		},
		
		/**
		 * Returns row by ID, index or node
		 * 
		 * @param {Object} id ID or index or node
		 * @return DataGridRow instance for row
		 * @type {Object}
		 */
		'item': function (id) {
			if (typeof id === 'string') {
				return this.getRowByID(id);
			} else if (typeof id === 'number') {
				return this.getRowByIndex(id);
			} else if (typeof id === 'object' && id.isInstanceOf) {
				if (id.isInstanceOf('Node')) {
					return this.getRowByNode(id);
				} else if (id.isInstanceOf('DataGridRow')) {
					return id;
				}
			}
			return null;
		},
		
		/**
		 * Returns row by ID
		 * 
		 * @param {String} row_id
		 * @return DataGridRow instance for row
		 * @type {Object}
		 */
		'getRowByID': function (row_id) {
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
		 * @return DataGridRow instance for row
		 * @type {Object}
		 */
		'getRowByIndex': function (index) {
			var rows = this.rows;
			if (index >= 0 && index < rows.length) {
				return rows[index];
			}
			return null;
		},
		
		/**
		 * Returns row by node
		 * 
		 * @param {Object} node Node
		 * @return DataGridRow instance for row
		 * @type {Object}
		 */
		'getRowByNode': function (node) {
			var node = node.test('TR') ? node : node.closest('TR'),
				rows = this.rows;
			
			if (!node) return null;
			
			for(var i=0,ii=rows.length; i<ii; i++) {
				if (rows[i].getNode().compareTo(node)) return rows[i];
			}
			return null;
		},
		
		/**
		 * Removes row by ID and returns removed row data
		 * 
		 * @param {String} row_id Row ID
		 * @param {Boolean} keep Don't destroy row, only remove it
		 * @return Removed row data
		 * @type {Object}
		 */
		'remove': function (row_id, keep) {
			var row = this.item(row_id),
				rows = this.rows;
			
			if (!row) return null;
			
			for(var i=0,ii=rows.length; i<ii; i++) {
				if (rows[i] === row) {
					var data = row.getData();
					this.rows = rows.slice(0, i).concat(rows.slice(i+1));
					
					this.fire('row:remove', {'data': data, 'row': row});
					
					if (!keep) {
						row.destroy();
					}
					
					return data;
				}
			}
			
			//Trigger change
			this.handleChange();
			
			return null;
		},
		
		/**
		 * Remove all rows from DataGrid
		 */
		'removeAllRows': function () {
			//If beginChange was already called before removeAllRows() then skip it
			var changing = this.reference_point ? true : false;
			if (!changing) this.beginChange();
			
			var rows = this.rows;
			for(var i=0,ii=rows.length; i<ii; i++) {
				rows[i].destroy();
			}
			this.rows = [];
			
			if (!changing) this.endChange();
			
			this.fire('row:removeAll', {'rows': rows});
		},
		
		/**
		 * Add row to DataGrid or move existing row to another position
		 * 
		 * @param {Object} row Row data
		 * @param {Object} before Record before which to insert new row. Optional, default is at the end
		 * @param {Boolean} skip_check Don't check if row is already in data. Optional, default is false
		 * @return Row object
		 * @type {Object}
		 */
		'add': function (row, before, skip_check) {
			var rows = this.rows,
				found = false;
			
			//If not DataGridRow object, then create it
			if (!(row instanceof Supra.DataGridRow)) {
				row = new Supra.DataGridRow(this, row, rows.length);
			}
			
			if (!skip_check) {
				//Check if row is not in the data already
				var id = row.getID();
				for(var i=0,ii=rows.length; i<ii; i++) {
					if (id == rows[i].getID()) {
						//Remove from data
						rows.splice(i, 1);
						break;
					}
				}
			}
			
			//Find row before which to insert
			if (typeof before == 'number' && before >= 0 && before < rows.length) {
				before = rows[before];
			}
			
			if (before && before instanceof Supra.DataGridRow) {
				var id = before.getID();
				for(var i=0,ii=rows.length; i<ii; i++) {
					if (rows[i].getID() == id) {
						//Add to data
						rows.splice(i, 0, row);
						before.getNode().insert(row.getNode(), 'before');
						found = true;
						break;
					}
				}
			}
			
			if (!found) {
				//Add to row list
				rows.push(row);
				
				//Insert into DOM
				this.tableBodyNode.append(row.getNode());
				
				//Trigger event
				this.fire('row:add', {'data': row.getData(), 'row': row});
				
				//Trigger change
				this.handleChange();
			}
			
			return row;
		},
		
		/**
		 * Add row to DataGrid or move existing row to another position
		 * Alias of add
		 * 
		 * @param {Object} row Row data
		 * @param {Object} before Record before which to insert new row. Optional, default is at the end
		 * @param {Boolean} skip_check Don't check if row is already in data. Optional, default is false
		 * @return Row object
		 * @type {Object}
		 */
		'insert': function (row, before, skip_check) {
			return this.add(row, before, skip_check);
		},
		
		/**
		 * Returns all column information
		 * 
		 * @return Column information
		 * @type {Array}
		 */
		'getColumns': function () {
			return this.get('columns');
		},
		
		/**
		 * Returns column by ID
		 * 
		 * @param {String} id Column ID
		 * @return Column information
		 * @type {Object}
		 */
		'getColumn': function (id) {
			var columns = this.get('columns'),
				i = 0,
				ii = columns.length;
			
			for(; i<ii; i++) {
				if (columns[i].id == id) return columns[i];
			}
			
			return null;
		},
		
		/**
		 * Add column
		 * 
		 * @param {String} id
		 * @param {String} title
		 */
		'addColumn': function (id, title) {
			var data = id;
			if (!Y.Lang.isObject(id)) {
				data = {'id': id, 'title': title};
			}
			
			data = Supra.mix({}, COLUMN_DEFINITION, data);
			
			if (!('id' in data) || !data.id) {
				Y.log('All DataGrid column must have an ID', 'error');
				return this;
			}
			
			//Add to column list
			var columns = this.get('columns');
			columns.push(data);
			this.set('columns', columns);
			
			return this;
		},
		
		/**
		 * Add several columns to DataGrid
		 * 
		 * @param {Object} data
		 */
		'addColumns': function (data) {
			var columns = this.get('columns').concat(data);
			this.set('columns', columns);
			return this;
		},
		
		/**
		 * Set columns
		 * 
		 * @param {Object} value
		 */
		'setColumns': function (value) {
			this.set('columns', value);
			return this;
		},
		
		/**
		 * Returns array of column IDs from which are made unique row ID
		 * 
		 * @return Array of column IDs
		 * @type {Array}
		 */
		'getIDColumn': function () {
			return this.get('idColumn');
		},
		
		/**
		 * Set request URI
		 * 
		 * @param {String} uri
		 * @return Request URI
		 * @type {String}
		 */
		'setRequestURI': function (uri) {
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
		'getRequestURI': function () {
			return this.get('requestURI');
		},
		
		/**
		 * Set DataSource
		 * 
		 * @param {Object} datasource
		 * @return DataSource instance
		 * @type {Object}
		 */
		'setDataSource': function (datasource) {
			return datasource;
		},
		
		/**
		 * Returns DataSource instance
		 * 
		 * @return DataSource
		 * @type {Object}
		 */
		'getDataSource': function () {
			return this.get('dataSource');
		},
		
		/**
		 * Remove table from DOM to do manipulation with it
		 * Needed, because single cell manipulation while table is in
		 * DOM is slow
		 */
		'beginChange': function () {
			if (this.reference_point) return this;
			
			if (this.scrollable) {
				this.scroll_offset = this.scrollable.get('contentBox').get('scrollTop');
			}
			
			this.reference_point = Y.DOM.removeFromDOM(this.tableNode);
			
			return this;
		},
		
		/**
		 * Restore tables position in DOM after manipulation has done
		 */
		'endChange': function () {
			if (!this.reference_point) return this;
			
			Y.DOM.restoreInDOM(this.reference_point);
			this.reference_point = null;
			
			//Restore scroll position
			if (this.scrollable) {
				this.scrollable.get('contentBox').set('scrollTop', this.scroll_offset);
			}
			
			//Trigger change
			this.handleChange();
			
			return this;
		},
		
		/**
		 * Handle data change
		 */
		'handleChange': function () {
			var node = this.get('nodeScrollable');
			if (node) {
				node.fire('contentResize');
			} else {
				if (this.scrollable) {
					this.scrollable.syncUI();
				}
			}
			
			return this;
		},
		
		
		
		/**
		 * ------------------------------ ATTRIBUTES ------------------------------
		 */
		
		
		
		/**
		 * Set columns
		 * 
		 * @param {Object} value
		 * @return Normalized value
		 * @type {Object}
		 * @private
		 */
		'_setColumns': function (value) {
			var columns = [],
				data = null;
				
			for(var i in value) {
				if (value.hasOwnProperty(i)) {
					data = Supra.mix({}, COLUMN_DEFINITION, value[i]);
					
					if (typeof data.formatter === 'string') {
						if (typeof FORMATTERS[data.formatter] === 'function') {
							data.formatter = FORMATTERS[data.formatter];
						}
					}
					
					if (!('id' in data) || !data.id) {
						Y.log('All DataGrid columns must have an ID', 'error');
						continue;
					}
					
					columns.push(data);
				}
			}
			
			return columns;
		},
		
		/**
		 * Attribute "idColumn" getter 
		 * @private
		 */
		'_getIDColumn': function (val) {
			if (val) return val;
			
			var columns = this.get('columns'),
				data_columns = this.get('dataColumns'),
				regex = /([^a-z0-9]id[^a-z0-9]|^id[^a-z0-9]|[^a-z0-9]id$)/i;
			
			for(var i=0,ii=columns.length; i<ii; i++) {
				if (columns[i].id == 'id' || columns[i].id.match(regex)) {
					val = columns[i].id;
					break;
				}
			}
			
			if (!val) {
				for(var i=0,ii=data_columns.length; i<ii; i++) {
					if (data_columns[i].id == 'id' || data_columns[i].id.match(regex)) {
						val = data_columns[i].id;
						break;
					}
				}
			}
			
			if (!val) {
				val = [columns[0].id];
			}
			
			if (val && !Y.Lang.isArray(val)) {
				val = [val];
			}
			
			return val;
		},
		
		/**
		 * Style attribute setter
		 * 
		 * @param {String} style New style
		 * @return New style attribute value
		 * @type {String}
		 * @private
		 */
		'_setStyle': function (style) {
			var old_style = String(this.get('style') || 'grid');
			this.get('boundingBox').removeClass(this.getClassName(old_style));
			
			style = String(style || 'grid');
			this.get('boundingBox').addClass(this.getClassName(style));
			
			return style;
		}, 
		
		/**
		 * Set loading attribute
		 * 
		 * @param {String} loading New loading value
		 * @return New loading attribute value
		 * @type {String}
		 * @private
		 */
		'_setLoading': function (loading) {
			this.get('boundingBox').toggleClass('su-datagrid-loading', loading);
			return !!loading;
		},
		
		/**
		 * Clickable attribute setter
		 * 
		 * @param {String} clickable New clickable attribute value
		 * @return New clickable attribute value
		 * @type {String}
		 * @private
		 */
		'_setClickable': function (clickable) {
			this.get('boundingBox').toggleClass(this.getClassName('clickable'), clickable);
			return clickable;
		},
		
		/**
		 * tableHeadingVisible attribute setter
		 * 
		 * @param {Boolean} visible New visibility attribute value
		 * @return New attribute value
		 * @type {Boolean}
		 * @private
		 */
		'_setTableHeadingVisible': function (visible) {
			
			if (this.get('rendered')) {
				var node = this.get('tableHeadingNode');
				if (node) {
					node.ancestor().toggleClass('hidden', !visible);
				}
			}
			return !!visible;
		},
		
		/**
		 * Destructor
		 */
		'destructor': function () {
			this.scrollable.destroy();
			this.set('scrollable', null);
		},
		
		'addClass': function () {
			var box = this.get('boundingBox');
			if (box) box.addClass.apply(box, arguments);
			return this;
		},
		
		'removeClass': function () {
			var box = this.get('boundingBox');
			if (box) box.removeClass.apply(box, arguments);
			return this;
		},
		
		'toggleClass': function () {
			var box = this.get('boundingBox');
			if (box) box.toggleClass.apply(box, arguments);
			return this;
		},
		
		'hasClass': function () {
			var box = this.get('boundingBox');
			if (box) return box.hasClass.apply(box, arguments);
			return false;
		}
	});
	
	DataGrid.FORMATTERS = FORMATTERS;
	
	Supra.DataGrid = DataGrid;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['widget', 'datasource', 'dataschema', 'datatype', 'querystring', 'supra.datagrid-row', 'supra.scrollable']});