//Add module group
Supra.setModuleGroupPath('linkmanager', Supra.Manager.Loader.getActionFolder('LinkManager') + 'modules/');

//Add module definitions
Supra.addModule('linkmanager.sitemap-linkmanager-node', {
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
Supra('supra.input', 'supra.slideshow', 'linkmanager.sitemap-linkmanager-node', 'supra.medialibrary', function (Y) {
		//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager,
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
			'files': true,
			'email': true
		},
		'page': {
			'pages': true,
			'group_pages': false,
			'external': true,
			'images': false,
			'files': false,
			'email': false
		},
		'page-internal': {
			'pages': true,
			'group_pages': false,
			'external': false,
			'images': false,
			'files': false,
			'email': false
		},
		'page-external': {
			'pages': false,
			'group_pages': false,
			'external': true,
			'images': false,
			'files': false,
			'email': false
		},
		'image': {
			'pages': false,
			'group_pages': false,
			'external': false,
			'images': true,
			'files': false,
			'email': false
		},
		'email': {
			'pages': false,
			'group_pages': false,
			'external': false,
			'images': false,
			'files': false,
			'email': true
		},
		'tree': {
			'pages': true,
			'group_pages': false,
			'external': false,
			'images': false,
			'files': false,
			'email': false
		}
	};
	
	//Regular expression to test external link without protocol
	var TEST_EXTERNAL_LINK = /^[a-z0-9]+[a-z0-9-]*[a-z0-9]+(\.[a-z0-9]+)+(\/|$)/i,
		REPLACE_LOCAL_DOMAIN = /https?:\/\/[^\/]+/;
	
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
				
				//Main slide buttons
				var content = this.one('#linkToRoot .select'),
					path    = Supra.Manager.Loader.getActionInfo('LinkManager').folder,
					button;
				
				button = new Supra.Button({
					'style': 'link-type',
					'icon': path + 'images/page.png',
					'label': Supra.Intl.get(['linkmanager', 'link_to_page'])
				});
				button.render(content);
				button.addClass(button.getClassName('fill'));
				button.on('click', this.openTargetSlide, this, 'linkToPage');
				
				button = new Supra.Button({
					'style': 'link-type',
					'icon': path + 'images/file.png',
					'label': Supra.Intl.get(['linkmanager', 'link_to_file'])
				});
				button.render(content);
				button.addClass(button.getClassName('fill'));
				button.on('click', this.openTargetSlide, this, 'linkToFile');
				
				button = new Supra.Button({
					'style': 'link-type',
					'icon': path + 'images/email.png',
					'label': Supra.Intl.get(['linkmanager', 'link_to_email'])
				});
				button.render(content);
				button.addClass(button.getClassName('fill'));
				button.on('click', this.openTargetSlide, this, 'linkToEmail');
				
			
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
				
				title = Supra.Intl.get(['linkmanager', 'title']);
			
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
				if (slide_id == 'linkToPage') {
					if (this.mode === 'tree') {
						title += ' ' + Supra.Intl.get(['linkmanager', 'title_tree']);
					} else {
						title += ' ' + Supra.Intl.get(['linkmanager', 'title_page']);
					}
				} else if (slide_id == 'linkToFile') {
					title += ' ' + Supra.Intl.get(['linkmanager', 'title_file']);
				} else if (slide_id == 'linkToEmail') {
					title += ' ' + Supra.Intl.get(['linkmanager', 'title_email']);
				}
				
				this.set('title', title);
			
			//Call slide callback if there is one
				if (slide_id in this.slide) {
					var node = this.slideshow.getSlide(slide_id);
					this.slide[slide_id].call(this, node);
				}
			
			//Update "Insert" / "Close" button label 
				this.updateInsertButton();
		},
		
		/**
		 * Hide action
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			
			//Reset widget state
			if (this.tree) {
				this.tree.resetAll();
			}
			
			//Show previous buttons
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			//Retore editor toolbar
			if (this.options.restoreEditorToolbar) {
				this.options.restoreEditorToolbar = false;
				Manager.getAction('EditorToolbar').execute();
			}
			
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
		openTargetSlide: function (e, id) {
			//var target = e.target.closest('a'),
			//	id = target.getAttribute('data-slideshow');
			
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
								Y.one('#linkToPage').toggleClass('no-switch', !evt.newVal);
							}
						}, this);
						
						//On href change update button label
						this.form.getInput('href').after('input', this.updateInsertButton, this);
						this.form.getInput('href').after('change', this.updateInsertButton, this);
						
						this.link_slideshow.on('slideChange', this.updateInsertButton, this);
						
						this.link_slideshow.syncUI();
					
					//Create tree
						//Use sitemap data
						this.locale = Supra.data.get('locale');
						this.requestUri = this.getTreeRequestURI();
						
						//Create tree
						this.tree = new Supra.Tree({
							'srcNode': node.one('.tree'),
							'requestUri': this.requestUri,
							'groupNodesSelectable': this.selectable.group_pages,
							'defaultChildType': Supra.LinkMapTreeNode
						});
						this.tree.plug(Supra.Tree.ExpandHistoryPlugin);
						this.tree.render();
						this.tree.set('loading', true);
						
						//Update scrollbars on toggle
						this.tree.on('toggle', function () {
							this.get('boundingBox').closest('.su-scrollable-content').fire('contentResize');
						});
						
						//On node change update button label
						this.tree.after('selectedNodeChange', this.updateInsertButton, this);
				} else {
					if (!this.tree.getData() && this.selectable.pages) {
						this.tree.set('loading', true);
						
						this.requestUri = this.getTreeRequestURI();
						this.tree.set('requestUri', this.requestUri);
						
						this.tree.reload();
					}
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
							
							//Use media library action data path
							'listURI': medialibrary.getDataPath('list'),
							'viewURI': medialibrary.getDataPath('view'),
							
							//Allow selecting files and images
							'foldersSelectable': false,
							'filesSelectable': false,
							'imagesSelectable': false,
							
							//Display only specific items
							'displayType': this.getMediaListDisplayType()
						})).render();
						
						//On file select change button to "Insert"
						list.slideshow.after('slideChange', this.updateInsertButton, this);
						list.slideshow.after('slideChange', this.updateBackButton, this);
					
					//Reload data
						this.medialist.reload();
					
				} else {
					//Update displayType
						var display_type = this.getMediaListDisplayType();
						if (display_type != this.medialist.get('displayType')) {
							this.medialist.set('displayType', display_type);
						}
					
					//Reload data
						this.medialist.reload();
					
					//Update slideshow
						this.medialist.slideshow.syncUI();
				}
			},
			
			/**
			 * When "Link to email" slide is opened create widgets
			 * 
			 * @param {Object} node
			 */
			linkToEmail: function (node) {
				if (!this.link_email) {
					//On href change update button label
					this.form.getInput('email_title').after('input', this.updateInsertButton, this);
					this.form.getInput('email_title').after('change', this.updateInsertButton, this);
					this.link_email = true;
				}
			}
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
			} else if (!this.selectable.images) {
				//Files only
				display_type = Supra.MediaLibraryList.DISPLAY_FILES;
			}
			
			return display_type;
		},
		
		/**
		 * Returns request URI for tree
		 * 
		 * @returns {String} Request URI
		 * @private
		 */
		getTreeRequestURI: function () {
			var uri = this.options.treeRequestURI;
			
			if (uri) {
				return uri + (uri.indexOf('?') !== -1 ? '&' : '?') + 'locale=' + this.locale;
			} else {
				return Supra.Url.generate('cms_pages_sitemap_pages_list')
						+ '?locale='
						+ this.locale
						+ '&existing_only=1';
				
//				return Supra.Manager.Loader.getActionInfo('SiteMap').path_data +
//							'?locale=' + this.locale +
//							'&existing_only=1';
			}
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
					if (item && item.type != Supra.MediaLibraryList.TYPE_FOLDER) {
						show_insert = true;
					}
					break;
				case 'linkToEmail':
					//Email tab
					if (Y.Lang.trim(this.form.getInput('email_title').get('value'))) {
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
		setDisplayMode: function (mode, selectable) {
			//Update settings
			Supra.mix(this.selectable, selectable);
			
			if (!selectable.files && !selectable.images && !selectable.email) {
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
				
			} else if (!selectable.pages && !selectable.external && !selectable.email) {
				//Only files or images can be selected
				this.slideshow.set('noAnimations', true);
				this.slideshow.scrollBack();
				this.slideshow.set('slide', 'linkToFile');
				this.slideshow.set('noAnimations', false);
			} else if (!selectable.pages && !selectable.external && !selectable.files && !selectable.images) {
				// Only email can be selected
				this.slideshow.set('noAnimations', true);
				this.slideshow.scrollBack();
				this.slideshow.set('slide', 'linkToEmail');
				this.slideshow.set('noAnimations', false);
			} else {
				this.form.getInput('linktype').show();
			}
			
			// Remove link label
			var label;
			
			if (mode === 'tree') {
				label = Supra.Intl.get(['linkmanager', 'remove_tree']);
			} else {
				label = Supra.Intl.get(['linkmanager', 'remove_link']);
			}
			
			this.button_remove.set('label', label);
		},
		
		/**
		 * Restore state matching data
		 * 
		 * @param {Object} data Link data
		 * @private
		 */
		setData: function (data) {
			this.initial_data = data;
			
			data = Supra.mix({
				'type': '',
				'target': '',
				'button': false,
				'title': '',
				'href': '',
				'email_title': '',
				'email_button': false,
				'page_id': null,
				'page_master_id': null,
				'file_id': null,
				'file_path': [],
				'file_title': '',
				'file_target': '',
				'file_button': false,
				'linktype': 'internal'
			}, data || {});
			
			//Show footer for existing link and hide for new link
			var hide_footer = (this.mode == 'link' && !data.page_id && !data.file_id && !data.href && !data.email_title);
			
			this.one('.sidebar-footer').toggleClass('hidden', hide_footer);
			this.one('.sidebar-content').toggleClass('has-footer', !hide_footer);
			
			//Hide link controls?
			this.one('.sidebar-content').toggleClass('has-link-controls', !this.options.hideLinkControls);
			
			//Since file title, button and file target is different input then 'title', 'button' and 'target' are used to transfer data
			//reverse it
			if (data.title && !data.file_title) {
				data.file_title = data.title;
			}
			if (data.title && !data.email_title) {
				data.email_title = data.title;
			}
			if (data.target && !data.file_target) {
				data.file_target = data.target;
			}
			
			data.file_button = data.email_button = data.button;
			
			if (this.link_slideshow) {
				this.link_slideshow.set('noAnimations', true);
			}
			
			//If locale has changed since last time this action was opened then reload tree data
			var reloading_tree = false;
			if (this.locale && this.locale != Supra.data.get('locale')) {
				this.locale = Supra.data.get('locale');
				reloading_tree = true;
			}
			
			//If some option changed, then reload tree also
			if (this.tree && this.selectable.pages) {
				//If some option changed, then reload tree also
				if (this.selectable.group_pages != this.tree.get('groupNodesSelectable')) {
					this.tree.set('groupNodesSelectable', this.selectable.group_pages);
					reloading_tree = true;
				}
				
				// If URI changed then reload tree too
				var request_uri = this.getTreeRequestURI();
				if (this.requestUri != request_uri) {
					this.tree.set('requestUri', request_uri);
					this.requestUri = request_uri;
					reloading_tree = true;
				}
				
				//Reload tree if needed
				if (reloading_tree) {
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
			this.slideshow.scrollRoot();
			
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
				case 'email':
					this.slideshow.set('slide', 'linkToEmail');
					break;
				default:
						
					if (!this.selectable.pages && !this.selectable.external && !this.selectable.email) {
						//Only media library
						if (this.medialist) this.medialist.open(null);
						this.slideshow.set('slide', 'linkToFile');
					} else if (!this.selectable.images && !this.selectable.files && !this.selectable.email) {
						//Only pages
						this.slideshow.set('slide', 'linkToPage');
						
						this.link_slideshow.set('noAnimations', true);
						if (!this.selectable.pages && this.selectable.external) {
							this.link_slideshow.set('slide', 'linkManagerExternal');	
						} else if (this.selectable.pages && !this.selectable.external) {
							this.link_slideshow.set('slide', 'linkManagerInternal');
						}
						this.link_slideshow.set('noAnimations', false);
						
					} else if (!this.selectable.pages && !this.selectable.external && !this.selectable.images && !this.selectable.files) {
						//Only email
						this.slideshow.set('slide', 'linkToEmail');
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
			
			this.updateInsertButton();
		},
		
		/**
		 * Returns link data
		 * 
		 * @return Link data
		 * @type {Object}
		 * @private
		 */
		getData: function () {
			var data = Supra.mix(this.data || {}, this.form.getValues('name')),
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
						'button': data.button || false,
						'title': page_title
					};
				} else {
					var page_title = data.title,
						page_href  = data.href;
					
					if (this.options.hideLinkControls) {
						page_title = page_href;
					}
					
					//Add protocol to external links without it
					if (page_href && TEST_EXTERNAL_LINK.test(page_href)) {
						page_href = 'http://' + page_href;
					}
					
					//Remove domain from links which point to same host
					page_href = page_href.replace(REPLACE_LOCAL_DOMAIN, function (all) {
						var host_link = all.toLowerCase(),
							host_self = (document.location.protocol + '//' + document.location.hostname).toLowerCase();
						
						if (host_link == host_self) {
							return '';
						} else {
							return all;
						}
					});
					
					//Decode to preserve space
					try {
						page_href = decodeURIComponent(page_href);
					} catch (e) {}
					
					//Link to external resource
					return {
						'resource': 'link',
						'href': page_href,
						'target': data.target ? '_blank' : '',
						'button': data.button || false,
						'title': page_title
					};
				}
			} else if (slide_id == 'linkToFile') {
				//Link to file
				var item_data = this.medialist.getSelectedItem();
				if (!item_data) return;
				
				//File path for image is taken from original image
				var file_path = item_data.file_web_path;
				if (!file_path && item_data.type == Supra.MediaLibraryList.TYPE_IMAGE) {
					file_path = item_data.sizes.original.external_path;
				}
				
				return {
					'resource': 'file',
					'href': file_path,
					'target': data.file_target,
					'button': data.file_button || false,
					'title': data.file_title,
					'file_id': item_data.id,
					'file_path': item_data.path
				};
			} else if (slide_id == 'linkToEmail') {
				var email = data.email_title,
					href  = 'mailto:' + email;
				
				return {
					'resource': 'email',
					'href': href,
					'target': '',
					'button': data.email_button || false,
					'title': email
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
				
				'treeRequestURI': null,

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
			this.setDisplayMode(this.mode, this.selectable);
			
			//Set initial data
			this.setData(data);
			
			//Toolbar
			if (this.options.hideToolbar) {
				//Hide editor toolbar
				if (Manager.getAction('EditorToolbar').get('visible')) {
					this.options.restoreEditorToolbar = true;
					Manager.getAction('EditorToolbar').hide();
				}
				
				//Hide buttons
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