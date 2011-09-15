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
			this.loadPage(data ? data.id : '');
			
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
			if (pagetoolbar.get('created')) {
				//Execute action, last argument 'true' is used to initialize but not show
				Manager.executeAction('EditorToolbar', true);
			} else {
				pagetoolbar.once('execute', function () {
					//Execute action, last argument 'true' is used to initialize but not show
					Manager.executeAction('EditorToolbar', true);
				});
			}
			
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
		loadPage: function (page_id) {
			this.loading = true;
			this.data = null;
			
			Supra.io(this.getDataPath(), {
				'data': {
					'page_id': page_id || '',
					'locale': Supra.data.get('locale')
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
			var uri = this.getDataPath('publish'),
				page_data = this.getPageData();
			
			var post_data = {
				'page_id': page_data.id,
				'locale': Supra.data.get('locale'),
				'action': 'publish'
			};
			
			Supra.io(uri, {
				'data': post_data,
				'method': 'post'
			});
		},
		
		/**
		 * Unlock page, same is automatically done in publish
		 */
		unlockPage: function () {
			var uri = this.getDataPath('unlock'),
				page_data = this.getPageData();
			
			var post_data = {
				'page_id': page_data.id,
				'locale': Supra.data.get('locale')
			};
			
			Supra.io(uri, {
				'data': post_data,
				'method': 'post'
			});
		},
		
		/**
		 * Lock page, if page is already locked show message
		 *
		 * @param {Boolean} force Force lock
		 */
		lockPage: function (force) {
			var uri = this.getDataPath('lock'),
				page_data = this.getPageData(),
				buttons = Manager.PageButtons.buttons.Root;
			
			//Set loading style on button
			buttons[0].set('loading', true);
			
			//Send data
			var post_data = {
				'page_id': page_data.id,
				'locale': Supra.data.get('locale'),
				'force': (force ? 1 : 0)
			};
			
			Supra.io(uri, {
				'data': post_data,
				'method': 'post',
				'context': this,
				'on': {
					'complete': this.lockResponse
				}
			}, this);
		},
		
		/**
		 * On page lock request success start editing,
		 * on failure show message
		 *
		 * @param {Object} data Response data
		 * @param {Boolean} status Response status
		 */
		lockResponse: function (data /* Response data */, status /* Response status */) {
			//Unset loading style
			var buttons = Manager.PageButtons.buttons.Root;
			buttons[0].set('loading', false);
			
			//Handle response
			if (status && data === true || data === 1) {
				
				//Success
				Manager.PageContent.startEditing();
				
			} else if (status && data) {
				
				//Compile message template and change date and time format
				var template = Supra.Intl.get([this.getType(), 'locked_message']);
				template = Supra.Template.compile(template);
				
				data.datetime = Y.DataType.Date.reformat(data.datetime, '%Y-%m-%d %H:%M:%S', Supra.data.get('dateFormat') + ' ' + Supra.data.get('timeFormatShort'));
				
				//"Unlock" may not be visible
				var buttons = [];
				if (data.allow_unlock) {
					//Some users may not have permissions to unlock page
					//or may have lower level access than user who locked it
					buttons = [{
						'id': 'unlock',
						'label': Supra.Intl.get([this.getType(), 'unlock']),
						'click': function () {
							if (this.isPage()) {
								this.lockPage(true);
							} else {
								this.lockTemplate(true);
							}
						},
						'context': this,
						'style': 'mid-green'
					}];
				}
				
				//
				Manager.executeAction('Confirmation', {
					'message': template(data),
					'useMask': true,
					'buttons': buttons.concat([
						{
							'id': 'cancel',
							'label': Supra.Intl.get(['buttons', 'cancel'])
						}
					])
				});
			}
		},
		
		/**
		 * Delete page
		 */
		deleteCurrentPage: function (data, locale) {
			var page_data = this.data,
				page_id = page_data.id,
				locale = Supra.data.get('locale');
			
			this.deletePage(page_id, locale, this.onDeleteComplete, this);
		},
		
		/**
		 * Delete page
		 *
		 * @param {Number} page_id Page ID
		 * @param {String} locale Current locale
		 * @param {Function} callback Callback function, optional
		 * @param {Object} context Callback function context, optional
		 */
		deletePage: function (page_id, locale, callback, context) {
			var uri = this.getDataPath('delete');
			
			var post_data = {
				'page_id': page_id,
				'locale': locale,
				'action': 'delete'
			};
			
			Supra.io(uri, {
				'data': post_data,
				'method': 'post',
				'context': context,
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
		 * Update page data and returns new page data to callback
		 * 
		 * @param {Object} data Page data
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback function context
		 */
		updatePage: function (data, callback, context) {
			var uri = this.getDataPath('save');
			
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
			return Manager.Page.data;
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
			} else if ('layout' in changes) {
				/* @TODO */
			}
			
			Supra.mix(this.data, page_data);
		},
		
		/**
		 * Returns true if currently edited page is not template
		 *
		 * @return True if editing page not template
		 * @type {Boolean}
		 */
		isPage: function () {
			var data = Manager.Page.data;
			return !!(!data || data.type == 'page');
		},
		
		/**
		 * Returns true if currently edited page is template
		 *
		 * @return True if editing template
		 * @type {Boolean}
		 */
		isTemplate: function () {
			return !this.isPage();
		},
		
		/**
		 * Returns 'page' is currently editing page or 'template' if editing template
		 *
		 * @return 'page' if editing page, otherwise 'template'
		 * @type {String}
		 */
		getType: function () {
			var data = Manager.Page.data;
			return data ? data.type : 'page';
		}
	});
	
});
