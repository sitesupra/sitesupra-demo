YUI.add('supra.crud-plugin-list', function (Y) {
	//Invoke strict mode
	"use strict";
	
	
	/**
	 * Crud list plugin, adds Data grid, sorting, delete and insert
	 * functionality
	 */
	function Plugin (config) {
		Plugin.superclass.constructor.apply(this, arguments);
	}
	
	Plugin.NAME = "list";
	Plugin.NS = "list";
	
	Plugin.ATTRS = {
		'srcNode': {
			value: null
		},
		'configuration': {
			value: null
		}
	};
	
	
	Y.extend(Plugin, Y.Plugin.Base, {
		
		
		/**
		 * List of sub-widgets
		 * @type {Object}
		 * @private
		 */
		widgets: null,
		
		
		/**
		 * Initialize plugin
		 */
		initializer: function () {
			var host = this.get('host'),
				configuration = host.get('configuration');
			
			this.widgets = {};
			
			if (configuration) {
				this._ready({'newVal': configuration});
			} else {
				host.once('configurationChange', this._ready, this);
			}
		},
		
		/**
		 * Teardown plugin
		 */
		destructor: function () {
			var widgets = this.widgets,
				key;
			
			// Destroy all widgets
			for (key in widgets) {
				if (widgets[key].destroy) {
					widgets[key].destroy();
				}
			}
			
			this.widgets = null;
		},
		
		
		/* ------------------------------ API ------------------------------ */
		
		
		/**
		 * Reload datagrid with given parameters
		 *
		 * @param {Object} filters Filters
		 */
		setFilters: function (filters) {
			var data_grid = this.widgets.data_grid;
			
			data_grid.requestParams.removeAll();
			data_grid.requestParams.set(filters);
			data_grid.reset();
		},
		
		
		/* ------------------------------ Render ------------------------------ */
		
		
		/**
		 * Configuration has been loaded and UI can be set up
		 *
		 * @private
		 */
		_ready: function (e) {
			// Render
			var host     = this.get('host'),
				config   = e.newVal,
				view;
			
			this.set('configuration', config);
			this._renderList();
		},
		
		_renderList: function () {
			var data_grid,
				data_grid_new,
				data_grid_delete,
				config = this.get('configuration'),
				container = this.get('srcNode');
			
			data_grid = new Supra.DataGrid({
				'requestURI': config.getDataPath('datalist'),
				'requestParams': {
					// Provider ID already added in config.getDataPath
				},
				'columns': config.getConfigFields('ui_list.fields', 'DataGrid'),
				'dataColumns': config.getConfigFields('ui_edit.fields', 'DataGrid'),
				'idColumn': ['id'], //[this.get('primaryKey')],
				'tableHeadingFixed': false
			});
			
			data_grid.plug(Supra.DataGrid.LoaderPlugin, {
				'recordHeight': 40
			});
			data_grid.plug(Supra.DataGrid.DraggablePlugin, {
				'dd-sort':   config.getConfigValue('attributes.sortable'),
				'dd-insert': config.getConfigValue('attributes.create'),
				'dd-delete': config.getConfigValue('attributes.delete')
			});
			
			data_grid.render(container);
			
			data_grid.on('row:click', this._handleRecordClick, this);
			data_grid.on('drag:sort', this._handleRecordSort, this);
			data_grid.on('drag:insert', this._handleRowInsert, this);
			
			/**
			 * Create new item and delete icons
			 */
			if (config.getConfigValue('attributes.create')) {
				data_grid_new = new Supra.DataGridNewItem({
					'newItemLabel': Supra.Intl.get(['crud', 'new_item'])
				});
				
				data_grid_new.render(container);
				data_grid_new.on('insert:click', this._handleRowInsert, this);
			}
			
			if (config.getConfigValue('attributes.delete')) {
				data_grid_delete = new Supra.DataGridDelete({
					'dataGrid': data_grid
				});
				
				data_grid_delete.render(container);
				data_grid_delete.on('delete', this._handleRowDelete, this);
			}
			
			this.widgets.data_grid = data_grid;
			this.widgets.data_grid_new = data_grid_new;
			this.widgets.data_grid_delete = data_grid_delete;
		},
		
		_handleRecordClick: function (e) {
			var row = e.row,
				record_id = row.getID();
			
			if (record_id) {
				this.fire('edit', {
					'recordId': record_id,
					'data': row.getData(),
					'row': row
				});
			}
		},
		
		_handleRowInsert: function (e) {
			this.fire('edit', {
				'recordId': null,
				'data': {},
				'row': null,
				'values': {
					'record-before': e.recordPrevious ? e.recordPrevious.getID() : '',
					'record-after': e.recordNext ? e.recordNext.getID() : ''
				}
			});
		},
		
		/**
		 * On record sort send to server
		 * 
		 * @private
		 */
		_handleRecordSort: function (e) {
			var config = this.get('configuration'),
				uri = config.getDataPath('sort'),
				data_grid = this.widgets.data_grid;
			
			//Update data
			data_grid.add(e.record, e.newRecordNext);
			
			Supra.io(uri, {
				'data': {
					'providerId': config.getConfigValue('attributes.id'),
					'id': e.record.getID(),
					'record-after':  e.newRecordNext ? e.newRecordNext.getID() : '',
					'record-before': e.newRecordPrevious ? e.newRecordPrevious.getID() : ''
				},
				'method': 'post',
				'context': this,
				'on': {
					'failure': function () {
						//Restore previous position
						data_grid.add(e.record, e.oldRecordNext);
					}
				}
			});
		},
		
		/**
		 * Handle record delete event
		 *
		 * @param {Object} e Event facade object
		 * @private
		 */
		_handleRowDelete: function (e) {
			var record_id = e.row.getID(),
				message = Supra.Intl.get(['crud', 'delete_confirmation']);
			
			Supra.Manager.executeAction('Confirmation', {
				'message': message,
				'useMask': true,
				'buttons': [
					{'id': 'delete', 'label': '{# buttons.yes #}', 'click': function () { this.deleteRecordConfirm(record_id) }, 'context': this},
					{'id': 'no', 'label': '{# buttons.no #}'}
				]
			});
		},
		
			/**
		 * Delete record after confirmation
		 * 
		 * @param {String} record_id Record ID
		 */
		deleteRecordConfirm: function (record_id) {
			//Delete record
			if (record_id) {
				var config = this.get('configuration'),
					uri = config.getDataPath('delete'),
					
					post_data = {
						'id': record_id,
						'providerId': config.getConfigValue('attributes.id')
					};
				
				if (config.getConfigValue('attributes.locale')) {
					//@TODO Add locale if it's supported
				}
				
				Supra.io(uri, {
					'data': post_data,
					'method': 'post',
					'context': this,
					'on': {
						'success': function () {
							this.widgets.data_grid.remove(record_id);
						}
					}
				});
			}
			
			//Transition back to list mode
			this.set('mode', 'list');
		},
		
	});
	
	(Supra.Crud || (Supra.Crud = {})).PluginList = Plugin;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: [
	'plugin',
	'supra.template',
	'supra.datagrid',
	'supra.datagrid-loader',
	'supra.datagrid-draggable',
	'supra.datagrid-delete',
	'supra.datagrid-new-item'
]});
