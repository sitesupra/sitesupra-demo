//Invoke strict mode
"use strict";

Supra(
	
	'supra.datagrid',
	
function (Y) {
	
	//Shortcuts
	var Manager	= Supra.Manager;
	var Action	= Manager.Action;
	var NAME	= 'CashierSubscriptions';
	
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
		 * DataGrid for standard plans
		 * 
		 * @type {Object}
		 * @private
		 */
		dataGridStandard: null,
		
		/**
		 * Custom options DataGrid
		 * 
		 * @type {Object}
		 * @private
		 */
		dataGridCustom: null,
		
		
		
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			
			var definition = {
				'idColumn': ['id'],
				
				'dataColumns': [
					//ID shouldn't be in the list
					{'id': 'id'}
				],
				
				'columns': [{
					'id': 'title',
					'title': Supra.Intl.get(['cashier', 'subscriptions', 'custom_title']),
					'width': '65%'
				}, {
					'id': 'billing_term',
					'title': Supra.Intl.get(['cashier', 'subscriptions', 'billing_term']),
					'width': '10%'
				}, {
					'id': 'billing_date',
					'title': Supra.Intl.get(['cashier', 'subscriptions', 'next_billing_date']),
					'width': '20%'
				}, {
					'id': 'cancel',
					'title': '',
					'formatter': function (column, value, data) {
						return '<a>' + Supra.Intl.get(['cashier', 'subscriptions', 'cancel']) + '</a>';
					},
					'align': 'right',
					'width': '10%'
				}],
				
				'style': 'dark',
				'scrollable': false
			};
			
			this.dataGridStandard = new Supra.DataGrid(
				Supra.mix({
					'requestURI': this.getDataPath('dev/standard')
				}, definition)
			);
			
			this.dataGridCustom = new Supra.DataGrid(
				Supra.mix({
					'requestURI': this.getDataPath('dev/custom')
				}, definition)
			);
			
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			var place_holder = this.getPlaceHolder();
			
			this.dataGridStandard.render(place_holder);
			this.dataGridCustom.render(place_holder);
			
			this.dataGridStandard.tableBodyNode.delegate('click', this.cancelSubscriptionConfirmation, 'a', this);
			this.dataGridCustom.tableBodyNode.delegate('click', this.cancelSubscriptionConfirmation, 'a', this);
		},
		
		/**
		 * Confirm that user wants to cancel subscription
		 */
		cancelSubscriptionConfirmation: function (e) {
			var row = this.dataGridStandard.getRowByNode(e.target) || this.dataGridCustom.getRowByNode(e.target),
				message = '';
			
			if (!row) return;
			
			message = Supra.Intl.get(['cashier', 'subscriptions', 'confirmation']);
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
							this.cancelSubscription(row.id);
						}
					}, {
						'id': 'no'
					}
				]
			});
		},
		
		/**
		 * Cancel subscription
		 */
		cancelSubscription: function (id) {
			Supra.io(this.getDataPath('dev/cancel'), {
				'data': {'id': id},
				'method': 'post',
				'context': this,
				'on': {
					'success': function () {
						var row = this.dataGridStandard.item(id) || this.dataGridCustom.item(id),
							node = row.getNode();
						
						//Remove padding for nicer animation
						node.all('td').transition({
							'paddingTop': '0px',
							'paddingBottom': '0px',
							'duration': 0.25
						});
						
						node.transition({
							'opacity': 0,
							'duration': 0.25
						}, Y.bind(function () {
							this.dataGridStandard.remove(id);
							this.dataGridCustom.remove(id);
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