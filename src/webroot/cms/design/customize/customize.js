//Invoke strict mode
"use strict";

/**
 * Main manager action, initiates all other actions
 */
Supra(function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Customize',
		
		/**
		 * Action doesn't have stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: false,
		
		/**
		 * Action doesn't have template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: false,
		
		/**
		 * Dependancies
		 * @type {Array}
		 */
		DEPENDANCIES: ['Preview'],
		
		
		
		/**
		 * Design data
		 * @type {Array}
		 * @private
		 */
		data: null,
		
		/**
		 * Template
		 * @type {Function}
		 * @private
		 */
		contentTemplate: null,
		
		/**
		 * Description Supra.Scrollable instance
		 * @type {Object}
		 * @private
		 */
		scrollableDescription: null,
		
		/**
		 * Supra.ImageSlider instance
		 * @type {Object}
		 * @private
		 */
		imageSlider: null,
		
		/**
		 * Arrows
		 * @type {Object}
		 * @private
		 */
		nodeArrowNext: null,
		nodeArrowNextVisible: false,
		nodeArrowPrev: null,
		nodeArrowPrevVisible: false,
		
		
		
		/**
		 * Set place holder node
		 */
		create: function () {
			this.set('placeHolderNode', Y.one('#designOverview'));
		},
		
		/**
		 * @constructor
		 */
		initialize: function () {
			//Set buttons
			var icon_path = Manager.Loader.getStaticPath() + Manager.Loader.getBasePath() + '/images/toolbar/';
			
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, [
				{
					'id': 'background',
					'title': Supra.Intl.get(['design', 'toolbar', 'background']),
					'icon': icon_path + 'icon-background.png',
					'action': 'CustomizeSidebar',
					'actionFunction': 'setForm',
					'type': 'button'
				},
				{
					'id': 'menu',
					'title': Supra.Intl.get(['design', 'toolbar', 'menu']),
					'icon': icon_path + 'icon-menu.png',
					'action': 'CustomizeSidebar',
					'actionFunction': 'setForm',
					'type': 'button'
				},
				{
					'id': 'fonts',
					'title': Supra.Intl.get(['design', 'toolbar', 'fonts']),
					'icon': icon_path + 'icon-fonts.png',
					'action': 'CustomizeSidebar',
					'actionFunction': 'setForm',
					'type': 'button'
				},
				{
					'id': 'buttons',
					'title': Supra.Intl.get(['design', 'toolbar', 'buttons']),
					'icon': icon_path + 'icon-buttons.png',
					'action': 'CustomizeSidebar',
					'actionFunction': 'setForm',
					'type': 'button'
				}
			]);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'back',
				'label': Supra.Intl.get(['design', 'toolbar', 'back']),
				'context': this,
				'callback': this.back
			}, {
				'id': 'select',
				'label': Supra.Intl.get(['design', 'toolbar', 'select']),
				'context': this,
				'callback': this.close,
				'style': 'mid-blue'
			}]);
		},
		
		/**
		 * Render widgets, etc.
		 */
		render: function () {
			
		},
		
		
		/*
		 * ------------------------------- API --------------------------------
		 */
		
		
		/**
		 * Open design list
		 */
		back: function () {
			this.hide();
			Manager.executeAction('Preview');
			Manager.executeAction('DesignBar');
		},
		
		/**
		 * Close application
		 */
		close: function () {
			//@TODO
		},
		
		/**
		 * Hide
		 */
		hide: function () {
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			this.set("visible", false);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			Manager.getAction('Preview').execute();
			Manager.getAction('DesignBar').hide();
			Manager.getAction('PageToolbar').setActiveAction(this.NAME);
			Manager.getAction('PageButtons').setActiveAction(this.NAME);
		}
	});
	
});