//Invoke strict mode
"use strict";

Supra(
	
	'supra.datagrid',
	
function (Y) {
	
	//Shortcuts
	var Manager	= Supra.Manager;
	var Action	= Manager.Action;
	var NAME	= 'CashierCards';
	
	//When Cashier is closed close also this one
	Supra.Manager.getAction('Cashier').addChildAction(NAME);
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: NAME,
		
		/**
		 * Placeholder node
		 * @type {Object}
		 * @private
		 */
		PLACE_HOLDER: Supra.Manager.getAction('Cashier').getSlide(NAME),
		
		/**
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		
		
		/**
		 * Widget list
		 */
		widgets: {
			//DataGrid
			dataGrid: null,
			//"Add new card" button
			newCardButton: null,
			//New card form
			newCardForm: null,
			//New card form footer
			newCardFooter: null
		},
		
		
		
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			
			this.widgets.dataGrid = new Supra.DataGrid({
				//Url
				'requestURI': this.getDataPath('dev/cards'),
				
				'idColumn': ['id'],
				
				'dataColumns': [
					//ID shouldn't be in the list
					{'id': 'id'}
				],
				
				'columns': [{
					'id': 'number',
					'title': Supra.Intl.get(['cashier', 'cards', 'number']),
					'width': '30%'
				}, {
					'id': 'expires',
					'title': Supra.Intl.get(['cashier', 'cards', 'expires']),
				}, {
					'id': 'delete',
					'title': '',
					'renderer': Y.bind(this.dataGridRenderButton, this),
					'align': 'right',
					'width': '10%'
				}],
				
				'style': 'dark',
				'scrollable': false,
				
				'srcNode': this.one('div.datagrid')
			});
			
			this.widgets.newCardButton = new Supra.Button({
				'srcNode': this.one('button')
			});
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			this.widgets.dataGrid.render();
			this.widgets.dataGrid.on('row:remove', this.afterRowRemove, this);
			this.widgets.dataGrid.on('load:success', this.afterDataGridLoad, this);
			
			this.widgets.newCardButton.render();
			this.widgets.newCardButton.on('click', this.showNewCardForm, this);
		},
		
		
		/*
		 * ----------------------------------- Card list -----------------------------------
		 */
		
		
		/**
		 * After row is removed hide data grid if there are no more rows
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		afterRowRemove: function (e) {
			var datagrid = e.row.host,
				rows = datagrid.getAllRows();
			
			if (!rows.length) {
				datagrid.hide();
				this.one('div.empty-message').removeClass('hidden');
			}
		},
		
		/**
		 * After data grid data is loaded check if there are any records
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		afterDataGridLoad: function (e) {
			if (!e.results.length) {
				e.target.hide();
				this.one('div.empty-message').removeClass('hidden');
			}
		},
		
		/**
		 * Render button into datagrid column
		 * 
		 * @param {String} column Column ID
		 * @param {String} value Column value
		 * @param {Object} data All row data
		 * @param {Object} td Y.Node instance for cell
		 * @private
		 */
		dataGridRenderButton: function (column, value, data, td) {
			var button = new Supra.Button({
				'style': 'small-red',
				'label': Supra.Intl.get(['cashier', 'cards', 'delete'])
			});
			
			button.render(td);
			button.on('click', this.deleteCardConfirmation, this, td);
		},
		
		/**
		 * Confirm that user wants to cancel subscription
		 * 
		 * @param {Event} e Event facade object
		 * @param {Object} td Y.Node instance for cell
		 * @private
		 */
		deleteCardConfirmation: function (e, td) {
			var row = this.widgets.dataGrid.getRowByNode(td),
				message = '';
			
			if (!row) return;
			
			message = Supra.Intl.get(['cashier', 'cards', 'confirmation']);
			message = (Supra.Template.compile(message))(row.getData());
			
			Manager.executeAction('Confirmation', {
				'message': message,
				'useMask': true,
				'buttons': [
					{
						'id': 'yes',
						'style': 'small-red',
						'context': this,
						'click': function () {
							this.deleteCard(row.id);
						}
					}, {
						'id': 'no'
					}
				]
			});
		},
		
		/**
		 * Cancel subscription
		 * 
		 * @param {String} id Card id
		 * @private
		 */
		deleteCard: function (id) {
			Supra.io(this.getDataPath('dev/delete'), {
				'data': {'id': id},
				'method': 'post',
				'context': this,
				'on': {
					'success': function () {
						var row = this.widgets.dataGrid.item(id),
							node = row.getNode();
						
						//Remove padding for nicer animation
						node.all('td').transition({
							'paddingTop': '0px',
							'paddingBottom': '0px',
							'duration': 0.25
						});
						
						//Fade out row which will be removed
						node.transition({
							'opacity': 0,
							'duration': 0.25
						}, Y.bind(function () {
							this.widgets.dataGrid.remove(id);
						}, this));
					}
				}
			})
		},
		
		
		/*
		 * ----------------------------------- New card -----------------------------------
		 */
		
		
		/**
		 * Render new card form
		 * 
		 * @private
		 */
		renderNewCardForm: function () {
			if (this.widgets.newCardForm) return this.widgets.newCardForm;
			
			var node	= this.one('form.new-card'),
				form	= this.widgets.newCardForm = new Supra.Form( {'srcNode': node} ),
				footer	= this.widgets.newCardFooter = new Supra.Footer( {'srcNode': node.one('div.footer')} );
			
			node.removeClass('hidden');
			
			form.render();
			form.hide();
			form.on('submit', this.saveNewCard, this);
			
			footer.render();
			
			return form;
		},
		
		/**
		 * Show new card form
		 * 
		 * @private
		 */
		showNewCardForm: function () {
			var form = this.renderNewCardForm();
			
			form.resetValues();
			form.show();
			
			form.get('boundingBox')
					.setStyles({'display': 'block', 'opacity': 0})
					.transition({'opacity': 1, 'duration': 0.35});
			
			this.widgets.newCardButton.hide();
		},
		
		/**
		 * Hide new card form
		 * 
		 * @private
		 */
		hideNewCardForm: function () {
			var form = this.widgets.newCardForm;
			
			form.get('boundingBox')
					.transition({'opacity': 0, 'duration': 0.35}, Y.bind(function () {
						form.hide();
					}, this));
			
			this.widgets.newCardButton.show();
		},
		
		/**
		 * Save new card
		 * 
		 * @private
		 */
		saveNewCard: function () {
			var form	= this.widgets.newCardForm,
				inputs	= form.getInputs('name'),
				values	= form.getSaveValues('name'),
				data	= {};
			
			if (!values.name) return inputs.name.set('error', true);
			inputs.name.set('error', false);
			
			if (!values.number) return inputs.number.set('error', true);
			inputs.number.set('error', false);
			
			if (!values.valid_till_month) return inputs.valid_till_month.set('error', true);
			inputs.valid_till_month.set('error', false);
			
			if (!values.valid_till_year) return inputs.valid_till_year.set('error', true);
			inputs.valid_till_year.set('error', false);
			
			if (!values.cvc) return inputs.cvc.set('error', true);
			inputs.cvc.set('error', false);
			
			this.disableNewCardUI();
			
			//Get post data
			data.card = values;
			
			Supra.io(this.getDataPath('dev/create'), {
				'method': 'post',
				'data': data,
				'context': this,
				'on': {
					'success': this.newCardSuccess,
					'failure': this.newCardFailure
				}
			});
		},
		
		/**
		 * On sucessful creation
		 * 
		 * @param {Object} data Request response data
		 * @param {Boolean} status Response status
		 * @private
		 */
		newCardSuccess: function (data, status) {
			this.enableNewCardUI();
			
			this.widgets.dataGrid.add(data);
			this.hideNewCardForm();
		},
		
		/**
		 * On unsucessful creation revert UI changes
		 * 
		 * @param {Object} data Request response data
		 * @param {Boolean} status Response status
		 * @private
		 */
		newCardFailure: function (data, status) {
			this.enableNewCardUI();
		},
		
		/**
		 * Disable new card UI
		 * 
		 * @private
		 */
		disableNewCardUI: function () {
			this.widgets.newCardForm.set('disabled', true);
			this.widgets.newCardFooter.getButton('save').set('loading', true);
		},
		
		/**
		 * Enable new card UI
		 * 
		 * @private
		 */
		enableNewCardUI: function () {
			this.widgets.newCardForm.set('disabled', false);
			this.widgets.newCardFooter.getButton('save').set('loading', false);
		},
		
		
		/*
		 * ----------------------------------- API -----------------------------------
		 */
		
		
		/**
		 * Hide
		 */
		hide: function () {
			
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
			
			Supra.Manager.getAction('Cashier').setSlide(this.NAME);
		}
	});
	
});