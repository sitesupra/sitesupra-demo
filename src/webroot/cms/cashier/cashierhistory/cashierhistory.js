//Invoke strict mode
"use strict";

Supra(
	
	'supra.datagrid',
	
function (Y) {
	
	//Shortcuts
	var Manager	= Supra.Manager;
	var Action	= Manager.Action;
	var NAME	= 'CashierHistory';
	
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
		 * DataGrid
		 * 
		 * @type {Object}
		 * @private
		 */
		dataGrid: null,
		
		
		
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			
			this.dataGrid = new Supra.DataGrid({
				//Url
				'requestURI': this.getDataPath('dev/history'),
				
				'idColumn': ['id'],
				
				'dataColumns': [
					//ID shouldn't be in the list
					{'id': 'id'}
				],
				
				'columns': [{
					'id': 'date',
					'title': Supra.Intl.get(['cashier', 'history', 'payment_date']),
					'width': '30%'
				}, {
					'id': 'total',
					'title': Supra.Intl.get(['cashier', 'history', 'total']),
				}, {
					'id': 'print',
					'title': '',
					'formatter': function (column, value, data) {
						return '<a>' + Supra.Intl.get(['cashier', 'history', 'print']) + '</a>';
					},
					'align': 'right',
					'width': '10%'
				}],
				
				'style': 'dark',
				'scrollable': false
			});
			
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			var place_holder = this.getPlaceHolder();
			
			this.dataGrid.render(place_holder);
			
			this.dataGrid.tableBodyNode.delegate('click', this.printReceipt, 'a', this);
		},
		
		/**
		 * Confirm that user wants to cancel subscription
		 */
		printReceipt: function (e) {
			var row = this.dataGrid.getRowByNode(e.target);
			if (!row) return;
			
			var data = row.getData();
			//@TODO Do what?
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