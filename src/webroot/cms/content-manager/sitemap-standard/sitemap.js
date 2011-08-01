//Invoke strict mode
"use strict";

//Add module definitions
SU.addModule('website.sitemap-tree-node', {
	path: 'sitemap/modules/sitemap-tree-node.js',
	requires: ['supra.tree-dragable']
});
SU.addModule('website.sitemap-tree-newpage', {
	path: 'sitemap/modules/sitemap-tree-newpage.js',
	requires: ['supra.tree-dragable']
});

SU('supra.languagebar', 'website.sitemap-tree-node', 'website.sitemap-tree-newpage', function (Y) {
	
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
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		
		
		
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
		 * Property panel
		 * @type {Object}
		 * @private
		 */
		panel: null,
		
		/**
		 * Property panel target data
		 * @type {Object}
		 * @private
		 */
		property_data: null,
		
		/**
		 * Delete page button
		 * @type {Object]
		 * @private
		 */
		button_delete: null,
		
		
		
		
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
			
			this.initializeTree();
			this.initializeLanguageBar();
			
			//When action is hidden hide container
			this.on('visibleChange', function (evt) {
				if (evt.newVal != evt.prevVal) {
					if (evt.newVal) {
						this.one().removeClass('hidden');
					} else {
						this.one().addClass('hidden');
					}
				}
			}, this);
		},
		
		/**
		 * Create tree
		 * 
		 * @private
		 */
		initializeTree: function () {
			this.tree = new SU.TreeDragable({
				'srcNode': this.one('.tree'),
				'requestUri': this.getDataPath() + '?locale=' + SU.data.get('locale'),
				'defaultChildType': SU.SitemapTreeNode
			});
			
			this.tree.plug(SU.Tree.ExpandHistoryPlugin);
			this.tree.plug(SU.Tree.NewPagePlugin, {
				'dragNode': this.one('.new-page-button')
			});
			
			this.tree.get('boundingBox').delegate('mouseenter', function (evt) {
				var target = evt.target.closest('div.tree-node');
				var id = this.tree.getIdByNode(target);
				
				if (id) {
					var data = this.tree.getIndexedData()[id];
					this.showPropertyPanel(target, data);
				}
			}, 'div.tree-node', this);
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
					//Reload tree
					this.tree.set('requestUri', this.getDataPath() + '?locale=' + evt.newVal);
					this.tree.reload();
				}
			}, this);
		},
		
		/**
		 * Create panel
		 * 
		 * @private
		 */
		initializePanel: function () {
			if (!this.panel) {
				this.panel = new Supra.Panel({
					'srcNode': this.one('.sitemap-settings').removeClass('sitemap-settings'),
					'arrowPosition': ['L', 'C'],
					'arrowVisible': true
				});
				this.panel.get('boundingBox').addClass('sitemap-settings');
				this.panel.render();
				
				//On language change tree is reloaded
				this.languagebar.on('localeChange', this.panel.hide, this.panel);
			}
			
			if (!this.form) {
				//Create form
				var contbox = this.panel.get('contentBox');
				var form = this.form = new Supra.Form({
					'srcNode': contbox.one('form'),
					'autoDiscoverInputs': true,
					'inputs': [
						{'id': 'title', 'type': 'String', 'useReplacement': true},
						{'id': 'path', 'type': 'Path', 'useReplacement': true}
					]
				});
				form.render(contbox);
				
				//On input change save value
				var inputs = form.getInputs();
				for(var id in inputs) {
					inputs[id].on('change', this.onPagePropertyChange, this);
				}
				
				//New page button
				var buttons = contbox.all('button');
				var btn = new Supra.Button({'srcNode': buttons.item(0), 'style': 'mid-blue'});
					btn.render();
					btn.on('click', this.insertNewPage, this);
					
				//Delete button
				var btn = new Supra.Button({'srcNode': buttons.item(1), 'style': 'mid-red'});
					btn.render();
					btn.on('click', this.deletePage, this);
				
				this.button_delete = btn;
			}
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
				'data': evt.data
			});
			
			this.hide();
		},
		
		/**
		 * On page property change
		 */
		onPagePropertyChange: function (event) {
			var input = event.target,
				input_id = input.get('id'),
				input_value = input.get('saveValue'),
				uri = this.getDataPath('save'),
				post_data = {
					'page_id': this.property_data.id,
					'version_id': this.property_data.version
				};
			
			post_data[input_id] = input_value;
			
			Supra.io(uri, {
				'data': post_data,
				'method': 'post',
				'on': {
					'success': function () {
						var treenode = this.tree.getNodeById(post_data.page_id);
						if (input_id == 'title') {
							treenode.get('boundingBox').one('label').set('text', input_value);
						}
					}
				}
			}, this);
		},
		
		/**
		 * Set property values
		 * 
		 * @param {Object} data
		 * @private
		 */
		setPropertyValues: function (data) {
			this.property_data = data;
			
			var path = data.fullpath || '/';
				path = path.substr(0, path.lastIndexOf(data.path));
			
			if (!data.path) {
				//Root page
				this.button_delete.set('disabled', true);
			} else {
				this.button_delete.set('disabled', false);
			}
			
			var path_input = this.form.getInput('path');
			this.form.setValues(data, 'id');
			
			path_input.set('path', path);
			path_input.set('disabled', !data.path);
		},
		
		/**
		 * Open property panel
		 * 
		 * @param {Object} data Property form data
		 * @private
		 */
		showPropertyPanel: function (target, data) {
			if (!this.panel) {
				this.initializePanel();
			} else {
				//If there are focused inputs, then don't change
				var inputs = this.form.getInputs();
				for(var id in inputs) {
					if (inputs[id].get('focused')) return;
				}
			}
			
			//Position panel
			var treebox = this.tree.get('boundingBox'),
				pos = treebox.getXY(),
				pos_x = treebox.get('offsetWidth') + pos[0] + 10;
			
			this.panel.set('x', pos_x);
			
			//Position arrow
			if (target) {
				this.setPropertyValues(data);
				
				this.panel.show();
				this.panel.set('arrowAlign', target);
			}
		},
		
		/**
		 * Insert new page
		 */
		insertNewPage: function () {
			if (!this.property_data) return;
			
			var target = this.tree.getNodeById(this.property_data.id);
			this.tree.newpage.addChild('inside', target, function (data) {
				
				var node = this.tree.getNodeById(data.id);
				var target = node.get('boundingBox');
				this.showPropertyPanel(target, node.get('data'));
				
			}, this);
		},
		
		/**
		 * Delete selected page
		 * 
		 * @private
		 */
		deletePage: function () {
			if (!this.property_data) return;
			
			Manager.executeAction('Confirmation', {
				'message': SU.Intl.get(['settings', 'delete_message']),
				'buttons': [
					{'id': 'delete', 'label': SU.Intl.get(['buttons', 'yes']), 'click': this.deletePageConfirm, 'context': this},
					{'id': 'no', 'label': SU.Intl.get(['buttons', 'no'])}
				]
			});
		},
		
		/**
		 * After user confirmed page deletion collect page data and
		 * delete it
		 * 
		 * @private
		 */
		deletePageConfirm: function () {
			//Send request to server
			var page_id = this.property_data.id,
				version_id = this.property_data.version,
				locale = this.languagebar.get('locale');
			
			Manager.Page.deletePage(page_id, version_id, locale, function () {
				//Hide properties
				this.panel.hide();
				this.property_data = null;
				
				//Reload tree
				this.tree.reload();
			}, this);
		},
		
		/**
		 * Render widgets and bind
		 */
		render: function () {
			//Render tree
			this.tree.render();
			
			//Render language bar
			this.languagebar.render(this.one('.languages'));
			
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