YUI().add('supra.iframe', function (Y) {
	//Invoke strict mode
	'use strict';
	
	//Shortcuts
	var Color = Y.DataType.Color,
		REGEX_FIND_SCRIPT = /<script ([^>]*)>.*?<\/script[^>]*>/gi,
		REGEX_ATTRS = /([a-z0-9_-]+)(=("[^"]*"|'[^']*'|[^\s]*))?/gi;
	
	
	/**
	 * Iframe content widget
	 */
	function Iframe (config) {
		this.events = [];
		this.contentChangeTrigger = [];
		
		Iframe.superclass.constructor.apply(this, arguments);
	}
	
	Iframe.NAME = 'iframe';
	Iframe.CSS_PREFIX = 'su-' + Iframe.NAME;
	
	Iframe.HTML_PARSER = {
		'nodeIframe': function (srcNode) {
			if (srcNode.test('iframe')) {
				return srcNode;
			} else {
				return srcNode.one('iframe');
			}
		}
	};
	
	Iframe.ATTRS = {
		//Iframe URL
		'url': {
			'value': '',
			'setter': '_setURL'
		},
		//Iframe HTML
		'html': {
			'value': null,
			'setter': '_setHTML'
		},
		
		// Loading state
		'loading': {
			value: false,
			setter: '_setLoading'
		},
		
		//Iframe document element
		'doc': {
			'value': null
		},
		//Iframe window object
		'win': {
			'value': null
		},
		
		//Google APIs font list
		'fonts': {
			'value': [],
			'setter': '_setFonts'
		},
		
		// Stylesheet parser, Supra.IframeStylesheetParser
		'stylesheetParser': {
			value: null,
			getter: '_getStylesheetParser'
		},
		
		// Automatically initialize listeners for handling drag and drop
		'initDndListeners': {
			value: true
		},
		
		// Prevent navigation to another pages
		'preventNavigation': {
			value: true
		},
		
		// Prevent form submit, if 
		// preventNavigation is true, then this is ignored
		'preventFormNavigation': {
			value: true
		},
		
		// Prevent navigation to external pages
		'preventExternalNavigation': {
			value: true
		},
		
		
		// Overlay visibility
		'overlayVisible': {
			value: false,
			setter: '_setOverlayVisible'
		}
	};
	
	Y.extend(Iframe, Y.Widget, {
		/**
		 * Content box template
		 * @type {String}
		 * @private
		 */
		CONTENT_TEMPLATE: '<iframe />',
		
		/**
		 * Iframe overlay, used for D&D to allow dragging over iframe
		 * @type {Object}
		 * @private
		 */
		overlay: null,
		
		/**
		 * Request used to get fonts CSS file
		 * @type {String}
		 * @private
		 */
		fontsURI: '',
		
		/**
		 * Stylesheet parser
		 * @type {Object}
		 * @private
		 */
		stylesheetParser: null,
		
		/**
		 * Supra.GoogleFonts instance
		 * @type {Object}
		 * @private
		 */
		googleFonts: null,
		
		/**
		 * Property which caused content change or null if none of the properties did
		 * Value is array of "cleanup", "url" or "html"
		 * @type {Array}
		 * @private
		 */
		contentChangeTrigger: null,
		
		/**
		 * Layout change listener is binded
		 * @type {Boolean}
		 * @private
		 */
		layoutBinded: false,
		
		/**
		 * Last known top offset
		 * Used to update content scroll position
		 * @type {Number}
		 * @private
		 */
		layoutOffsetTop: null,
		
		/**
		 * List of events attached to current document, which should be
		 * removed when content is reloaded
		 * @type {Array}
		 * @private
		 */
		events: null,
		
		
		/**
		 * Render UI
		 * 
		 * @private
		 */
		renderUI: function () {
			Iframe.superclass.renderUI.apply(this, arguments);
			
			var url  = this.get('url'),
				html = this.get('html'),
				box  = this.get('boundingBox');
			
			if (url) {
				this.set('url', url);
			} else if (html) {
				this.set('html', html);
			}
			
			// Loading icon
			box.append(Y.Node.create('<div class="loading-icon"></div>'));
			
			// Overlay
			this.overlay = Y.Node.create('<div class="su-iframe-overlay hidden"></div>');
			box.append(this.overlay);
			
			if (this.get('loading')) {
				this._setLoading(true);
			}
		},
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			this._onImageLoad = Y.bind(this._onImageLoad, this);
			this._onImageLoadTrigger = Supra.throttle(this._onImageLoadTrigger, 100, this, true);
			
			this.after('docChange', this._afterDocChange, this);
			this.after('fontsChange', this._afterFontsChange, this);
		},
		
		/**
		 * Bind event listeners, but only after content has been loaded, because
		 * IE9 triggers 'load' even when it's not neccessary
		 */
		bindUIIframe: function () {
			if (!this._bindUIIframe) {
				this._bindUIIframe = true;
				this.get('contentBox').on('load', this._afterContentChange, this);
			}
		},
		
		/**
		 * Sync attribute values with UI state
		 * 
		 * @private
		 */
		syncUI: function () {
			var url = this.get('url'),
				html = this.get('html');
			
			if (url) {
				this._setURL(url);
			} else if (html) {
				this._setHTML(html);
			}
		},
		
		/**
		 * Content is ready and can be initialized
		 * 
		 * @private
		 */
		contentInitializer: function () {
			var doc   = this.get('doc'),
				body  = new Y.Node(doc.body),
				ready = null;
			
			//Loading is done, remove loading style
			this.set('loading', false);
			
			//Bind to layout change
			if (this.layout && !this.layoutBinded) {
				this.layoutBinded = true;
				this.layout.on('sync', this.onLayoutSync, this);
			}
			
			//Reattach DOM listeners
			this.applyContentEventListeners();
			
			//Trigger ready event when everything is actually ready
			ready = Y.bind(function () {
				this.fire('ready', {'iframe': this, 'body': body, 'doc': doc});
				this.get('contentBox').fire('ready');
			}, this);
			
			if (doc.readyState == 'complete' || doc.readyState == 'loaded') {
				ready();
			} else {
				this.get('win').addEventListener('load', Y.bind(function () {
					this.loadEventTriggered = true;
					ready();
				}, this), false);
			}
			
			//Register document with DDM
			if (this.get('initDndListeners') && Y.DD) {
				Y.DD.DDM.regDoc(doc);
			}
		},
		
		/**
		 * Content is about to be destroyed, clean up
		 * 
		 * @private
		 */
		contentDestructor: function () {
			this.fire('cleanup');
			
			var doc = this.get('doc'),
				parser = this.stylesheetParser,
				fonts = this.googleFonts,
				events = this.events;
			
			// Remove event listeners
			for (var i=0, ii=events.length; i < ii; i++) {
				events[i].detach();
			}
			
			if (doc) {
				//Unregister document from DDM
				if (this.get('initDndListeners') && Y.DD) {
					Y.DD.DDM.unregDoc(doc);
				}
				
				// Remove all listeners
				Y.Node(doc).destroy(true);
				
				// Reload content to clean up
				this.contentChangeTrigger.push('cleanup');
				doc.location.replace('about:blank');
				
				this.set('doc', null);
				this.set('win', null);
			}
			
			if (fonts) {
				fonts.destroy();
				this.googleFonts = null;
			}
			
			if (parser) {
				parser.destroy();
				this.stylesheetParser = null;
			}
			
			this.events = [];
			this.layoutOffsetTop = null;
			this.queuedListeners = [];
		},
		
		
		/*
		 * ---------------------------------- PRIVATE: URL CHANGE ---------------------------------
		 */
		
		
		/**
		 * After URL change get win and doc objects
		 * 
		 * @private
		 */
		afterSetURL: function () {
			this.afterUnkownOriginContentChange();
		},
		
		/**
		 * Before URL change
		 * 
		 * @private
		 */
		beforeSetURL: function () {
		},
		
		
		/*
		 * ---------------------------------- CONTENT ---------------------------------
		 */
		
		
		/**
		 * After content change check why it was changed
		 * If url or html attribute change didn't triggered this then
		 * update document
		 * 
		 * @private
		 */
		_afterContentChange: function () {
			if (this.contentChangeTrigger.length) {
				// Content change was triggered by cleanup or url or html change, do nothing
				this.contentChangeTrigger.shift();
			} else {
				// None of the properties caused content change, so there is no handler.
				// Update manually
				this.afterUnkownOriginContentChange();
			}
		},
		
		afterUnkownOriginContentChange: function () {
			//Save document & window instances
			var win = this.get('contentBox').getDOMNode().contentWindow,
				doc = win.document,
				body = doc.body;
			
			this.set('win', win);
			this.set('doc', doc);
			
			this.afterWriteHTML();
		},
		
		
		/*
		 * ---------------------------------- PRIVATE: HTML CONTENT ---------------------------------
		 */
		
		
		/**
		 * Write HTML into iframe
		 * 
		 * @param {String} html HTML
		 * @private
		 */
		writeHTML: function (html) {
			var win = this.get('contentBox').getDOMNode().contentWindow,
				doc = win.document,
				scripts = [],
				fonts = this.get('fonts');
			
			doc.open('text/html', 'replace');
			
			//All link for Google fonts
			if (fonts) {
				html = Supra.GoogleFonts.addFontsToHTML(html, fonts);
			}
			
			//IE freezes when trying to insert <script> with src attribute using writeln
			if (Supra.Y.UA.ie) {
				html = html.replace(REGEX_FIND_SCRIPT, function (m, attrs_str) {
					var attrs = {},
						regex = REGEX_ATTRS;
					
					attrs_str.replace(REGEX_ATTRS, function (attr, name, _tmp, value) {
						attrs[name] = value ? value.replace(/^["']|["']$/g, '') : null;
					});
					
					if (attrs.src) {
						scripts.push(attrs);
						return '';
					} else {
						return m;
					}
				});
			}
			
			doc.writeln(html);
			doc.close();
			
			if (Supra.Y.UA.ie) {
				//Load scripts one by one to make sure order is correct
				var loadNextScript = function () {
					if (!scripts.length) return;
					
					var tag = scripts.shift(),
						node = doc.createElement('SCRIPT'),
						key;
					
					for (key in tag) {
						if (key == 'src') {
							node[key] = tag[key] ? tag[key] : "";
						} else {
							node.setAttribute(key, tag[key] ? tag[key] : "");
						}
					}
					
					node.type = 'text/javascript';
					node.onload = loadNextScript;
					doc.body.appendChild(node);
				};
				
				Supra.immediate(this, loadNextScript);
			}
			
			//Save document & window instances
			var win = this.get('contentBox').getDOMNode().contentWindow,
				doc = win.document;
			
			this.set('win', win);
			this.set('doc', doc);
			
			//Small delay before continue
			var timer = Y.later(50, this, function () {
				if (this.get('doc').body) {
					timer.cancel();
					this.afterWriteHTML();
				}
			}, [], true);
		},
		
		/**
		 * Get all existing stylesheets, add new ones and wait till they are loaded
		 * 
		 * @private
		 */
		afterWriteHTML: function () {
			var doc = this.get('doc'),
				body = new Y.Node(doc.body),
				html;
			
			//Start observing any following iframe reloads
			this.bindUIIframe();
			
			//Handle link click, form submit, etc.
			this.handleContentElementBehaviour(body);
			
			//Add "supra-cms" class to the <html> element
			Y.Node(doc).one('html').addClass('supra-cms');
			
			//Add "ie" class to the <html> element
			html = Y.Node(doc).one('html');
			
			if (Supra.Y.UA.ie) {
				html.addClass('ie');
			} else {
				html.addClass('non-ie');
				
				if (Supra.Y.UA.gecko) {
					html.addClass('gecko');
				} else if (Supra.Y.UA.webkit) {
					html.addClass('webkit');
				}
			}
			
			//Get all stylesheet links
			var links = [],
				elements = Y.Node(doc).all('link[rel="stylesheet"]'),
				link = null,
				href = '',
				type = '';
 			
 			for(var i=0,ii=elements.size(); i < ii; i++) {
				href = elements.item(i).getAttribute('href');
				type = elements.item(i).getAttribute('type');
				
				// Not google font stylesheets and not .less files
				if ((!href || href.indexOf(Supra.GoogleFonts.API_URI) === -1) && (!type || type === 'text/css')) {
					links.push(Y.Node.getDOMNode(elements.item(i)));
				}
			}
			
			//Add stylesheets to iframe, load using combo
			if (!Supra.data.get(['supra.htmleditor', 'stylesheets', 'skip_default'], false)) {
				var action = Supra.Manager.getAction('PageContent');
				
				if (action.get('loaded')) {
					link = this.addStyleSheet(Y.config.comboBase + action.getActionPath() + 'iframe.css');
					if (link) {
						links.push(link);
					}
				}
			}
			
			//When stylesheets are loaded initialize content
			this.observeStylesheetLoad(links, body);
		},
		
		/**
		 * Wait till stylesheets are loaded
		 * 
		 * @private
		 */
		observeStylesheetLoad: function (links) {
			var timer = Y.later(50, this, function () {
				var loaded = true,
					protocol = document.location.protocol,
					secure = (protocol == 'https:'),
					href = '';
				
				for(var i=0,ii=links.length; i < ii; i++) {
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
					this.contentInitializer();
				}
			}, [], true);
		},
		
		
		/**
		 * Prevent user from leaving page by preventing 
		 * default link and form behaviour
		 * 
		 * @private
		 */
		handleContentElementBehaviour: function (body) {
			//Links
			this.events.push(
				Y.delegate('click', this.handleContentLinkClick, body, 'a', this)
			);
			
			//Forms
			this.events.push(
				Y.delegate('submit', this.handleContentFormSubmit, body, 'form', this)
			);
		},
		
		/**
		 * Handles page link click in cms
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		handleContentLinkClick: function (e) {
			if (this.get('preventNavigation')) {
				e.preventDefault();
				return;
			}
			
			// Prevent navigation to external domain
			var target = e.target.closest('a'),
				href = target.getAttribute('href'),
				path = null,
				doc = this.get('doc'),
				regex_absolute = /^([a-z]:\/\/|\/)/i,
				regex_pathname = /^[^?]+/i,
				event_result = null;
			
			if (!href) {
				// There is nowhere to navigate to
				return;
			}
			
			if (href.indexOf(doc.location.hostname) == -1 && href.match(/^[a-z]+:\/\//i)) {
				if (!this.get('preventExternalNavigation')) {
					// Open new tab/window
					window.open(href);
				}
				
				// Trying to navigate to another domain
				e.preventDefault();
				return;
			}
			
			event_result = this.fire('navigate', {
				'target': target,
				'href': href,
				'iframe': this
			});
			
			if (event_result === false) {
				// Navigation will be handled by separate handler
				e.preventDefault();
				return;
			}
			
			// Change iframe URL
			if (href && (href[0] == '?')) {
				// Relative link starting with ?, we need to add at least / at the begining
				// otherwise page will go to /cms?...
				path = this.get('url').replace(/\?.*/, '');
				
				this.set('url', path + href);
				e.preventDefault();
			} else if (href && href[0] !== '#') {
				// URL must be absolute, not relative
				path = '';
				if (!regex_absolute.test(href)) {
					path = document.location.pathname;
					if (path.substr(-1, 1) != '/') path += '/';
				}
				
				this.set('url', path + href);
				e.preventDefault();
			}
		},
		
		/**
		 * Handles page link click in cms
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		handleContentFormSubmit: function (e) {
			if (this.get('preventNavigation') || this.get('preventFormNavigation')) {
				e.preventDefault();
			}
		},
		
		
		/*
		 * ---------------------------------- GOOGLE FONTS ---------------------------------
		 */
		

		/**
		 * Handle document change
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */		
		_afterDocChange: function (e) {
			// Google fonts
			if (this.googleFonts) {
				this.googleFonts.set('doc', e.newVal);
			} else {
				this.googleFonts = new Supra.GoogleFonts({
					'doc': e.newVal,
					'fonts': this.get('fonts')
				});
			}
			
			// Images
			if (e.newVal) {
				e.newVal.addEventListener('load', this._onImageLoad, true);
			}
		},
		
		/**
		 * Handle fonts change
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		_afterFontsChange: function (e) {
			if (this.googleFonts) {
				this.googleFonts.set('fonts', e.newVal);
			} else {
				this.googleFonts = new Supra.GoogleFonts({
					'doc': this.get('doc'),
					'fonts': e.newVal
				});
			}
		},
		
		
		/*
		 * ---------------------------------- IMAGES ---------------------------------
		 */
		
		
		/**
		 * List of image elements for which event should be triggered
		 * @type {Array}
		 * @private
		 */
		_loadedImages: null,
		
		/**
		 * Trigger image load event
		 * 
		 * @param {HTMLElement} node Image element
		 * @private
		 */
		_onImageLoad: function (e) {
			if (e.target && e.target.tagName === 'IMG') {
				this._loadedImages = this._loadedImages || [];
				this._loadedImages.push(e.target);
				this._onImageLoadTrigger();
			}
		},
		
		/**
		 * Trigger actual event
		 */
		_onImageLoadTrigger: function () {
			var nodes = this._loadedImages;
			this._loadedImages = [];
			this.fire('image-load', {'images': nodes});
		},
		
		
		/*
		 * ---------------------------------- ATTRIBUTES ---------------------------------
		 */
		
		
		/**
		 * URL attribute setter
		 * 
		 * @param {String} url New iframe URL
		 * @returns {String} New iframe URL
		 * @private
		 */
		_setURL: function (url) {
			if (!this.get('rendered')) return url;
			
			if (url) {
				// Clean up
				this.contentDestructor();
				
				// State
				this.contentChangeTrigger.push(url);
				
				// Style
				this.set('loading', true);
				
				// Allow settings
				this.beforeSetURL();
				
				//
				var iframe = this.get('contentBox');
				iframe.once('load', this.afterSetURL, this);
				iframe.setAttribute('src', url);
			}
			
			return url;
		},
		
		/**
		 * HTML attribute setter
		 * 
		 * @param {String} html New content HTML
		 * @returns {String} New html
		 * @private
		 */
		_setHTML: function (html) {
			if (!this.get('rendered')) return html;
			
			//Clean up
			this.contentDestructor();
			
			// State
			this.contentChangeTrigger.push('html');
			
			//Change iframe HTML, small delay to make sure
			//all JS is really cleaned up (Chrome issue)
			Y.later(16, this, function () {
				this.writeHTML(html);
			});
			
			return html;
		},
		
		/**
		 * Load fonts from Google Fonts
		 * 
		 * @private
		 */
		_setFonts: function (fonts) {
			var fonts = (this.get('fonts') || []).concat(fonts),
				i = 0,
				ii = fonts.length,
				unique_arr = [],
				unique_hash = {},
				id = null;
			
			// Extract unique
			for (; i < ii; i++) {
				if (typeof fonts[i] === 'string') {
					fonts[i] = {'family': fonts[i]};
				}
				
				id = fonts[i].apis || fonts[i].family;
				
				if (!(id in unique_hash)) {
					unique_hash[id] = true;
					unique_arr.push(fonts[i]);
				}
			}
			
			fonts = unique_arr;
			return fonts;
		},
		
		/**
		 * Set loading state
		 */
		_setLoading: function (value) {
			this.get('boundingBox').toggleClass('su-page-iframe-loading', value);
			return value;
		},
		
		/**
		 * stylesheetParser attribute getter
		 * 
		 * @param {Object} value
		 */
		_getStylesheetParser: function (value) {
			if (this.stylesheetParser) return this.stylesheetParser;
			
			var parser = new Supra.IframeStylesheetParser({
				'iframe': this.get('contentBox'),
				'doc': this.get('doc'),
				'win': this.get('win')
			});
			
			this.stylesheetParser = parser;
			return parser;
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
		
		
		/*
		 * ---------------------------------- API ---------------------------------
		 */
		
		
		/**
		 * Returns one element inside iframe content
		 * Returns Y.Node
		 * 
		 * @param {String} selector CSS selector
		 * @return First element matching CSS selector, Y.Node instance
		 * @type {Object}
		 */
		one: function (selector) {
			var doc = this.get('doc');
			if (doc) {
				return Y.Node(doc).one(selector);
			} else {
				return null;
			}
		},
		
		/**
		 * Returns all elements inside iframe content
		 * Returns Y.NodeList
		 * 
		 * @param {String} selector CSS selector
		 * @return All elements matching CSS selector, Y.NodeList instance
		 * @type {Object}
		 */
		all: function (selector) {
			var doc = this.get('doc');
			if (doc) {
				return Y.Node(doc).all(selector);
			} else {
				return null;
			}
		},
		
		/**
		 * Add class to iframe
		 * 
		 * @param {String} classname Class name
		 */
		addClass: function () {
			var box = this.get('boundingBox');
			if (box) box.addClass.apply(box, arguments);
			return this;
		},
		
		/**
		 * Remove class from iframe
		 * 
		 * @param {String} classname Class name
		 */
		removeClass: function () {
			var box = this.get('boundingBox');
			if (box) box.removeClass.apply(box, arguments);
			return this;
		},
		
		/**
		 * Toggle class
		 * 
		 * @param {String} classname Class name
		 */
		toggleClass: function () {
			var box = this.get('boundingBox');
			if (box) box.toggleClass.apply(box, arguments);
			return this;
		},
		
		/**
		 * Returns true if iframe bounding box has given class name
		 * 
		 * @param {String} classname Class name
		 * @returns {Boolean} True if iframe bounding box has classname, otherwise false
		 */
		hasClass: function () {
			var box = this.get('boundingBox');
			if (box) return box.hasClass.apply(box, arguments);
			return false;
		},
		
		/**
		 * Add script to the page content
		 * 
		 * Configuration options:
		 *     amd - if true, then will try finding 'require' function and use it to load script
		 *           default is false
		 *     combo - if true, then will use YUI combo to load files, only if there is more than one
		 *             file and 'amd' is false. Default is true
		 *     complete - callback function
		 * 
		 * @param {String} src SRC attribute value
		 * @param {Object} options Configuration
		 * @return Newly created script element
		 * @type {HTMLElement}
		 */
		addScript: function (src, options) {
			var doc = this.get('doc'),
				win,
				head = doc.getElementsByTagName('HEAD')[0],
				script = null,
				i, ii,
				url,
				
				use_amd = options && options.amd,
				use_combo = !options || !('combo' in options) || options.combo,
				
				callback = options && typeof options.complete === 'function' ? options.complete : null;
			
			if (typeof src === 'string') {
				src = [src];
				use_combo = false;
			}
			
			if (src.length > 1 && use_combo) {
				// Use combo only when loading multiple files
				url = Y.Env._loader.comboBase;
				url += src.join('&');
				src = [url];
				
				// Combo and amd are not compatible
				use_amd = false;
			}
			
			if (use_amd) {
				// Check if 'require' exists
				win = this.get('win');
				
				if (typeof win.require !== 'function') {
					use_amd = false;
				}
			}
			
			if (src.length) {
				if (use_amd) {
					win.require(src, callback);
				} else {
					for (i=0, ii=src.length; i < ii; i++) {
						script = doc.createElement('script');
						script.setAttribute('type', 'text/javascript');
						script.setAttribute('src', src[i]);
						
						head.appendChild(script);
					}
					
					if (callback) callback();
				}
			} else {
				if (callback) callback();
			}
			
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
			if (this.one('link[href="' + href + '"]')) {
				return null;
			}
			
			var doc = this.get('doc'),
				head = doc.getElementsByTagName('HEAD')[0],
				link = doc.createElement('link');
				link.rel = 'stylesheet';
				link.type = 'text/css';
				link.href = href;
			
			if (head.childNodes.length) {
				head.insertBefore(link, head.childNodes[0]);
			} else {
				head.appendChild(link);
			}
			
			return link;
		},
		
		
		/* ------------------------------------------- Scroll ------------------------------------------- */
		
		
		/**
		 * Returns scroll position
		 * 
		 * @returns {Array} Array with X and Y scroll positions
		 */
		getScroll: function () {
			var doc = this.get('doc'),
				body = doc ? doc.body : null,
				html = doc ? doc.getElementsByTagName('HTML')[0] : null;
			
			return [
					(html ? html.scrollLeft : 0) || (body ? body.scrollLeft : 0),
					(html ? html.scrollTop : 0) || (body ? body.scrollTop : 0) 
				];
		},
		
		/**
		 * Set scroll vertical position
		 * 
		 * @param {Array|Number} scroll Array with X and Y scroll positions or vertical scroll position
		 * @param {Boolean} animate Animate scroll instead of just setting it
		 */
		setScroll: function (scroll, animate) {
			var doc  = this.get('doc'),
				ydoc = Y.Node(this.get('doc')),
				win,
				body,
				html,
				x = null, y = null;
			
			// Normalize
			if (typeof scroll === 'number') {
				scroll = [null, scroll];
			}
			if (typeof scroll[0] == 'number') {
				x = Math.max(0, Math.min(ydoc.get('docHeight') - ydoc.get('winHeight'), scroll[0]));
			}
			if (typeof scroll[1] == 'number') {
				y = Math.max(0, Math.min(ydoc.get('docHeight') - ydoc.get('winHeight'), scroll[1]));
			}
			
			if (Y.Lang.isArray(scroll)) {
				if (animate === true) {
					win = Y.Node(this.get('win'));
					
					if (x !== null || y !== null) {
						var props = {};
						
						if (x !== null) {
							props.scrollLeft = x;
						}
						if (y !== null) {
							props.scrollTop = y;
						}
						
						var anim = new Supra.Y.Anim({
							node: win,
						    duration: 0.25,
						    easing: Supra.Y.Easing.easeOutStrong,
							to: props
						});
						anim.run();
					}
				} else {
					doc  = this.get('doc');
					body = doc ? doc.body : null;
					html = doc ? doc.getElementsByTagName('HTML')[0] : null;
					
					if (x !== null) {
						html.scrollLeft = body.scrollLeft = x;
					}
					if (y !== null) {
						html.scrollTop = body.scrollTop = y;
					}
				}
			}
		},
		
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
					scroll = this.getScroll()[1],
					scroll_to = scroll + diff;
				
				if (html || body) {
					this.setScroll([null, scroll_to]);
					this.layoutOffsetTop = event.offset.top;
				}
			}
		},
		
		
		/* ------------------------------------------- Events ------------------------------------------- */
		
		
		/**
		 * Listeners which are not attached, but should be once DOM is ready
		 * @type {Array}
		 * @private
		 */
		queuedListeners: [],
		
		/**
		 * Apply content event listeners
		 * 
		 * @private
		 */
		applyContentEventListeners: function () {
			var listeners = this.queuedListeners,
				i = 0,
				ii = listeners.length;
			
			for (; i < ii; i++) {
				listeners[i].call(this);
			}
			
			this.queuedListeners = [];
		},
		
		/**
		 * Trigger event in content using jQuery or CustomEvent
		 * 
		 * @param {String} event_name Event name
		 * @param {HTMLElement|Node|jQuery} node Element on which to fire event, optional
		 * @param {Object|Null} data Additional data which to pass to event, optional
		 */
		fireContentEvent: function (event_name, node, data) {
			var win = this.get('win'),
				doc = this.get('doc'),
				jQuery = win.jQuery,
				event_object,
				refresh_events = {'update': true, 'refresh': true, 'resize': true};
			
			// Find HTMLElement or list of elements
			if (node) {
				if (node.getDOMNode) {
					// Y.Node
					node = node.getDOMNode();
				}
			} else {
				node = doc;
			}
			
			if (jQuery) {
				if (jQuery.refresh && refresh_events[event_name]) {
					// jQuery.refresh
					var fn = event_name,
						jquery_element = jQuery(node),
						args = [jquery_element];
					
					if (event_name == 'refresh') {
						fn = 'init';
					} else if (event_name == 'update' || event_name == 'resize') {
						fn = 'trigger';
						args = [event_name, jquery_element, data];
					}
					
					if (jQuery.refresh[fn]) {
						return jQuery.refresh[fn].apply(jQuery.refresh, args);
					}
				} else {
					// jQuery.trigger
					event_object = jQuery.Event(event_name);
					
					// Errors in front-end code must not cause CMS failure
					try {
						jQuery(node || doc).trigger(event_object, data);
					} catch (e) {
						// Report that error occured
						if (console && console.error) {
							console.error(e);
						}
					}
				}
			} else if (node.dispatchEvent) {
				// Built-in event mechanism
				if (typeof win.CustomEvent == 'function') {
					// Modern browsers
					event_object = new CustomEvent(event_name, {'bubbles': true, 'cancelable': true, 'detail': data});
				} else {
					// IE9
					event_object = document.createEvent('CustomEvent');
					event_object.initCustomEvent(event_name, true /* bubbles */, true /* cancelable */, data);
				}
				
				try {
					(node || doc).dispatchEvent(event_object);
				} catch (e) {
					// Report that error occured
					if (console && console.error) {
						console.error(e);
					}
				}
			}
		},
		
		/**
		 * Observe content event
		 * 
		 * @param {Object} event_name Event name
		 * @param {HTMLElement|Node|jQuery} node Element which to observe, optional 
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback context, optional
		 */
		onContentEvent: function (event_name, node, callback, context) {
			var win = this.get('win'),
				doc = this.get('doc'),
				jQuery = win.jQuery;
			
			// Normalize arguments
			if (typeof node === 'function') {
				context = callback;
				callback = node;
				node = null;
			}
			
			// Bind callback to correct context
			if (context) {
				callback = Y.bind(callback, context);
			}
			
			// If still loading then queue listener, because document
			// may not be ready and would loose listener after content reload 
			if (this.get('loading')) {
				this.queuedListeners.push(function () {
					this.onContentEvent(event_name, node, callback, context);
				});
				
				return {
					'detach': Y.bind(function () {
						this.offContentEvent(event_name, node, callback);
					}, this)
				};
			}
			
			// Find HTMLElement or list of elements
			if (node) {
				if (node.getDOMNode) {
					// Y.Node
					node = node.getDOMNode();
				}
			}
			
			if (jQuery) {
				// jQuery
				jQuery(node || doc).on(event_name, callback);
			} else {
				// Native	
				(node || doc).addEventListener(event_name, callback, false);
			}
			
			return {
				'detach': Y.bind(function () {
					this.offContentEvent(event_name, node, callback);
				}, this)
			};
		},
		
		/**
		 * Detach content event listener
		 * 
		 * @param {Object} event_name Event name
		 * @param {HTMLElement|Node|jQuery} node Element, optional 
		 * @param {Function} callback Callback function
		 */
		offContentEvent: function (event_name, node, callback) {
			var win = this.get('win'),
				doc = this.get('doc'),
				jQuery = win.jQuery;
			
			// Normalize arguments
			if (typeof node === 'function') {
				callback = node;
				node = null;
			}
			
			// Find HTMLElement or list of elements
			if (node) {
				if (node.getDOMNode) {
					// Y.Node
					node = node.getDOMNode();
				}
			}
			
			// jQuery
			if (jQuery) {
				jQuery(node || doc).off(event_name, callback);
			}
			
			// Native
			(node || doc).removeEventListener(event_name, callback, false);
		},
		
		/**
		 * Observe content event, but trigger callback only once
		 * 
		 * @param {Object} event_name Event name
		 * @param {HTMLElement|Node|jQuery} node Element which to observe, optional 
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback context, optional
		 */
		onceContentEvent: function (event_name, node, callback, context) {
			// Normalize arguments
			if (typeof node === 'function') {
				context = callback;
				callback = node;
				node = null;
			}
			
			// Bind callback to correct context
			if (context) {
				callback = Y.bind(callback, context);
			}
			
			var evt = this.onContentEvent(event_name, node, function () {
				evt.detach();
				callback.apply(this, arguments);
			});
			
			return evt;
		}
		
	});
	
	Supra.Iframe = Iframe;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['widget', 'supra.iframe-stylesheet-parser']});
