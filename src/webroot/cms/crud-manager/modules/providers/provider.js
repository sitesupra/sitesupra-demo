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
				tmp = null;
			
			//Set IDs
			for(key in fields) {
				fields[key].id = key;
			}
			
			//Set field list
			for(key=0, len=tmp_list.length; key<len; key++) {
				tmp = fields[tmp_list[key]];
				if (tmp) {
					fields_list.push({
						'id': tmp.id,
						'title': tmp.label
					});
				}
			}
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
				var uri = Supra.Manager.getAction('Form').getDataPath('delete'),
					post_data = {
						'id': record_id,
						'providerId': this.get('id')
					};
				
				if (this.get('locale')) {
					//@TODO Add locale if it's supported
				}
				
				Supra.io(uri, {
					'data': post_data,
					'method': 'post'
				});
				
				this.data_grid.removeRow(record_id);
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
					'requestURI': Supra.Manager.Root.getDataPath('datalist'),
					'requestParams': request_params,
					'columns': this.getListFields(),
					'dataColumns': this.getEditFields(),
					'idColumn': this.get('primaryKey'),
					'tableHeadingFixed': true
				});
				
				this.data_grid.plug(Supra.DataGrid.LoaderPlugin);
				this.data_grid.plug(Supra.DataGrid.DragablePlugin, {
					'dd-sort': this.get('sortable'),
					'dd-insert': this.get('create'),
					'dd-delete': this.get('delete')
				});
				
				this.data_grid.render(container);
				
				this.data_grid.on('row:click', this._handleRecordClick, this);
				this.data_grid.on('drag:sort', this._handleRecordSort, this);
				this.data_grid.on('drag:insert', this._handleRowInsert, this);
				
				/**
				 * Create drag and drop bar
				 */
				this.bar = new Supra.DataGridBar({
					'new-item': this.get('create'),
					'recycle-bin': this.get('delete')
				});
				this.bar.render(container);
				
				this.bar.on('insert:click', this._handleRowInsert, this);
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
					'urlSave': Supra.Manager.getAction('Form').getDataPath('save')
				});
				
				this.form.render(container);
				
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
				
				this.form.setValues(values, 'id');
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
				'record-before': e.recordNext ? e.recordNext.getID() : '',
				'record-after': e.recordPrevious ? e.recordPrevious.getID() : ''
			});
		},
		
		/**
		 * On record sort send to server
		 * 
		 * @private
		 */
		_handleRecordSort: function (e) {
			var uri = Supra.Manager.getAction('Root').getDataPath('sort'),
				data_grid = this.data_grid;
			
			//Update data
			data_grid.addRow(e.record, e.newRecordNext);
			
			Supra.io(uri, {
				'data': {
					'record': e.record.getID(),
					'record-after':  e.newRecordPrevious ? e.newRecordPrevious.getID() : '',
					'record-before': e.newRecordNext ? e.newRecordNext.getID() : ''
				},
				'method': 'post',
				'context': this,
				'on': {
					'failure': function () {
						//Restore previous position
						data_grid.addRow(e.record, e.oldRecordNext);
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
	
	var CRUD = Supra.CRUD = Supra.CRUD || {};
	Supra.CRUD.Provider = Provider;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['widget', 'supra.form', 'website.datagrid', 'website.datagrid-bar']});