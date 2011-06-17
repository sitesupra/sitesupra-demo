SU('dd-drag', function (Y) {
	
	var includes = [
		'includes/contents/proto.js',
		'includes/contents/html.js',
		'includes/contents/list.js',
		'includes/contents/sample.js',
		'includes/plugin-controls.js',
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
				this.iframeObj.set('visible', evt.newVal);
			}, this);
			
			//Plugin
			this.iframeObj.plug(SU.PluginLayout, {
				'offset': [30, 76, 30, 30]	//Default offset from page viewport
			});
			
			this.iframeObj.layout.addOffset(SU.Manager.getAction('PageTopBar').tabs, 'top', 10);
			this.iframeObj.layout.addOffset(SU.Manager.getAction('PageBottomBar').footer, 'bottom', 20);
			
			//Add editor toolbar when it's ready
			var toolbar = SU.Manager.getAction('EditorToolbar');
			var callback = Y.bind(function () {
				this.iframeObj.layout.addOffset(SU.Manager.getAction('EditorToolbar').toolbar, 'top', 5);
			}, this);
			
			if (toolbar.get('created')) {
				callback();
			} else {
				toolbar.on('render', callback);
			}
			
			//Media bar
			var mediabar = SU.Manager.getAction('MediaBar');
			mediabar.on('render', function () {
				mediabar.panel.plug(SU.PluginLayout, {
					'offset': [30, 76, 30, 30]	//Default offset from page viewport
				});
				mediabar.panel.layout.addOffset(SU.Manager.getAction('PageTopBar').tabs, 'top', 10);
				mediabar.panel.layout.addOffset(SU.Manager.getAction('PageBottomBar').footer, 'bottom', 20);
				mediabar.panel.layout.addOffset(SU.Manager.getAction('EditorToolbar').toolbar, 'top', 5);
				
				mediabar.panel.set('xy', [30, 76]);
				mediabar.panel.layout.syncUI();
				
				this.iframeObj.layout.addOffset(mediabar.panel, 'left', 15);
				
				mediabar.on('insert', function (event) {
					var content = SU.Manager.PageContent.getContentContainer().get('activeContent');
					if (content && 'editor' in content) {
						var data = event.image;
						content.editor.exec('insertimage', data);
					}
				});
			}, this);
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