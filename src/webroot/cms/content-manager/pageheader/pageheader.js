//Invoke strict mode
"use strict";

/**
 * Page header action:
 * title, language bar, version
 */
Supra('supra.languagebar', function (Y) {

	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.Action;
	
	//Create Action class
	new Action(Action.PluginContainer, {
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'PageHeader',
		
		/**
		 * No stylesheet for this action
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * No template for this action
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		/**
		 * Dependancy list
		 * @type {Array}
		 */
		DEPENDANCIES: [],
		
		/**
		 * Content placeholder
		 * @type {Y.Node}
		 */
		PLACE_HOLDER: Y.one('#cmsHeader'),
		
		
		
		/**
		 * Language bar
		 * @type {Object}
		 * @private
		 */
		languagebar: null,
		
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 * @private
		 */
		initialize: function () {
			//Create language bar
			this.languagebar = new SU.LanguageBar({
				'locale': SU.data.get('locale'),
				'contexts': SU.data.get('contexts')
			});
			
			this.languagebar.on('localeChange', function (evt) {
				if (evt.newVal != evt.prevVal) {
					//Change global locale and reload page
					Supra.data.set('locale', evt.newVal);
					Supra.Manager.Page.loadPage(Supra.data.get(['page', 'id']));
				}
			}, this);
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			this.languagebar.render(this.one('.languages'));
		},
		
		/**
		 * Set version title
		 * @private
		 */
		setVersionTitle: function (title) {
			var version = Supra.Intl.get(['page', 'version_' + title]);
			this.one('.version').set('text', version);
		},
		
		/**
		 * Execute action, update data
		 */
		execute: function () {
			//If SiteMap is visible, then don't show header
			if (Supra.Manager.getAction('SiteMap').get('visible')) return;
			
			this.show();
			
			var page = Manager.Page.getPageData();
			this.one('.page-title').set('text', page ? page.title : '')
								   .setAttribute('title', page ? page.title : '');
			
			this.setVersionTitle(page && page.published ? 'published' : 'draft');
			
			var locale = Supra.data.get('locale');
			this.languagebar.set('locale', locale);
		}
	});
	
});