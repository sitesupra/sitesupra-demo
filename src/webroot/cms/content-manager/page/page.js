//Invoke strict mode
"use strict";

Supra(function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Page',
		
		/**
		 * Action doesn't have stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: false,
		
		/**
		 * Action doesn't have template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: false,
		
		
		
		
		/**
		 * Loading page data
		 * @type {Boolean}
		 */
		loading: false,
		
		/**
		 * Page data
		 * @type {Object}
		 */
		data: null,
		
		
		
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			//When page manager is hidden, hide other page actions
			this.addChildAction('LayoutContainers');
			this.addChildAction('EditorToolbar');
			this.addChildAction('PageContent');
			this.addChildAction('PageButtons');
		},
		
		/**
		 * Execute action
		 * 
		 * @param {Object} data Data
		 */
		execute: function (data) {
			//Load data
			this.loadPage(data ? data.id : '', data ? data.version : '');
			
			//Load all other actions
			Manager.executeAction('Blocks');
			Manager.executeAction('LayoutContainers', this.onLayoutReady, this);
			
			//Show all actions
			Manager.getAction('PageContent').show();
		},
		
		/**
		 * When layout is ready create buttons and content
		 */
		onLayoutReady: function () {
			var pagecontent = Manager.getAction('PageContent'),
				pagetoolbar = Manager.getAction('PageToolbar');
			
			
			Manager.executeAction('PageButtons');
			Manager.executeAction('PageContent');
			
			//When content is ready bind to layout changes
			pagecontent.on('iframeReady', this.onIframeReady, this);
			
			//Show PageToolbar and load EditorToolbar
			pagetoolbar.execute();
			pagetoolbar.once('execute', function () {
				//Execute action, last argument 'true' is used to hide toolbar
				Manager.executeAction('EditorToolbar', true);
			});
		},
		
		/**
		 * When iframe is ready 
		 */
		onIframeReady: function () {
			var pagecontent = Manager.getAction('PageContent'),
				iframeObj = pagecontent.iframeObj,
				layoutTopContainer = SU.Manager.getAction('LayoutTopContainer'),
				layoutLeftContainer = SU.Manager.getAction('LayoutLeftContainer'),
				layoutRightContainer = SU.Manager.getAction('LayoutRightContainer');
				
			//iFrame position sync with other actions
			iframeObj.plug(SU.PluginLayout, {
				'offset': [10, 10, 10, 10]	//Default offset from page viewport
			});
			
			//Top bar 
			iframeObj.layout.addOffset(layoutTopContainer, layoutTopContainer.one(), 'top', 10);
			iframeObj.layout.addOffset(layoutLeftContainer, layoutLeftContainer.one(), 'left', 10);
			iframeObj.layout.addOffset(layoutRightContainer, layoutRightContainer.one(), 'right', 10);
		},
		
		/**
		 * Load page data
		 * 
		 * @param {Number} page_id
		 * @private
		 */
		loadPage: function (page_id, version_id) {
			this.loading = true;
			this.data = null;
			
			Supra.io(this.getDataPath(), {
				'data': {
					'page_id': page_id || '',
					'version_id': version_id || ''
				},
				'on': {
					'complete': this.onLoadComplete
				}
			}, this);
		},
		
		/**
		 * On page load complete update data
		 * 
		 * @param {Number} transaction Request transaction ID
		 * @param {Object} data Response JSON data
		 */
		onLoadComplete: function (data, status) {
			this.loading = false;
			
			if (status) {
				this.data = data;
				this.fire('loaded', {'data': data});
			}
		},
		
		/**
		 * Publish page
		 */
		publishPage: function () {
			var uri = this.getDataPath('save') + '.php',
				page_data = this.data;
			
			var post_data = {
				'page_id': page_data.id,
				'version_id': page_data.version.id,
				'locale': Supra.data.get('locale'),
				'action': 'publish'
			};
			
			Supra.io(uri, {
				'data': post_data,
				'method': 'post',
				'on': {
					'success': this.onPublishComplete
				}
			}, this);
		},
		
		/**
		 * On save complete update page data
		 * 
		 * @param {Number} transaction Request transaction ID
		 * @param {Object} data Response JSON data
		 */
		onPublishComplete: function (data, status) {
			this.setPageData({
				'version': data
			});
		},
		
		/**
		 * Delete page
		 */
		deleteCurrentPage: function (data, locale) {
			var page_data = this.data,
				page_id = page_data.id,
				version_id = page_data.version.id,
				locale = Supra.data.get('locale');
			
			this.deletePage(page_id, version_id, locale, this.onDeleteComplete, this);
		},
		
		/**
		 * Delete page
		 */
		deletePage: function (page_id, version_id, locale, callback, context) {
			var uri = this.getDataPath('delete');
			
			var post_data = {
				'page_id': page_id,
				'version_id': version_id,
				'locale': locale,
				'action': 'delete'
			};
			
			Supra.io(uri, {
				'data': post_data,
				'method': 'post',
				'on': {'success': callback}
			}, context);
		},
		
		/**
		 * On delete request complete load new page
		 * 
		 * @param {Number} transaction Request transaction ID
		 * @param {Object} data Response JSON data
		 */
		onDeleteComplete: function (data, status) {
			//Data is page ID which should be loaded next (parent page?)
			this.loadPage(data);
		},
		
		/**
		 * Create new page and returns page data to callback
		 * 
		 * @param {Object} data Page data
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback function context
		 */
		createPage: function (data, callback, context) {
			var uri = this.getDataPath('create');
			
			Supra.io(uri, {
				'data': data,
				'method': 'post',
				'context': context,
				'on': {
					'success': callback
				}
			});
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
		 * Update page data
		 * 
		 * @param {Object} page_data
		 */
		setPageData: function (page_data) {
			//Find all changes
			var changes = {};
			for(var i in page_data) {
				if (!(i in this.data) || this.data[i] != page_data[i]) {
					changes[i] = page_data[i];
				}
			}
			
			if ('template' in changes) {
				/* @TODO */
			}
			
			Supra.mix(this.data, page_data);
		}
	});
	
});
