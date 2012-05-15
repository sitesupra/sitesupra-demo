//Invoke strict mode
"use strict";

Supra.addModule('website.input-checkbox-standard', {
	path: 'checkbox/checkbox.js',
	requires: ['supra.input-proto']
});

Supra.addModule('website.input-slider-cashier', {
	path: 'slider/slider.js',
	requires: ['supra.input-proto', 'slider']
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
			
			//Terms and conditions checkbox
			termsInput: null,
			
			//Card list buttons
			payButtons: [],
			
			//New card form
			newCardForm: null,
			
			//New card footer
			newCardFooter: null
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
			Supra.io(this.getDataPath('dev/reciept'), this.renderReceiptData, this);
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
					groups.item(1).removeClass('hidden')
					lists[1].append(node);
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
			
			//Load payment card information
			this.loadCardInformation();
			
			//Render widgets
			if (!this.widgets.termsInput) {
				this.widgets.termsInput = new Supra.Input.CheckboxStandard({
					'srcNode': this.one('#cashierTerms')
				});
				
				this.widgets.termsInput.render();
				
				this.one('div.payments div.cards p a').on('click', this.showNewCardForm, this);
			}
		},
		
		/**
		 * Load payment card information
		 * 
		 * @private
		 */
		loadCardInformation: function () {
			Supra.io(Manager.getAction('CashierCards').getDataPath('dev/cards'), this.renderPaymentForm, this);
		},
		
		/**
		 * Render payment form
		 * 
		 * @param {Object} data Payment card request response data
		 * @private
		 */
		renderPaymentForm: function (data) {
			var container	= this.one('div.payments'),
				results		= this.results,
				
				total		= Supra.data.get(['currency', 'symbol']) + results.total,
				bill		= Supra.Intl.get(['cashier', 'payments', 'to_bill']).replace('{{ period }}', Supra.Intl.get(['cashier', 'payments', results.period + '_bill'])),
				terms		= Supra.Intl.get(['cashier', 'payments', 'terms']).replace('{{ period }}', Supra.Intl.get(['cashier', 'payments', results.period + '_bill'])),
				
				termsInput	= this.widgets.termsInput;
			
			termsInput.get('labelNode').set('innerHTML', terms);
			
			container.one('div.payment-total span b').set('text', total);
			container.one('div.payment-total span small').set('text', bill);
			
			//Render card list
			this.renderCardList(data.results);
			
			//Remove loading style
			container.removeClass('loading');
		},
		
		/**
		 * Render card list
		 * 
		 * @param {Array} cards Card data
		 * @private
		 */
		renderCardList: function (cards) {
			var cardsList	= this.one('div.payments div.cards tbody'),
				cardsTempl	= Supra.Template('cashierCardsItem'),
				buttons		= this.widgets.payButtons;
			
			//Remove button widgets
			for(var i=0,ii=buttons.length; i<ii; i++) buttons[i].destroy();
			this.widgets.payButtons = [];
			
			//Empty content
			cardsList.empty();
			
			//Render new content
			for(var i=0,ii=cards.length; i<ii; i++) {
				cardsList.append(cardsTempl(cards[i]));
			}
			
			//Initialize button widgets
			cardsList.all('button').each(function (button) {
				button = new Supra.Button({'srcNode': button});
				button.render();
				button.on('click', this.pay, this);
				
				this.widgets.payButtons.push(button);
			}, this);
		},
		
		/**
		 * Show new card form
		 * 
		 * @private
		 */
		showNewCardForm: function () {
			this.renderNewCardForm();
			
			var cardsContainer	= this.one('div.payments div.cards'),
				formContainer	= this.one('div.payments form.new-card');
			
			cardsContainer.transition({
				'opacity': 0,
				'duration': 0.25
			}, Y.bind(function () {
				cardsContainer.addClass('hidden');
				formContainer.removeClass('hidden').setStyles({
					'opacity': 0
				}).transition({
					'opacity': 1,
					'duration': 0.25
				})
			}, this));
		},
		
		/**
		 * Start payment
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		pay: function (e) {
			var button	= e.target,
				data	= {},
				terms	= this.widgets.termsInput;
			
			if (!terms.get('value')) {
				terms.set('error', true);
				return false;
			} else {
				terms.set('error', false);
			}
			
			this.disablePaymentUI();
			button.set('loading', true);
			
			//Get post data
			data.card_id  = button.get('boundingBox').closest('tr').getAttribute('data-id');
			data.products = Y.Array.map(this.getSelectedOptions(), function (item) {
				return item.id;
			});
			
			Supra.io(this.getDataPath('dev/pay'), {
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
		 * Render new card form
		 * 
		 * @private
		 */
		renderNewCardForm: function () {
			if (this.widgets.newCardForm) return;
			
			var form = this.widgets.newCardForm = new Supra.Form({
				'srcNode': this.one('div.payments form.new-card')
			});
			
			var footer = this.widgets.newCardFooter = new Supra.Footer({
				'srcNode': this.one('div.payments form.new-card div.footer')
			});
			
			footer.render();
			form.render();
			
			form.on('submit', this.payWithNewCard, this);
		},
		
		/**
		 * Start payment with new card
		 * 
		 * @private
		 */
		payWithNewCard: function () {
			var form	= this.widgets.newCardForm,
				inputs	= form.getInputs('name'),
				values	= form.getSaveValues('name'),
				terms	= this.widgets.termsInput,
				data	= {};
			
			if (!terms.get('value')) return terms.set('error', true);
			terms.set('error', false);
			
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
			this.widgets.newCardFooter.getButton('save').set('loading', true);
			
			//Get post data
			data.card = values;
			data.products = Y.Array.map(this.getSelectedOptions(), function (item) {
				return item.id;
			});
			
			Supra.io(this.getDataPath('dev/pay'), {
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
			this.widgets.termsInput.set('disabled', false);
			
			//Reload subscription and history lists
			var action = Supra.Manager.getAction('CashierHistory');
			if (action.get('loaded')) action.reload();
			
			var action = Supra.Manager.getAction('CashierSubscriptions');
			if (action.get('loaded')) action.reload();
			
			var action = Supra.Manager.getAction('CashierCards');
			if (action.get('loaded')) action.reload();
			
			//...
			Supra.Manager.executeAction('Confirmation', {
				'message': 'Payment done, what now?',
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
			this.widgets.termsInput.set('disabled', false);
		},
		
		/**
		 * Enable all controls on payment UI
		 * 
		 * @private
		 */
		enablePaymentUI: function () {
			var buttons	= this.widgets.payButtons,
				i		= 0,
				ii		= buttons.length;
			
			for(; i<ii; i++) {
				buttons[i].set('disabled', false).set('loading', false);
			}
			
			this.widgets.termsInput.set('disabled', false);
			
			if (this.widgets.newCardForm) {
				this.widgets.newCardForm.set('disabled', false);
			}
			if (this.widgets.newCardFooter) {
				var button = this.widgets.newCardFooter.getButton('save');
				button.set('disabled', false).set('loading', false);
			}
		},
		
		/**
		 * Disable all controls on payment UI
		 * 
		 * @private
		 */
		disablePaymentUI: function () {
			var buttons	= this.widgets.payButtons,
				i		= 0,
				ii		= buttons.length;
			
			for(; i<ii; i++) {
				buttons[i].set('disabled', true);
			}
			
			this.widgets.termsInput.set('disabled', true);
			
			if (this.widgets.newCardForm) {
				this.widgets.newCardForm.set('disabled', true);
			}
			if (this.widgets.newCardFooter) {
				var button = this.widgets.newCardFooter.getButton('save');
				button.set('disabled', true);
			}
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
		}
	});
	
});