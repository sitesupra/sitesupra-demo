//Invoke strict mode
"use strict";

//Add module group
SU.setModuleGroupPath('linkmanager', Supra.Manager.Loader.getActionFolder('LinkManager') + 'modules/');

//Add module definitions
SU.addModule('linkmanager.sitemap-linkmanager-node', {
	//Link manager can be included from other managers, make sure 'website.' prefix doesn't break it
	path: 'tree-node.js',
	requires: ['supra.tree', 'supra.tree-node']
});

/**
 * Link Manager
 * 
 * Execute arguments:
 *   data - Link Data
 *   options - Link manager options
 *   callback - Link manager close event callback, new link data is passed as first argument
 *   context - Callback execution context
 * 
 * Options:
 *   mode - Mode can be 'link' (to choose page, image or file), 'page' (to choose page) or 'image' (to choose only 'image')
 *   selectable - List of selectable items, use only if you can't do the same with 'mode' option:
 *     {'pages': true, 'external': true, 'images': true, 'files': true}
 *   hideToolbar - toolbar buttons will be hidden while link manager is open
 *   hideLinkControls - link controls will be hidden, default is false
 */
SU('supra.input', 'supra.slideshow', 'linkmanager.sitemap-linkmanager-node', 'supra.medialibrary', function (Y) {
	
	//Shortcuts
	var Manager = SU.Manager,
		Action = Manager.Action,
		Loader = Manager.Loader;
	
	//Add as right bar child
	Manager.getAction('LayoutLeftContainer').addChildAction('LinkManager');
	
	//Modes
	var MODES = {
		'link': {
			//In link mode is also shown "Remove link" button
			'pages': true,
			'group_pages': false,
			'external': true,
			'images': true,
			'files': true
		},
		'page': {
			'pages': true,
			'group_pages': false,
			'external': true,
			'images': false,
			'files': false
		},
		'image': {
			'pages': false,
			'group_pages': false,
			'external': false,
			'images': true,
			'files': false
		}
	};
	
	//Create Action class
	new Action(Action.PluginLayoutSidebar, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'LinkManager',
		
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
		 * Current mode
		 * @type {String}
		 */
		mode: null,
		
		/**
		 * List of selectable item types
		 * @type {Object}
		 */
		selectable: {},
		
		/**
		 * Manager options
		 * @type {Object}
		 */
		options: {},
		
		/**
		 * Callback function
		 * @type {Function}
		 */
		callback: null,
		
		/**
		 * Callback function execution context
		 * @type {Object}
		 */
		context: null,
		
		
		
		
		/**
		 * "Link to file" / "Link to page" slideshow, Supra.Slideshow instance
		 * @type {Object}
		 */
		slideshow: null,
		
		/**
		 * "Internal" / "External" slideshow, Supra.Slideshow instance
		 * @type {Object}
		 */
		link_slideshow: null,
		
		/**
		 * Media library list, Supra.MediaLibraryList instance
		 * @type {Object}
		 */
		medialist: null,
		
		/**
		 * Supra.Form instance
		 * @type {Object}
		 */
		form: null,
		
		
		
		/**
		 * Link data
		 * @type {Object}
		 */
		data: {},
		
		/**
		 * Original data with which link manager was opened
		 * @type {Object}
		 */
		initial_data: null,
		
		/**
		 * Last known locale
		 * @type {String}
		 */
		locale: null,
		
		
		
		/**
		 * Initialize main widgets
		 * Widgets specific to file or page are created when slide is opened
		 * 
		 * @private
		 */
		initialize: function () {
			//Load media library Intl data
				var app_path = Manager.Loader.getStaticPath() + Manager.Loader.getActionBasePath('MediaLibrary');
				Supra.Intl.loadAppData(app_path);
			
			//Create main slideshow
				this.slideshow = new Supra.Slideshow({
					'srcNode': this.one('div.slideshow')
				});
			
			//Remove link button
				var button = this.one('.sidebar-footer button');
				this.button_remove = new Supra.Button({'srcNode': button});
				
			//Create form
				this.form = new Supra.Form({
					'srcNode': this.one('form')
				});
		},
		
		/**
		 * Render main widgets and add event listeners
		 * 
		 * @private
		 */
		render: function () {
			//Toolbar buttons
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			//Slideshow widget
				this.slideshow.render();
				this.slideshow.after('slideChange', this.onMainSlideChange, this);
				
				//On main slide link click open specific slide
				var links = this.all('#linkToRoot a[data-slideshow]');
					links.on('click', this.openTargetSlide, this);
			
			//Back and Close buttons
				this.get('backButton')
						.hide()
						.on('click', this.scrollBack, this);
				
				this.get('controlButton')
						.on('click', this.close, this);
			
			//Remove link button
				this.button_remove
						.render()
						.on('click', this.removeLink, this);
			
			//Create form
				this.form.render();
		},
		
		/**
		 * On slideshow slide change update heading, button visibility
		 * and call appropriate callback function: onLinkToPage or onLinkToFile
		 * 
		 * @param {Object} evt Event object
		 * @private
		 */
		onMainSlideChange: function (evt) {
			if (evt.newVal == evt.prevVal) return;
			
			var slide_id = evt.newVal,
				
				fn = null,
				node = null,
				
				show_back_button = true,
				selectable = this.selectable,
				medialibrary_visible = selectable.files || selectable.images,
				link_visible = selectable.pages || selectable.external,
				
				title = SU.Intl.get(['linkmanager', 'title']);
			
			//Show or hide back button
				if (!medialibrary_visible || !link_visible) {
					//If media library or link manager can't be selected
					//then there is no need to go to root slide
					if (this.slideshow.history.length <= 2) {
						show_back_button = false;
					}
				} else if (this.slideshow.history.length <= 1) {
					show_back_button = false;
				}
				
				if (!show_back_button) {
					this.get('backButton').hide();
				} else {
					this.get('backButton').show();
				}
				
			//Update title
				if (this.slideshow.history.length > 1) {
					title += ' ' + SU.Intl.get(['linkmanager', slide_id == 'linkToPage' ? 'title_page' : 'title_file']);
				}
				
				this.set('title', title);
			
			//Call slide callback if there is one
				if (slide_id in this.slide) {
					var node = this.slideshow.getSlide(slide_id);
					this.slide[slide_id].call(this, node);
				}
		},
		
		/**
		 * Hide action
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			
			//Show previous buttons
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			//Hide action
			Manager.getAction('LayoutLeftContainer').unsetActiveAction(this.NAME);
		},
		
		/**
		 * Scroll back slideshow 
		 */
		scrollBack: function () {
			//Back button also controls medialist slideshow
			if (this.slideshow.get('slide') == 'linkToFile') {
				if (!this.medialist.slideshow.isRootSlide()) {
					this.medialist.slideshow.scrollBack();
					return;
				}
			}
			
			this.slideshow.scrollBack();
			this.updateInsertButton();
		},
		
		/**
		 * On slideshow link click navigate to slide
		 */
		openTargetSlide: function (e) {
			var target = e.target.closest('a'),
				id = target.getAttribute('data-slideshow');
			
			if (id) {
				this.slideshow.set('slide', id);
			}
		},
		
		/**
		 * When slide is selected one of these callbacks will be called
		 */
		slide: {
			/**
			 * When "Link to page" slide is opened update state
			 * and create widgets if needed
		 	* 
		 	* @param {Object} node Container node
			 */
			linkToPage: function (node) {
				if (!this.link_slideshow) {
					//Internal / External
						//Create slideshow
						var slideshow = this.link_slideshow = (new Supra.Slideshow({
							'srcNode': node.one('div.slideshow')
						})).render();
						
						//On "Internal / External" switch value change show slide
						this.form.getInput('linkManagerType').on('change', function (evt) {
							var slide = 'linkManager' + evt.value.substr(0,1).toUpperCase() + evt.value.substr(1);
							slideshow.set('slide', slide);
						}, this);
						
						//On "Internal / External" hide show slide
						this.form.getInput('linkManagerType').on('visibleChange', function (evt) {
							if (evt.prevVal != evt.newVal) {
								Y.one('#linkToPage').setClass('no-switch', !evt.newVal);
							}
						}, this);
						
						//On href change update button label
						this.form.getInput('href').on('change', this.updateInsertButton, this);
						
						this.link_slideshow.on('slideChange', this.updateInsertButton, this);
						
						this.link_slideshow.syncUI();
					
					//Create tree
						//Use sitemap data
						this.locale = Supra.data.get('locale');
						var sitemap_data_path = SU.Manager.Loader.getActionInfo('SiteMap').path_data +
												'?locale=' + this.locale +
												'&existing_only=1';
						
						//Create tree
						this.tree = new Supra.Tree({
							'srcNode': node.one('.tree'),
							'requestUri': sitemap_data_path,
							'groupNodesSelectable': this.options.selectable.group_pages,
							'defaultChildType': Supra.LinkMapTreeNode
						});
						this.tree.plug(SU.Tree.ExpandHistoryPlugin);
						this.tree.render();
						this.tree.set('loading', true);
						
						//Update scrollbars on toggle
						this.tree.on('toggle', function () {
							this.get('boundingBox').closest('.su-scrollable-content').fire('contentResize');
						});
						
						//On node change update button label
						this.tree.after('selectedNodeChange', this.updateInsertButton, this);
				}
				
				this.updateBackButton();
				this.updateInsertButton();
			},
			
			/**
			 * When "Link to file" slide is opened create widgets
			 * and reload medialist
			 * 
			 * @param {Object} node
			 */
			linkToFile: function (node) {
				if (!this.medialist) {
					//"Open App" button
						var btn = new Supra.Button({'srcNode': node.one('button'), 'style': 'small'});
						btn.on('click', function () {
							Manager.executeAction('MediaLibrary');
							Manager.getAction('MediaLibrary').once('hide', function () {
								//Reload data
								this.medialist.reload();
							}, this);
						}, this);
						btn.render();
						
					//Create list widget
						var medialibrary = Manager.getAction('MediaLibrary');
						var list = this.medialist = (new Supra.MediaLibraryList({
							'srcNode': node.one('#linkToFileMediaList'),
							'foldersSelectable': false,
							'filesSelectable': false,
							'listURI': medialibrary.getDataPath('list'),
							'viewURI': medialibrary.getDataPath('view'),
							'displayType': this.getMediaListDisplayType()
						})).render();
						
						//On file select change button to "Insert"
						list.slideshow.after('slideChange', this.updateInsertButton, this);
						list.slideshow.after('slideChange', this.updateBackButton, this);
				} else {
					//Update displayType
						var display_type = this.getMediaListDisplayType();
						if (display_type != this.medialist.get('displayType')) {
							this.medialist.set('displayType', display_type);
						}
					
					//Reload data
						this.medialist.reload();
				}
			},
		},
		
		/**
		 * Returns media list display type based on what can be selected
		 * 
		 * @return Display type
		 * @type {Number}
		 * @private
		 */
		getMediaListDisplayType: function () {
			var display_type = Supra.MediaLibraryList.DISPLAY_ALL;
			
			if (!this.selectable.files) {
				//Images only
				display_type = Supra.MediaLibraryList.DISPLAY_IMAGES;
			} else if (this.selectable.images) {
				//Files only
				display_type = Supra.MediaLibraryList.DISPLAY_FILES;
			}
			
			return display_type;
		},
		
		/**
		 * Update button label to "Insert" or "Close"
		 * 
		 * @private
		 */
		updateInsertButton: function () {
			var show_insert = false;
			
			switch(this.slideshow.get('slide')) {
				case 'linkToPage':
					switch(this.link_slideshow.get('slide')) {
						case 'linkManagerInternal':
							//Tree tab
							if (this.tree.get('selectedNode')) {
								show_insert = true;
							}
							break;
						case 'linkManagerExternal':
							//External href input tab
							if (Y.Lang.trim(this.form.getInput('href').get('value'))) {
								show_insert = true;
							}
							break;
					}
					break;
				case 'linkToFile':
					//Media library tab
					var item = this.medialist.getSelectedItem();
					if (item && item.type != Supra.MediaLibraryData.TYPE_FOLDER) {
						show_insert = true;
					}
					break;
			}
			
			var button = this.get('controlButton');
			button.set('label', show_insert ? '{#buttons.insert#}' : '{#buttons.close#}');
		},
		
		/**
		 * Update back button visibilty when in media library slide
		 */
		updateBackButton: function () {
			switch(this.slideshow.get('slide')) {
				case 'linkToFile':
					//Don't show back button on first slide if there is no 'linkToPage' slide
					if (!this.selectable.pages && !this.selectable.external) {
						if (this.medialist.slideshow.history.length <= 1) {
							this.get('backButton').hide();
						} else {
							this.get('backButton').show();
						}
					}
					break;
			}
		},
			
		/**
		 * Update UI so that user can select only items matching
		 * configuration (options.selectable)
		 * 
		 * @private
		 */
		setDisplayMode: function (selectable) {
			//Update settings
			Supra.mix(this.selectable, selectable);
			
			if (!selectable.files && !selectable.images) {
				//Only pages can be selected
				this.slideshow.set('noAnimations', true);
				this.slideshow.scrollBack();
				this.slideshow.set('slide', 'linkToPage');
				this.slideshow.set('noAnimations', false);
				
				//Switch between Internal and External not needed?
				if (!selectable.pages || !selectable.external) {
					this.form.getInput('linktype').hide();
				} else {
					this.form.getInput('linktype').show();
				}
				
				if (!selectable.pages) {
					//Only external link
					this.link_slideshow.set('noAnimations', true);
					this.link_slideshow.set('slide', 'linkManagerExternal');
					this.link_slideshow.set('noAnimations', false);
				} else if (!selectable.external) {
					//Only pages link
					this.link_slideshow.set('noAnimations', true);
					this.link_slideshow.set('slide', 'linkManagerInternal');
					this.link_slideshow.set('noAnimations', false);
				}
				
			} else if (!selectable.pages && !selectable.external) {
				//Only files or images can be selected
				this.slideshow.set('noAnimations', true);
				this.slideshow.scrollBack();
				this.slideshow.set('slide', 'linkToFile');
				this.slideshow.set('noAnimations', false);
			} else {
				this.form.getInput('linktype').show();
			}
		},
		
		/**
		 * Restore state matching data
		 * 
		 * @param {Object} data Link data
		 * @private
		 */
		setData: function (data) {
			this.initial_data = data;
			
			data = SU.mix({
				'type': '',
				'target': '',
				'title': '',
				'href': '',
				'page_id': null,
				'page_master_id': null,
				'file_id': null,
				'file_path': [],
				'file_title': '',
				'linktype': 'internal'
			}, data || {});
			
			//Show footer for existing link and hide for new link
			var hide_footer = (this.mode == 'link' && !data.page_id && !data.file_id && !data.href);
			
			this.one('.sidebar-footer').setClass('hidden', hide_footer);
			this.one('.sidebar-content').setClass('has-footer', !hide_footer);
			
			//Hide link controls?
			this.one('.sidebar-content').setClass('has-link-controls', !this.options.hideLinkControls);
			
			//Since file title is different input 'title' is used to transfer data
			//reverse it
			if (data.title && !data.file_title) {
				data.file_title = data.title;
			}
			
			if (this.link_slideshow) {
				this.link_slideshow.set('noAnimations', true);
			}
			
			//If locale has changed since last time this action was opened then reload tree data
			var reloading_tree = false;
			if (this.locale && this.locale != Supra.data.get('locale')) {
				reloading_tree = true;
			}
			
			//If some option changed, then reload tree also
			if (this.tree) {
				//If some option changed, then reload tree also
				if (this.options.selectable.group_pages != this.tree.get('groupNodesSelectable')) {
					this.tree.set('groupNodesSelectable', this.options.selectable.group_pages);
					reloading_tree = true;
				}
				
				//Reload tree if needed
				if (reloading_tree) {
					this.locale = Supra.data.get('locale');
					var sitemap_data_path = SU.Manager.Loader.getActionInfo('SiteMap').path_data +
											'?locale=' + this.locale +
											'&existing_only=1';
					
					this.tree.set('requestUri', sitemap_data_path);
					this.tree.reload();
				}
				
				//Reset tree selected node
				this.tree.set('selectedNode', null);
			}
			
			//Change "target" value from "_blank" to true and "" to false
			//to make it compatible with checkbox
			data.target = (data.target == '_blank' ? true : false);
			
			//Set values by input name
			this.form.setValues(data, 'name');
			
			this.data = data;
			this.slideshow.set('noAnimations', true);
			
			switch (data.resource) {
				case 'page':
					this.slideshow.set('slide', 'linkToPage');
					
					this.link_slideshow.set('noAnimations', true);
					this.form.getInput('linkManagerType').set('value', 'internal');
					this.link_slideshow.set('noAnimations', false);
					
					var key,
						value;

					// Supports selection by page ID or master ID
					if (data.page_id) {
						key = 'id';
						value = data.page_id;
					} else if (data.page_master_id) {
						key = 'master_id';
						value = data.page_master_id;
					}

					if (key) {
						var node = this.tree.getNodeBy(key, value);
						if (!node || reloading_tree) {
							this.tree.once('render:complete', function () {
								this.tree.set('selectedNode', null);

								var node = this.tree.getNodeBy(key, value);
								if (node) this.tree.set('selectedNode', node);
								
								//Update scrollbars
								this.tree.get('boundingBox').closest('.su-scrollable-content').fire('contentResize');
							}, this);
						} else {
							this.tree.set('selectedNode', node);
						}
					}
					
					break;
				case 'link':
					this.slideshow.set('slide', 'linkToPage');
					
					this.link_slideshow.set('noAnimations', true);
					this.form.getInput('linkManagerType').set('value', 'external');
					this.link_slideshow.set('noAnimations', false);
					
					break;
				case 'file':
					this.slideshow.set('slide', 'linkToFile');
					
					var path = [].concat(data.file_path, [data.file_id]);
					this.medialist.set('noAnimations', true);
					this.medialist.open(path);
					this.medialist.set('noAnimations', false);
					
					break;
				default:
						
					if (!this.selectable.pages && !this.selectable.external) {
						//Only media library
						if (this.medialist) this.medialist.open(null);
						this.slideshow.set('slide', 'linkToFile');
					} else if (!this.selectable.images && !this.selectable.files) {
						//Only pages
						this.slideshow.set('slide', 'linkToPage');
					} else {
						//All, open root folder
						this.slideshow.set('slide', 'linkToRoot');
						if (this.medialist) this.medialist.open(null);
					}
					
					break;
			}
			
			if (this.link_slideshow) {
				this.link_slideshow.set('noAnimations', false);
			}
			this.slideshow.set('noAnimations', false);
		},
		
		/**
		 * Returns link data
		 * 
		 * @return Link data
		 * @type {Object}
		 * @private
		 */
		getData: function () {
			var data = SU.mix(this.data || {}, this.form.getValues('name')),
				slide_id = this.slideshow.get('slide');
			
			if (slide_id == 'linkToPage') {
				if (data.linktype == 'internal') {
					//Link to page
					var tree_node = this.tree.get('selectedNode'),
						page_data = null,
						page_id = '',
						page_master_id = '',
						page_path = '',
						page_title = data.title || '';
					
					if (tree_node) {
						page_data = tree_node.get('data');
						if (page_data) {
							page_id = page_data.id;
							page_master_id = page_data.master_id;
							page_path = page_data.full_path || page_data.title;
							
							if (this.options.hideLinkControls) {
								page_title = page_data.title;
							}
						}
					}
					
					return {
						'resource': 'page',
						'page_id': page_id,
						'page_master_id': page_master_id,
						'href': page_path,
						'target': data.target ? '_blank' : '',
						'title': page_title
					};
				} else {
					var page_title = data.title;
					if (this.options.hideLinkControls) {
						page_title = data.href;
					}
					
					//Link to external resource
					return {
						'resource': 'link',
						'href': data.href,
						'target': data.target ? '_blank' : '',
						'title': page_title
					};
				}
			} else if (slide_id == 'linkToFile') {
				//Link to file
				var item_data = this.medialist.getSelectedItem();
				if (!item_data) return;
				
				//File path for image is taken from original image
				var file_path = item_data.file_web_path;
				if (!file_path && item_data.type == Supra.MediaLibraryData.TYPE_IMAGE) {
					file_path = item_data.sizes.original.external_path;
				}
				
				return {
					'resource': 'file',
					'href': file_path,
					'target': '',
					'title': data.file_title || data.title,
					'file_id': item_data.id,
					'file_path': item_data.path
				};
			}
		},
		
		
		
		
		/**
		 * Remove link and close manager
		 */
		removeLink: function () {
			if (this.callback) {
				this.callback.call(this.context, null);
				this.callback = this.context = null;
			}
			
			this.close(true);
		},
		
		/**
		 * Close and save data
		 * 
		 * @param {Boolean} allow_remove Removing link is allowed
		 */
		close: function (allow_remove) {
			if (this.callback) {
				var data = this.getData();
				
				if (allow_remove !== true) {
					//If not allowed to remove, then return original data
					data = data || this.initial_data;
				}
				
				this.callback.call(this.context, data);
			}
			
			this.callback = this.context = null;
			this.hide();
		},
		
		/**
		 * Set options
		 * 
		 * @param {Object} options Link manager display options
		 */
		setOptions: function (options) {
			var mode = options && options.mode ? options.mode : 'link',
				selectable = MODES[mode],
				hide_link_controls = mode == 'link' ? false : true;
			
			this.options = {
				'mode': mode,
				'hideToolbar': false,
				'hideLinkControls': hide_link_controls,
				'selectable': selectable,
				
				'container': null
			};
			
			if (options) {
				Supra.mix(this.options, options || {}, true);
			}
			
			if (this.selectable.images || this.selectable.files) {
				//Can safely change container only if sidebar header is
				//not needed, otherwise UI will be unusable
				this.options.container = null;
			}
			
			//Move or restore slide container
			if (!this.options.container) {
				this.slideshow.get('contentBox').append(this.slideshow.getSlide('linkToPage'));
			} else {
				this.options.hideToolbar = false;
				this.options.container.append(this.slideshow.getSlide('linkToPage'));
			}
			
			this.mode = this.options.mode;
			this.selectable = this.options.selectable;
		},
		
		/**
		 * Set callback
		 * 
		 * @param {Function} callback Callback function which is called when LinkManager is closed
		 * @param {Object} context Callback function execution context
		 */
		setCallback: function (callback, context) {
			//Callback
			if (Y.Lang.isFunction(callback)) {
				this.callback = callback;
				this.context = context || this;
			} else {
				this.callback = null;
				this.context = null;
			}
		},
		
		/**
		 * Execute action
		 * 
		 * @param {Object} data Existing link data
		 * @param {Object} options Link manager display options. Optional argument
		 * @param {Function} callback Callback function which is called when LinkManager is closed
		 * @param {Object} context Callback function execution context
		 */
		execute: function (data, options, callback, context) {
			//Options is optional
			if (Y.Lang.isFunction(options)) {
				context = callback;
				callback = options;
				options = null;
			}
			
			//Link manager options
			this.setOptions(options);
			
			//Callback
			this.setCallback(callback, context);
			
			//Set display mode
			this.setDisplayMode(this.selectable);
			
			//Set initial data
			this.setData(data);
			
			//Toolbar
			if (this.options.hideToolbar) {
				Manager.getAction('PageToolbar').setActiveAction(this.NAME);
				Manager.getAction('PageButtons').setActiveAction(this.NAME);
			}
			
			if (!this.options.container) {
				Manager.getAction('LayoutLeftContainer').setActiveAction(this.NAME);
				this.show();
			}
			
			//Update UI
			if (this.slideshow) this.slideshow.syncUI();
			if (this.link_slideshow) this.link_slideshow.syncUI();
			if (this.medialist) this.medialist.slideshow.syncUI();
		}
	});
	
});