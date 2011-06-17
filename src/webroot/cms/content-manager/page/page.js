SU(function (Y) {

	//Shortcut
	var Manager = SU.Manager;
	var Action = Manager.Action;
	
	//Create Action class
	new Action(Action.PluginPanel, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Page',
		
		/**
		 * Page manager has stylesheet, include it
		 * @type {Boolean}
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Loading page data
		 */
		loading: false,
		
		/**
		 * Page data
		 */
		data: null,
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			// When page manager is hidden, hide header item
			this.on('visibleChange', function (event) {
				if (!event.newVal) {
					SU.Manager.getAction('Header').getItem('page').hide();
				}
			});
			
			//When page manager is hidden, hide other page actions
			this.addChildAction('PageTopBar');
			this.addChildAction('EditorToolbar');
			this.addChildAction('PageContent');
			this.addChildAction('PageBottomBar');
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			//Set panel style
			this.panel.addClass(Y.ClassNameManager.getClassName('page'));
		},
		
		/**
		 * Execute action
		 * 
		 * @param {Object} data Data
		 */
		execute: function (data) {
			//Add 'Page' item to header
			var header = SU.Manager.getAction('Header');
			
			var item = header.getItem('page');
			if (item) {
				item.setTitle(data.title);
				item.show();
			} else {
				header.addItem('page', {
					'title': data.title
				});
			}
			
			//Load data
			this.loadPageData(data.id);
			
			//Load all other actions
			SU.Manager.executeAction('Blocks');
			SU.Manager.executeAction('PageTopBar');
			SU.Manager.executeAction('EditorToolbar');
			SU.Manager.executeAction('PageBottomBar');
			SU.Manager.executeAction('PageContent');
			
			//Show / hide top bar when toolbar ir hidden / shown
			var topbar = SU.Manager.getAction('PageTopBar');
			var toolbar = SU.Manager.getAction('EditorToolbar');
			
			toolbar.on('visibleChange', function (evt) {
				if (evt.newVal != evt.prevVal) {
					if (evt.newVal) {
						topbar.hide();
					} else {
						topbar.show();
					}
				}
			});
			
			//Bind to editing-start / end
			var content = SU.Manager.getAction('PageContent');
			var bottom = SU.Manager.getAction('PageBottomBar');
			
			content.on('activeContentChange', function (evt) {
				if (evt.newVal) {
					var content = evt.newVal;
					var block_type = content.get('data').type;
					var block = SU.Manager.Blocks.getBlock(block_type);
					
					this.setPageTitle(this.data.title, block.title);
					
					//Hide bottombar while editing content
					bottom.hide();
				} else {
					this.setPageTitle(this.data.title);
					
					//Show bottombar when not editing content
					bottom.show();
				}
			}, this);
			
			//Show all actions
			SU.Manager.getAction('PageTopBar').show();
			SU.Manager.getAction('PageBottomBar').show();
			SU.Manager.getAction('PageContent').show();
		},
		
		/**
		 * Load page data
		 * 
		 * @param {Number} page_id
		 * @private
		 */
		loadPageData: function (page_id) {
			this.loading = true;
			this.data = null;
			
			SU.io(this.getDataPath(), {
				'data': {'id': page_id},
				'on': {
					'success': function (evt, data) {
		    			this.loading = false;
						this.data = data;
						this.setPageTitle(data.title);
						
						this.fire('loaded', {'data': data});
					}
				}
			}, this);
		},
		
		/**
		 * Returns page data if page is loaded, otherwise null
		 * 
		 * @return Page data
		 * @type {Object}
		 */
		getPageData: function () {
			return this.data;
		},
		
		/**
		 * Change page title
		 * 
		 * @param {Object} page
		 * @param {Object} block
		 */
		setPageTitle: function (page, block) {
			var page = page;
			var block = block;
			var labelType = '';		//Editing block
			var labelMajor = '';	//Free text
			var labelMinor = '';	//at About us page
			var html = '';
			
			if (block) {
				labelType = 'Editing block';
				labelMajor = block;
				labelMinor = 'at ' + page + ' page';
			} else if (page) {
				labelType = 'Editing page';
				labelMajor = page;
			}
			
			if (labelType) {
				var html = '<span>' + Y.Lang.escapeHTML(labelType) + ':</span> <b>' + Y.Lang.escapeHTML(labelMajor) + '</b> <em>' + Y.Lang.escapeHTML(labelMinor) + '</em>';
			}
			
			this.getContainer().one('h2').set('innerHTML', html);
			
			//Change page title in header
			SU.Manager.Header.getItem('page').setTitle(page);
		},
		
		/**
		 * Hide editor toolbar
		 */
		hideEditorToolbar: function () {
			SU.Manager.getAction('EditorToolbar').hide();
		},
		
		/**
		 * Show editor toolbar
		 */
		showEditorToolbar: function () {
			SU.Manager.getAction('EditorToolbar').show();
		},
		
		/**
		 * Hide media library
		 */
		hideMediaLibrary: function () {
			SU.Manager.getAction('MediaBar').hide();
		},
		
		/**
		 * Show media library
		 */
		showMediaLibrary: function () {
			SU.Manager.executeAction('MediaBar');
		}
	});
	
});