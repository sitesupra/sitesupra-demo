/**
 * Page header action:
 * title, language bar, version
 */
Supra('supra.languagebar', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.Action,
		Root = Manager.getAction('Root');
	
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
		 * Back button
		 * @type {Object}
		 * @private
		 */
		back_button: null,
		
		/**
		 * Has anything changed
		 * @type {Boolean}
		 * @private
		 */
		has_changes: false,
		
		/**
		 * Title stack
		 * @type {Array}
		 * @private
		 */
		titlesIds: [],
		titlesTitles: [],
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 * @private
		 */
		initialize: function () {
			//Create language bar
			this.languagebar = new Supra.LanguageBar({
				'locale': Supra.data.get('locale'),
				'contexts': Supra.data.get('contexts'),
				'visible': Supra.data.get('languageFeaturesEnabled')
			});
			
			//Set available localizations
			var page = Manager.Page.getPageData();
			if (page && page.localizations) {
				this.setAvailableLocalizations(page.localizations, page.global);
			}
			
			// Add back button
			this.back_button = new Supra.Button({
				'label': Supra.Intl.get(['page', 'back_to_sitemap']),
				'style': 'mid'
			});
			
			// On back button click open sitemap
			this.back_button.on('click', Manager.Page.openSiteMap, Manager.Page);
			
			//On change reload page
			this.languagebar.on('localeChange', function (evt) {
				if (evt.newVal != evt.prevVal) {
					var page = Manager.Page.getPageData();
					
					// No page loaded
					if ( ! page) {
						return;
					}
					
					if (page.localizations && evt.newVal in page.localizations) {
						//Change global locale and reload page
						Supra.data.set('locale', evt.newVal);
						
						var pageId = page.localizations[evt.newVal].page_id;
						Root.router.save(Root.ROUTE_PAGE.replace(':page_id', pageId));
					} else {
						//Warning about not exising translation, offer to translate
						Manager.executeAction('Confirmation', {
							'message': '{#page.page_doesnt_exist_in_locale#}',
							'useMask': true,
							'buttons': [{
								'id': 'yes',
								'label': Supra.Intl.get(['buttons', 'yes']),
								'click': this._createLocalization,
								'context': this,
								'args': [true, evt.prevVal, evt.newVal]
							},
							{
								'id': 'no',
								'label': Supra.Intl.get(['buttons', 'no']),
								'click': this._createLocalization,
								'context': this,
								'args': [false, evt.prevVal, evt.newVal]
							}]
						});
					}
				}
			}, this);
		},
		
		/**
		 * Calls localization server method or cancels the process
		 * @param evt {Object}
		 * @param args {Array}
		 * @private
		 */
		_createLocalization: function(evt, args) {
			
			var success = args[0],
				oldLocale = args[1],
				newLocale = args[2];
			
			if ( ! success) {
				this.languagebar.set('locale', oldLocale);
				
				return;
			}
			
			var page = Manager.Page.getPageData(),
				fn;

			if (page.type == 'templates') {
				fn = 'createTemplateLocalization';
			} else {
				fn = 'createPageLocalization';
			}
			
			Manager.Page[fn](page.id, {locale: newLocale}, oldLocale, this._createLocalizationComplete, this);
		},
		
		/**
		 * Opens the newly created localization on success
		 * @param data {Object}
		 * @param status {Boolean}
		 * @private
		 */
		_createLocalizationComplete: function(data, status) {

			if (status) {
				Supra.data.set('locale', data.locale);

				var pageId = data.id;
				Root.router.save(Root.ROUTE_PAGE.replace(':page_id', pageId));
			}
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			this.languagebar.render(this.one('.languages'));
			
			this.back_button.render(this.one('.back'));
			
			if (!Supra.data.get('languageFeaturesEnabled')) {
				this.one('.languages').addClass('hidden');
			}
		},
		
		/**
		 * Set version title
		 * 
		 * @param {String} title Version title
		 * @private
		 */
		setVersionTitle: function (title) {
			if (title == 'autosaved') {
				
				//User modified content
				this.has_changes = true;
				
			} else if (title == 'draft') {
				
				//User saved without making any modifications
				//If page was previously published, then it stays published
				var page_data = Manager.Page.getPageData();
				if (!this.has_changes && page_data && page_data.published) {
					title = 'published';
				}
				
			} else if (title == 'published') {
				
				//User published page
				this.has_changes = false;
				
			}
			
			var version = Supra.Intl.get(['page', 'version_' + title]);
			
			if (Supra.data.get('languageFeaturesEnabled')) {
				version = ', ' + version + '.';
			}
			
			this.one('.version').set('text', version);
		},
		
		/**
		 * Set available locales, only these will be shown in locale dropdown
		 * 
		 * @param {Object} locales
		 */
		setAvailableLocalizations: function (locales, global) {
			if (!this.languagebar) return;
			
			var contexts = Supra.data.get('contexts'),
				context = null,
				item = null,
				filtered = [],
				global = global || false;
			
			if (locales) {
				for(var i=0,ii=contexts.length; i<ii; i++) {
					context = contexts[i];
					
					item = {
						'title': context.title,
						'languages': []
					};
					
					for(var k=0,kk=context.languages.length; k<kk; k++) {
						if (global || context.languages[k].id in locales) {
							item.languages.push(context.languages[k]);
						}
					}
					
					if (item.languages.length) {
						filtered.push(item);
					}
				}
			} else {
				filtered = contexts;
			}
			
			this.languagebar.set('contexts', filtered);
			this.languagebar.set('locale', this.languagebar.get('locale'));
		},
		
		/**
		 * Set title
		 * 
		 * @param {String} id Title group ID
		 * @param {String} title Title text
		 */
		setTitle: function (id, title) {
			var titlesIds = this.titlesIds,
				titlesTitles = this.titlesTitles,
				index = Y.Array.indexOf(titlesIds, id);
			
			if (index != -1) {
				titlesIds.splice(index);
				titlesTitles.splice(index);
			}
			
			titlesIds.push(id);
			titlesTitles.push(title);
			
			this.one('.page-title').set('text', title)
								   .setAttribute('title', title);
		},
		
		/**
		 * Change back button label depending on current application
		 * 
		 * @param {String} id Application Id
		 */
		setApplicationId: function (id) {
			var label  = '',
				app    = 'ContentManager',
				header = Manager.Header;
			
			if (id == 'blog') {
				label = Supra.Intl.get(['page', 'back_to_blog']);
				app = 'BlogManager';
			} else {
				label = Supra.Intl.get(['page', 'back_to_sitemap']);
			}
			
			this.back_button.set('label', label);
		},
		
		/**
		 * Unset title
		 * 
		 * @param {String} id Title group ID
		 * @param {String} title Title text
		 */
		unsetTitle: function (id, title) {
			var titlesIds = this.titlesIds,
				titlesTitles = this.titlesTitles,
				index = Y.Array.indexOf(titlesIds, id);
			
			if (index > 0 && (title === null || title === undefined || titlesTitles[index] == title)) {
				this.setTitle(titlesIds[index - 1], titlesTitles[index - 1]);
			}
		},
		
		/**
		 * Execute action, update data
		 * 
		 * @param {Boolean} ignore_locale_change Don't update locale
		 */
		execute: function (ignore_locale_change) {
			//Don't change locale if page is loading
			if (!ignore_locale_change) {
				var locale = Supra.data.get('locale');
				this.languagebar.set('locale', locale);
			}
			
			//If SiteMap is visible, then don't show header
			if (Manager.getAction('SiteMap').get('visible')) return;
			
			this.show();
			
			var page = Manager.Page.getPageData();
			
			this.setTitle("page", page ? page.title : '');
			this.setApplicationId(page ? page.application_id : null);
			this.setVersionTitle(page && page.published ? 'published' : 'draft');
			this.has_changes = false;
		}
	});
	
});