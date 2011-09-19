//Invoke strict mode
"use strict";

/**
 * Main manager action, initiates all other actions
 */
Supra(function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	/**
	 * Default page toolbar buttons
	 * @type {Object}
	 */
	var DEFAULT_TOOLBAR = {
		'Root': [
			{
				'id': 'sitemap',
				'title': SU.Intl.get(['sitemap', 'button']),
				'icon': '/cms/lib/supra/img/toolbar/icon-sitemap.png',
				'action': 'SiteMap',
				'type': 'button'	/* Default is 'toggle' */
			}
		],
		'Page': [
			{
				'id': 'blockbar',
				'title': SU.Intl.get(['insertblock', 'button']),
				'icon': '/cms/lib/supra/img/toolbar/icon-insert.png',
				'action': 'PageInsertBlock',
				'permissions': ['block', 'insert']
			},
			{
				'id': 'history',
				'title': SU.Intl.get(['history', 'button']),
				'icon': '/cms/lib/supra/img/toolbar/icon-history.png',
				'action': 'PageHistory'
			},
			{
				'id': 'settings',
				'title': SU.Intl.get(['settings', 'button']),
				'icon': '/cms/lib/supra/img/toolbar/icon-settings.png',
				'action': 'PageSettings'
			}
		]
	};
	
	/**
	 * Default buttons
	 * @type {Object}
	 */
	var DEFAULT_BUTTONS = {
		'Root': [
			{
				'id': 'edit',
				'callback': function () {
					if (Manager.Page.isPage()) {
						Manager.Page.lockPage();
					} else {
						Manager.Template.lockTemplate();
					}
				}
			},
			{
				'id': 'unlock',
				'visible': false,
				'callback': function () {
					//Force page unlock
					Manager.Page.unlockPage(true);
				}
			}
		]
	};
	
	Supra.Manager.getAction('PageToolbar').set('buttons', DEFAULT_TOOLBAR);
	Supra.Manager.getAction('PageButtons').set('buttons', DEFAULT_BUTTONS);
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Root',
		
		/**
		 * Dependancies
		 * @type {Array}
		 */
		DEPENDANCIES: ['Page', 'Template', 'PageToolbar', 'PageButtons', 'PageContent'],
		
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
		 * Bind Actions together
		 */
		render: function () {
			this.addChildAction('Page');
			
			//Show loading screen until content is loaded (last executed action)
			Y.one('body').addClass('loading');
			
			SU.Manager.getAction('PageContent').after('iframeReady', function () {
				Y.one('body').removeClass('loading');
			});
			
			//On page unload destroy everything???
			Y.on('beforeunload', function () {
			    this.destroy();
			}, this);
			
			//Load page after execute
			this.on('render', function () {
				SU.Manager.executeAction('Page', Supra.data.get('page', {'id': 0}));
				SU.Manager.executeAction('Template');
			});
			
			this.bindSiteMap();
		},
		
		/**
		 * Bind SiteMap action to Page
		 */
		bindSiteMap: function () {
			//When page is selected in sitemap load it
			Manager.getAction('SiteMap').on('page:select', function (evt) {
				//evt.data is in format  {'id': 1}
				Manager.getAction('Page').execute(evt.data);
			}, this);
		},
		
		
		/**
		 * Execute action
		 */
		execute: function () {
			var toolbar = Manager.getAction('PageToolbar'),
				buttons = Manager.getAction('PageButtons'),
				content = Manager.getAction('PageContent');
			
			if (toolbar.get('created')) {
				toolbar.setActiveAction(this.NAME);
			}
			if (buttons.get('created')) {
				buttons.setActiveAction(this.NAME);
			}
			
			if (content.get('created')) { 
				content.stopEditing();
			}
		}
	});
	
});