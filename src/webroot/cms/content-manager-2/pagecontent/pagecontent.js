SU('dd-drag', function (Y) {
	
	var includes = [
		'includes/contents/proto.js',
		'includes/contents/html.js',
		'includes/contents/list.js',
		'includes/contents/sample.js',
		'includes/plugin-properties.js',
		'includes/iframe.js',
		'includes/layout.js'
	];

	//Shortcut
	var Manager = SU.Manager;
	var Action = Manager.Action;
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'PageContent',
		
		/**
		 * Page manager has stylesheet, include it
		 * @type {Boolean}
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Page editing state
		 * @type {Boolean}
		 */
		editing: false,
		
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			var incl = includes,
				path = this.getPath(),
				args = [];
			
			//Change path	
			for(var id in incl) {
				args.push(path + incl[id]);
			}
			
			//Load modules
			Y.Get.script(args, {
				'onSuccess': function () {
					//Create classes
					SU('supra.page-iframe', 'supra.plugin-layout', Y.bind(this.ready, this));
				},
				'context': this
			});
		},
		
		/**
		 * Returns iframe content object
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
		 * All modules are been loaded
		 * @private
		 */
		ready: function () {
			var page_data = SU.Manager.Page.getPageData();
			
			this.iframeObj = new this.Iframe({
				'srcNode': this.getContainer(),
				'url': page_data.internal_url,
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
			var medialibrary = SU.Manager.getAction('MediaLibrary');
			medialibrary.on('insert', function (event) {
				var content = this.getContentContainer().get('activeContent');
				if (content && 'editor' in content) {
					var data = event.image;
					content.editor.exec('insertimage', data);
				}
			}, this);
			
			this.fire('iframeReady');
		},
		
		/**
		 * Add item to contents D&D list
		 */
		registerDD: function (item) {
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
				this.iframeObj.showOverlay();
	        }, this);
			
	        dd.on('drag:end', function(e) {
				
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
						SU.Manager.PageInsertBlock.hide();
					}
				}
				
				this.fire('dragend');
				e.preventDefault();
				
				//Because of Editor toolbar, container top position changes and 
				//drag node is not moved back into correct position
				item.node.setStyles({'left': 'auto', 'top': 'auto'});
				
				//Hide overlay to allow interacting with content
				this.iframeObj.hideOverlay();
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
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'close',
				'callback': function () {
					// @TODO Revert all changes
					Y.log('Revert all changes...');
					Manager.Root.execute();
				}
			}, {
				'id': 'save',
				'callback': function () {
					// @TODO Save all content
					Y.log('Save all content...');
					Manager.Root.execute();
				}
			}, {
				'id': 'publish',
				'callback': function () {
					// @TODO Save all content & publish
					Y.log('Save all content & publish...');
					Manager.Root.execute();
				}
			}]);
		},
		
		/**
		 * On editing start change toolbar
		 */
		startEditing: function () {
			Manager.PageToolbar.setActiveGroupAction('Page');
			if (!this.editing) {
				this.editing = true;
				Manager.getAction('PageButtons').setActiveAction(this.NAME);
			}
		},
		
		/**
		 * Set editing state to stoped
		 */
		stopEditing: function () {
			if (this.editing) {
				this.editing = false;
				Manager.PageContent.getIframe().contents.set('activeContent', null);
			}
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
		 * Execute action
		 * 
		 * @param {Object} data Data
		 */
		execute: function (data) {
		}
	});
	
});