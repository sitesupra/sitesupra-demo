/*
 * SU.Manager.PageContent.Iframe
 */
YUI.add('supra.page-iframe', function (Y) {
	
	//Shortcut
	var Action = SU.Manager.PageContent;
	
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
		/*
		 * Iframe element
		 */
		'nodeIframe': {
			value: null
		},
		/*
		 * Iframe url
		 */
		'url': {
			value: null,
			setter: '_setUrl'
		},
		/*
		 * Page content blocks
		 */
		'contentData': {
			value: null
		},
		/*
		 * Iframe document instance
		 */
		'doc': {
			value: null
		},
		/*
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
		
		_onIframeLoad: function () {
			//Save document & window instances
			var win = Y.Node.getDOMNode(this.get('nodeIframe')).contentWindow;
			var doc = win.document;
			this.set('win', win);
			this.set('doc', doc);
			
			//Prevent from leaving page by disabling links
			//and inputs, which prevents forms from submitting
			var body = new Y.Node(doc.body);
			var links = body.all('a');
			
			for(var i=0,ii=links.size(); i<ii; i++) {
				var link = links.item(i);
				link.set('href', 'javascript://');
				link.removeAttribute('onclick');
			}
			
			var inputs = body.all('input,button,select,textarea');
			
			for(var i=0,ii=inputs.size(); i<ii; i++) {
				inputs.item(i).set('disabled', true);
			}
			
			//Trigger ready event
			this.fire('ready', {'iframe': this, 'body': body});
			
			//Add stylesheet to iframe
			var links = [];
			links[links.length] = this.addStyleSheet("/cms/supra/build/button/button.css");
			links[links.length] = this.addStyleSheet("/cms/supra/build/input/input.css");
			links[links.length] = this.addStyleSheet("/cms/content-manager/pagecontent/iframe.css");
			
			//Wait till stylesheets are loaded
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
					this.contents = new PageContents({'iframe': this, 'doc': doc, 'win': win, 'body': body, 'contentData': this.get('contentData')});
					this.contents.render();
					
					this.contents.on('activeContentChange', function (event) {
					    this.fire('activeContentChange', {newVal: event.newVal, prevVal: event.prevVal});
					}, this);
					
				} else {
					setTimeout(fn, 50);
				}
			}, this);
			setTimeout(fn, 50);
		},
		
		showOverlay: function () {
			this.overlay.removeClass('hidden');
		},
		
		hideOverlay: function () {
			this.overlay.addClass('hidden');
		},
		
		bindUI: function () {
			PageIframe.superclass.renderUI.apply(this, arguments);
			
			//Load callback
			this.get('nodeIframe').on('load', this._onIframeLoad, this);
		},
		
		renderUI: function () {
			PageIframe.superclass.renderUI.apply(this, arguments);

			var cont = this.get('contentBox');
			var iframe = this.get('nodeIframe');
			
			this.overlay = Y.Node.create('<div class="yui3-iframe-overlay hidden"></div>');
			cont.append(this.overlay);
			
			this.set('url', this.get('url'));
			cont.removeClass('hidden');
		},
		
		_setUrl: function (url) {
			this.get('nodeIframe').setAttribute('src', url);
			return url;
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
			value: null,
			setter: '_setActiveContent'
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
		
		destructor: function () {
			var r = PageContents.superclass.initializer.apply(this, arguments);
			
			
			
			return r;
		},
		
		bindUI: function () {
			
			var win = this.get('iframe').get('win');
			
			//Fix context
			var fn = Y.bind(function () {
				
				for(var i in this.contentBlocks) {
					this.contentBlocks[i].syncUI();
				}
				
			}, this);
			
			//Bind block D&D
			this.on('block:dragend', function (e) {
				var region = Y.DOM._getRegion(e.position[1], e.position[0]+1, e.position[1]+1, e.position[0]);
				for(var i in this.contentBlocks) {
					var node = this.contentBlocks[i].getNode();
					
					if (node.inRegion(region) && this.contentBlocks[i].isChildTypeAllowed(e.block.id)) {
						return this.contentBlocks[i].fire('dragend:hit', {dragnode: e.dragnode, block: e.block});
					}
				}
				
			}, this);
			
			this.on('block:dragstart', function (e) {
				this.set('highlight', true);
				var type = e.block.id;
				
				for(var i in this.contentBlocks) {
					if (this.contentBlocks[i].isChildTypeAllowed(type)) {
						this.contentBlocks[i].set('highlight', true);
					}
				}
				
			}, this);
			
			Y.on('resize', Y.throttle(fn, 50), win);
		},
		
		_renderContentBlocks: function (data) {
			var body = this.get('body');
			var doc = this.get('doc');
			var win = this.get('win');
			
			for(var i=0,ii=data.length; i<ii; i++) {
				
				var type = data[i].type;
				var classname = type[0].toUpperCase() + type.substr(1);
				if (classname in Action) {
					var block = this.contentBlocks[data[i].id] = new Action[classname]({
						'doc': doc,
						'win': win,
						'body': body,
						'data': data[i],
						'parent': null,
						'super': this
					});
					block.render();
				}
				
			}
		},
		
		renderUI: function () {
			
			var data = this.get('contentData');
			if (data) {
				this._renderContentBlocks(data);
			}
			
			this.get('body').addClass('yui3-editable');
		},
		
		render: function () {
			this.renderUI();
			this.bindUI();
		},
		
		removeChild: function (child) {
			for(var i in this.contentBlocks) {
				if (this.contentBlocks[i] === child) {
					delete(this.contentBlocks[i]);
					child.destroy();
				}
			}
		},
		
		_setActiveContent: function (content) {
			var old = this.get('activeContent');
			
			if (old) {
				old.set('editing', false);
			}
			
			if (content instanceof Action.Proto) {
				if (!old || content !== old) {
					content.set('editing', true);
					return content;
				}
			} else if (Y.Lang.isString(content)) {
				if (!old || content != old.getId()) {
					//@TODO
				}
			} else {
				SU.Manager.Page.hideEditorToolbar();
			}
			
			return content;
		},
		
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
		}
	});
	
	Action.IframeContents = PageContents;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['widget', 'supra.page-content-proto', 'supra.page-content-list', 'supra.page-content-html', 'supra.page-content-sample']});