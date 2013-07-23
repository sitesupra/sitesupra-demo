//Invoke strict mode
"use strict";

/**
 * Custom modules
 */
Supra.addModule('website.providers', {
	path: 'providers/providers.js',
	requires: [
		'widget',
		'website.provider'
	]
});
Supra.addModule('website.provider', {
	path: 'providers/provider.js',
	requires: [
		'widget',
		'supra.datagrid',
		'supra.datagrid-loader',
		'supra.datagrid-draggable',
		'website.datagrid-delete',
		'supra.datagrid-new-item',
		'supra.form'
	]
});

Supra.addModule('website.datagrid-delete', {
	path: 'datagrid-delete/datagrid-delete.js',
	requires: [
		'widget',
		'dd-drop'
	]
});

/**
 * Main manager action, initiates all other actions
 */
Supra(
	'supra.slideshow',
	
	'supra.datagrid',
	'supra.datagrid-loader',
	'supra.datagrid-draggable',
	
	'website.providers',
	
function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	
	//Create Action class
	new Action(Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Root',
		
		/**
		 * Action doesn't have stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Action doesn't have template
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
		 * Returns container for data grid
		 * 
		 * @param {String} id Data provider ID
		 * @return Container element
		 * @type {Object}
		 */
		getDataGridContainer: function (id) {
			return this.slideshow.addSlide({
				'id': 'datagrid-' + id,
				'scrollable': false
			}).one('.su-slide-content');
		},
		
		/**
		 * Returns container for form
		 * 
		 * @param {String} id Data provider ID
		 * @return Container element
		 * @type {Object}
		 */
		getFormContainer: function (id) {
			return this.slideshow.addSlide({'id': 'form-' + id})
									.one('.su-slide-content')
										.addClass('ui-light')
										.addClass('ui-light-background');
		},
		
		/**
		 * @constructor
		 */
		initialize: function () {
			//Show loading icon
			Y.one('body').addClass('loading');
			
			//On page unload destroy everything
			Y.on('beforeunload', function () {
			    this.destroy();
			}, this);
			
			Supra.CRUD.Providers.on('ready', this.setup, this);
			Supra.CRUD.Providers.initialize();
			
			//Toolbar buttons
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, [{
				'id': 'filters',
				'type': 'toggle',
				'title': Supra.Intl.get(['crud', 'filter', 'title']),
				'icon': '/cms/lib/supra/img/toolbar/icon-filters.png',
				'action': 'Filters'
			}]);
		},
		
		/**
		 * Set up
		 */
		setup: function (e) {
			Y.one('body').removeClass('loading');
			
			//Create slideshow
			this.slideshow = new Supra.Slideshow({
				'animationUnitType': '%'
			});
			
			this.slideshow.render(this.one());
			
			//Set active provider
			for(var provider_id in e.providers) {
				Supra.CRUD.Providers.setActiveProvider(provider_id);
				break;
			}
			
			//Bind listeners
			var provider = null;
			for(var provider_id in e.providers) {
				provider = e.providers[provider_id];
				provider.after('modeChange', this.handleModeChange, this);
			}
			
			this.slideshow.syncUI();
			this.one().after('contentResize', this.slideshow.syncUI, this.slideshow);
			
			Manager.LayoutRightContainer.on('contentResize', this.slideshow.syncUI, this.slideshow);
			Manager.LayoutLeftContainer.on('contentResize', this.slideshow.syncUI, this.slideshow);
		},
		
		/**
		 * On mode change slide slideshow
		 */
		handleModeChange: function (e) {
			if (e.newVal != e.prevVal) {
				var provider = Supra.CRUD.Providers.getActiveProvider(),
					provider_id = provider.get('id'),
					datagrid = provider.getDataGrid(),
					form = provider.getForm(),
					footer = provider.getFooter();
				
				if (e.newVal == 'list') {
					//Show DataGrid and hide Form after animation
					datagrid.show();
					this.slideshow.scrollTo('datagrid-' + provider_id, function () {
						form.hide();
						footer.hide();
					});
				} else {
					//Show Form and hide DataGrid after animation
					Supra.Manager.executeAction('Form');
					
					form.show();
					footer.show();
					this.slideshow.scrollTo('form-' + provider_id, function () {
						datagrid.hide();
					});
				}
			}
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
		}
	});
	
});