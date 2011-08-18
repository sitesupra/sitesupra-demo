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

SU('anim', 'transition', 'supra.languagebar', 'website.sitemap-flowmap-item', 'website.sitemap-flowmap-item-normal', 'website.sitemap-tree-newpage', 'website.sitemap-settings', function (Y) {
	
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
			//Add buttons to toolbar
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, [{
				'id': 'history',
				'title': SU.Intl.get(['sitemap', 'undo_history']),
				'icon': '/cms/lib/supra/img/toolbar/icon-history.png',
				'action': 'PageHistory'
			}]);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			//Drag & drop
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
				'localeLabel': SU.Intl.get(['sitemap', 'viewing_structure'])
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
			
			//Page move
			this.flowmap.on('drop', this.onPageMove, this);
			
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
		
		onPageMove: function (event) {
			var position = event.position,
				drag_id = event.drag.id,
				drop_id = event.drop.id,
				source = this.flowmap.getNodeById(drag_id),
				target = this.flowmap.getNodeById(drop_id),
				
				post_data = {
					//New parent ID
					'parent': drop_id,
					//Item ID before which drag item was inserted
					'reference': '',
					//Dragged item ID
					'page': drag_id
				};
			
			if (position == 'before') {
				var parent = target.get('parent');
				parent = parent ? parent.get('data').id : 0;
				
				post_data.reference = drop_id;
				post_data.parent = parent;
			} else if (position == 'after') {
				var parent = target.get('parent');
				parent = parent ? parent.get('data').id : 0;
				
				var ref = target.next(); 
				if (ref) {
					post_data.reference = ref.get('data').id;
				}
				
				post_data.parent = parent;
			}
			
			//Send request
			var uri = this.getDataPath('move'),
				old;
			
			Supra.io(uri, {
				'data': post_data,
				'method': 'post',
				'context': this,
				'on': {
					'failure': function () {
						//Revert changes
						this.flowmap.reload();
					}
				}
			})
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
					'id': evt.data.id
				}
			});
			
			//Set selected in data
			Supra.data.set('page', {
				'id': evt.data.id
			});
			
			var target = this.flowmap.getNodeById(evt.data.id);
			this.animate(target.get('boundingBox'));
		},
		
		/**
		 * Animate sitemap out
		 */
		animate: function (node, reverse) {
			if (reverse) {
				if (this.anim_node) {
					this.anim_node.setStyles({'opacity': 1, 'display': 'block'});
				}
				this.set('visible', true);
			}
			
			var node = node ? node.one('div.flowmap-node-inner, div.tree-node') : null,
				node_region = node ? node.get('region') : null,
				anim_from = null,
				anim_to = {'left': '10px', 'top': '60px', 'right': '10px', 'bottom': '10px', 'opacity': 1},
				target_region = this.one().get('region');
			
			if (!this.animation) {
				this.anim_node = this.one('.yui3-sitemap-anim');
				this.one('.yui3-sitemap').insert(this.anim_node, 'after');
				
				this.animation = new Y.Anim({
					'node': this.anim_node,
					'duration': 0.5,
					'easing': Y.Easing.easeIn
				});
			}
			
			if (!node_region) {
				node_region = {
					'width': 146,
					'height': 182,
					'left': ~~(target_region.width / 2 - 73),
					'top': 103
				};
			}
			
			anim_from = {
				'left': node_region.left + 'px',
				'right': target_region.width - node_region.width - node_region.left + 'px',
				'top': node_region.top + 'px',
				'bottom': target_region.height - node_region.height - node_region.top + 'px',
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
					//Fade out
					
					this.anim_node.transition({
						'opacity': 0,
						'easing': 'ease-out',
    					'duration': 0.25
					}, function () {
						this.setStyle('display', 'none');
					});
					
					this.set('visible', false);
					
					Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
					Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
					
				} else {
					Manager.getAction('PageToolbar').setActiveAction(this.NAME);
					Manager.getAction('PageButtons').setActiveAction(this.NAME);
					
					this.anim_node.hide();
				}
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
				this.animate(null);
			}
			
			return this;
		},
		
		show: function () {
			
			var node = this.flowmap.get('selectedNode');
			if (node) {
				this.animate(node.get('boundingBox'), true);
			} else {
				this.animate(null, true);
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