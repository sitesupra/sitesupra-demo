//Invoke strict mode
"use strict";

YUI.add('website.provider', function (Y) {
	
	/**
	 * Provider
	 */
	function Provider (attr, config) {
		this.fields = config.fields;
		this.fields_list = config.ui_list;
		this.fields_edit = config.ui_edit;
		this.lists = config.lists;
		this.filters = config.filters;
		
		Provider.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Provider.NAME = 'Provider';
	
	Provider.ATTRS = {
		/**
		 * Unique ID among all providers
		 */
		'id': {
			'value': null
		},
		
		/**
		 * Active state, only one provider can be active at any
		 * given time
		 */
		'active': {
			'value': false,
			'setter': '_setActiveState'
		},
		
		/**
		 * Provider mode, valid modes are 'list' and 'edit'
		 */
		'mode': {
			'value': 'list',
			'setter': '_setModeState'
		},
		
		/**
		 * Title
		 */
		'title': {
			'value': ''
		},
		
		/**
		 * Allow delete functionality
		 */
		'delete': {
			'value': true
		},
		
		/**
		 * Allow create functionality
		 */
		'create': {
			'value': true
		},
		
		/**
		 * Allow manual sorting of records
		 */
		'sortable': {
			'value': true
		},
		
		/**
		 * Enable locale selector
		 */
		'locale': {
			'value': true
		},
		
		/**
		 * Primary key for records
		 */
		'primaryKey': {
			'value': 'id'
		},
		
		/**
		 * Selected record ID
		 */
		'recordId': {
			'value': null
		}
	};
	
	Y.extend(Provider, Y.Base, {
		
		/**
		 * All field configuration
		 * @type {Object}
		 * @private
		 */
		fields: {},
		
		/**
		 * List of fields for list mode
		 * @type {Array}
		 * @private
		 */
		fields_list: [],
		
		/**
		 * List of fields for edit mode
		 * @type {Array}
		 * @private
		 */
		fields_edit: [],
		
		/**
		 * Value lists
		 * @type {Object}
		 * @private
		 */
		lists: {},
		
		/**
		 * Filter list
		 * @type {Object}
		 * @private
		 */
		filters: {},
		
		/**
		 * Form instance
		 * @type {Supra.Form}
		 * @private
		 */
		form: null,
		
		/**
		 * Form footer, Supra.Footer instance
		 * @type {Supra.Footer}
		 * @private
		 */
		footer: null,
		
		/**
		 * Data grid instance
		 * @type {Supra.DataGrid}
		 * @private
		 */
		data_grid: null,
		
		
		
		/**
		 * @private
		 */
		initializer: function () {
			var fields = this.fields,
				tmp_list = this.fields_list,
				tmp_edit = this.fields_edit,
				fields_list = this.fields_list = [],
				fields_edit = this.fields_edit = [],
				lists = this.lists,
				field = null,
				key = null,
				len = 0,
				tmp = null,
				conf = null;
			
			//Set IDs
			for(key in fields) {
				fields[key].id = key;
			}
			
			//Set field list
			for(key=0, len=tmp_edit.length; key<len; key++) {
				field = fields[tmp_edit[key]];
				if (field) {
					//Resolve value lists
					if (field.valuesListId) {
						field.values = lists[field.valuesListId] || [];
					}
					fields_edit.push(field);
				}
			}
			for(key=0, len=tmp_list.length; key<len; key++) {
				tmp = fields[tmp_list[key]];
				if (tmp) {
					conf = {
						'id': tmp.id,
						'title': tmp.label
					};
					
					//Select and SelectList field output should be taken from value list
					if (tmp.type == 'Select' || tmp.type == 'SelectList') {
						conf.values = tmp.values;
						conf.formatter = this._formatSelectColumnValue;
					}
					
					fields_list.push(conf);
				}
			}
		},
		
		/**
		 * Returns form instance
		 * 
		 * @return Form instance for provider
		 * @type {Supra.Form}
		 */
		getForm: function () {
			return this.form;
		},
		
		/**
		 * Returns data grid instance
		 * 
		 * @return DataGrid instance for provider
		 * @type {Supra.DataGrid}
		 */
		getDataGrid: function () {
			return this.data_grid;
		},
		
		/**
		 * Returns footer instance
		 * 
		 * @return Form footer instance
		 * @type {Supra.Footer}
		 */
		getFooter: function () {
			return this.footer;
		},
		
		/**
		 * Returns all field configuration
		 * 
		 * @return All field configuration
		 * @type {Object}
		 */
		getFields: function () {
			return this.fields;
		},
		
		/**
		 * Returns field configuration
		 * 
		 * @return Field configuration
		 * @type {Object}
		 */
		getField: function (field_id) {
			var fields = this.getFields();
			return fields[field_id] || null;
		},
		
		/**
		 * Returns configuration for all list mode fields
		 * 
		 * @return Configuration for list mode fields
		 * @type {Array}
		 */
		getListFields: function () {
			return this.fields_list;
		},
		
		/**
		 * Returns configuration for all edit mode fields
		 * 
		 * @return Configuration for edit mode fields
		 * @type {Array}
		 */
		getEditFields: function () {
			return this.fields_edit;
		},
		
		/**
		 * Return currently edited record ID
		 * 
		 * @return Currently edited record ID
		 * @type {String}
		 */
		getRecordId: function () {
			return this.get('recordId');
		},
		
		/**
		 * Returns currently edited record data
		 * 
		 * @return Currently edited record data
		 * @type {Ojbect}
		 */
		getRecord: function () {
			var record_id = this.get('recordId'),
				data_grid = this.data_grid;
			
			if (record_id && data_grid) {
				return data_grid.getRowByID(record_id).data;
			}
			
			return null;
		},
		
		/**
		 * Delete record
		 * 
		 * @param {String} record_id Record ID
		 */
		deleteRecord: function (record_id) {
			var record_id = record_id || this.get('recordId');
			
			//If in edit mode, then empty record_id is for new item
			if (!record_id && this.get('mode') == 'list') return;
			
			var message = Supra.Intl.get(['crud', 'delete_confirmation']);
			
			Supra.Manager.executeAction('Confirmation', {
				'message': message,
				'useMask': true,
				'buttons': [
					{'id': 'delete', 'label': 'Yes', 'click': function () { this.deleteRecordConfirm(record_id) }, 'context': this},
					{'id': 'no', 'label': 'No'}
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
				var uri = Supra.CRUD.getDataPath('delete'),
					post_data = {
						'id': record_id,
						'providerId': this.get('id')
					};
				
				if (this.get('locale')) {
					//@TODO Add locale if it's supported
				}
				
				Supra.io(uri, {
					'data': post_data,
					'method': 'post',
					'context': this,
					'on': {
						'success': function () {
							this.data_grid.remove(record_id);
						}
					}
				});
			}
			
			//Transition back to list mode
			this.set('mode', 'list');
		},
		
		/**
		 * Create data grid
		 * 
		 * @private
		 */
		_createDataGrid: function () {
			if (!this.data_grid) {
				var container = Supra.Manager.Root.getDataGridContainer(this.get('id'));
				
				//Get request params
				var request_params = {
					'providerId': this.get('id')
				};
				
				//@TODO Initial filter values
				
				if (this.get('locale')) {
					//@TODO Add locale to the request params
				}
				
				this.data_grid = new Supra.DataGrid({
					'requestURI': Supra.CRUD.getDataPath('datalist'),
					'requestParams': request_params,
					'columns': this.getListFields(),
					'dataColumns': this.getEditFields(),
					'idColumn': [this.get('primaryKey')],
					'tableHeadingFixed': false
				});
				
				this.data_grid.plug(Supra.DataGrid.LoaderPlugin, {
					'recordHeight': 40
				});
				this.data_grid.plug(Supra.DataGrid.DraggablePlugin, {
					'dd-sort':   this.get('sortable'),
					'dd-insert': this.get('create'),
					'dd-delete': this.get('delete')
				});
				
				this.data_grid.render(container);
				
				this.data_grid.on('row:click', this._handleRecordClick, this);
				this.data_grid.on('drag:sort', this._handleRecordSort, this);
				this.data_grid.on('drag:insert', this._handleRowInsert, this);
				
				/**
				 * Create new item and delete icons
				 */
				if (this.get('create')) {
					this.data_grid_new = new Supra.DataGridNewItem({
						'newItemLabel': Supra.Intl.get(['crud', 'new_item'])
					});
					
					this.data_grid_new.render(container);
					this.data_grid_new.on('insert:click', this._handleRowInsert, this);
				}
				
				if (this.get('delete')) {
					this.data_grid_delete = new Supra.DataGridDelete();
					this.data_grid_delete.render(container);
				}
				
			}
		},
		
		/**
		 * Format data grid column value
		 * 
		 * @param {String} col_id Column ID
		 * @param {String} value Column text
		 */
		_formatSelectColumnValue: function (col_id, value, data) {
			var column = this.host.getColumn(col_id);
			
			if (column) {
				var values = column.values,
					i = 0,
					ii = values.length;
				
				for(; i<ii; i++) {
					if (values[i].id == value) return Y.Escape.html(values[i].title);
				}
				
				return Y.Escape.html(value || '');
			} else {
				return Y.Escape.html(value || '');
			}
		},
		
		/**
		 * Create form
		 * 
		 * @private
		 */
		_createForm: function () {
			if (!this.form) {
				var container = Supra.Manager.Root.getFormContainer(this.get('id'));
				
				this.form = new Supra.Form({
					'autoDiscoverInputs': false,
					'inputs': this.getEditFields(),
					'urlSave': Supra.CRUD.getDataPath('save')
				});
				
				this.form.render(container);
				this.form.resetValues();
				
				//Footer
				var buttons = [];
				
				if (this.get('delete')) {
					buttons.push({
						'id': 'delete'
					});
				}
				
				this.footer = new Supra.Footer({'buttons': buttons});
				this.footer.render(container);
				
				if (this.get('delete')) {
					this.footer.getButton('delete').on('click', function () {
						this.deleteRecord(this.get('recordId'));
					}, this);
					this.form.on('disabledChange', function (evt) {
						this.footer.getButton('delete').set('disabled', evt.newVal);
					}, this);
				}
			}
		},
		
		/**
		 * Active state setter
		 * 
		 * @param {Boolean} state New state
		 * @return New state
		 * @type {Boolean}
		 * @private
		 */
		_setActiveState: function (state) {
			if (state && !this.data_grid) {
				//Create data grid
				this._createDataGrid();
			}
			
			if (state) {
				// Show or hide buttons
				var button = Supra.Manager.PageToolbar.getActionButton('filters');
				if (this.filters) {
					button.show();
				} else {
					button.hide();
				}
			}
			
			return !!state;
		},
		
		/**
		 * Mode state setter
		 * 
		 * @param {String} state New state
		 * @return New state
		 * @type {String}
		 * @private
		 */
		_setModeState: function (state) {
			//Only active provider can have state 'form'
			if (!this.get('active') || (state != 'list' && state != 'edit')) {
				state = 'list';
			}
			
			if (state == 'edit') {
				var filters = Supra.Manager.getAction('Filters');
				if (filters.get('visible')) {
					Supra.Manager.LayoutContainers.setInstantResize(true);
					filters.hide();
					Supra.Manager.LayoutContainers.setInstantResize(false);
				}
				
				if (!this.form) {
					this._createForm();
				} else {
					this.form.resetValues();
				}
				
				var values = Supra.mix({
					'providerId': this.get('id'),
					'record-before': null,
					'record-after': null
				}, this.getRecord());
				
				this.form.setValuesObject(values, 'id');
			} else {
				//Hide buttons, because we are using slider and form will not be
				//automatically hidden
				Supra.Manager.getAction('PageButtons').unsetActiveAction('Form');
				Supra.Manager.getAction('PageToolbar').unsetActiveAction('Form');
			}
			
			return state;
		},
		
		/**
		 * On record click open form
		 * 
		 * @private
		 */
		_handleRecordClick: function (e) {
			var record_id = e.row.getID();
			if (record_id) {
				this.set('recordId', record_id);
				this.set('mode', 'edit');
			}
		},
		
		/**
		 * 
		 */
		_handleRowInsert: function (e) {
			this.set('recordId', '');
			this.set('mode', 'edit');
			
			this.form.setValues({
				'record-before': e.recordPrevious ? e.recordPrevious.getID() : '',
				'record-after': e.recordNext ? e.recordNext.getID() : ''
			});
		},
		
		/**
		 * On record sort send to server
		 * 
		 * @private
		 */
		_handleRecordSort: function (e) {
			var uri = Supra.CRUD.getDataPath('sort'),
				data_grid = this.data_grid;
			
			//Update data
			data_grid.add(e.record, e.newRecordNext);
			
			Supra.io(uri, {
				'data': {
					'providerId': this.get('id'),
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
		 * Destructor
		 * 
		 * @private
		 */
		destroy: function () {
			Provider.superclass.destroy.apply(this, arguments);
			
			this.data_grid.destroy();
			this.form.destroy();
			this.footer.destroy();
			
			delete(this.fields);
			delete(this.fields_list);
			delete(this.fields_edit);
			delete(this.lists);
		}
		
	});
	
	
	/**
	 * Namespace, get manager data path
	 */
	var CRUD = Supra.CRUD = Supra.CRUD || {
		/**
		 * Returns CRUD manager data path by ID
		 * 
		 * @param {String} id ID
		 * @return Path
		 * @type {String}
		 */
		getDataPath: function (id, default_value) {
			return Supra.data.get(['crudManagerPaths', id], default_value);
		}
	};
	
	Supra.CRUD.Provider = Provider;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['widget', 'supra.datagrid', 'supra.datagrid-loader', 'supra.datagrid-draggable', 'website.datagrid-delete', 'supra.datagrid-new-item', 'supra.form']});