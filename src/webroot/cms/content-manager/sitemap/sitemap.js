//Invoke strict mode
"use strict";

//Add module definitions
Supra.addModule('website.sitemap-tree', {
	path: 'sitemap/modules/tree/tree.js',
	requires: ['widget']
});

Supra.addModule('website.sitemap-tree-node', {
	path: 'sitemap/modules/tree/tree-node.js',
	requires: ['website.sitemap-tree', 'supra.template', 'dd']
});
Supra.addModule('website.sitemap-tree-node-fake', {
	path: 'sitemap/modules/tree/tree-node-fake.js',
	requires: ['website.sitemap-tree', 'supra.template', 'dd', 'website.sitemap-tree-node']
});
Supra.addModule('website.sitemap-tree-node-list', {
	path: 'sitemap/modules/tree/tree-node-list.js',
	requires: ['website.sitemap-tree-node-app', 'supra.datagrid', 'supra.datagrid-loader']
});
Supra.addModule('website.sitemap-tree-node-app', {
	path: 'sitemap/modules/tree/tree-node-app.js',
	requires: ['website.sitemap-tree-node']
});
Supra.addModule('website.sitemap-tree-node-app-news', {
	path: 'sitemap/modules/tree/tree-node-app-news.js',
	requires: ['website.sitemap-tree-node-app']
});

Supra.addModule('website.sitemap-tree-view', {
	path: 'sitemap/modules/tree/tree-view.js',
	requires: ['website.sitemap-tree', 'anim']
});
Supra.addModule('website.sitemap-tree-data', {
	path: 'sitemap/modules/tree/tree-data.js',
	requires: ['website.sitemap-tree']
});
Supra.addModule('website.sitemap-tree-util', {
	path: 'sitemap/modules/tree/tree-util.js',
	requires: ['website.sitemap-tree']
});

Supra.addModule('website.sitemap-plugin-page-edit', {
	path: 'sitemap/modules/plugin-page-edit.js',
	requires: ['supra.input']
});
Supra.addModule('website.sitemap-plugin-page-add', {
	path: 'sitemap/modules/plugin-page-add.js',
	requires: ['supra.input']
});
Supra.addModule('website.sitemap-plugin-page-global', {
	path: 'sitemap/modules/plugin-page-global.js',
	requires: ['supra.input']
});


Supra.addModule('website.sitemap-new-page', {
	path: 'sitemap/modules/new-page.js',
	requires: ['supra.scrollable']
});
Supra.addModule('website.sitemap-delete-page', {
	path: 'sitemap/modules/delete-page.js',
	requires: ['widget', 'dd']
});


