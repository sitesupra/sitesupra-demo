//Invoke strict mode
"use strict";

//Add module definitions
SU.addModule('website.sitemap-tree-node', {
	path: 'sitemap/modules/sitemap-tree-node.js',
	requires: ['supra.tree-dragable']
});

SU('supra.languagebar', 'website.sitemap-tree-node', function (Y) {

	var LOCALE_LANGUAGEBAR_LABEL = 'Viewing structure for:';

	//Shortcut
	var Manager = SU.Manager,
		Action = Manager.Action;
	
	//Create Action class
	new Action({
		
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
		 * Tree widget instance
		 * @type {Object}
		 * @private
		 */
		tree: null,
		
		/**
		 * Language bar widget instance
		 * @type {Object}
		 * @private
		 */
		languagebar: null,
		
		/**
		 * Page select mode (select or insert)
		 * @type {String}
		 * @private
		 */
		mode: 'select',
		
		/**
		 * Selected page node
		 * @type {Object}
		 * @private
		 */
		selectedPageNode: null,
		
		/**
		 * Returns selected page ID or null if none of the pages is selected
		 * 
		 * @return Selected page ID
		 * @type {Number}
		 */
		getSelectedPageID: function () {
			var page = this.getSelectedPageData();
			return page ? page.id : null;
		},
		
		/**
		 * Returns select page data or null if none of the pages is selected
		 * 
		 * @return Select page data
		 * @type {Object}
		 */
		getSelectedPageData: function () {
			var node = this.tree.get('selectedNode');
			return node ? node.get('data') : null;
		},
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 */
		initialize: function () {
			Manager.getAction('PageContent').initDD();
			
			//Create tree
			this.tree = new SU.TreeDragable({
				'srcNode': this.getContainer('.tree'),
				'requestUri': this.getDataPath() + '?locale=' + SU.data.get('locale'),
				'defaultChildType': SU.SitemapTreeNode
			});
			
			this.tree.plug(SU.Tree.ExpandHistoryPlugin);
			
			//Create language bar
			this.languagebar = new SU.LanguageBar({
				'locale': SU.data.get('locale'),
				'contexts': SU.data.get('contexts'),
				
				'localeLabel': LOCALE_LANGUAGEBAR_LABEL
			});
			
			this.languagebar.on('localeChange', function (evt) {
				if (evt.newVal != evt.prevVal) {
					//Reload tree
					this.tree.set('requestUri', this.getDataPath() + '?locale=' + evt.newVal);
					this.tree.reload();
				}
			}, this);
			
			//When action is hidden hide container
			this.on('visibleChange', function (evt) {
				if (evt.newVal != evt.prevVal) {
					if (evt.newVal) {
						this.getContainer().removeClass('hidden');
					} else {
						this.getContainer().addClass('hidden');
					}
				}
			}, this);
		},
		
		/**
		 * Handle tree node click event
		 * 
		 * @param {Object} evt Event
		 * @private
		 */
		onTreeNodeClick: function (evt) {
			if (this.mode == 'select') {
				//Before changing page update locale
				Supra.data.set('locale', this.languagebar.get('locale'));
				
				//Change page
				this.fire('page:select', {
					'data': evt.data
				});
				
				this.hide();
			} else {
				var data = evt.data;
				
				//Restore select mode
				this.mode = 'select';
				this.tree.set('selectedNode', this.selectedPageNode);
				this.button_newpage.set('disabled', false);
				
				//Don't select page
				evt.halt();
			}
		},
		
		/**
		 * Render widgets and bind
		 */
		render: function () {
			//Render tree
			this.tree.render();
			
			//Render language bar
			this.languagebar.render(this.getContainer('.languages'));
			
			//Page select event
			this.tree.on('node-click', this.onTreeNodeClick, this);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
		}
	});
	
});