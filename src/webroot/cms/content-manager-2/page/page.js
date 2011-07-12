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
		 * No need for stylesheet
		 * @type {Boolean}
		 */
		HAS_STYLESHEET: false,
		
		/**
		 * No need for template
		 * @type {Boolean}
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
			// When page manager is hidden, hide header item
			this.on('visibleChange', function (event) {
				if (event.newVal != evt.prevVal && !event.newVal) {
					Manager.getAction('Header').getItem('page').hide();
				}
			});
			
			//When page manager is hidden, hide other page actions
			this.addChildAction('LayoutContainers');
			this.addChildAction('EditorToolbar');
			this.addChildAction('PageContent');
			this.addChildAction('PageButtons');
		},
		
		/**
		 * Render widgets, bind listeners
		 * @private
		 */
		render: function () {
			//Bind to editing-start / end to change title in header
			Manager.getAction('PageContent').on('activeContentChange', function (evt) {
				if (evt.newVal != evt.prevVal) {
					if (evt.newVal) {
						this.setPageTitle(this.data.title, evt.newVal.getTitle());
					} else {
						this.setPageTitle(this.data.title);
					}
				}
			}, this);
		},
		
		/**
		 * Execute action
		 * 
		 * @param {Object} data Data
		 */
		execute: function (data) {
			//Load data
			this.loadPage(data ? data.id : '');
			
			//Load all other actions
			Manager.executeAction('Blocks');
			Manager.executeAction('LayoutContainers');
			
			var containers = Manager.getAction('LayoutContainers');
			containers.on('execute', function () {
				Manager.executeAction('PageButtons');
				Manager.executeAction('PageContent');
			});
			
			//Show all actions
			Manager.getAction('PageContent').show();
		},
		
		/**
		 * Load page data
		 * 
		 * @param {Number} page_id
		 * @private
		 */
		loadPage: function (page_id) {
			this.loading = true;
			this.data = null;
			
			Supra.io(this.getDataPath(), {
				'data': {'id': page_id},
				'on': {
					'success': this.onLoadComplete
				}
			}, this);
		},
		
		/**
		 * On page load complete update data
		 * 
		 * @param {Number} transaction Request transaction ID
		 * @param {Object} data Response JSON data
		 */
		onLoadComplete: function (transaction, data) {
			this.loading = false;
			this.data = data;
			this.setPageTitle(data.title);
			
			this.fire('loaded', {'data': data});
		},
		
		/**
		 * Publish page
		 */
		publishPage: function () {
			var uri = this.getDataPath('save') + '.php',
				page_data = this.data;
			
			var post_data = {
				'page': page_data.id,
				'version': page_data.version.id,
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
		onPublishComplete: function (transaction, data) {
			this.setPageData({
				'version': data
			});
		},
		
		/**
		 * Delete page
		 */
		deletePage: function () {
			var uri = this.getDataPath('delete') + '.php',
				page_data = this.data;
			
			var post_data = {
				'page': page_data.id,
				'version': page_data.version.id,
				'locale': Supra.data.get('locale'),
				'action': 'delete'
			};
			
			Supra.io(uri, {
				'data': post_data,
				'method': 'post',
				'on': {
					'success': this.onDeleteComplete
				}
			}, this);
		},
		
		/**
		 * On delete request complete load new page
		 * 
		 * @param {Number} transaction Request transaction ID
		 * @param {Object} data Response JSON data
		 */
		onDeleteComplete: function (transaction, data) {
			//Data is page ID which should be loaded next (parent page?)
			this.loadPage(data);
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
			
			if ('title' in changes) {
				this.setPageTitle(changes.title);
			}
			
			if ('template' in changes) {
				/* @TODO */
			}
			
			Supra.mix(this.data, page_data);
		},
		
		/**
		 * Change page title
		 * 
		 * @param {Object} page
		 * @param {Object} block
		 */
		setPageTitle: function (page, block) {
			var header = Manager.getAction('Header');
			if (!header.isInitialized()) {
				header.on('execute', function () {
					this.setPageTitle(page, block);
				}, this);
				return;
			}
			
			var page = page;
			var block = block;
			var labelType = '';		//Editing block
			var labelMajor = '';	//Free text
			var labelMinor = '';	//at About us page
			var html = '';
			
			if (block) {
				labelMajor = Y.Lang.escapeHTML(block);
				labelMinor = ' <small>at</small> ' + Y.Lang.escapeHTML(page) + ' <small>page</small>';
			} else if (page) {
				labelMajor = Y.Lang.escapeHTML(page);
			}
			
			html = labelMajor + labelMinor;
			
			//Change page title in header
			var item = header.getItem('page');
			if (item) {
				item.setTitle(html, true);
				item.show();
			} else {
				header.addItem('page', {'title': html});
			}
		}
	});
	
});
