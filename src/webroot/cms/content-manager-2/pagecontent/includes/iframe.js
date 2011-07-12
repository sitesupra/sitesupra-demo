//Invoke strict mode
"use strict";

/*
 * SU.Manager.PageContent.Iframe
 */
YUI.add('supra.page-iframe', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager,
		Action = Manager.PageContent;
	
	/*
	 * Iframe
	 */
	function PageIframe (config) {
		PageIframe.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	PageIframe.NAME = 'page-iframe';
	PageIframe.CLASS_NAME = Y.ClassNameManager.getClassName(PageIframe.NAME);
	PageIframe.ATTRS = {
		/**
		 * Iframe element
		 */
		'nodeIframe': {
			value: null
		},
		/**
		 * Iframe HTML content
		 */
		'html': {
			value: null
		},
		/**
		 * Overlay visibility
		 */
		'overlayVisible': {
			value: false,
			setter: '_setOverlayVisible'
		},
		/**
		 * Page content blocks
		 */
		'contentData': {
			value: null
		},
		
		/**
		 * Iframe document instance
		 */
		'doc': {
			value: null
		},
		/**
		 * Iframe window instance
		 */
		'win': {
			value: null
		}
	};
	
	PageIframe.HTML_PARSER = {
		'nodeIframe': function (srcNode) {
			var iframe = srcNode.one('iframe');
			this.set('nodeIframe', iframe);
			return iframe;
		}
	};
	
	Y.extend(PageIframe, Y.Widget, {
		
		/**
		 * Iframe overlay, used for D&D to allow dragging over iframe
		 * @type {Object}
		 */
		overlay: null,
		
		addScript: function (src) {
			var doc = this.get('doc');
			var script = doc.createElement('script');
				script.type = "text/javascript";
				script.href = src;
			
			doc.getElementsByTagName('HEAD')[0].appendChild(script); 
		},
		
		addStyleSheet: function (href) {
			var doc = this.get('doc');
			var link = doc.createElement('link');
				link.rel = "stylesheet";
				link.type = "text/css";
				link.href = href;
				
			doc.getElementsByTagName('HEAD')[0].appendChild(link);
			return link;
		},
		
		renderUI: function () {
			PageIframe.superclass.renderUI.apply(this, arguments);

			var cont = this.get('contentBox');
			var iframe = this.get('nodeIframe');
			
			this.overlay = Y.Node.create('<div class="yui3-iframe-overlay hidden"></div>');
			cont.append(this.overlay);
			
			this.setHTML(this.get('html'));
			cont.removeClass('hidden');
		},
		
		/**
		 * Overlay visiblity setter
		 * 
		 * @param {Object} value
		 * @private
		 */
		_setOverlayVisible: function (value) {
			if (value) {
				this.overlay.removeClass('hidden');
			} else {
				this.overlay.addClass('hidden');
			}
			return !!value;
		},
		
		/**
		 * Prevent user from leaving page by disabling links 
		 * and inputs, which will prevent from submit
		 * 
		 * @private
		 */
		_preventFromLeaving: function (body) {
			//Prevent from leaving page 
			var links = body.all('a');
			
			for(var i=0,ii=links.size(); i<ii; i++) {
				var link = links.item(i);
				link.removeAttribute('onclick');
			}
			Y.delegate('click', function (e) {
				e.preventDefault();
			}, body, 'a');
			
			var inputs = body.all('input,button,select,textarea');
			
			for(var i=0,ii=inputs.size(); i<ii; i++) {
				inputs.item(i).set('disabled', true);
			}
		},
		
		/**
		 * Wait till stylesheets are loaded
		 */
		_onStylesheetLoad: function (links, body) {
			var fn = Y.bind(function () {
				var loaded = true;
				for(var i=0,ii=links.length; i<ii; i++) {
					if (!links[i].sheet) {
						loaded = false;
						break;
					}
				}
				
				if (loaded) {
					//Add contents
					if (this.contents) this.contents.destroy();
					this.contents = new PageContents({'iframe': this, 'doc': this.get('doc'), 'win': this.get('win'), 'body': body, 'contentData': this.get('contentData')});
					this.contents.render();
					
					this.contents.on('activeContentChange', function (event) {
						if (event.newVal) {
							Action.startEditing();
						}
					});
					this.contents.after('activeContentChange', function (event) {
						this.fire('activeContentChange', {newVal: event.newVal, prevVal: event.prevVal});
					}, this);
					
					//Trigger ready event
					this.fire('ready', {'iframe': this, 'body': body});
			
				} else {
					setTimeout(fn, 50);
				}
			}, this);
			setTimeout(fn, 50);
		},
		
		_afterSetHTML: function () {
			var doc = this.get('doc'),
				body = new Y.Node(doc.body);
			
			this._preventFromLeaving(body);
			
			//Add stylesheets to iframe
			var links = [];
			if (!SU.data.get(['supra.htmleditor', 'stylesheets', 'skip_default'], false)) {
				links.push(this.addStyleSheet("/cms/content-manager-2/pagecontent/iframe.css"));
			}
			
			//When stylesheets are loaded initialize PageContents
			this._onStylesheetLoad(links, body);
		},
		
		/**
		 * On HTML attribute change update iframe content and page content blocks
		 * 
		 * @param {String} html
		 * @return HTML
		 * @type {String}
		 * @private
		 */
		setHTML: function (html) {
			//Set attribute
			this.set('html', html);
			
			//Clean up
			this._unsetHTML();
			
			//Save document & window instances
			var win = Y.Node.getDOMNode(this.get('nodeIframe')).contentWindow;
			var doc = win.document;
			this.set('win', win);
			this.set('doc', doc);
			
			//Change iframe HTML
			doc.writeln(html);
			doc.close();
			
			//Small delay before continuing
			setTimeout(Y.bind(this._afterSetHTML, this), 50);
			
			return html;
		},
		
		/**
		 * Clean up before HTML change
		 * 
		 * @private
		 */
		_unsetHTML: function () {
			if (this.contents) {
				this.contents.destroy();
				this.contents = null;
			}
		}
		
	});
	
	Action.Iframe = PageIframe;
	
	
	
	/*
	 * Editable content
	 */
	function PageContents (config) {
		PageContents.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	PageContents.NAME = 'page-iframe-contents';
	PageContents.CLASS_NAME = Y.ClassNameManager.getClassName(PageContents.NAME);
	PageContents.ATTRS = {
		'iframe': {
			value: null
		},
		'win': {
			value: null,
		},
		'doc': {
			value: null,
		},
		'body': {
			value: null
		},
		'contentData': {
			value: null
		},
		'disabled': {
			value: false
		},
		'activeContent': {
			value: null
		},
		/*
		 * Highlight list nodes
		 */
		'highlight': {
			value: false,
			setter: '_setHighlight'
		}
	};
	
	Y.extend(PageContents, Y.Base, {
		contentBlocks: {},
		
		bindUI: function () {
			
			//Set 'editing' attribute after content changes
			this.after('activeContentChange', function (evt) {
				if (evt.newVal !== evt.prevVal) {
					if (evt.prevVal && evt.prevVal.get('editing')) {
						evt.prevVal.set('editing', false);
					}
					if (evt.newVal && !evt.newVal.get('editing')) {
						evt.newVal.set('editing', true);
					}
				}
			});
			
			//Bind block D&D
			this.on('block:dragend', function (e) {
				if (e.block) {
					var region = Y.DOM._getRegion(e.position[1], e.position[0]+88, e.position[1]+88, e.position[0]);
					for(var i in this.contentBlocks) {
						var node = this.contentBlocks[i].getNode(),
							intersect = node.intersect(region);
						
						if (intersect.inRegion && this.contentBlocks[i].isChildTypeAllowed(e.block.id)) {
							return this.contentBlocks[i].fire('dragend:hit', {dragnode: e.dragnode, block: e.block});
						}
					}
				}
			}, this);
			
			this.on('block:dragstart', function (e) {
				//Only if dragging block
				if (e.block) {
					this.set('highlight', true);
					var type = e.block.id;
					
					for(var i in this.contentBlocks) {
						if (this.contentBlocks[i].isChildTypeAllowed(type)) {
							this.contentBlocks[i].set('highlight', true);
						}
					}
				}
			}, this);
			
			this.once('destroy', this.beforeDestroy, this);
			
			//Fix context
			var win = this.get('iframe').get('win');
			this.onResize = Y.throttle(Y.bind(this.onResize, this), 50);
			Y.on('resize', this.onResize, win);
		},
		
		/**
		 * On resize sync overlay position
		 */
		onResize: function () {
			for(var i in this.contentBlocks) {
				this.contentBlocks[i].syncOverlayPosition();
			}
		},
		
		/**
		 * Create children
		 * 
		 * @param {Object} data
		 * @private
		 */
		createChildren: function (data) {
			var data = data || this.get('contentData');
			if (data) {
				var body = this.get('body');
				var doc = this.get('doc');
				var win = this.get('win');
				
				for(var i=0,ii=data.length; i<ii; i++) {
					
					var type = data[i].type;
					var properties = Manager.Blocks.getBlock(type);
					var classname = properties && properties.classname ? properties.classname : type[0].toUpperCase() + type.substr(1);
					
					if (classname in Action) {
						var block = this.contentBlocks[data[i].id] = new Action[classname]({
							'doc': doc,
							'win': win,
							'body': body,
							'data': data[i],
							'parent': null,
							'super': this,
							'dragable': !data[i].locked,
							'editable': !data[i].locked
						});
						block.render();
					} else {
						Y.error('Class "' + classname + '" for content "' + data[i].id + '" is missing.');
					}
					
				}
			}
		},
		
		renderUI: function () {
			this.createChildren();
			this.get('body').addClass('yui3-editable');
		},
		
		render: function () {
			this.renderUI();
			this.bindUI();
		},
		
		/**
		 * Loads and returns block data
		 * 
		 * @param {Object} data Block information
		 * @param {Function} callback Callback function
		 * @param {Object} context
		 */
		getBlockInsertData: function (data, callback, context) {
			var url = Manager.PageContent.getDataPath('insertblock') + '.php';
			var page_info = Manager.Page.getPageData();
			
			data = Supra.mix({
				'page_id': page_info.id,
				'version_id': page_info.version.id,
				
				'locale': Supra.data.get('locale')
			}, data);
			
			Supra.io(url, {
				'data': data,
				'on': {
					'success': function (evt, data) {
						if (data && Y.Lang.isFunction(callback)) {
							callback.call(context, data);
						}
					}
				}
			});
		},
		
		/**
		 * Send block delete request
		 * 
		 * @param {Object} block
		 */
		sendBlockDelete: function (block, callback, context) {
			var url = Manager.PageContent.getDataPath('deleteblock');
			var page_info = Manager.Page.getPageData();
			var data = {
				'page_id': page_info.id,
				'version_id': page_info.version.id,
				
				'id': block.getId(),
				
				'locale': Supra.data.get('locale')
			};
			
			Supra.io(url, {
				'data': data,
				'method': 'post',
				'on': {
					'success': function (evt, data) {
						if (Y.Lang.isFunction(callback)) {
							callback.call(context, data);
						}
					}
				},
				'context': context
			});
		},
		
		/**
		 * Save block order request
		 * 
		 * @param {Object} block
		 * @param {Object} order
		 */
		sendBlockOrder: function (block, order) {
			var url = Manager.PageContent.getDataPath('orderblocks');
			var page_info = Manager.Page.getPageData();
			var data = {
				'page_id': page_info.id,
				'version_id': page_info.version.id,
				
				'id': block.getId(),
				'order': order,
				
				'locale': Supra.data.get('locale')
			};
			
			Supra.io(url, {
				'data': data,
				'method': 'post'
			});
		},
		
		/**
		 * Save block properties
		 * 
		 * @param {Object} block Block
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback context
		 */
		sendBlockProperties: function (block, callback, context) {
			var url = Manager.PageContent.getDataPath('save'),
				page_data = Manager.Page.getPageData(),
				values = block.properties.getValues();
			
			//Some inputs (like InlineHTML) needs data to be processed before saving it
			var save_values = block.properties.getSaveValues();
			
			var post_data = {
				'id': page_data.id,
				'version': page_data.version.id,
				'block_id': block.getId(),
				'locale': Supra.data.get('locale'),
				'properties': save_values
			};
			
			Supra.io(url, {
				'data': post_data,
				'method': 'post',
				'on': {'success': callback}
			}, context);
		},
		
		/**
		 * Remove child Supra.Manager.PageContent.Proto object
		 * 
		 * @param {Object} child
		 */
		removeChild: function (child) {
			for(var i in this.contentBlocks) {
				if (this.contentBlocks[i] === child) {
					
					//Send request
					this.sendBlockDelete(child, function (response) {
						if (response) {
							delete(this.contentBlocks[i]);
							child.destroy();
						}
					}, this);
				}
			}
		},
		
		/**
		 * highlight attribute setter
		 * 
		 * @param {Boolean} value If true highlight will be shown
		 * @private
		 */
		_setHighlight: function (value) {
			if (value) {
				this.set('disabled', true);
				this.get('body').removeClass('yui3-editable');
				this.get('body').addClass('yui3-highlight');
				
				this.set('activeContent', null);
			} else {
				this.set('disabled', false);
				this.get('body').addClass('yui3-editable');
				this.get('body').removeClass('yui3-highlight');
				
				for (var i in this.contentBlocks) {
					this.contentBlocks[i].set('highlight', false);
				}
			}
			
			return !!value;
		},
		
		beforeDestroy: function () {
			//Destroy children
			var child = null,
				blocks = this.contentBlocks;
			
			for(var i in blocks) {
				child = blocks[i];
				delete(blocks[i]);
				child.destroy();
			}
			
			//Unsubscribe resize
			var win = this.get('iframe').get('win');
			Y.unsubscribe('resize', this.onResize, win);
		}
	});
	
	Action.IframeContents = PageContents;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['widget', 'supra.page-content-list', 'supra.page-content-editable']});