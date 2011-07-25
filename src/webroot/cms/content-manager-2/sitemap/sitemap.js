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

SU('anim', 'supra.languagebar', 'website.sitemap-flowmap-item', 'website.sitemap-flowmap-item-normal', 'website.sitemap-tree-newpage', 'website.sitemap-settings', function (Y) {

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
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		
		
		
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
		 * Animate node
		 * @type {Object}
		 * @private
		 */
		anim_node: null,
		
		/**
		 * Animation object
		 * @type {Object}
		 * @private
		 */
		animation: null,
		
		
		
		
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
			
			//New page
			var new_page_node = this.one('.new-page-button');
			if (Supra.Authorization.isAllowed(['page', 'create'], true)) {
				this.flowmap.plug(SU.Tree.NewPagePlugin, {
					'dragNode': new_page_node
				});
			} else {
				new_page_node.addClass('hidden');
			}
			
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
			
			var target = this.flowmap.getNodeById(evt.data.id);
			this.animate(target.get('boundingBox'));
		},
		
		/**
		 * Animate sitemap out
		 */
		animate: function (node, reverse) {
			if (reverse) {
				this.anim_node.setStyles({'opacity': 1, 'display': 'block'});
				this.set('visible', true);
			}
			
			var target_node = this.one('.yui3-sitemap-content'),
				target_region = target_node.get('region'),
				node = node.one('div.flowmap-node-inner, div.tree-node'),
				node_region = node.get('region'),
				anim_from = null,
				anim_to = {'left': '0px', 'top': '0px', 'right': '0px', 'bottom': '0px', 'opacity': 1};
			
			if (!this.animation) {
				this.anim_node = this.one('.yui3-sitemap-anim');
				target_node.append(this.anim_node);
				
				this.animation = new Y.Anim({
					'node': this.anim_node,
					'duration': 0.5,
					'easing': Y.Easing.easeIn
				});
			}
			
			anim_from = {
				'left': node_region.left - target_region.left + 'px',
				'right': target_region.right - node_region.right + 'px',
				'top': node_region.top - target_region.top + 'px',
				'bottom': target_region.bottom - node_region.bottom + 'px',
				'opacity': 0.35
			};
			
			if (reverse) {
				this.animation.set('from', anim_to);
				this.animation.set('to', anim_from);
			} else {
				this.animation.set('from', anim_from);
				this.animation.set('to', anim_to);
				
				this.anim_node.setStyles({'opacity': 0, 'display': 'block'});
			}
			
			this.animation.run();
			
			this.animation.once('end', function () {
				if (!reverse) {
					this.set('visible', false);
				}
				this.anim_node.hide();
			}, this);
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
		
		hide: function () {
			var node = this.flowmap.get('selectedNode');
			if (node) {
				this.animate(node.get('boundingBox'));
			} else {
				this.set('visible', false);
			}
			return this;
		},
		
		show: function () {
			
			var node = this.flowmap.get('selectedNode');
			if (node) {
				this.animate(node.get('boundingBox'), true);
			} else {
				this.set('visible', true);
			}
			
			return this;
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
		}
	});
	
});