//Invoke strict mode
"use strict";

Supra.addModule('website.input-checkbox-standard', {
	path: 'checkbox/checkbox.js',
	requires: ['supra.input-proto']
});

Supra.addModule('website.input-slider-cashier', {
	path: 'slider/slider.js',
	requires: ['supra.input-proto', 'transition', 'slider']
});

Supra(
	
	'datatype-number-format',
	'website.input-checkbox-standard',
	'website.input-slider-cashier',
	
function (Y) {
	
	//Shortcuts
	var Manager	= Supra.Manager;
	var Action	= Manager.Action;
	var NAME	= 'CashierReceipts';
	
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
		 * @type {Object}
		 * @private
		 */
		widgets: {
			//Custom option scrollable content
			listScrollable: null,
			
			//Checkbox inputs
			checkboxes: [],
			
			//Period slider
			slider: null,
			
			//Buy button
			buyButton: null,
			
			//Recurring payment checkbox
			recurringInput: null,
			
			//New card form
			cardForm: null,
			
			//New card footer
			cardFooter: null
		},
		
		/**
		 * Receipt data
		 * @type {Object}
		 * @private
		 */
		data: null,
		
		/**
		 * Calculated results
		 * @type {Object}
		 * @private
		 */
		results: {},
		
		
		
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			this.widgets.listScrollable = new Supra.Scrollable({
				'srcNode': this.one('div.receipt div.scrollable')
			});
			
			this.widgets.buyButton = new Supra.Button({
				'srcNode': this.one('div.total-results button'),
				'style': 'buy'
			});
			
			//Render slider
			this.widgets.slider = new Supra.Input.SliderCashier({
				'srcNode': this.one('div.total-form select')
			});
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			//Render scrollbar
			this.widgets.listScrollable.render();
			
			//Buy button
			this.widgets.buyButton.render();
			this.widgets.buyButton.on('click', this.showPaymentForm, this);
			
			//Slider
			this.widgets.slider.render();
			this.widgets.slider.after('valueChange', this.calculateReceiptAmount, this);
			
			//Load data
			this.loadReceiptData();
		},
		
		
		/*
		 * ----------------------------------- Receipt -----------------------------------
		 */
		
		
		/**
		 * Load receipt data
		 * 
		 * @private
		 */
		loadReceiptData: function () {
			Supra.io(this.getDataPath('dev/current-payments-status'), this.renderReceiptData, this);
		},
		
		/**
		 * Render receipt data
		 * 
		 * @param {Object} data Response data
		 * @private
		 */
		renderReceiptData: function (data) {
			var groups = this.all('div.receipt div.group'),
				subscriptions = data.subscriptions,
				lists = [],
				i = 0,
				ii = groups.size(),
				template = Supra.Template('cashierReceiptItem'),
				node = null;
			
			this.data = data;
			
			//Hide groups
			for (; i<ii; i++) {
				groups.item(i).addClass('hidden');
				lists.push(groups.item(i).one('ul').empty());
			}
			
			//Render items
			for(i=0, ii=subscriptions.length; i<ii; i++) {
				node = Y.Node.create(
							template(
								Supra.mix({
									'price_formatted': Supra.data.get(['currency', 'symbol']) + subscriptions[i].price
								}, subscriptions[i])
							)
						);
				
				if (subscriptions[i].group) {
					//Show group and add item
					groups.item(0).removeClass('hidden')
					lists[0].append(node);
					
					//Change group title
					groups.item(0).one('h3').set('text', subscriptions[i].group);
				} else {
					//Show group and add item
					/*
					groups.item(1).removeClass('hidden')
					lists[1].append(node);
					*/
				}
			}
			
			//Render checkboxes
			this.renderReceiptCheckboxWidgets();
			
			//Show content
			this.one('div.receipt').removeClass('loading');
			
			//Enable "BUY" button
			this.widgets.buyButton.set('disabled', false);
			
			//Update scrollbar
			this.widgets.listScrollable.syncUI();
			
			//Calculate results
			this.calculateReceiptAmount();
		},
		
		/**
		 * Render checkbox widgets for receipt
		 * 
		 * @private
		 */
		renderReceiptCheckboxWidgets: function () {
			var inputs		= this.all('div.receipt input[type="checkbox"]'),
				i			= 0,
				ii			= inputs.size(),
				checkbox	= null,
				checkboxes	= this.widgets.checkboxes;
			
			for(; i<ii; i++) {
				checkbox = new Supra.Input.CheckboxStandard({
					'srcNode': inputs.item(i)
				});
				
				checkbox.render();
				checkbox.after('valueChange', this.calculateReceiptAmount, this);
				checkboxes.push(checkbox);
			}
		},
		
		
		/*
		 * ----------------------------------- Payment -----------------------------------
		 */
		
		
		/**
		 * Show payment form
		 * 
		 * @private
		 */
		showPaymentForm: function () {
			//"0" can't be payed
			if (!this.results || !this.results.total) return;
			
			this.one('#cashierClipboardReceipt').addClass('hidden');
			this.one('#cashierClipboardPay').removeClass('hidden');
			
			//Animate
			var clipboard	= this.one('#cashierClipboard'),
				total		= this.one('#cashierTotal');
			
			total.transition({
				'opacity': 0,
				'marginLeft': '-145px',
				'duration': 0.5
			});
			
			clipboard.transition({
				'marginLeft': '-255px',
				'duration': 0.5
			});
			
			//Render form
			this.renderCardForm();
			this.setPaymentFormLabels();
		},
		
		/**
		 * Set payment form labels
		 * 
		 * @private
		 */
		setPaymentFormLabels: function () {
			var container		= this.one('div.payments'),
				results			= this.results,
				
				total			= Supra.data.get(['currency', 'symbol']) + results.total,
				bill			= Supra.Intl.get(['cashier', 'payments', 'to_bill']).replace('{{ period }}', Supra.Intl.get(['cashier', 'payments', results.period + '_bill'])),
				recurring		= Supra.Intl.get(['cashier', 'payments', 'recurring']).replace('{{ period }}', Supra.Intl.get(['cashier', 'payments', results.period + '_bill'])),
				
				recurringInput	= this.widgets.recurringInput;
			
			recurringInput.get('labelNode').set('innerHTML', recurring);
			
			container.one('div.payment-total span b').set('text', total);
			container.one('div.payment-total span small').set('text', bill);
			
			//Remove loading style
			container.removeClass('loading');
		},
		
		/**
		 * Render new card form
		 * 
		 * @private
		 */
		renderCardForm: function () {
			if (this.widgets.cardForm) return;
			
			var recurring = this.widgets.recurringInput = new Supra.Input.CheckboxStandard({
				'srcNode': this.one('#cashierRecurring')
			});
			
			var form = this.widgets.cardForm = new Supra.Form({
				'srcNode': this.one('div.payments form.new-card')
			});
			
			var footer = this.widgets.cardFooter = new Supra.Footer({
				'srcNode': this.one('div.payments form.new-card div.footer')
			});
			
			recurring.render();
			footer.render();
			form.render();
			
			form.on('submit', this.pay, this);
		},
		
		/**
		 * Start payment with new card
		 * 
		 * @private
		 */
		pay: function () {
			var form		= this.widgets.cardForm,
				inputs		= form.getInputs('name'),
				values		= form.getSaveValues('name'),
				recurring	= this.widgets.recurringInput,
				data		= {},
				uri			= '';
			
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
			
			this.disablePaymentUI();
			this.widgets.cardFooter.getButton('save').set('loading', true);
			
			//Get post data
			data.period = this.widgets.slider.get('value');
			data.card = values;
			data.products = Y.Array.map(this.getSelectedOptions(), function (item) {
				return item.id;
			});
			
			if (recurring.get('value')) {
				uri = this.getDataPath('dev/start-recurring-payment');
			} else {
				uri = this.getDataPath('dev/make-single-payment');
			}
			
			Supra.io(uri, {
				'method': 'post',
				'data': data,
				'context': this,
				'on': {
					'success': this.paySuccess,
					'failure': this.payFailure
				}
			});
		},
		
		/**
		 * On sucessful payment ...
		 * 
		 * @param {Object} data Request response data
		 * @param {Boolean} status Response status
		 * @private
		 */
		paySuccess: function (data, status) {
			this.enablePaymentUI();
			
			//Reload subscription and history lists
			var action = Supra.Manager.getAction('CashierHistory');
			if (action.get('loaded')) action.reload();
			
			var action = Supra.Manager.getAction('CashierSubscriptions');
			if (action.get('loaded')) action.reload();
			
			//...
			Supra.Manager.executeAction('Confirmation', {
				'message': 'Payment done, need confirmation screen!!!',
				'buttons': [
					{'id': 'ok', 'label': 'Ok'}
				]
			});
		},
		
		/**
		 * On unsucessful payment revert UI changes
		 * 
		 * @param {Object} data Request response data
		 * @param {Boolean} status Response status
		 * @private
		 */
		payFailure: function (data, status) {
			this.enablePaymentUI();
		},
		
		/**
		 * Enable all controls on payment UI
		 * 
		 * @private
		 */
		enablePaymentUI: function () {
			this.widgets.recurringInput.set('disabled', false);
			this.widgets.cardForm.set('disabled', false);
			this.widgets.cardFooter.getButton('save')
										.set('disabled', false)
										.set('loading', false);
		},
		
		/**
		 * Disable all controls on payment UI
		 * 
		 * @private
		 */
		disablePaymentUI: function () {
			this.widgets.recurringInput.set('disabled', true);
			this.widgets.cardForm.set('disabled', true);
			this.widgets.cardFooter.getButton('save')
										.set('disabled', true);
		},
		
		
		/*
		 * ----------------------------------- API -----------------------------------
		 */
		
		
		/**
		 * Returns all selected options
		 * 
		 * @return Selected option data
		 * @type {Array}
		 */
		getSelectedOptions: function () {
			var options = [],
				checkboxes = this.widgets.checkboxes,
				i = 0,
				ii = checkboxes.length,
				
				data = this.data.subscriptions;
			
			for(; i<ii; i++) {
				if (checkboxes[i].get('value')) {
					options.push(data[i]);
				}
			}
			
			return options;
		},
		
		/**
		 * Returns selected period
		 * 
		 * @return Selected period
		 * @type {Number}
		 */
		getSelectedPeriod: function () {
			return parseInt(this.widgets.slider.get('value'), 10);
		},
		
		/**
		 * On selected option change update stuff
		 * 
		 * @private
		 */
		calculateReceiptAmount: function () {
			var period			= this.getSelectedPeriod(),
				options			= this.getSelectedOptions(),
				discount_perc	= this.data.discounts[period],
				discount		= 0,
				sum				= 0,
				total			= 0;
			
			for(var i=0,ii=options.length; i<ii; i++) {
				sum += options[i].price * period;
			}
			
			discount = Math.floor(sum * discount_perc * 100) / 100;
			total = sum - discount;
			
			//Save results
			this.results = {
				'sum': sum,
				'discount': discount,
				'total': total,
				'period': period
			};
			
			//Update UI
			var node_results	= this.one('div.total-results'),
				node_save		= node_results.one('div.save b'),
				node_pay		= node_results.one('div.pay b'),
				currency_symbol	= Supra.data.get(['currency', 'symbol']);
			
			node_save.set('text', currency_symbol + discount);
			node_pay.set('text', currency_symbol + total);
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
			Supra.Manager.executeAction('CashierSubscriptions');
		}
	});
	
});