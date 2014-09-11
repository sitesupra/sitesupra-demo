YUI().add('supra.iframe', function (Y) {
	//Invoke strict mode
	'use strict';
	
	//Shortcuts
	var Color = Y.DataType.Color,
		REGEX_FIND_COLOR = /(#[0-9ABCDEF]+|rgb(a)?\([0-9\.\,\s]+\))/gi,
		REGEX_FIND_SCRIPT = /<script [^>]*src="?'?([^\s"']+).*?<\/script[^>]*>/gi;
	
	
	/**
	 * Iframe content widget
	 */
	function Iframe (config) {
		Iframe.superclass.constructor.apply(this, arguments);
		
		this.cssRulesCache = {};
		this.cssRulesColorPropertyCache = {};
		this.init.apply(this, arguments);
	}
	
	Iframe.NAME = 'iframe';
	Iframe.CSS_PREFIX = 'su-' + Iframe.NAME;
	
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
		
		// Prevent navigation to external pages
		'preventExternalNavigation': {
			value: true
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
		 * Cache for CSSRules by classname
		 * @type {Object}
		 * @private
		 */
		cssRulesCache: {},
		
		/**
		 * Cache for CSSRules properties by classname and property
		 * @type {Object}
		 * @private
		 */
		cssRulesColorPropertyCache: {},
		
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
		 * Value can be "url" or "html"
		 * @type {String}
		 * @private
		 */
		contentChangeTrigger: null,
		
		
		/**
		 * Render UI
		 * 
		 * @private
		 */
		renderUI: function () {
			Iframe.superclass.renderUI.apply(this, arguments);
			
			var url  = this.get('url'),
				html = this.get('html');
			
			if (url) {
				this.set('url', url);
			} else if (html) {
				this.set('html', html);
			}
			
			// Loading icon
			this.get('boundingBox').append(Y.Node.create('<div class="loading-icon"></div>'));
			
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
			this.get('contentBox').on('load', this._afterContentChange, this);
			
			this.after('docChange', this._afterDocChange, this);
			this.after('fontsChange', this._afterFontsChange, this);
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
			var doc  = this.get('doc'),
				body = new Y.Node(doc.body);
			
			//Loading is done, remove loading style
			this.set('loading', false);
			
			//Trigger ready event when everything is actually ready
			if (doc.readyState == 'complete' || doc.readyState == 'loaded') {
				this.fire('ready', {'iframe': this, 'body': body, 'doc': doc});
				this.get('contentBox').fire('ready');
			} else {
				this.get('win').addEventListener('load', Y.bind(function () {
					this.loadEventTriggered = true;
					this.fire('ready', {'iframe': this, 'body': body, 'doc': doc});
					this.get('contentBox').fire('ready');
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
			var doc = this.get('doc'),
				parser = this.stylesheetParser,
				fonts = this.googleFonts;
			
			if (doc) {
				//Unregister document from DDM
				if (this.get('initDndListeners') && Y.DD) {
					Y.DD.DDM.unregDoc(doc);
				}
				
				//Remove all listeners
				Y.Node(doc).destroy(true);
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
			
			this.flushCache();
		},
		
		
		/*
		 * ---------------------------------- PRIVATE ---------------------------------
		 */
		
		
		/**
		 * Update CSS rules property by replacing color
		 * 
		 * @param {String} property CSS property which will be updated
		 * @param {Array} rules List of rules which will be updated
		 * @param {String} value Color value
		 * @private
		 */
		updateRulesPropertyColor: function (property, rules, color) {
			var r = 0,
				rr = rules.length,
				styles = null,
				
				style = null,
				replaced = null,
				
				colorMix = this.colorMix,
				
				selector = null,
				cache = this.cssRulesColorPropertyCache,
				
				regex = REGEX_FIND_COLOR;
			
			for(; r<rr; r++) {
				styles = rules[r].style;
				selector = rules[r].selectorText;
				
				//Cache
				if (selector in cache && property in cache[selector]) {
					style = cache[selector][property];
				} else {
					style = styles[property];
					
					if (!(selector in cache)) cache[selector] = {};
					cache[selector][property] = style;
				}
				
				//Update styles
				if (style) {
					replaced = style.replace(REGEX_FIND_COLOR, function (a) { return colorMix(a, color) });
					
					if (replaced != style) {
						styles[property] = replaced;
					}
				}
			}
		},
		
		/**
		 * Mix colors for gradient
		 * 
		 * @param {String} a Overlay color
		 * @param {String} b Base color
		 * @private
		 */
		colorMix: function (overlay, base) {
			return Color.format(Color.math.overlay(overlay, base));
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
			if (this.contentChangeTrigger) {
				// Content change was triggered by url or html change
				this.contentChangeTrigger = null;
			} else {
				// None of the properties caused content change, update
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
				html = html.replace(REGEX_FIND_SCRIPT, function (m, src) {
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
				body = new Y.Node(doc.body);
			
			//Handle link click, form submit, etc.
			this.handleContentElementBehaviour(body);
			
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
				link = null,
				href = '',
				type = '';
 			
 			for(var i=0,ii=elements.size(); i<ii; i++) {
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
			Y.delegate('click', this.handleContentLinkClick, body, 'a', this);
			
			//Forms
			Y.delegate('submit', this.handleContentFormSubmit, body, 'form', this);
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
				regex_pathname = /^[^?]+/i;
			
			if (this.get('preventExternalNavigation')) {
				if (href.indexOf(doc.location.hostname) == -1 && href.match(/^[a-z]+:\/\//i)) {
					// Trying to navigate to another domain
					e.preventDefault();
					return;
				}
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
			if (this.get('preventNavigation')) {
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
			if (this.googleFonts) {
				this.googleFonts.set('doc', e.newVal);
			} else {
				this.googleFonts = new Supra.GoogleFonts({
					'doc': e.newVal,
					'fonts': this.get('fonts')
				});
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
				this.contentChangeTrigger = 'url';
				
				// Clean up
				this.contentDestructor();
				
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
			
			this.contentChangeTrigger = 'html';
			
			//Clean up
			this.contentDestructor();
			
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
			for (; i<ii; i++) {
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
			this.get('boundingBox').toggleClass('yui3-page-iframe-loading', value);
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
		 * @param {String} src SRC attribute value
		 * @return Newly created script element
		 * @type {HTMLElement}
		 */
		addScript: function (src) {
			var doc = this.get('doc');
			var script = doc.createElement('script');
				script.type = 'text/javascript';
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
		
		/**
		 * Returns all styleSheet CSSStyleRules where selector has classname
		 * 
		 * @param {String} selector CSS selector to match
		 * @return Array with CSSStyleRules
		 * @type {Array}
		 */
		getStyleSheetRulesBySelector: function (selector) {
			//For performance we need to cache these
			if (this.cssRulesCache[selector]) {
				return this.cssRulesCache[selector];
			}
			
			var doc = this.get('doc'),
				
				stylesheets = doc.styleSheets,
				s = 0, ss = stylesheets.length,
				
				rules = null,
				rule  = null,
				r = 0, rr = 0,
				
				results = [];
			
			for (; s<ss; s++) {
				try {
					if (rules = stylesheets[s].cssRules) {
						r = 0;
						rr = rules.length;
						
						for (; r<rr; r++) {
							rule = rules[r];
							
							if (rule.selectorText.indexOf(selector) != -1) {
								results.push(rule);
							}
						}
					}
				} catch (err) {
					//Tried accessing rules from stylesheet which is not
					//on same domain, skip!
				}
			}
			
			this.cssRulesCache[selector] = results;
			
			return results;
		},
		
		/**
		 * Set CSS styles to the CSSStyleRules matching selector
		 * 
		 * @param {String} selector CSS selector to match
		 * @param {Object} styles List of styles
		 */
		setStylesBySelector: function (selector, styles) {
			var rules = this.getStyleSheetRulesBySelector(selector),
				r = 0,
				rr = rules.length,
				
				key = null;
			
			for (; r<rr; r++) {
				Supra.mix(rules[r].style, styles);
			}
		},
		
		/**
		 * Returns CSS styles from the CSSStyleRules matching selector
		 * 
		 * @param {String} selector CSS selector to match
		 * @param {Array} properties List of properties to look for
		 */
		getStylesBySelector: function (selector, properties) {
			var rules = this.getStyleSheetRulesBySelector(selector),
				r = 0,
				rr = rules.length,
				
				prop = null,
				output = {};
			
			for(var i=0,ii=properties.length; i<ii; i++) {
				prop = properties[i];
				output[prop] = '';
				
				for(r=0; r<rr; r++) {
					if (rules[r].style[prop]) {
						output[prop] = rules[r].style[prop];
						break;
					}
				}
			}
			
			return output;
		},
		
		/**
		 * Update background gradient color
		 * 
		 * @param {String} selector CSS selector to find rules
		 * @param {String} color Base color
		 */
		updateBackgroundGradient: function (selector, color) {
			var rules = this.getStyleSheetRulesBySelector(selector);
			
			this.updateRulesPropertyColor('backgroundColor', rules, color);
			
			if (Y.UA.ie && Y.UA.ie < 10) {
				this.updateRulesPropertyColor('filter', rules, color);
			} else {
				this.updateRulesPropertyColor('backgroundImage', rules, color);
			}
			
			this.updateRulesPropertyColor('borderTopColor', rules, color);
			this.updateRulesPropertyColor('borderBottomColor', rules, color);
			this.updateRulesPropertyColor('borderLeftColor', rules, color);
			this.updateRulesPropertyColor('borderRightColor', rules, color);
		},
		
		/**
		 * Flush all cached
		 */
		flushCache: function () {
			this.cssRulesCache = {};
			this.cssRulesColorPropertyCache = {};
		}
	});
	
	Supra.Iframe = Iframe;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['widget', 'supra.iframe-stylesheet-parser']});