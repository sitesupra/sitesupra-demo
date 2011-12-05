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
	requires: ['widget', 'website.datagrid', 'supra.form']
});

SU.addModule('website.datagrid', {
	path: 'datagrid/datagrid.js',
	skinnable: true,
	requires: ['widget', 'datasource', 'dataschema', 'datatype', 'querystring', 'website.datagrid-row']
});
SU.addModule('website.datagrid-loader', {
	path: 'datagrid/datagrid-loader.js',
	skinnable: true,
	requires: ['plugin', 'website.datagrid']
});
SU.addModule('website.datagrid-row', {
	path: 'datagrid/datagrid-row.js',
	requires: ['widget']
});


/**
 * Main manager action, initiates all other actions
 */
Supra('supra.slideshow', 'website.providers', 'website.datagrid', 'website.datagrid-loader', function (Y) {

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
			return this.slideshow.addSlide('datagrid-' + id);
		},
		
		/**
		 * Returns container for form
		 * 
		 * @param {String} id Data provider ID
		 * @return Container element
		 * @type {Object}
		 */
		getFormContainer: function (id) {
			return this.slideshow.addSlide('form-' + id);
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
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
		}
	});
	
});