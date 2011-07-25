//Invoke strict mode
"use strict";

//Add module definitions
SU.addModule('website.sitemap-flowmap-item-normal', {
	path: 'sitemap/modules/flowmap-item-normal.js',
	requires: ['supra.tree-dragable', 'supra.tree-node-dragable']
});
SU.addModule('website.sitemap-flowmap-item', {
	path: 'sitemap/modules/flowmap-item.js',
	requires: ['website.sitemap-flowmap-item-normal']
});
SU.addModule('website.sitemap-tree-newpage', {
	path: 'sitemap/modules/sitemap-tree-newpage.js',
	requires: ['website.sitemap-flowmap-item']
});
SU.addModule('website.input-template', {
	path: 'sitemap/modules/input-template.js',
	requires: ['supra.input-proto']
});
SU.addModule('website.sitemap-settings', {
	path: 'sitemap/modules/sitemap-settings.js',
	requires: ['supra.panel', 'supra.form', 'website.input-template']
});

SU('supra.languagebar', 'website.sitemap-flowmap-item', 'website.sitemap-flowmap-item-normal', 'website.sitemap-tree-newpage', 'website.sitemap-settings', function (Y) {

	var LOCALE_LANGUAGEBAR_LABEL = 'Viewing structure for:';
	
	//Shortcut
	var Manager = SU.Manager,
		Action = Manager.Action;
	
	//Create Action class
	new Action(Manager.Action.PluginContainer, Manager.Action.PluginSitemapSettings, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'SiteMap',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Language bar widget instance
		 * @type {Object}
		 * @private
		 */
		languagebar: null,
		
		/**
		 * Property panel
		 * @type {Object}
		 * @private
		 */
		panel: null,
		
		/**
		 * Flowmap tree
		 * @type {Object}
		 * @private
		 */
		flowmap: null,
		
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 */
		initialize: function () {
			Manager.getAction('PageContent').initDD();
			
			this.initializeLanguageBar();
			this.initializeFlowMap();
		},
		
		/**
		 * Create language bar
		 * 
		 * @private
		 */
		initializeLanguageBar: function () {
			//Create language bar
			this.languagebar = new SU.LanguageBar({
				'locale': SU.data.get('locale'),
				'contexts': SU.data.get('contexts'),
				'localeLabel': LOCALE_LANGUAGEBAR_LABEL
			});
			
			this.languagebar.on('localeChange', function (evt) {
				if (evt.newVal != evt.prevVal) {
					this.flowmap.reload();
				}
			}, this);
		},
		
		/**
		 * Create flow map
		 * 
		 * @private
		 */
		initializeFlowMap: function () {
			//Create widget
			this.flowmap = new SU.TreeDragable({
				'srcNode': this.one('.flowmap'),
				'requestUri': this.getDataPath() + '?locale=' + SU.data.get('locale'),
				'defaultChildType': Supra.FlowMapItem
			});
			
			this.flowmap.plug(SU.Tree.ExpandHistoryPlugin);
			
			this.flowmap.plug(SU.Tree.NewPagePlugin, {
				'dragNode': this.one('.new-page-button')
			});
			
			//When tree is rendered set selected page
			this.flowmap.after('render:complete', function () {
				var page = Supra.data.get('page', {'id': 0});
				this.flowmap.set('selectedNode', null);
				this.flowmap.set('selectedNode', this.flowmap.getNodeById(page.id));
			}, this);
		},
		
		/**
		 * Handle tree node click event
		 * 
		 * @param {Object} evt Event
		 * @private
		 */
		onTreeNodeClick: function (evt) {
			//Before changing page update locale
			Supra.data.set('locale', this.languagebar.get('locale'));
			
			//Change page
			this.fire('page:select', {
				'data': {
					'id': evt.data.id,
					'version': evt.data.version
				}
			});
			
			//Set selected in data
			Supra.data.set('page', {
				'id': evt.data.id,
				'version': evt.data.version
			});
			
			this.hide();
		},
		
		/**
		 * Render widgets and attach event listeners
		 */
		render: function () {
			//Render language bar
			this.languagebar.render(this.one('.languages'));
			this.flowmap.render();
			
			//Page select event
			this.flowmap.on('node-click', this.onTreeNodeClick, this);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
		}
	});
	
});