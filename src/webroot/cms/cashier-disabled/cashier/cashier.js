//Invoke strict mode
"use strict";

Supra(
	
	'supra.slideshow',
	
function (Y) {
	
	//Toolbar buttons
	var TOOLBAR_BUTTONS = [
	    {
	        'id': 'CashierReceipts',
			'title': Supra.Intl.get(['cashier', 'title_receipts']),
			'icon': '/cms/lib/supra/img/toolbar/icon-history.png',
			'action': 'CashierReceipts',
			'type': 'tab'
	    },
	    /*{
	        'id': 'CashierSubscriptions',
			'title': Supra.Intl.get(['cashier', 'title_subscriptions']),
			'icon': '/cms/lib/supra/img/toolbar/icon-history.png',
			'action': 'CashierSubscriptions',
			'type': 'tab'
	    },*/
	    {
	        'id': 'CashierHistory',
			'title': Supra.Intl.get(['cashier', 'title_history']),
			'icon': '/cms/lib/supra/img/toolbar/icon-history.png',
			'action': 'CashierHistory',
			'type': 'tab'
	    }
	];
	
	//Shortcuts
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	//Create Action class
	new Action(Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'Cashier',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		/**
		 * Dependancies
		 * @type {Array}
		 */
		DEPENDANCIES: ['PageToolbar', 'PageButtons'],
		
		
		
		/**
		 * Slideshow object
		 * @type {Object}
		 * @private
		 */
		slideshow: null,
		
		
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			
		},
		
		getSlide: function (id) {
			return this.slideshow.getSlide(id).one('.su-slide-content');
		},
		
		setSlide: function (id) {
			this.slideshow.set('slide', id);
			
			//Update buttons
			var buttons = Supra.Manager.getAction('PageToolbar').getActionButtons('Cashier'),
				i = 0,
				ii = buttons.length;
			
			for(; i<ii; i++) {
				if (buttons[i].get('topbarButtonId') != id) {
					buttons[i].set('down', false);
				}
			}
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			//Add buttons to toolbar
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, TOOLBAR_BUTTONS);
			
			//Add side buttons
			if (Supra.data.get(['application', 'id']) == 'cashier') {
				//Cashier manager -> can't be closed
				Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			} else {
				//Opened from other manager -> can be closed
				Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
					'id': 'done',
					'context': this,
					'callback': function () {
						this.hide();
					}
				}]);
			}
			
			//Create slideshow
			this.slideshow = new Supra.Slideshow();
			this.slideshow.render(this.one());
			
			this.slideshow.addSlide('CashierReceipts');
			/* this.slideshow.addSlide('CashierSubscriptions'); */
			this.slideshow.addSlide('CashierHistory');
			
			//After resize update slideshow
			this.one().after('contentResize', this.slideshow.syncUI, this.slideshow);
		},
		
		/**
		 * Hide
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			Manager.getAction('PageToolbar').setActiveAction(this.NAME);
			Manager.getAction('PageButtons').setActiveAction(this.NAME);
			
			Supra.Manager.executeAction('CashierReceipts');
			
			this.show();
		}
	});
	
});