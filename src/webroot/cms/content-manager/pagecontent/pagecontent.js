//Invoke strict mode
"use strict";

SU('dd-drag', function (Y) {
	
	var LOCALE_LEAVE = 'There are unsaved changes. Are you sure you want to leave this page?';
	
	
	var includes = [
		'{pagecontent}includes/contents/proto.js',
		'{pagecontent}includes/contents/editable.js',
		'{pagecontent}includes/contents/list.js',
		'{pagecontent}includes/contents/gallery.js',
		'{pagecontent}includes/plugin-properties.js',
		'{pagecontent}includes/plugin-droptarget.js',
		'{pagecontent}includes/iframe.js'
	];

	//Shortcut
	var Manager = SU.Manager,
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
		 * Registered DND target (CMS document or iframe document) 
		 */
		dnd_target: null,
		
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
		 * Initialize
		 * @private
		 */
		initialize: function () {
			var incl = includes,
				path = this.getActionPath(),
				args = [];
			
			//Y.Controller route
			Root.route('/:page_id', Y.bind(this.onStopEditingRoute, this));
			Root.route('/:page_id/edit', Y.bind(this.onStartEditingRoute, this));
			
			//Change path	
			for(var id in incl) {
				args.push(incl[id].replace('{pagecontent}', path));
			}
			
			//Load modules
			Y.Get.script(args, {
				'onSuccess': function () {
					//Create classes
					SU('supra.page-iframe', 'supra.plugin-layout', Y.bind(function () {
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
			
			//If user tries to navigate away show prompt if there are unsaved changes
			window.onbeforeunload = Y.bind(function (evt) {
			    window.onbeforeunload = null;
				if (this.hasUnsavedChanges()) {
					evt.returnValue = LOCALE_LEAVE;
					return LOCALE_LEAVE;
				}
			}, this);
			
			
			Manager.getAction('Page').on('loaded', this.ready, this);
		},
		
		/**
		 * Returns PageIframe content object
		 * 
		 * @return Iframe object
		 * @type {Object}
		 */
		getIframe: function () {
			return this.iframeObj;
		},
		
		/**
		 * Returns iframe contents object
		 * 
		 * @return IframeContents object
		 * @type {Object}
		 */
		getContentContainer: function () {
			return this.getIframe().contents;
		},
		
		/**
		 * Returns all non-list content blocks
		 */
		getContentBlocks: function (container, ret) {
			if (!container) container = this.getContentContainer().contentBlocks;
			if (!ret) var ret = {};
			
			for(var id in container) {
				if (!container[id].isInstanceOf('page-content-list')) {
					ret[id] = container[id];
				} else if (container[id].children) {
					this.getContentBlocks(container[id].children, ret);
				}
			}
			
			return ret;
		},
		
		/**
		 * Returns active content object
		 * 
		 * @return Active content, Action.Proto instance
		 * @type {Object}
		 */
		getActiveContent: function () {
			return this.iframeObj.contents.get('activeContent');
		},
		
		/**
		 * On editing start change toolbar
		 */
		startEditing: function () {
			if (!this.editing) {
				var uri = '/' + Manager.Page.getPageData().id + '/edit';
				
				if (Root.getPath().indexOf(uri) === 0) {
					this.onStartEditingRoute();
				} else {
					Root.save(uri);
				}
			}
		},
		onStartEditingRoute: function (req) {
			if (!this.editing) {
				if (!this.iframeObj) {
					this.edit_on_ready = true;
					return;
				}
				
				this.editing = true;
				this.edit_on_ready = false;
				Manager.getAction('PageToolbar').setActiveAction('Page');
				Manager.getAction('PageButtons').setActiveAction(this.NAME);
				
				//Enable highlights
				this.getContentContainer().set('highlight', false);
			}
			
			if (req) req.next();
		},
		
		/**
		 * Set editing state to stoped
		 */
		stopEditing: function () {
			if (this.editing) {
				//Route only if on /12/edit page
				if (this.getPath().match(/^\/\d+\/edit$/)) {
					Root.save('/' + Manager.Page.getPageData().id);
				} else {
					this.onStopEditingRoute();
				}
			}
		},
		onStopEditingRoute: function (req) {
			if (this.editing) {
				this.editing = false;
				Manager.PageContent.getIframe().contents.set('activeContent', null);
				
				//Disable highlights
				this.getContentContainer().set('highlight', true);
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
			var page_data = Manager.Page.getPageData();
			
			//Wait till page data and dependancies are loaded
			if (!page_data || !this.dependancies_loaded) return;
			
			if (!this.iframeObj) {
				this.iframeObj = new this.Iframe({
					'srcNode': this.one(),
					'html': page_data.internal_html,
					'contentData': page_data.contents
				});
				
				//Render iframe
				this.iframeObj.render();
				
				this.iframeObj.on('activeContentChange', function (evt) {
					this.fire('activeContentChange', evt);
				}, this);
				
				//Show iframe when action is shown / hidden
				this.on('visibleChange', function (evt) {
					if (evt.newVal != evt.prevVal) {
						this.iframeObj.set('visible', evt.newVal);
					}
				}, this);
				
				//Media library handle file insert
				var mediasidebar = Manager.getAction('MediaSidebar');
				mediasidebar.on('insert', function (event) {
					var content = this.getActiveContent();
					if (content && 'editor' in content) {
						var data = event.image;
						content.editor.exec('insertimage', data);
					}
				}, this);
				
				this.fire('iframeReady');
				
				//If editing was called before content was ready or there is a route path
				//then call it now
				if (this.edit_on_ready || this.getPath().match(/\/\d+\/edit/)) {
					this.iframeObj.after('ready', function () {
						this.onStartEditingRoute();
					}, this);
				}
			} else {
				this.iframeObj.set('contentData', page_data.contents);
				this.iframeObj.setHTML(page_data.internal_html);
			}
		},
		
		/**
		 * Initialize document drag and drop
		 */
		initDD: function (target) {
			target = target || document;
			Y.config.doc = target;
			
			if (!target._dd_intialized) {
				//On each new DND item registration update YUI document if needed
				//This is needed to resolve YUI DD not being attached to correct document
				target._dd_intialized = true;
				Y.DD.DDM._setupListeners();
			}
		},
		
		/**
		 * Reset document drag and drop by removing bindings
		 * and re-attaching them
		 */
		resetDD: function (target) {
			Y.one(target).purge();
			target._dd_intialized = false;
			
			this.initDD(target);
			if (document !== target) this.initDD(document);
		},
		
		/**
		 * Add item to contents D&D list
		 */
		registerDD: function (item) {
			this.initDD(document);
			
			var dd = new Y.DD.Drag({
	            node: item.node,
	            dragMode: 'intersect'
	        });
			
			dd.on('drag:start', function(e) {
				this.fire('dragstart', {
					block: item.data,
					dragnode: dd
				});
				
				//Show overlay, otherwise it's not possible to drag over iframe
				this.iframeObj.set('overlayVisible', true);
	        }, this);
			
	        dd.on('drag:end', function(e) {
				e.preventDefault();
				
				var x = e.pageX, y = e.pageY;
				var r = Y.DOM._getRegion(y, x+1, y+1, x);
				var target = this.iframeObj.get('srcNode');
				
				if (target.inRegion(r)) {
					var xy = target.getXY();
					xy[0] = x - xy[0]; xy[1] = y - xy[1];
					
					var ret = this.fire('dragend:hit', {
						position: xy,
						block: item.data,
						dragnode: dd
					});
					
					if (!ret) {
						//Event was stopped == successful drop
						//delay to allow draged item to reset it's position if needed
						Y.later(15, this, function () {
							Manager.PageInsertBlock.hide();
						});
					}
				}
				
				this.fire('dragend');
				
				//Because of Editor toolbar, container top position changes and 
				//drag node is not moved back into correct position
				item.node.setStyles({'left': 'auto', 'top': 'auto'});
				
				//Hide overlay to allow interacting with content
				this.iframeObj.set('overlayVisible', false);
	        }, this);
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			this.on('dragstart', function (e) {
				this.getContentContainer().fire('block:dragstart', e);
			}, this);
			this.on('dragend', function () {
				this.getContentContainer().set('highlight', false);
			}, this);
			this.on('dragend:hit', function (e) {
				return this.getContentContainer().fire('block:dragend', e);
			}, this);
			
			//Add toolbar buttons
			var buttons = [];
			
			if (Supra.Authorization.isAllowed(['page', 'publish'], true)) {
				buttons.push({
					'id': 'publish',
					'callback': function () {
						if (Manager.Page.isPage()) {
							Manager.Page.publishPage();
						} else {
							Manager.Template.publishTemplate();
						}
						Manager.Root.execute();
					}
				});
			}
			
			buttons.push({
				'id': 'close',
				'callback': function () {
					if (Manager.Page.isPage()) {
						Manager.Page.unlockPage();
					} else {
						Manager.Template.unlockTemplate();
					}
					Manager.Root.execute();
				}
			});
			
			Manager.getAction('PageButtons').addActionButtons(this.NAME, buttons);
		},
		
		/**
		 * Returns if there are unsaved changed
		 * 
		 * @return True if has unsaved changes, otherwise false
		 * @type {Boolean}
		 */
		hasUnsavedChanges: function () {
			var blocks = this.getContentBlocks();
			for(var id in blocks) {
				if (blocks[id].get('changed')) return true;
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