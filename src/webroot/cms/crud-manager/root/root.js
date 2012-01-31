//Invoke strict mode
"use strict";

/**
 * Custom modules
 */
SU.addModule('website.providers', {
	path: 'providers/providers.js',
	requires: ['widget', 'website.provider']
});
SU.addModule('website.provider', {
	path: 'providers/provider.js',
	requires: ['widget', 'website.datagrid', 'website.datagrid-bar', 'supra.form']
});

SU.addModule('website.datagrid', {
	path: 'datagrid/datagrid.js',
	skinnable: true,
	requires: ['widget', 'datasource', 'dataschema', 'datatype', 'querystring', 'website.datagrid-row']
});
SU.addModule('website.datagrid-loader', {
	path: 'datagrid/datagrid-loader.js',
	requires: ['plugin', 'website.datagrid']
});
SU.addModule('website.datagrid-dragable', {
	path: 'datagrid/datagrid-dragable.js',
	requires: ['plugin', 'dd-delegate', 'dd-drag', 'dd-proxy', 'dd-drop', 'website.datagrid']
});
SU.addModule('website.datagrid-row', {
	path: 'datagrid/datagrid-row.js',
	requires: ['widget']
});

SU.addModule('website.datagrid-bar', {
	path: 'datagrid-bar/datagrid-bar.js',
	requires: ['widget', 'dd-drag', 'dd-drop']
});

/**
 * Main manager action, initiates all other actions
 */
Supra(
	'supra.slideshow',
	'website.providers',
	'website.datagrid',
	'website.datagrid-loader',
	'website.datagrid-dragable',
	
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
			return this.slideshow.addSlide({'id': 'datagrid-' + id});
		},
		
		/**
		 * Returns container for form
		 * 
		 * @param {String} id Data provider ID
		 * @return Container element
		 * @type {Object}
		 */
		getFormContainer: function (id) {
			return this.slideshow.addSlide({'id': 'form-' + id}).one('div');
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
		},
		
		/**
		 * Set up
		 */
		setup: function (e) {
			Y.one('body').removeClass('loading');
			
			//Create slideshow
			this.slideshow = new Supra.Slideshow();
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