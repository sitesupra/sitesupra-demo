SU('supra.tooltip', 'supra.tree', 'supra.tree-dragable', 'supra.tree-node-dragable', function (Y) {

	//Shortcut
	var Action = SU.Manager.Action;
	
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
			
			//Create tree
			this.tree = new SU.TreeDragable({
				srcNode: '#tree',
				requestUri: this.getDataPath()
			});
			
			this.tree.plug(SU.Tree.ExpandHistoryPlugin);
			
			//Create language bar
			//@TODO Add language selector
			
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
				this.fire('page:select', {
					'data': evt.data
				});
				
				this.hide();
			} else {
				this.tooltip.hide();
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
		 * Handle new page click
		 */
		onNewPageClick: function () {
			this.mode = 'insert';
			this.showTooltip('Select where you want to place your page');
			
			this.button_newpage.set('disabled', true);
			this.selectedPageNode = this.tree.get('selectedNode');
			this.tree.set('selectedNode', null);
		},
		
		/**
		 * Show tooltip message
		 */
		showTooltip: function (message) {
			if (!this.tooltip) {
				this.tooltip = new Supra.Tooltip({
					'alignTarget': this.tree.get('boundingBox'),
					'alignPosition': 'L'
				});
				
				this.tooltip.render(this.getContainer());
			}
			
			this.tooltip.set('textMessage', message);
			this.tooltip.show();
		},
		
		/**
		 * Render widgets and bind
		 */
		render: function () {
			//Render tree
			this.tree.render();
			
			//New page button
			var buttons = this.getContainer('.yui3-form-buttons');
			var btn = new Supra.Button({'label': 'New page', 'style': 'mid-blue'});
				btn.render(buttons);
				btn.on('click', this.onNewPageClick, this);
			
			this.button_newpage = btn;
			this.getContainer('.yui3-sitemap-content').prepend(buttons);
			
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