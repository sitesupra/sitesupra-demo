Supra(function (Y) {
	//Invoke strict mode
	"use strict";
	
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
			//Load page data if sitemap will not open
			if (Manager.Root.getRoutePath() != Manager.Root.ROUTE_SITEMAP) {
				this.loadPage(data ? data.id : '');
			}
			
			//Wait till blocks and layouts are done
			var queue = 0,
				self = this;
			
			var wait_queue = function () {
				queue++;
				return function () { queue--; if (!queue) self.onLayoutReady(); };
			};
			
			//Load all other actions
			Manager.executeAction('Blocks', wait_queue());
			Manager.executeAction('LayoutContainers', wait_queue());
		},
		
		/**
		 * When layout is ready create buttons and content
		 */
		onLayoutReady: function () {
			var pagecontent = Manager.getAction('PageContent'),
				pagetoolbar = Manager.getAction('PageToolbar');
			
			Manager.executeAction('PageHeader');
			Manager.executeAction('PageButtons');
			Manager.executeAction('PageContent');
			
			//Show all actions
			Manager.getAction('PageContent').show();
			
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
				iframe_handler = pagecontent.iframe_handler,
				layoutTopContainer = Supra.Manager.getAction('LayoutTopContainer'),
				layoutLeftContainer = Supra.Manager.getAction('LayoutLeftContainer'),
				layoutRightContainer = Supra.Manager.getAction('LayoutRightContainer');
				
			//iFrame position sync with other actions
			iframe_handler.plug(Supra.PluginLayout, {
				'offset': [0, 0, 0, 0]	//Default offset from page viewport
			});
			
			//Top bar 
			iframe_handler.layout.addOffset(layoutTopContainer, layoutTopContainer.one(), 'top', 0);
			iframe_handler.layout.addOffset(layoutLeftContainer, layoutLeftContainer.one(), 'left', 0);
			iframe_handler.layout.addOffset(layoutRightContainer, layoutRightContainer.one(), 'right', 0);
		},
		
		/**
		 * Converts path to page ID
		 * 
		 * @param {String} page_path
		 * @private
		 */
		getPageIdFromPath: function (page_path, callback, context) {
			Supra.io(this.getDataPath('path-to-id'), {
				'data': {
					'page_path': page_path,
					'locale': Supra.data.get('locale')
				},
				'context': context,
				'on': {
					'complete': callback
				}
			}, this);
		},
		
		/**
		 * Reload page data and content
		 */
		reloadPage: function () {
			//Reload page data
			this.loadPage(this.data.id);
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
			
			//Add loading style to the iframe
			var iframe_handler = Supra.Manager.PageContent.getIframeHandler();
			if (iframe_handler) iframe_handler.set('loading', true);
			
			//Add loading icon to button
			Supra.Manager.PageButtons.buttons.Root[0].set('loading', true);
			
			// Call the sitemap right ahead if page id is empty
			if (typeof page_id != 'string' || page_id == '') {
				this.onLoadComplete(null, false);
				return;
			}
			
			//Load page data
			Supra.io(this.getDataPath('page'), {
				'data': {
					'page_id': page_id || '',
					'locale': Supra.data.get('locale')
				},
				'permissions': [{
					'id': page_id,
					'type': 'page'
				}],
				'context': this,
				'on': {
					'complete': this.onLoadComplete
				}
			});
		},
		
		/**
		 * On page load complete update data
		 * 
		 * @param {Number} transaction Request transaction ID
		 * @param {Object} data Response JSON data
		 */
		onLoadComplete: function (data, status) {
			this.loading = false;
			var allow_edit = false;
			
			//Is user authorized to edit page?
			if (status && data) {
				allow_edit = Supra.Permission.get('page', data.id, 'edit_page', false);
				if (allow_edit === true && data.allow_edit === false) {
					allow_edit = false;
				}
			}
			
			// Change current locale
			if (status && data && data.locale) {
				Supra.data.set('locale', data.locale);
			}
			
			if (allow_edit) {
				//Edit button
				var button_edit = Supra.Manager.PageButtons.buttons.Root[0],
					button_unlock = Supra.Manager.PageButtons.buttons.Root[1],
					message_unlock = button_unlock.get('boundingBox').previous('p');
				
				//Remove loading icons from Edit button, which was added by publish and load
				button_edit.set('loading', false);
				
				if (status) {
					Supra.data.set({'page': {'id': data.id}});
					
					this.data = data;
					
					//Fire 'loaded' event, this will trigger PageContent to update iframe
					this.fire('loaded', {'data': data});
					
					//Check lock status
					var userlogin = Supra.data.get(['user', 'login']);
					if (data.lock && data.lock.userlogin != userlogin) {
						//Page locked by someone else
						
						button_edit.hide();
						button_unlock.show();
						button_unlock.set('disabled', !data.lock.allow_unlock);
						
						//Show message "Locked by ... on ..."
						if (!message_unlock) {
							message_unlock = Y.Node.create('<p class="yui3-page-butons-message"></p>');
							button_unlock.get('boundingBox').insert(message_unlock, 'before');
						}
						
						var template = Supra.Intl.get([this.getType(), 'locked_message']),
							lock_data = Supra.mix({}, data.lock, {
								'datetime': Y.DataType.Date.reformat(data.lock.datetime, 'in_datetime', 'out_datetime_short')
							});
						
						template = Supra.Template.compile(template);
						message_unlock.set('innerHTML', template(lock_data));
						
					} else if (data.lock) {
						//Page locked by user, switch to editing
						
						button_edit.show();
						button_unlock.hide();
						if (message_unlock) message_unlock.remove();
						
						//On first page load page content may not exist yet
						var content_action = Manager.getAction('PageContent');
						if (content_action.get('executed')) {
							content_action.startEditing();
						} else {
							content_action.after('execute', function () {
								content_action.startEditing();
							});
						}
						
					} else {
						//Page not locked, show "Edit page" button
						
						button_edit.show();
						button_unlock.hide();
						if (message_unlock) message_unlock.remove();
						
					}
					
					//Update localization list
					Manager.getAction('PageHeader').setAvailableLocalizations(data.localizations, data.global);
					
					//Update edit button label to "Edit page" or "Edit template"
					var label = Supra.Intl.get([this.getType(), 'edit']);
					button_edit.set('label', label);
					
				} else {
					//Remove loading style
					Y.one('body').removeClass('loading');
					button_edit.hide();
				}
			} else {
				if (status) {
					//Set data
					this.data = data;
					this.fire('loaded', {'data': data});
					
					//Hide edit buttons and message
					var button_edit = Supra.Manager.PageButtons.buttons.Root[0],
						button_unlock = Supra.Manager.PageButtons.buttons.Root[1],
						message_unlock = button_unlock.get('boundingBox').previous('p');
					
					button_edit.hide();
					button_unlock.hide();
					if (message_unlock) message_unlock.remove();
					
					//Update localization list
					Manager.getAction('PageHeader').setAvailableLocalizations(data.localizations, data.global);
					
				} else {
					//Remove loading style
					Y.one('body').removeClass('loading');
					
					//Open sitemap
					Supra.Manager.executeAction('SiteMap');
				}
			}
			
			if (status) {
				//Show page header
				Manager.getAction('PageHeader').execute();
			}
		},
		
		/**
		 * Publish page
		 */
		publishPage: function () {
			//Send request
			var uri = this.getDataPath('publish'),
				page_data = this.getPageData();
			
			//Permissions
			if (!Supra.Permission.get('page', page_data.id, 'supervise_page', false)) {
				return false;
			}
			
			//Change "Edit" button style to loading
			var button_edit = Supra.Manager.PageButtons.buttons.Root[0];
			button_edit.set('loading', true);
			
			
			var post_data = {
				'page_id': page_data.id,
				'locale': Supra.data.get('locale'),
				'action': 'publish'
			};
			
			Supra.io(uri, {
				'data': post_data,
				'method': 'post',
				
				//When called from Supra.Manager.Template context should still be Page
				'context': Manager.Page,
				'on': {
					'success': Manager.Page.onPublishPage,
					'failure': Manager.Page.onPublicFailure
				}
			});
		},
		
		/**
		 * On page publish reload page data
		 */
		onPublishPage: function () {
			this.onUnlockPage();
			
			//Reload page data
			this.reloadPage();
			
			//Show notification
			Supra.Manager.executeAction('Notification', Supra.Intl.get(['page', 'publish_notification']));
			
			//Change page version title
			Supra.Manager.getAction('PageHeader').setVersionTitle('published');
		},
		
		/**
		 * On publish failure re-enable button
		 */
		onPublicFailure: function () {
			this.onUnlockPage();
			
			//Change "Edit" button style to normal
			var button_edit = Supra.Manager.PageButtons.buttons.Root[0];
			button_edit.set('loading', false);
		},
		
		/**
		 * Unlock page, same is automatically done in publish
		 */
		unlockPage: function (force) {
			var uri = this.getDataPath('unlock'),
				page_data = this.getPageData(),
				button_unlock = Supra.Manager.PageButtons.buttons.Root[1];
			
			var post_data = {
				'page_id': page_data.id,
				'locale': Supra.data.get('locale'),
				'force': (force ? 1 : 0)
			};
			
			button_unlock.set('loading', true);
			
			Supra.io(uri, {
				'data': post_data,
				'method': 'post',
				'context': Manager.Page,
				'on': {
					'success': Manager.Page.onUnlockPage
				}
			});
		},
		
		/**
		 * Handle successful unlock
		 */
		onUnlockPage: function (data, status) {
			//Show edit and hide unlock buttons
			var button_edit = Supra.Manager.PageButtons.buttons.Root[0],
				button_unlock = Supra.Manager.PageButtons.buttons.Root[1],
				message_unlock = button_unlock.get('boundingBox').previous('p');
			
			button_edit.show();
			button_unlock.hide();
			button_unlock.set('loading', false);
			if (message_unlock) message_unlock.remove();
			
			//Remove lock information from page
			if (Manager.Page.data && Manager.Page.data.lock) {
				delete(Manager.Page.data.lock);
			}
			
			if (status) {
				//Show notification if unlock was called as Supra.io callback
				//and not directly from onPublishPage
				Supra.Manager.executeAction('Notification', Supra.Intl.get(['page', 'unlock_notification']));
				
				//Change page version title
				Supra.Manager.getAction('PageHeader').setVersionTitle('draft');
			}
		},
		
		/**
		 * Lock page
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
					'complete': this.onLockResponse
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
		onLockResponse: function (data /* Response data */, status /* Response status */) {
			//Unset loading style
			var buttons = Manager.PageButtons.buttons.Root;
			buttons[0].set('loading', false);
			
			//Handle response
			if (status && data === true || data === 1) {
				
				Manager.Page.data.lock = {
					'userlogin': Supra.data.get(['user', 'login'])
				};
				
				//Success
				Manager.PageContent.startEditing();
				
			} else if (status && data) {
				
				//Compile message template and change date and time format
				var template = Supra.Intl.get([this.getType(), 'locked_message']);
				template = Supra.Template.compile(template);
				
				data.datetime = Y.DataType.Date.reformat(data.datetime, 'in_datetime', 'out_datetime_short');
				
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
						'style': 'small-blue'
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
		 * 
		 * @param {Function} callback Callback function, optional
		 * @param {Object} context Callback function context, optional
		 */
		deleteCurrentPage: function (callback, context) {
			var page_data = this.data,
				page_id = page_data.id,
				locale = Supra.data.get('locale');
			
			this.deletePage(page_id, locale, function (data, status) {
				//Callback function
				if (callback) callback.apply(context || this, arguments);
				
				//Open sitemap
				this.onDeleteComplete(data, status);
			}, this);
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
				'on': {'complete': callback}
			}, context);
		},
		
		/**
		 * Delete virtual folder
		 *
		 * @param {Number} page_id Page ID
		 * @param {String} locale Current locale
		 * @param {Function} callback Callback function, optional
		 * @param {Object} context Callback function context, optional
		 */
		deleteVirtualFolder: function (page_id, locale, callback, context) {
			var action = Manager.getAction('VirtualFolder'),
				uri = action.getDataPath('delete');
			
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
		 * On delete request complete show sitemap
		 * 
		 * @param {Object} data Response JSON data
		 * @param {Boolean} status Request status
		 */
		onDeleteComplete: function (data, status) {
			if (status) {
				// unset current page id
				Supra.Manager.Page.setPageData({id: null});
				
				//Reset toolbar state
				Supra.Manager.PageToolbar.setActiveAction('Root');
				Supra.Manager.PageButtons.setActiveAction('Root');
				
				Supra.Manager.PageContent.onStopEditingRoute();
				Supra.Manager.executeAction('SiteMap');
			}
		},
		
		/**
		 * Rename virtual folder
		 */
		renameVirtualFolder: function (page_id, locale, title, callback, context) {
			var action = Manager.getAction('VirtualFolder'),
				uri = action.getDataPath('rename');
			
			var post_data = {
				'page_id': page_id,
				'title': title,
				'locale': locale,
				'action': 'rename'
			};
			
			Supra.io(uri, {
				'data': post_data,
				'method': 'post',
				'context': context,
				'on': {'success': callback}
			}, context);
		},
		
		/**
		 * Duplicate page
		 *
		 * @param {Object} post_data Post Data
		 * @param {Function} callback Callback function, optional
		 * @param {Object} context Callback function context, optional
		 */
		duplicatePage: function (post_data, callback, context) {
			var uri = this.getDataPath('duplicate');
			
			post_data = Supra.mix({
				'action': 'duplicate'
			}, post_data);
			
			Supra.io(uri, {
				'data': post_data,
				'method': 'post',
				'context': context,
				'on': {'complete': callback}
			}, context);
		},
		
		/**
		 * Create page localization
		 *
		 * @param {Number} page_id Page ID
		 * @param {Object} newData Object containing locale for the new page and path, title
		 * @param {String} source_locale Locale from which page will be copied
		 * @param {Function} callback Callback function, optional
		 * @param {Object} context Callback function context, optional
		 */
		createPageLocalization: function (page_id, newData, source_locale, callback, context) {
			var uri = this.getDataPath('create-localization');
			
			var post_data = Supra.mix({
				'page_id': page_id,
				'source_locale': source_locale,
				'action': 'duplicate'
			}, newData);
			
			Supra.io(uri, {
				'data': post_data,
				'method': 'post',
				'context': context,
				'on': {'complete': callback}
			}, context);
		},
		
		/**
		 * Create new page and returns page data to callback
		 * 
		 * @param {Object} data Page data
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback function context
		 */
		createPage: function (data, callback, context) {
			var uri = Supra.Url.generate('cms_pages_page_create');
			
			Supra.io(uri, {
				'data': data,
				'method': 'post',
				'context': context,
				'on': {
					'complete': callback
				}
			});
		},

		/**
		 * Create new group page and returns page data to callback
		 *
		 * @param {Object} data Page data
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback function context
		 */
		createGroup: function (data, callback, context) {
			var uri = Supra.Url.generate('cms_pages_group_create');

			Supra.io(uri, {
				'data': data,
				'method': 'post',
				'context': context,
				'on': {
					'complete': callback
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
					'complete': callback
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
			//Use Manager.Page reference, because this function is
			//copied (ussing assign) and used in Manager.Template
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
		},
		
		/**
		 * Close editing and open sitemap
		 */
		openSiteMap: function () {
			var test  = null,
				tests = [
				{
					'test': function () { return Supra.Manager.getAction('Dashboard').get('visible'); },
					'close': function () { return Supra.Manager.getAction('Dashboard').hide(); },
					'delay': 350
				},
				{
					'test': function () { return Supra.Manager.getAction('PageSettings').get('visible'); },
					'close': function () { return Supra.Manager.getAction('PageSettings').onDoneButton(); },
					'delay': 250
				},
				{
					'name': 'PageSourceEditor'
				},
				{
					'name': 'PageHistory'
				},
				{
					'name': 'PageInsertBlock'
				},
				{
					'name': 'BlocksView'
				},
				{
					'name': 'PageDesignManager'
				},
				{
					'name': 'MediaLibrary'
				},
				{
					'test': function () { return Supra.Manager.getAction('SlideshowManager').get('visible'); },
					'close': function () { return Supra.Manager.getAction('SlideshowManager').close(); },
					'delay': 350
				},
				{
					'test': function () { return Supra.Manager.getAction('GalleryManager').get('visible'); },
					'close': function () { return Supra.Manager.getAction('GalleryManager').applyChanges(); },
					'delay': 350
				},
				{
					'test': function () { return Supra.Manager.getAction('Gallery').get('visible'); },
					'close': function () { return Supra.Manager.getAction('Gallery').close(); },
					'delay': 350
				},
				{
					'test': function () { return Supra.Manager.getAction('PageContent').isEditing(); },
					'close': function () { return Supra.Manager.getAction('PageContent').stopEditing(); },
					'delay': 100
				}
			];
			
			for (var i=0,ii=tests.length; i<ii; i++) {
				test = tests[i];
				
				if (typeof test.name === 'string') {
					test = {
						'test': function () { return Supra.Manager.getAction(tests[i]).get('visible'); },
						'close': function () { return Supra.Manager.getAction(tests[i]).hide(); },
						'delay': test.delay || 250
					};
				}
				if (test.test()) {
					test.close();
					Y.later(test.delay, this, this.openSiteMap);
					return;
				}
			}
			
			var page = Manager.Page.getPageData();
			if (page.application_id == 'blog') {
				// Open blog
				Supra.Manager.executeAction('Blog', {
					'parent_id': page.application_page_id
				});
			} else {
				Supra.Manager.Root.routeSiteMapSave();
			}
		}
	});
	
});
