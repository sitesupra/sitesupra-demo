/*
 * Supra.Manager.PageContent.Iframe
 */
YUI.add('supra.iframe-handler', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.PageContent,
		Root = Manager.getAction('Root');
	
	var GOOGLE_FONT_API_URI = document.location.protocol + "//fonts.googleapis.com/css?family=";
	
	
	/*
	 * Iframe
	 */
	function IframeHandler (config) {
		IframeHandler.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	IframeHandler.NAME = 'page-iframe';
	IframeHandler.CLASS_NAME = Y.ClassNameManager.getClassName(IframeHandler.NAME);
	IframeHandler.ATTRS = {
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
		},
		/**
		 * Loading state
		 */
		'loading': {
			value: false,
			setter: '_setLoading'
		},
		
		/**
		 * Stylesheet parser, Supra.IframeStylesheetParser
		 */
		'stylesheetParser': {
			value: null,
			getter: '_getStylesheetParser'
		}
	};
	
	IframeHandler.HTML_PARSER = {
		'nodeIframe': function (srcNode) {
			var iframe = srcNode.one('iframe');
			this.set('nodeIframe', iframe);
			return iframe;
		}
	};
	
	Y.extend(IframeHandler, Y.Widget, {
		
		/**
		 * Iframe overlay, used for D&D to allow dragging over iframe
		 * @type {Object}
		 */
		overlay: null,
		
		/**
		 * Stylesheet parser
		 * @type {Object}
		 */
		stylesheetParser: null,
		
		/**
		 * Layout is binded
		 * @type {Number}
		 */
		layoutBinded: false,
		
		/**
		 * Last known top offset
		 */
		layoutOffsetTop: null,
		
		
		/**
		 * Add script to the page content
		 *
		 * @param {String} src SRC attribute value
		 * @return Newly created script element
		 * @type {HTMLElement}
		 */
		addScript: function (src) {
			var doc = this.get('doc');
			var script = doc.createElement('script');
				script.type = "text/javascript";
				script.href = src;
			
			doc.getElementsByTagName('HEAD')[0].appendChild(script);
			return script; 
		},
		
		/**
		 * Add stylesheet link to the page content
		 *
		 * @param {String} href HREF attribute value
		 * @return Newly created link element or null if link elements already exists
		 * @type {HTMLElement}
		 */
		addStyleSheet: function (href) {
			//If link already exists then don't add it
			if (Y.Node(this.get('doc')).one('link[href="' + href + '"]')) {
				return null;
			}
			
			var doc = this.get('doc'),
				head = doc.getElementsByTagName('HEAD')[0],
				link = doc.createElement('link');
				link.rel = "stylesheet";
				link.type = "text/css";
				link.href = href;
			
			if (head.childNodes.length) {
				head.insertBefore(link, head.childNodes[0]);
			} else {
				head.appendChild(link);
			}
			
			return link;
		},
		
		
		/**
		 * Create contents object
		 */
		createContent: function () {
			//Add contents
			var doc  = this.get('doc'),
				body = new Y.Node(doc.body);
			
			if (this.contents) this.contents.destroy();
			this.contents = new Action.IframeContents({'iframe': this, 'doc': doc, 'win': this.get('win'), 'body': body, 'contentData': this.get('contentData')});
			this.contents.render();
			
			//Register document for drag and drop
			Y.DD.DDM.regDoc(doc);
			
			//Disable editing
			var path = Root.getRoutePath(),
				editing = Root.ROUTE_PAGE_EDIT_R.test(path) || Root.ROUTE_PAGE_CONT_R.test(path);
			
			if (!editing) {
				this.contents.set('highlightMode', 'disabled');
			} else {
				this.contents.set('highlightMode', 'edit');
			}
			
			this.contents.on('activeChildChange', function (event) {
				if (event.newVal) {
					Action.startEditing();
				}
			});
			
			this.contents.after('activeChildChange', function (event) {
				this.fire('activeChildChange', {newVal: event.newVal, prevVal: event.prevVal});
			}, this);
			
			//Loading is done, remove loading style
			this.set('loading', false);
			
			//Bind to layout
			if (this.layout && !this.layoutBinded) {
				this.layoutBinded = true;
				this.layout.on('sync', this.onLayoutSync, this);
			}
			
			//Trigger ready event when everything is actually ready
			var onready = Y.bind(function () {
				this.fire('ready', {'iframe': this, 'body': body});
				this.get('nodeIframe').fire('ready');
			}, this);
			
			if (doc.readyState == 'complete' || doc.readyState == 'loaded') {
				onready();
			} else {
				this.get('win').addEventListener('load', onready, false);
			}
		},
		
		/**
		 * Destroy contents object
		 */
		destroyContent: function () {
			var doc = this.get('doc'),
				iframe = this.get('nodeIframe');
			
			if (doc) {
				// Remove document from DDM
				Y.DD.DDM.unregDoc(doc);
			}
			
			if (this.contents) {
				// This will destroy content and all attached widgets, plugins etc.
				this.contents.destroy();
				this.contents = null;
			}
			
			if (doc) {
				// Remove all listeners, widgets, plugins, etc. which hasn't been removed
				// by contents.destroy()
				Y.Node(doc).destroy(true);
				
				// Set to blank to remove all JS
				doc.location.replace("about:blank");
				
				this.set('doc', null);
				this.set('win', null);
			}
		},
		
		/**
		 * Returns content object
		 *
		 * @return Content object instance
		 * @type {Object}
		 */
		getContent: function () {
			return this.contents;
		},
		
		/**
		 * On HTML attribute change update iframe content and page content blocks
		 * 
		 * @param {String} html
		 * @param {Boolean} preview_only Set only HTML, but it shouldn't be editable
		 * @return HTML
		 * @type {String}
		 */
		setHTML: function (html, preview_only) {
			//Clean up
			this.destroyContent();
			
			//Change iframe HTML, small delay to make sure
			//all JS is really cleaned up (Chrome & IE issue)
			Y.later(16, this, function () {
				//Set attribute
				this.set('html', html);
				
				//Change iframe HTML
				this.writeHTML(html);
				
				//Save document & window instances
				var win = this.get('nodeIframe').getDOMNode().contentWindow;
				var doc = win.document;
				
				this.set('win', win);
				this.set('doc', doc);
				
				//Small delay before continue
				var timer = Y.later(50, this, function () {
					if (this.get('doc').body) {
						timer.cancel();
						this._afterSetHTML(preview_only);
					}
				}, [], true);
			});
			
			return html;
		},
		
		/**
		 * Write HTML into iframe
		 * 
		 * @param {String} html HTML
		 * @private
		 */
		writeHTML: function (html) {
			var win = this.get('nodeIframe').getDOMNode().contentWindow;
			var doc = win.document;
			var scripts = [];
			
			doc.open("text/html", "replace");
			
			//IE freezes when trying to insert <script> with src attribute using writeln
			if (Supra.Y.UA.ie) {
				html = html.replace(/<script [^>]*src="?'?([^\s"']+).*?<\/script[^>]*>/gi, function (m, src) {
					scripts.push(src);
					return '';
				});
			}
			
			doc.writeln(html);
			doc.close();
			
			if (Supra.Y.UA.ie) {
				//Load scripts one by one to make sure order is correct
				var loadNextScript = function () {
					if (!scripts.length) return;
					
					var source = scripts.shift();
					var node = doc.createElement('SCRIPT');
					
					node.src = source;
					node.type = 'text/javascript';
					node.onload = loadNextScript;
					doc.body.appendChild(node);
				};
				
				Supra.immediate(this, loadNextScript);
			}
		},
		
		/**
		 * Show preview of the version
		 *
		 * @param {String} version_id
		 */
		showVersionPreview: function (version_id, callback, context) {
			var url = Manager.Page.getDataPath('version-preview');
			Supra.io(url, {
				'data': {
					'page_id': Manager.Page.getPageData().id,
					'version_id': version_id,
					'locale': Supra.data.get('locale')
				},
				'context': this,
				'on': {
					'complete': function (data, status) {
						if (data) {
							this.setHTML(data.internal_html, true);
						}
						
						if (Y.Lang.isFunction(callback)) {
							callback.call(context || window, data, status);
						}
					}
				}
			});
		},
		
		/**
		 * Returns scroll position
		 */
		getScroll: function () {
			var doc = this.get('doc'),
				body = doc.body,
				html = doc.getElementsByTagName('HTML')[0];
			
			return [
					(html ? html.scrollLeft : 0) || (body ? body.scrollLeft : 0),
					(html ? html.scrollTop : 0) || (body ? body.scrollTop : 0) 
				];
		},
		
		/**
		 * Change scroll position
		 * 
		 * @param {Array} scroll Array with X and Y scroll position
		 */
		setScroll: function (scroll) {
			var doc = this.get('doc'),
				body = doc.body,
				html = doc.getElementsByTagName('HTML')[0];
			
			if (Y.Lang.isArray(scroll)) {
				if (typeof scroll[0] == 'number') {
					html.scrollLeft = scroll[0];
					body.scrollLeft = scroll[0];
				}
				if (typeof scroll[1] == 'number') {
					html.scrollTop = scroll[1];
					body.scrollTop = scroll[1];
				}
			}
		},
		
		/**
		 * Render UI
		 */
		renderUI: function () {
			IframeHandler.superclass.renderUI.apply(this, arguments);
			
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
			this.overlay.toggleClass('hidden', !value);
			return !!value;
		},
		
		/**
		 * stylesheetParser attribute getter
		 * 
		 * @param {Object} value
		 */
		_getStylesheetParser: function (value) {
			if (this.stylesheetParser) return this.stylesheetParser;
			
			var parser = new Supra.IframeStylesheetParser({
				"iframe": this.get("nodeIframe"),
				"doc": this.get("doc"),
				"win": this.get("win")
			});
			
			this.stylesheetParser = parser;
			return parser;
		},
		
		/**
		 * Prevent user from leaving page by preventing 
		 * default link and form behaviour
		 * 
		 * @private
		 */
		_handleContentElementBehaviour: function (body) {
			//Links
			Y.delegate('click', this._handleContentLinkClick, body, 'a', this);
			
			//Forms
			Y.delegate('submit', function (e) {
				e.preventDefault();
			}, body, 'form');
		},
		
		/**
		 * Handles page link click in cms
		 */
		_handleContentLinkClick: function (e) {
			//External links should be opened in new window
			//Internal links should be opened as page
			//Javascript,hash,relative and mail links should be ignored
			var target = e.target.closest('a'),
				href = null,
				local_links = new RegExp('^\\?|^mailto:|^javascript:|^#|' + document.location.pathname + '#', 'i');

			if (target.test('.editing a')) {
				//If clicked on link inside content which is beeing edited, then don't do anything
				e.preventDefault();
				return;
			}
			if (target && (href = target.get('href')) && !local_links.test(href) && href.indexOf(document.location.pathname) == -1) {
				var regExp = new RegExp('^' + document.location.protocol 
					+ '//' 
					+ document.location.host.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&")
					+ '($|/)', 'i');

				if (!regExp.test(href)) {
					//External link
					window.open(href);
				} else if (!Action.isEditing()) {
					//If is editing, then don't change page

					Manager.Page.getPageIdFromPath(href, this._handleRedirect, this);
				}
			}

			e.preventDefault();
		},
		
		/**
		 * Redirects to another page localization
		 */
		_handleRedirect: function (data, status) {
			if (status && data && data.page_id) {
				if (data.page_id != Supra.data.get(['page', 'id'])) {
					if (data.redirect) {

						Supra.Manager.executeAction('Confirmation', {
							'message': '{#page.follow_redirect#}',
							'useMask': true,
							'buttons': [{
								'id': 'yes',
								'label': Supra.Intl.get(['buttons', 'yes']),
								'click': this._handleRedirectConfirmation,
								'context': this,
								'args': [true, data]
							},
							{
								'id': 'no',
								'label': Supra.Intl.get(['buttons', 'no']),
								'click': this._handleRedirectConfirmation,
								'context': this,
								'args': [false, data]
							}]
						});

						return;
					}

					Supra.data.set('locale', data.locale);

					//Stop editing
					Action.stopEditing();

					//Change path
					Root.router.save(Root.ROUTE_PAGE.replace(':page_id', data.page_id));
				}
			} else {
				//TODO: open the link in the new tab or show message with link to the page
//				window.open(href);
			}
		},
		
		/**
		 * If page has redirect will ask you follow redirect or not
		 */
		_handleRedirectConfirmation: function (e, args) {
			var follow = args[0],
			data = args[1],
			redirect_page_id = data.page_id;
			
			Supra.data.set('locale', data.locale);
									
			//Stop editing
			Action.stopEditing();
			
			if(follow) {
				redirect_page_id = data.redirect_page_id;
			}
			
			//Change path
			Root.router.save(Root.ROUTE_PAGE.replace(':page_id', redirect_page_id));
		},
		/**
		 * Wait till stylesheets are loaded
		 * 
		 * @private
		 */
		_onStylesheetLoad: function (links) {
			var timer = Y.later(50, this, function () {
				var loaded = true,
					protocol = document.location.protocol,
					secure = (protocol == 'https:'),
					href = '';
				
				for(var i=0,ii=links.length; i<ii; i++) {
					if (!links[i].sheet) {
						//If there is no href or href is http while cms is in https, then there will never be a sheet
						href = links[i].getAttribute('href');
						if (href) {
							if (secure && href.indexOf('http:') != -1) {
								// Link href is http while CMS is in https, we can't access such links
								// so skip it
							} else {
								loaded = false;
								break;
							}
						}
					}
				}
				
				if (loaded) {
					timer.cancel();
					this.createContent();
				}
			}, [], true);
		},
		
		/**
		 * Get all existing stylesheets, add new ones and wait till they are loaded
		 * 
		 * @private
		 */
		_afterSetHTML: function (preview_only) {
			var doc = this.get('doc'),
				body = new Y.Node(doc.body);
			
			this._handleContentElementBehaviour(body);
			
			//Add "supra-cms" class to the <html> element
			Y.Node(doc).one('html').addClass('supra-cms');
			
			//Add "ie" class to the <html> element
			if (Supra.Y.UA.ie) {
				Y.Node(doc).one('html').addClass('ie');
			} else {
				Y.Node(doc).one('html').addClass('non-ie');
			}
			
			//Get all stylesheet links
			var links = [],
				elements = Y.Node(doc).all('link[rel="stylesheet"]'),
				app_path = null,
				link = null,
				href = '';
 			
 			for(var i=0,ii=elements.size(); i<ii; i++) {
				href = elements.item(i).getAttribute('href');
				
				// Not google font stylesheet
				if (!href || href.indexOf(GOOGLE_FONT_API_URI) === -1) {
					links.push(Y.Node.getDOMNode(elements.item(i)));
				}
			}
			
			//Add stylesheets to iframe, load using combo
			if (!Supra.data.get(['supra.htmleditor', 'stylesheets', 'skip_default'], false)) {
//				link = this.addStyleSheet(Y.config.comboBase + Action.getActionPath() + "iframe.css");
				link = this.addStyleSheet(Action.getActionPath() + "iframe.css");
				if (link) {
					links.push(link);
				}
			}
			
			//In preview mode there is no drag and drop and no editing
			if (!preview_only) {
				//When stylesheets are loaded initialize IframeContents
				this._onStylesheetLoad(links, body);
			}
		},
		
		/**
		 * Set loading state
		 */
		_setLoading: function (value) {
			this.get('contentBox').toggleClass('yui3-page-iframe-loading', value);
		},
		
		
		/* ------------------------------------------- Layout ------------------------------------------- */
		
		
		/**
		 * On layout sync update content scroll to match new offset
		 * This is done so that user don't see content jumping when top-container height changes
		 * 
		 * @param {Object} event
		 */
		onLayoutSync: function (event) {
			if (this.layoutOffsetTop === null) {
				this.layoutOffsetTop = event.offset.top;
			}
			
			if (this.layoutOffsetTop != event.offset.top) {
				var diff = event.offset.top - this.layoutOffsetTop,
					doc = this.get('doc'),
					body = doc.body,
					html = doc.querySelector('HTML'),
					scroll = (html ? html.scrollTop : 0) || (body ? body.scrollTop : 0),
					scroll_to = scroll + diff;
				
				html.scrollTop = scroll_to;
				body.scrollTop = scroll_to;
				
				this.layoutOffsetTop = event.offset.top;
			}
		}
		
	});
	
	Manager.PageContent.IframeHandler = IframeHandler;
	
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn);this.fn = function () {};
	
}, YUI.version, {'requires': ['widget', 'supra.iframe']});
