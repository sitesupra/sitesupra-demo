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
		 * Template as a string
		 * @type {String}
		 * @private
		 */
		template: '<div class="empty-message hidden"><div><p>' + Supra.Intl.get(['cashier', 'cards', 'empty']) + '</p></div></div>',
		
		
		
		/**
		 * DataGrid
		 * 
		 * @type {Object}
		 * @private
		 */
		dataGrid: null,
		
		/**
		 * "Add new card" button
		 * 
		 * @type {Object}
		 * @private
		 */
		newCardButton: null,
		
		
		
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			
			this.dataGrid = new Supra.DataGrid({
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
				'scrollable': false
			});
			
			this.newCardButton = new Supra.Button({
				'style': 'small-blue',
				'label': Supra.Intl.get(['cashier', 'cards', 'add'])
			});
			
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			var place_holder = this.getPlaceHolder();
			
			this.dataGrid.render(place_holder);
			this.newCardButton.render(place_holder);
			
			this.dataGrid.on('row:remove', this.afterRowRemove, this);
			this.dataGrid.on('load:success', this.afterDataGridLoad, this);
		},
		
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
			var row = this.dataGrid.getRowByNode(td),
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
						var row = this.dataGrid.item(id),
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
							this.dataGrid.remove(id);
						}, this));
					}
				}
			})
		},
		
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