Supra(
	'anim', 'transition',
	'website.sitemap-tree',
	'website.sitemap-tree-node', 'website.sitemap-tree-node-fake', 'website.sitemap-tree-node-list', 'website.sitemap-tree-node-app', 'website.sitemap-tree-node-app-news',
	'website.sitemap-tree-view', 'website.sitemap-tree-data', 'website.sitemap-tree-util',
	'website.sitemap-plugin-page-edit', 'website.sitemap-plugin-page-add', 'website.sitemap-plugin-page-global',
	'website.sitemap-new-page', 'website.sitemap-delete-page',
function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.Action;
	
	//Create Action class
	new Action(Manager.Action.PluginContainer, Action.PluginMainContent, {
		
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
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		
		
		
		/**
		 * Tree instance {Supra.Manager.SiteMap.Tree}
		 * @type {Object}
		 * @private
		 */
		tree: null,
		
		/**
		 * New page list, instance of {Supra.Manager.SiteMap.NewPage}
		 * @type {Object}
		 * @private
		 */
		newPage: null,
		
		/**
		 * Recycle bin, instance of {Supra.Manager.SiteMap.DeletePage}
		 * @type {Object}
		 * @private
		 */
		deletePage: null,
		
		/**
		 * Language selector
		 * @type {Object}
		 * @private
		 */
		languageSelector: null,
		
		/**
		 * Sitemap action is executed for first time
		 * @type {Boolean}
		 * @private
		 */
		firstExec: true,
		
		/**
		 * Hiding in progress
		 * @type {Boolean}
		 * @private
		 */
		hiding: false,
		
		/**
		 * Node which is used for animation
		 * @type {Object}
		 * @private
		 */
		animationNode: null,
		
		/**
		 * Animation object
		 * @type {Object}
		 * @private
		 */
		animation: null,
		
		
		
		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 * 
		 * @private
		 */
		initialize: function () {
			//Set locale
			this.locale = Supra.data.get('locale');
			
			//Add buttons to toolbar
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, [{
				'id': 'mode_pages',
				'type': 'button',
				'title': Supra.Intl.get(['sitemap', 'pages']),
				'icon': '/cms/lib/supra/img/toolbar/icon-pages.png',
				
				'action': 'SiteMap',
				'actionFunction': 'setModePages'
			}, {
				'id': 'mode_templates',
				'type': 'button',
				'title': Supra.Intl.get(['sitemap', 'templates']),
				'icon': '/cms/lib/supra/img/toolbar/icon-templates.png',
				
				'action': 'SiteMap',
				'actionFunction': 'setModeTemplates'
			}]);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
		},
		
		/**
		 * Render widgets and attach event listeners
		 * 
		 * @private
		 */
		render: function () {
			//Animation
			this.animationNode = this.one('div.animation-node');
			this.animation = new Y.Anim({
				'node': this.animationNode,
				'duration': 0.5,
				'easing': Y.Easing.easeIn
			});
			
			//Language selector
			this.languageSelector = this.renderLanguageBar();
			
			//Tree
			this.tree = new this.Tree({
				'srcNode': this.one('#tree'),
				'requestURI': this.getDataPath('sitemap'),
				'mode': 'pages',
				'locale': this.locale
			});
			
			//Add plugins
			this.tree.plug(this.PluginTreeUtilities, {});
			this.tree.plug(this.PluginPageEdit, {});
			this.tree.plug(this.PluginPageAdd, {});
			this.tree.plug(this.PluginPageGlobal, {});
			
			//Render
			this.tree.render();
			
			//New page list
			this.newPage = this.renderNewPage();
			
			//Recycle bin
			this.deletePage = this.renderRecycleBin();
			
			//While tree is loading show icon
			this.tree.on('loadingChange', this.handleLoading, this);
			this.tree.on('page:select', this.triggerPageSelect, this);
			this.tree.on('page:move', this.handlePageMove, this)
		},
		
		/**
		 * Render language bar widget
		 * 
		 * @private
		 */
		renderLanguageBar: function () {
			//Get locales
			var contexts = Supra.data.get('contexts'),
				values = [],
				widget = null;
			
			for(var i=0,ii=contexts.length; i<ii; i++) values = values.concat(contexts[i].languages);
			
			//Create widget
			widget = new Supra.Input.SelectList({
				'label': Supra.Intl.get(['sitemap', 'select_language']),
				'values': values,
				'value': this.locale
			});
			
			widget.render(this.one('div.su-sitemap-languages'));
			
			widget.set('value', this.locale);
			widget.after('valueChange', this.handleLocaleChange, this);
			
			return widget;
		},
		
		/**
		 * Render NewPage widget
		 * 
		 * @private
		 */
		renderNewPage: function () {
			var widget = new Manager.SiteMap.NewPage();
			widget.render(this.one());
			
			return widget;
		},
		
		/**
		 * Render recycle bin widget
		 * 
		 * @private
		 */
		renderRecycleBin: function () {
			var widget = new Manager.SiteMap.DeletePage();
			widget.render(this.one());
			
			return widget;
		},
		
		/**
		 * Handle locale change
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		handleLocaleChange: function (e) {
			if (this.tree && !e.silent) {
				this.tree.set('locale', e.newVal);
			}
		},
		
		/**
		 * Trigger page:select event on SiteMap
		 * 
		 * @param {Event} evt Event facade object
		 * @private
		 */
		triggerPageSelect: function (evt) {
			
			evt.data = Supra.mix({}, evt.data);
			if(evt.data.redirect && evt.data.localized && evt.data.redirect_page_id != '') {
				Supra.Manager.executeAction('Confirmation', {
							'message': '{#page.follow_redirect#}',
							'useMask': true,
							'buttons': [{
								'id': 'yes',
								'label': Supra.Intl.get(['buttons', 'yes']),
								'click': this._handleRedirectConfirmation,
								'context': this,
								'args': [true, evt]
							},
							{
								'id': 'no',
								'label': Supra.Intl.get(['buttons', 'no']),
								'click': this._handleRedirectConfirmation,
								'context': this,
								'args': [false, evt]
							}]
						});
			
				return;
			}
			
			this.fire('page:select', {
				'data': evt.data,
				'node': evt.node
			});
		},
		
		/**
		 *
		 */
		_handleRedirectConfirmation: function(e, args) {
			var follow = args[0],
			evt = args[1];
			
			if(follow) {
				evt.data.id = evt.data.redirect_page_id;
			}
			
			this.fire('page:select', {
				'data': evt.data,
				'node': evt.node
			});
		},
		
		/**
		 * Save new page location
		 * 
		 * @param {Event} evt Event facade object
		 * @private
		 */
		handlePageMove: function (evt) {
			var node = evt.node,
				reference = node.next(),
				reference_type = 'before';
			
			if ( ! reference) {
				reference = node.previous();
				reference_type = 'after';
			}
			
			var post_data = {
				//New parent ID
				'parent_id': node.get('root') ? 0 : node.get('parent').get('data').id,
				//Item ID before which drag item was inserted
				'reference_id': reference ? reference.get('data').id : '',
				'reference_type': reference_type,
				//Dragged item ID
				'page_id': node.get('data').id,
				
				//Locale
				'locale': this.languageSelector.get('value')
			};
			
			//Update data full_path
			node.updateFullPath();
			
			//Send request
			Supra.io(this.getDataPath('move'), {
				'data': post_data,
				'method': 'post',
				'context': this,
				'on': {
					'failure': function () {
						//Revert changes
						//@TODO
					}
				}
			});
		},
		
		/**
		 * When tree loading attribute value changes show or hide icon
		 * 
		 * @param {Event} evt Event facade object
		 * @private
		 */
		handleLoading: function (evt) {
			if (evt.newVal != evt.prevVal) {
				if (evt.newVal) {
					this.one().addClass('loading');
				} else {
					this.one().removeClass('loading');
				}
			}
		},
		
		
		
		/**
		 * ------------------------------ ANIMATION ------------------------------
		 */
		
		
		
		/**
		 * Animate sitemap in/out
		 * 
		 * @param {Object} node Node to animate into
		 * @param {Boolean} reverse Reverse animation
		 * @private
		 */
		animate: function (node, reverse) {
			//Visiblity state, set before calculating regions
			if (reverse) {
				this.animationNode.setStyles({'opacity': 1, 'display': 'block', 'left': 0, 'top': 0, 'right': 0, 'bottom': 0});
				this.set('visible', true);
			}
			
			//
			var animationNode = this.animationNode,
				animation     = this.animation,
				
				styles_from   = {},
				styles_to     = {'left': 0, 'top': 0, 'right': 0, 'bottom': 0, 'opacity': 1},
				
				target_reg    = this.one().get('region'),
				
				node          = node || this.tree.get('contentBox'),
				node_reg      = node.get('region');
			
			//Animation styles
			styles_from = {
				'left':    node_reg.left,
				'right':   target_reg.width - node_reg.width - node_reg.left,
				'top':     node_reg.top,
				'bottom':  target_reg.height - node_reg.height - node_reg.top,
				'opacity': 0.35
			};
			
			if (reverse) {
				animation.set('from', styles_to);
				animation.set('to', styles_from);
			} else {
				animation.set('from', styles_from);
				animation.set('to', styles_to);
				
				animationNode.setStyles({'opacity': 0, 'display': 'block'});
			}
			
			animation.run();
			
			animation.once('end', function () {
				if (!reverse) {
					//Fade out
					this.animationNode.transition({
						'opacity': 0,
						'easing': 'ease-out',
    					'duration': 0.25
					}, function () {
						this.setStyle('display', 'none');
					});
					
					this.set('visible', false);
					
					Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
					Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
					
					Manager.executeAction('PageHeader', true);
					
					//Clean up tree
					this.tree.removeAll(null, true);
					
					//State
					this.hiding = false;
				} else {
					Manager.getAction('PageToolbar').setActiveAction(this.NAME);
					Manager.getAction('PageButtons').setActiveAction(this.NAME);
					
					this.animationNode.hide();
				}
			}, this);
			
		},
		
		
		
		/**
		 * ------------------------------ API ------------------------------
		 */
		
		
		
		/**
		 * Set mode to pages
		 * 
		 * @param {Boolean} silent Change mode silently without triggering reload
		 * @param {Boolean} force Force mode change even if current mode is already pages
		 */
		setModePages: function (silent, force) {
			if (this.tree.get('mode') != 'pages' || force === true) {
				this.tree.set('requestURI', this.getDataPath('sitemap'));
				this.tree.set('mode', 'pages', {'silent': true});
				this.newPage.set('mode', 'pages');
				
				Manager.getAction('PageToolbar').getActionButton('mode_pages').set('down', true);
				Manager.getAction('PageToolbar').getActionButton('mode_templates').set('down', false);
				
				if (silent !== true) {
					//Update URI
					var Root = Supra.Manager.Root;
					Root.router.save(Root.ROUTE_SITEMAP);
				}
			}
		},
		
		/**
		 * Set mode to templates
		 * 
		 * @param {Boolean} silent Change mode silently without triggering reload
		 * @param {Boolean} force Force mode change even if current mode is already templates
		 */
		setModeTemplates: function (silent, force) {
			if (this.tree.get('mode') != 'templates' || force === true) {
				this.tree.set('requestURI', this.getDataPath('templates'));
				this.tree.set('mode', 'templates', {'silent': true});
				this.newPage.set('mode', 'templates');
				
				Manager.getAction('PageToolbar').getActionButton('mode_pages').set('down', false);
				Manager.getAction('PageToolbar').getActionButton('mode_templates').set('down', true);
				
				if (silent !== true) {
					//Update URI
					var Root = Supra.Manager.Root;
					Root.router.save(Root.ROUTE_TEMPLATES);
				}
			}
		},
		
		/**
		 * Expand all pages in path
		 * 
		 * @param {Array} path List of pages
		 */
		restoreState: function (path) {
			if (path.length) {
				
				var id = null,
					i = 0,
					ii = path.length,
					
					tree = this.tree,
					data = tree.get('data'),
					item = null,
					next = function () { this.restoreState(path); };
				
				for (; i<ii; i++) {
					id = path[i];
					if (id) {
						item = tree.item(id);
						id = null;
						if (item) {
							path[i] = null;
							
							if (!item.get('expanded')) {
								item.once('expanded', next, this);
								item.set('expanded', true);
							}
						}
					}
				}
				
				if (tree.get('loading')) {
					tree.once('load:complete', next, this);
				}
			}
		},
		
		/**
		 * Returns selected node or root node
		 * 
		 * @return Selected or root node if none is selected or can't be found
		 * @type {Object}
		 */
		getSelectedNode: function () {
			var page_id = Supra.data.get(['page', 'id'], null),
				node = null;
			
			if (page_id) {
				node = this.tree.item(page_id);
				if (node) return node;
			}
			
			return null;
		},
		
		/**
		 * Hide sitemap
		 */
		hide: function () {
			if (this.hiding) return this;
			this.hiding = true;
			
			var page_id = Supra.data.get(['page', 'id'], null),
				node = this.getSelectedNode();
			
			if (node) {
				this.animate(node.get('itemBox'));
			} else {
				this.animate(null);
			}
			
			return this;
		},
		
		/**
		 * Show sitemap
		 */
		show: function () {
			var node = this.getSelectedNode();
			
			if (Manager.getAction('PageContent').get('executed')) {
				//If opening from page
				if (node) {
					this.animate(node.get('itemBox'), true);
				} else {
					this.animate(null, true);
				}
			} else {
				//Opening on load
				this.animationNode.setStyle('display', 'none');
				this.set('visible', true);
				
				Manager.getAction('PageToolbar').setActiveAction(this.NAME);
				Manager.getAction('PageButtons').setActiveAction(this.NAME);
			}
			
			return this;
		},
		
		/**
		 * Execute action
		 */
		execute: function (options) {
			if (!this.get('visible')) {
				this.show();
				
				//Hide page header
				Manager.getAction('PageHeader').hide();
			}
			
			options = Supra.mix({'mode': 'pages'}, options || {});
			
			var page_data = Manager.Page.getPageData(),
				page_locale = null;
			
			if (this.firstExec) {
				this.firstExec = false;
				Y.one('body').removeClass('loading');
			} else {
				page_locale = page_data ? page_data.locale : this.languageSelector.get('value');
				
				//Open sitemap in same language as currently opened page
				if (page_locale != this.languageSelector.get('value')) {
					//Change locale without triggering reload
					this.languageSelector.set('value', page_locale, {'silent': true});
				}
			}
			
			//Change mode
			if (options.mode == 'pages') {
				this.setModePages(true, true);
			} else {
				this.setModeTemplates(true, true);
			}
			
			//Start loading data
			this.tree.get('data').load();
			
			//Show previously opened page
			if (page_data && page_data.tree_path) {
				this.restoreState([].concat(page_data.tree_path));
			}
		}
	});
	
});