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
	requires: ['supra.panel', 'supra.input', 'website.input-template']
});
SU.addModule('website.sitemap-new-page', {
	path: 'sitemap/modules/sitemap-new-page.js',
	requires: ['supra.panel', 'supra.input', 'website.input-template']
});

SU('anim', 'transition', 'supra.languagebar', 'website.sitemap-flowmap-item', 'website.sitemap-flowmap-item-normal', 'website.sitemap-tree-newpage', 'website.sitemap-new-page', 'website.sitemap-settings', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager,
		Action = Manager.Action;
	
	//Create Action class
	new Action(Manager.Action.PluginContainer, Manager.Action.PluginSitemapSettings, Manager.Action.PluginSitemapNewPage, {
		
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
		 * Type inputs
		 * @type {Object}
		 * @private
		 */
		input_type: null,
		
		/**
		 * First execute request
		 * @type {Boolean}
		 * @private
		 */
		first_exec: true,
		
		/**
		 * Last known locale
		 * @type {String}
		 * @private
		 */
		locale: null,
		
		
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 */
		initialize: function () {
			//Set locale
			this.locale = Supra.data.get('locale');
			
			//Add buttons to toolbar
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, [{
				'id': 'recyclebin',
				'title': SU.Intl.get(['sitemap', 'recycle_bin']),
				'icon': '/cms/lib/supra/img/toolbar/icon-recycle.png',
				'action': 'SiteMapRecycle'
			}/*, {
				'id': 'history',
				'title': SU.Intl.get(['sitemap', 'undo_history']),
				'icon': '/cms/lib/supra/img/toolbar/icon-history.png',
				'action': 'PageHistory'
			}*/]);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			//Drag & drop
			Manager.getAction('PageContent').initDD();
			
			this.initializeLanguageBar();
			this.initializeTypeInput();
			this.initializeFlowMap();
			this.initializeApplicationList();
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
					this.locale = evt.newVal;
					this.flowmap.set('requestUri', this.getRequestUri(evt.newVal));
					this.flowmap.reload();
					this.setLoading(true);
				}
			}, this);
		},
		
		/**
		 * Create type selection list
		 *
		 * @private
		 */
		initializeTypeInput: function () {
			this.input_type = new Supra.Input.SelectList({
				'srcNode': this.one('select[name="type"]')
			});
			
			this.input_type.on('change', function (evt) {
				this.flowmap.set('requestUri', this.getRequestUri(null, evt.value));
				this.flowmap.reload();
				this.flowmap.newpage.setType(evt.value);
				this.setLoading(true);
				
				//Trigger event on Action
				this.fire('typeChange', {'value': evt.value});
				
				var recycle = Manager.getAction('SiteMapRecycle');
				if (recycle.get('visible')) {
					recycle.load(evt.value);
				}
				
				//Template drop target
				var new_root_item = this.one('div.item-drop');
				
				if (evt.value == 'templates') {
					new_root_item.removeClass('page-drop').addClass('template-drop');
				} else {
					new_root_item.removeClass('template-drop');
				}
				
			}, this);
		},
		
		/**
		 * Returns sitemap type (page or template)
		 *
		 * @return Sitemap type
		 * @type {String}
		 */
		getType: function () {
			return this.input_type.getValue();
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
				'requestUri': this.getRequestUri(),
				'defaultChildType': Supra.FlowMapItem
			});
			
			this.flowmap.plug(SU.Tree.ExpandHistoryPlugin);
			
			//After load update permission list
			this.flowmap.after('render:complete', this.loadFlowMapPermissions, this);
			
			//Page move
			this.flowmap.on('drop', this.onPageMove, this);
			
			//New page
			var new_page_list_node = this.one('.additional'),
				new_item_drop = this.one('div.item-drop');
			
			this.flowmap.plug(SU.Tree.NewPagePlugin, {
				'dragNode': new_page_list_node,
				'newItemDropNode': new_item_drop
			});
			
			//When tree is rendered set selected page
			this.flowmap.after('render:complete', function () {
				var page = Supra.data.get('page', {'id': 0});
				this.flowmap.set('selectedNode', null);
				this.flowmap.set('selectedNode', this.flowmap.getNodeById(page.id));
				
				this.setLoading(false);
			}, this);
		},
		
		/**
		 * Load permissions for all pages
		 */
		loadFlowMapPermissions: function () {
			var data = this.flowmap.getData(),
				permission  = [];
			
			
			//Get all page IDs
			var traverse = function (data, permission) {
				for(var i=0,ii=data.length; i<ii; i++) {
					if (!data[i].temporary) {
						permission.push({'id': data[i].id, 'type': 'page'});
						
						if (data[i].children && data[i].children.length) {
							traverse(data[i].children, permission);
						}
					}
				}
			};
			
			traverse(data, permission);
				
			//Request permission list
			if (permission.length) {
				Supra.Permission.request(permission, this.onLoadFlowMapPermissions, this);
			}
			
			//If there are no pages, then allow creating new root page
			var new_root_page = this.one('div.item-drop'),
				drag_drop_types = this.one('div.additional'),
				has_pages = false;
			
			for(var i=0,ii=data.length; i<ii; i++) {
				if (!data[i].temporary) {
					has_pages = true; break;
				}
			}
			
			if (has_pages || this.input_type.get('value') != 'sitemap') {
				new_root_page.removeClass('page-drop');
				drag_drop_types.removeClass('type-sitemap-first');
			} else {
				new_root_page.addClass('page-drop');
				drag_drop_types.addClass('type-sitemap-first');
			}
		},
		
		/**
		 * On permission load
		 */
		onLoadFlowMapPermissions: function (permissions) {
			var pages = permissions.page,
				id    = null,
				tree  = this.flowmap,
				node  = null,
				type  = this.getType(),
				is_global = false;
			
			//Enable editing if it's allowed
			for(id in pages) {
				node = tree.getNodeById(id);
				if (node) {
					if (pages[id].edit_page) {
						
						is_global = node.get('data').global;
						
						//Enable editing only if not global and not root page
						if (!is_global && (!node.isRoot() || type == 'templates')) {
							node.get('boundingBox').one('.edit').removeClass('edit-hidden');
						}
						
						//Enable selecting global pages which were disabled
						if (is_global) {
							node.set('selectable', true);
						}
						
						//Enable drag and drop
						if (node.dd) {
							node.dd.set('lock', false);
						}
					} else {
						if (node.dd) {
							node.dd.set('lock', true);
						}
					}
				}
			}
		},
		
		/**
		 * New item application list
		 */
		initializeApplicationList: function () {
			Supra.io(this.getDataPath('applications'), {
				'context': this,
				'on': {
					'success': this.renderApplicationList
				}
			});
		},
		
		/**
		 * Render application list
		 */
		renderApplicationList: function (data) {
			var target = this.one('div.new-item div.additional'),
				tpl = Supra.Template('additionalNewItems'),
				i = 0,
				ii = data.length,
				node = null;
			
			for(; i<ii; i++) {
				node = Y.Node.create(tpl(data[i]));
				target.append(node);
				
				this.flowmap.newpage.createProxyTreeNode(node, {'type': 'application', 'application_id': data[i].id});
			}
		},
		
		/**
		 * Load and show hidden pages
		 * 
		 * @param {String} page_id Page ID
		 */
		showHiddenPages: function (page_id) {
			var uri = this.getRequestUri();
			Supra.io(uri, {
				'data': {
					'root': page_id
				},
				'context': this,
				'on': {
					'success': function (data) {
						for(var i=0,ii=data.length; i<ii; i++) {
							data[i].is_hidden_page = true;
						}
						this.flowmap.getNodeById(page_id).get('data').has_hidden_pages = false;
						this.addPagesToTree(page_id, data);
					}
				}
			});
		},
		
		/**
		 * Load and show all pages (except hidden)
		 * 
		 * @param {String} page_id Page ID
		 */
		showAllPages: function (page_id) {
			var uri = this.getRequestUri();
			Supra.io(uri, {
				'data': {
					'root': page_id,
					'expand': true
				},
				'context': this,
				'on': {
					'success': function (data) {
						var tree_node = this.flowmap.getNodeById(page_id)
						
						tree_node.removeNonHiddenChildren();
						this.addPagesToTree(page_id, data);
						
						//Collapse all children
						for(var i=0,ii=tree_node.size(); i<ii; i++) {
							tree_node.item(i).collapseAll();
						}
					}
				}
			})
		},
		
		/**
		 * Add pages to the tree
		 * 
		 * @param {String} page_id
		 * @param {Array} Children data
		 */
		addPagesToTree: function (page_id, data) {
			var node = this.flowmap.getNodeById(page_id),
				node_data = node.get('data'),
				indexed_data = this.flowmap.getIndexedData();
			
			//Update data with 'parent' property
			for(var i=0,ii=data.length; i<ii; i++) {
				data[i].parent = page_id;
				indexed_data[data[i].id] = data[i];
			}
			
			//Add children data to the node data
			node_data.children = node_data.children || [];
			node_data.children = node_data.children.concat(data);
			
			//Add to tree
			node.addChildren(data);
		},
		
		/**
		 * Returns flowmap request URI
		 * 
		 * @param {String} locale Optional. Locale
		 * @param {String} type Optional. Type
		 * @private
		 */
		getRequestUri: function (locale, type) {
			var locale = locale || this.locale;
			var type = type || this.input_type.getValue();
			
			return this.getDataPath(type) + '?locale=' + locale;
		},
		
		/**
		 * Set loading state
		 *
		 * @param {Boolean} state Loading state
		 * @private
		 */
		setLoading: function (state) {
			var node = this.one('div.yui3-sitemap-scrollable');
			node.setClass('loading', state);
		},
		
		/**
		 * Returns drop position data
		 * 
		 * @param {Object} target Drop target
		 * @param {String} drop_id Drop target ID
		 * @param {String} drag_id Drag ID
		 * @param {String} position Drop position
		 * @return Drop data
		 * @type {Object}
		 */
		getDropPositionData: function (target, drop_id, drag_id, position) {
			var data = {
				//New parent ID
				'parent_id': drop_id,
				//Item ID before which drag item was inserted
				'reference_id': '',
				//Dragged item ID
				'page_id': drag_id,
				
				//Locale
				'locale': this.languagebar.get('locale')
			};
			
			if (position == 'before') {
				var parent = target.get('parent');
				parent = parent ? parent.get('data').id : 0;
				
				data.reference_id = drop_id;
				data.parent_id = parent;
			} else if (position == 'after') {
				var parent = target.get('parent');
				parent = parent ? parent.get('data').id : 0;
				
				var ref = target.next(); 
				if (ref) {
					data.reference_id = ref.get('data').id;
				}
				
				data.parent_id = parent;
			}
			
			return data;
		},
		
		onPageMove: function (event) {
			//New page also triggers this event, but drag.id is empty
			if (!event.drag.id) return;
			
			//New page can be dragged, but shouldn't send request to server 
			if (String(event.drag.id).indexOf('yui_') != -1) {
				var drag_id = event.drag.id;
				
				//Update new page popup position
				Y.later(250, this, function () {
					var source = source = this.flowmap.getNodeById(drag_id),
						node = source.get('boundingBox').one('.tree-node, .flowmap-node-inner');
					this.plugins.getPlugin('PluginSitemapNewPage').position(node);
				});
				
				return;
			}
			
			var position = event.position,
				drag_id = event.drag.id,
				drop_id = event.drop.id,
				source = this.flowmap.getNodeById(drag_id),
				target = this.flowmap.getNodeById(drop_id),
				post_data = this.getDropPositionData(target, drop_id, drag_id, position);
			
			//Send request
			var uri = this.getDataPath('move');
			
			Supra.io(uri, {
				'data': post_data,
				'method': 'post',
				'context': this,
				'on': {
					'failure': function () {
						//Revert changes
						this.flowmap.reload();
						this.setLoading(true);
					}
				}
			});
		},
		
		/**
		 * Handle tree node click event
		 * 
		 * @param {Object} evt Event
		 * @private
		 */
		onTreeNodeClick: function (evt) {
			//Can't open page which is not create yet (temporary node)
			if (evt.data.id.match(/^yui_/)) {
				evt.halt();
				return false;
			}
			
			//Before changing page update locale
			Supra.data.set('locale', this.languagebar.get('locale'));
			
			//Change page
			if (this.fire('page:select', {'data': evt.data})) {
				this.onPageOpen(evt.data.id);
			}
			
		},
		
		onPageOpen: function (page_id) {
			//Set selected in data
			Supra.data.set('page', {
				'id': page_id
			});
			
			var target = this.flowmap.getNodeById(page_id);
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
					
					Manager.executeAction('PageHeader', true);
					
					//Clean up tree
					this.flowmap.empty();
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
			
			//Render type input
			this.input_type.render();
			
			//Render tree
			this.flowmap.render();
			
			//Page select event
			this.flowmap.on('node-click', this.onTreeNodeClick, this);
			
			//Layout
			var node = this.one(),
				layoutTopContainer = SU.Manager.getAction('LayoutTopContainer'),
				layoutLeftContainer = SU.Manager.getAction('LayoutLeftContainer'),
				layoutRightContainer = SU.Manager.getAction('LayoutRightContainer');
				
			//Content position sync with other actions
			node.plug(SU.PluginLayout, {
				'offset': [0, 0, 0, 0]	//Default offset from page viewport
			});
			
			//Top bar 
			node.layout.addOffset(layoutTopContainer, layoutTopContainer.one(), 'top', 0);
			node.layout.addOffset(layoutLeftContainer, layoutLeftContainer.one(), 'left', 0);
			node.layout.addOffset(layoutRightContainer, layoutRightContainer.one(), 'right', 0);
		},
		
		/**
		 * Returns tree instance
		 *
		 * @return Tree instance
		 * @type {Object}
		 */
		getTree: function () {
			return this.flowmap;
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
			
			if (!this.first_exec) {
				var page_data = Manager.Page.getPageData(),
					page_locale = page_data ? page_data.locale : this.languagebar.get('locale');
				
				//Open sitemap in same language as currently opened page
				if (page_locale != this.languagebar.get('locale')) {
					this.languagebar.set('locale', page_locale);
				} else {
					this.flowmap.reload();
					this.setLoading(true);
				}
			}
			
			this.first_exec = false;
			
			//Hide page header
			Manager.getAction('PageHeader').hide();
		}
	});
	
});