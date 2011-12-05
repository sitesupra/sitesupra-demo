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
			'value': true
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
		 * Form instance
		 * @type {Supra.Form}
		 * @private
		 */
		form: null,
		
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
				if (fields[tmp_edit[key]]) {
					fields_edit.push(fields[tmp_edit[key]]);
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
				return data_grid.getRecord(record_id);
			}
			
			return null;
		},
		
		/**
		 * Create data grid
		 * 
		 * @private
		 */
		_createDataGrid: function () {
			if (!this.data_grid) {
				
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
					'idColumn': this.get('primaryKey'),
					'tableHeadingFixed': true
				});
				
				this.data_grid.plug(Supra.DataGrid.LoaderPlugin);
				
				this.data_grid.render(
					Supra.Manager.Root.getDataGridContainer(this.get('id'))
				);
				
			}
		},
		
		/**
		 * Create form
		 * 
		 * @private
		 */
		_createForm: function () {
			if (!this.form) {
				
				this.form = new Supra.Form({
					'inputs': this.getEditFields()
				});
				
				this.form.render(
					Supra.Manager.Root.getFormContainer(this.get('id'))
				);
				
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
			if (!this.get('active') || (state != 'list' && state != 'form')) {
				state = 'list';
			}
			
			if (state == 'form' && this.get('active') && !this.form) {
				this._createForm();
			}
			
			return state;
		}
		
	});
	
	var CRUD = Supra.CRUD = Supra.CRUD || {};
	Supra.CRUD.Provider = Provider;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['widget', 'supra.form', 'website.datagrid']});