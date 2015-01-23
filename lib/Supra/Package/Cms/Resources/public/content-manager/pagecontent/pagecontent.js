Supra(function (Y) {
	//Invoke strict mode
	"use strict";
	
	var BLOCK_PROTOTYPES = [
		'Proto',
		'Editable',
		'List'
	];
	
	var includes = [
		'{pagecontent}includes/plugin-properties.js',
		'{pagecontent}includes/contents.js',
		'{pagecontent}includes/plugin-ordering.js'
	];

	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.Action,
		Root = Manager.getAction('Root');
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'PageContent',
		
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
		 * Page editing state
		 * @type {Boolean}
		 */
		editing: false,
		
		/**
		 * Dependancies has been loaded
		 * @type {Boolean}
		 */
		dependancies_loaded: false,
		
		/**
		 * Start editing when content is loaded
		 * @type {Boolean}
		 */
		edit_on_ready: false,
		
		
		/**
		 * IframeHandler class instance
		 * Handles all interactions with iframe
		 * @type {Object}
		 */
		iframe_handler: null,
		
		/**
		 * Iframe contents handler
		 * @type {Object}
		 */
		contents: null,
		
		
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			//Load sidebar content settings before all other modules
			Manager.loadAction('PageContentSettings');
			
			//Y.Router route
			Root.router.route(Root.ROUTE_PAGE, 		Y.bind(this.onStopEditingRoute, this));
			Root.router.route(Root.ROUTE_PAGE_EDIT,	Y.bind(this.onStartEditingRoute, this));
			
			//If user tries to navigate away show prompt if there are unsaved changes
			window.onbeforeunload = Y.bind(function (evt) {
			    window.onbeforeunload = null;
				if (this.hasUnsavedChanges()) {
					var message = Supra.Intl.get(['page', 'unsaved_changed']);
					evt.returnValue = message;
					return message;
				}
			}, this);
			
			//Settings
			var settings = Manager.getAction('PageContentSettings');
			if (settings.get('loaded')) {
				this.loadModules();
			} else {
				settings.on('loaded', this.loadModules, this);
			}
			
			//Page
			var page = Manager.getAction('Page');
			if (page.get('loaded')) {
				this.ready();
			}
			
			//When page will reload we need to update iframe
			page.on('loaded', this.ready, this);
		},
		
		loadModules: function () {
			var incl = includes,
				blocks = this.BLOCK_PROTOTYPES = BLOCK_PROTOTYPES,
				path = this.getActionPath(),
				args = [],
				
				// combo base
				url = Y.Env._loader.comboBase;
			
			//Load blocks
			for(var i=blocks.length-1; i>=0; i--) {
				incl.unshift('{pagecontent}includes/contents/' + blocks[i].toLowerCase() + '.js');
			}
			
			//Change path	
			for(var id in incl) {
				args.push(incl[id].replace('{pagecontent}', path));
			}
			
			// Combo
			url += args.join('&');
			
			//Load modules
			Y.Get.script(url, {
				'onSuccess': function () {
					//Create classes
					Supra('dd-drag', 'supra.iframe', 'supra.iframe-contents', 'supra.plugin-layout', 'supra.lipsum', Y.bind(function () {
						this.dependancies_loaded = true;
						this.ready();
					}, this));
				},
				attributes: {
					'async': 'async',	//Load asynchronously
					'defer': 'defer'	//For browsers that doesn't support async
				},
				'context': this
			});
		},
		
		/**
		 * Returns iframe contents object
		 * 
		 * @return IframeContents object
		 * @type {Object}
		 */
		getContent: function () {
			return this.contents;
		},
		
		getIframe: function () {
			return this.iframe_handler;
		},
		
		/**
		 * On editing start change toolbar
		 */
		startEditing: function () {
			if (!this.editing) {
				var uri = Root.ROUTE_PAGE_EDIT.replace(':page_id', Manager.Page.getPageData().id);
				
				if (Root.getRoutePath().indexOf(uri) === 0) {
					//If already target URI, then start editing
					this.onStartEditingRoute();
				} else {
					//Navigate to target URI
					Root.router.save(uri);
				}
			}
		},
		onStartEditingRoute: function (req) {
			var page = Manager.getAction('Page'),
				template = Manager.getAction('Template'),
				data = page.getPageData(),
				is_allowed = Supra.Permission.get('page', data.id, 'edit_page', false);
			
			if (!this.iframe_handler || !this.getContent()) {
				// Called before content is ready
				// do nothing
			} else if (!this.editing && is_allowed) {
				//Check lock status
				var userlogin = Supra.data.get(['user', 'login']);
				if (data && data.lock && data.lock.userlogin == userlogin) {
					
					//Start editing
					if (!this.iframe_handler) {
						this.edit_on_ready = true;
						return;
					}
					
					this.editing = true;
					this.edit_on_ready = false;
					Manager.getAction('PageToolbar').setActiveAction('Page');
					Manager.getAction('PageButtons').setActiveAction(this.NAME);
					
					//Disable "Publish" button is there are no permissions for that
					var button_publish = Supra.Manager.PageButtons.buttons.PageContent[0];
					
					if (!Supra.Permission.get('page', data.id, 'supervise_page', false)) {
						button_publish.set('disabled', true);
					} else {
						button_publish.set('disabled', false);
					}
					
					//"Settings" button label
					var settingsButton = Manager.getAction('PageToolbar').getActionButton('settings');
					if (data.type == 'page') {
						settingsButton.set('label', Supra.Intl.get(['settings', 'button_page']));
					} else {
						settingsButton.set('label', Supra.Intl.get(['settings', 'button_template']));
					}
					
					// Disable navigation using content links
					this.iframe_handler.set('preventNavigation', true);
					
					if (this.getContent()) {
						//Enable highlights
						this.getContent().set('highlightMode', 'edit');
						
						//Resize overlays
						this.getContent().resizeOverlays();
					}
					
					// Event
					this.fire('startEditing');
					
				} else {
					
					//Page lock information is not known, unlock page
					if (page.isPage()) {
						page.lockPage();
					} else {
						template.lockTemplate();
					}
					
				}
			}
			
			if (req) req.next();
		},
		
		/**
		 * Set editing state to stoped
		 */
		stopEditing: function () {
			if (this.editing) {
				//Route only if on /12/edit page
				if (Root.getRoutePath().match(Root.ROUTE_PAGE_EDIT_R)) {
					var uri = Root.ROUTE_PAGE.replace(':page_id', Manager.Page.getPageData().id);
					Root.router.save(uri);
				} else {
					this.onStopEditingRoute();
				}
			}
		},
		onStopEditingRoute: function (req) {
			if (this.editing) {
				this.editing = false;
				
				//In other version preview page is not editable
				var content = this.getContent();
				if (content) {
					//Stop editing
					content.set('activeChild', null);
					
					//Disable highlights
					content.set('highlightMode', 'disabled');
					
					// Enable navigation using content links
					this.iframe_handler.set('preventNavigation', false);
					
					// Event
					this.fire('stopEditing');
				}
			}
			
			if (req) req.next();
		},
		
		/**
		 * Returns true if page is being edited, otherwise false
		 * 
		 * @return If page is edited
		 * @type {Boolean}
		 */
		isEditing: function () {
			return this.editing;
		},
		
		/**
		 * All modules are been loaded
		 * @private
		 */
		ready: function () {
			var page_data = Manager.Page.getPageData(),
				iframe = this.iframe_handler,
				params = {};
			
			//Wait till page data and dependancies are loaded
			if (!page_data || !this.dependancies_loaded) {
				return;
			}
			
			if (!iframe) {
				params = {
					'srcNode': this.one('iframe') || this.one(),
					'preventNavigation': false,
					'preventExternalNavigation': false // Opens popup
				};
				
				if (Root.getRoutePath().match(Root.ROUTE_PAGE_EDIT_R)) {
					// While editing content disable navigation to another pages
					// using links in the content
					params.preventNavigation = true;
				}
				
				this.iframe_handler = iframe = new Supra.Iframe(params);
				
				iframe.on('ready', this.onIframeReady, this);
				iframe.on('cleanup', this.onIframeCleanup, this);
				iframe.on('navigate', this.onIframeNavigate, this);
				
				//Render iframe
				iframe.render();
				this.one('.su-page-iframe-temp').addClass('su-page-iframe').removeClass('su-page-iframe-temp');
				
				//Set loading style and content
				iframe.set('loading', true);
				iframe.set('html', page_data.internal_html);
				
				//Show iframe when action is shown / hidden
				this.on('visibleChange', function (evt) {
					if (evt.newVal != evt.prevVal) {
						iframe.set('visible', evt.newVal);
					}
				}, this);
				
				//Media library handle file insert
				var mediasidebar = Manager.getAction('MediaSidebar');
				mediasidebar.on('insert', function (event) {
					var content = this.getContent().get('activeChild');
					if (content && 'editor' in content) {
						var data = event.image;
						content.editor.exec('insertimage', data);
					}
				}, this);
				
				this.fire('iframeReady');
				
				//If editing was called before content was ready or there is a route path
				//then call it now
				if (this.edit_on_ready || Root.getRoutePath().match(Root.ROUTE_PAGE_EDIT_R) || Root.getRoutePath().match(Root.ROUTE_PAGE_CONT_R)) {
					iframe.once('ready', function () {
						this.edit_on_ready = false;
						this.onStartEditingRoute();
					}, this);
				}
			} else {
				iframe.set('html', page_data.internal_html);
			}
		},
		
		
		/* ------------------------ Iframe ------------------------ */ 
		
		
		/**
		 * On iframe ready create contents object
		 * 
		 * @private
		 */
		onIframeReady: function () {
			var page_data = Manager.Page.getPageData(),
				iframe    = this.iframe_handler,
				doc       = iframe.get('doc');
			
			iframe.set('loading', false);
			
			this.contents = new this.IframeContents({
				'iframe': iframe,
				'doc': doc,
				'win': iframe.get('win'),
				'body': Y.Node(doc.body),
				'contentData': page_data.contents
			});
			
			this.contents.render();
			
			this.contents.on('activeChildChange', function (event) {
				if (event.newVal) {
					this.startEditing();
				}
			}, this);
			
			// Trigger events, to which other actions could bind to, because
			// this.contents changes each time iframe content is reloaded
			this.contents.before('activeChildChange', function (event) {
				this.fire('beforeActiveChildChange', {newVal: event.newVal, prevVal: event.prevVal});
			}, this);
			
			this.contents.after('activeChildChange', function (event) {
				this.fire('activeChildChange', {newVal: event.newVal, prevVal: event.prevVal});
			}, this);
			
			// If editing then start
			var uri = Root.ROUTE_PAGE_EDIT.replace(':page_id', Manager.Page.getPageData().id);
			if (Root.getRoutePath().indexOf(uri) === 0) {
				//If already target URI, then start editing
				this.onStartEditingRoute();
			}
		},
		
		/**
		 * On iframe cleanup remove contents object
		 */
		onIframeCleanup: function () {
			if (this.contents) {
				this.contents.destroy();
				this.contents = null;
			}
		},
		
		/**
		 * On iframe navigation
		 */
		onIframeNavigate: function (e) {
			var href = e.href;
			
			Manager.Page.getPageIdFromPath(href, this._iframeHandleRedirect, this);
			
			// Prevent iframe from actually changing anything
			e.halt();
		},
		
		/**
		 * Handle redirect
		 */
		_iframeHandleRedirect: function (data, status) {
			if (status && data && data.page_id) {
				if (data.page_id != Supra.data.get(['page', 'id'])) {
					if (data.redirect) {

						Supra.Manager.executeAction('Confirmation', {
							'message': '{#page.follow_redirect#}',
							'useMask': true,
							'buttons': [{
								'id': 'yes',
								'label': Supra.Intl.get(['buttons', 'yes']),
								'click': this._iframeHandleRedirectConfirmation,
								'context': this,
								'args': [true, data]
							},
							{
								'id': 'no',
								'label': Supra.Intl.get(['buttons', 'no']),
								'click': this._iframeHandleRedirectConfirmation,
								'context': this,
								'args': [false, data]
							}]
						});

						return;
					}

					Supra.data.set('locale', data.locale);

					//Stop editing
					this.stopEditing();
					
					//Change path
					Root.router.save(Root.ROUTE_PAGE.replace(':page_id', data.page_id));
				}
			}
		},
		
		/**
		 * If page has redirect will ask you follow redirect or not
		 */
		_iframeHandleRedirectConfirmation: function (e, args) {
			var follow = args[0],
			data = args[1],
			redirect_page_id = data.page_id;
			
			Supra.data.set('locale', data.locale);
									
			//Stop editing
			this.stopEditing();
			
			if(follow) {
				redirect_page_id = data.redirect_page_id;
			}
			
			//Change path
			Root.router.save(Root.ROUTE_PAGE.replace(':page_id', redirect_page_id));
		},
		
		
		/* ------------------------ Drag and drop ------------------------ */
		
		
		/**
		 * Add item to contents D&D list
		 */
		registerDD: function (item) {
			
			var dd = new Y.DD.Drag({
				offsetNode: false, // exactly where cursor is
	            node: item.node,
	            dragMode: 'intersect'
	        });
	        
	        if (item.useProxy) {
	        	dd.plug(Y.Plugin.DDProxy, {
	        		cloneNode: true,
	        		moveOnEnd: false
	        	});
	        }
			
			dd.on('drag:start', function(e) {
				this.fire('dragstart', {
					block: item.data,
					dragnode: dd
				});
				
				if (item.useProxy) {
					var proxy = e.target.get('dragNode');
					proxy.addClass(item.proxyClassName || 'su-blocks-block');
					proxy.appendTo(Y.one('body'));
				}
				
				//Show overlay, otherwise it's not possible to drag over iframe
				this.iframe_handler.set('overlayVisible', true);
	        }, this);
			
			dd.on('drag:drag', function(e) {
				var scroll = this.iframe_handler.getScroll();
				var x = e.pageX, y = e.pageY;
				var r = Y.DOM._getRegion(y, x+1, y+1, x);
				var target = this.iframe_handler.get('srcNode');
				
				if (target.inRegion(r)) {
					var xy = target.getXY();
					xy[0] = x - xy[0] + scroll[0];
					xy[1] = y - xy[1] + scroll[1];
					
					var ret = this.fire('dragmove', {
						position: xy,
						block: item.data,
						dragnode: dd
					});
				}
			}, this);
			
			dd.on('drag:dropmiss', function(e) {
				//Hide overlay
				this.iframe_handler.set('overlayVisible', false);
			}, this);
			
	        dd.on('drag:end', function(e) {
				e.preventDefault();
				
				var scroll = this.iframe_handler.getScroll();
				var x = e.pageX, y = e.pageY;
				var r = Y.DOM._getRegion(y, x+1, y+1, x);
				var target = this.iframe_handler.get('srcNode');
				
				if (target.inRegion(r)) {
					var xy = target.getXY();
					xy[0] = x - xy[0] + scroll[0]; xy[1] = y - xy[1] + scroll[1];
					
					var ret = this.fire('dragend:hit', {
						position: xy,
						block: item.data,
						dragnode: dd
					});
					
					// @TODO Is following needed???
					if (!ret) {
						Manager.PageInsertBlock.hide();
						
						//Event was stopped == successful drop
						//delay to allow draged item to reset it's position if needed
						Supra.immediate(this, function () {
							this.getContent().set('highlightMode', 'edit');
						});
					}
				}
				
				this.fire('dragend');
				
				//Because of Editor toolbar, container top position changes and 
				//drag node is not moved back into correct position
				item.node.setStyles({'left': 'auto', 'top': 'auto'});
				
				//Hide overlay to allow interacting with content
				this.iframe_handler.set('overlayVisible', false);
	        }, this);
	        
	        return dd;
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			var highlight = false;
			
			this.on('dragmove', function (e) {
				this.getContent().fire('block:dragmove', e);
			}, this);
			this.on('dragstart', function (e) {
				this.getContent().fire('block:dragstart', e);
			}, this);
			this.on('dragend:hit', function (e) {
				return this.getContent().fire('block:dragend:hit', e);
			}, this);
			this.on('dragend', function (e) {
				this.getContent().fire('block:dragend:miss', e);
			}, this);
			
			//Add toolbar buttons
			var buttons = [
				{
					'id': 'publish',
					'callback': function () {
						if (Manager.Page.isPage()) {
							Manager.Page.publishPage();
						} else {
							Manager.Template.publishTemplate();
						}
						Manager.Root.execute();
					}
				},
				{
					'id': 'close',
					'callback': function () {
						if (Manager.Page.isPage()) {
							Manager.Page.unlockPage();
						} else {
							Manager.Template.unlockTemplate();
						}
						Manager.Root.execute();
					}
				}
			];
			
			Manager.getAction('PageButtons').addActionButtons(this.NAME, buttons);
		},
		
		/**
		 * Returns if there are unsaved changed
		 * 
		 * @return True if has unsaved changes, otherwise false
		 * @type {Boolean}
		 */
		hasUnsavedChanges: function () {
			var content = this.getContent(),
				children,
				child_id;
			
			if (!content) return false;
			
			children = content.getAllChildren();
			for(child_id in children) {
				if (children[child_id].get('changed')) return true;
			}
			return false;
		},
		
		/**
		 * Execute action
		 * 
		 * @param {Object} data Data
		 */
		execute: function (data) {
		}
	});
	
});
