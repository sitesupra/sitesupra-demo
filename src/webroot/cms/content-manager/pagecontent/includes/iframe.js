//Invoke strict mode
"use strict";

/*
 * Supra.Manager.PageContent.Iframe
 */
YUI.add('supra.iframe-handler', function (Y) {
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.PageContent,
		Root = Manager.getAction('Root');
	
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
			
			var doc = this.get('doc');
			var link = doc.createElement('link');
				link.rel = "stylesheet";
				link.type = "text/css";
				link.href = href;
			
			doc.getElementsByTagName('HEAD')[0].appendChild(link);
			return link;
		},
		
		
		/**
		 * Create contents object
		 */
		createContent: function () {
			//Add contents
			var body = new Y.Node(this.get('doc').body);
			
			if (this.contents) this.contents.destroy();
			this.contents = new Action.IframeContents({'iframe': this, 'doc': this.get('doc'), 'win': this.get('win'), 'body': body, 'contentData': this.get('contentData')});
			this.contents.render();
			
			//Disable editing
			this.contents.set('highlight', true);
			
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
			
			//Trigger ready event
			this.fire('ready', {'iframe': this, 'body': body});
		},
		
		/**
		 * Destroy contents object
		 */
		destroyContent: function () {
			var doc = this.get('doc');
			
			if (this.contents) {
				this.contents.destroy();
				this.contents = null;
			}
			
			if (doc) {
				//Remove all listeners
				Y.one(doc).purge(true);
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
			//Set attribute
			this.set('html', html);
			
			//Clean up
			this.destroyContent();
			
			//Save document & window instances
			var win = this.get('nodeIframe').getDOMNode().contentWindow;
			var doc = win.document;
			this.set('win', win);
			this.set('doc', doc);
			
			//Change iframe HTML
			this.writeHTML(html);
			
			//Small delay before continue
			Y.later(50, this, function () {
				this._afterSetHTML(preview_only);
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
				
				doc.body.onload = loadNextScript;
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
			this.overlay.setClass('hidden', !value);
			return !!value;
		},
		
		/**
		 * Prevent user from leaving page by preventing 
		 * default link and form behaviour
		 * 
		 * @private
		 */
		_handleContentElementBehaviour: function (body) {
			//Links
			Y.delegate('click', function (e) {
				//External links should be opened in new window
				//Internal links should be opened as page
				//Javascript,hash and mail links should be ignored
				var target = e.target,
					href = null,
					local_links = new RegExp('^mailto:|^javascript:|' + document.location.pathname + '#', 'i');
				
				if (target.test('.editing a')) {
					//If clicked on link inside content which is beeing edited, then don't do anything
					e.preventDefault();
					return;
				}
				if (!target.test('a')) {
					target = target.ancestor('a');
				}
				if (target && (href = target.get('href')) && !local_links.test(href)) {
					
					var regExp = new RegExp('^' + document.location.protocol 
						+ '//' 
						+ document.location.host.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&")
						+ '($|/)', 'i');
					
					if (!regExp.test(href)) {
						//External link
						window.open(href);
					} else if (!Action.isEditing()) {
						//If is editing, then don't change page
						
						Manager.Page.getPageIdFromPath(href, function (data, status) {
							if (status && data && data.page_id) {
								if (data.page_id != Supra.data.get(['page', 'id'])) {
									
									Supra.data.set('locale', data.locale);
									
									//Stop editing
									Action.stopEditing();

									//Change path
									Root.save(Root.ROUTE_PAGE.replace(':page_id', data.page_id));
								}
							} else {
								//TODO: open the link in the new tab or show message with link to the page
								window.open(href);
							}
						});
					}
				}
				
				e.preventDefault();
			}, body, 'a');
			
			//Forms
			Y.delegate('submit', function (e) {
				e.preventDefault();
			}, body, 'form');
		},
		
		/**
		 * Wait till stylesheets are loaded
		 * 
		 * @private
		 */
		_onStylesheetLoad: function (links) {
			var fn = Y.bind(function () {
				var loaded = true;
				for(var i=0,ii=links.length; i<ii; i++) {
					if (!links[i].sheet) {
						loaded = false;
						break;
					} else {
					}
				}
				
				if (loaded) {
					Y.later(50, this, function () {
						this.createContent();
					});
				} else {
					setTimeout(fn, 50);
				}
			}, this);
			setTimeout(fn, 50);
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
			
			//Get all stylesheet links
			var links = [],
				elements = Y.Node(doc).all('link[rel="stylesheet"]'),
				app_path = null,
				link = null;
			
			for(var i=0,ii=elements.size(); i<ii; i++) {
				links.push(Y.Node.getDOMNode(elements.item(i)));
			}
			
			//Add stylesheets to iframe
			if (!Supra.data.get(['supra.htmleditor', 'stylesheets', 'skip_default'], false)) {
				link = this.addStyleSheet(Action.getActionPath() + "iframe.css");
				if (link) {
					links.push(link);
				}
			}
			
			//In preview mode there is no drag and drop and no editing
			if (!preview_only) {
				//Reset DD
				Action.resetDD(doc);
				
				//When stylesheets are loaded initialize IframeContents
				this._onStylesheetLoad(links, body);
			}
		},
		
		/**
		 * Set loading state
		 */
		_setLoading: function (value) {
			this.get('contentBox').setClass('yui3-page-iframe-loading', value);
		}
		
	});
	
	Manager.PageContent.IframeHandler = IframeHandler;
	
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['widget']});