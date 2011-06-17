SU('supra.tree', 'supra.tree-dragable', 'supra.tree-node-dragable', function (Y) {

	//Shortcut
	var Action = SU.Manager.Action;
	
	//Create Action class
	new Action(Action.PluginPanel, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'SiteMap',
		
		/**
		 * Sitemap has stylesheet
		 * It will be automatically loaded
		 * @type {Boolean}
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Panel widget instance
		 * @type {Object}
		 */
		panel: null,
		
		/**
		 * Tree widget instance
		 * @type {Object}
		 */
		tree: null,
		
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
		 * Returns selected page node or null if there is no selection
		 * 
		 * @return Select node (Y.Node)
		 * @type {Object}
		 */
		getSelectedNode: function () {
			var node = this.tree.get('selectedNode');
			return node ? node.get('boundingBox') : null;
		},
		
		/**
		 * Deselect page
		 */
		deselectPage: function () {
			this.tree.set('selectedNode', null);
			return this;
		},
		
		/**
		 * Returns page node by ID
		 * 
		 * @param {Object} id
		 * @return Page node (Y.Node);
		 */
		getNodeById: function (id) {
			var node = this.tree.getNodeById(id);
			return node ? node.get('boundingBox') : null;
		},
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 */
		initialize: function () {
			
			//Create tree
			this.tree = new SU.TreeDragable({
				srcNode: '#tree',
				requestUri: this.getDataPath()
			});
			
			this.tree.plug(SU.Tree.ExpandHistoryPlugin);
			
			this.tree.get('boundingBox')
				.addClass(Y.ClassNameManager.getClassName('panel', 'inner'));
			
			//Create language bar
			//@TODO Add language selector
		},
		
		/**
		 * Render widgets and bind
		 */
		render: function () {
			//Add "Pages" to the header
			SU.Manager.Header.addItem('sitemap', {
				'type': 'link',
				'icon': '/cms/supra/img/apps/content_32x32.png',
				'title': 'Pages'
			});
			
			//Set panel style
			this.panel.get('boundingBox').setStyles({
			    'position': 'relative',
			    'left': 0,
			    'top': 0,
			    'width': 'auto'
			});
			this.panel.addClass(Y.ClassNameManager.getClassName('tree', 'panel'));
			
			//Render tree
			this.tree.render();
			
			//Bubble tree events
			this.bubbleEvents(this.tree, {
				'node-click': 'page-select'
			});
		}
	});
	
});