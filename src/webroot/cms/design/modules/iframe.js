//Invoke strict mode
"use strict";
	
YUI.add("website.iframe", function (Y) {
	
	//Shortcuts
	var Color = Y.DataType.Color;
	
	//List of fonts, which doesn't need to be loaded from Google Web Fonts
	var SAFE_FONTS = [
		"Arial", "Tahoma", "Helvetica", "sans-serif", "Arial Black", "Impact",
		"Trebuchet MS", "MS Sans Serif", "MS Serif", "Geneva", "Comic Sans MS" /* trololol.... */,
		"Palatino Linotype", "Book Antiqua", "Palatino", "Monaco", "Charcoal",
		"Courier New", "Georgia", "Times New Roman", "Times",
		"Lucida Console", "Lucida Sans Unicode", "Lucida Grande", "Gadget",
		"monospace"
	];
	
	//Map function to lowercase all array items
	var LOWERCASE_MAP = function (str) {
		return String(str || '').toLowerCase();
	};
	
	
	function DesignIframe (config) {
		DesignIframe.superclass.constructor.apply(this, arguments);
		
		this.cssRulesCache = {};
		this.cssRulesColorPropertyCache = {};
		this.init.apply(this, arguments);
	}
	
	DesignIframe.NAME = "design-iframe";
	DesignIframe.CSS_PREFIX = "su-" + DesignIframe.NAME;
	
	DesignIframe.ATTRS = {
		//Iframe URL
		"url": {
			"value": "",
			"setter": "_setURL"
		},
		//Iframe document element
		"doc": {
			"value": null
		},
		//Iframe window object
		"win": {
			"value": null
		},
		//Google APIs font list
		"fonts": {
			"value": [],
			"setter": "_setFonts"
		}
	};
	
	Y.extend(DesignIframe, Y.Widget, {
		/**
		 * Content box template
		 * @type {String}
		 * @private
		 */
		CONTENT_TEMPLATE: "<iframe />",
		
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
		 * Render UI
		 * 
		 * @private
		 */
		renderUI: function () {
			this.get("contentBox").on("load", this._onIframeLoad, this);
		},
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			
			
			
		},
		
		/**
		 * Sync attribute values with UI state
		 * 
		 * @private
		 */
		syncUI: function () {
			var url = this.get("url");
			if (url) this._setURL(url);
		},
		
		
		
		/*
		 * ---------------------------------- PRIVATE ---------------------------------
		 */
		
		
		/**
		 * Handle iframe load
		 * 
		 * @private
		 */
		_onIframeLoad: function () {
			var iframe = this.get("contentBox").getDOMNode(),
				win = iframe.contentWindow,
				doc = win.document;
			
			this.set("doc", doc);
			this.set("win", win);
			
			this._setFonts();
			
			this.fire('ready');
		},
		
		
		/*
		 * ---------------------------------- ATTRIBUTES ---------------------------------
		 */
		
		
		/**
		 * URL attribute setter
		 * 
		 * @param {String} url New iframe URL
		 * @return New iframe URL
		 * @type {String}
		 * @private
		 */
		_setURL: function (url) {
			if (!this.get("rendered")) return url;
			
			if (url && url != this.get("url")) {
				this.set("doc", null);
				this.set("win", null);
				
				this.flushCache();
				
				this.get("contentBox").setAttribute("src", url);
			}
			
			return url;
		},
		
		/**
		 * Load fonts from Google Fonts
		 * 
		 * @private
		 */
		_setFonts: function (fonts) {
			if (!this.get("rendered") || !this.get("doc")) return fonts;
			
			var fonts = Y.Lang.isArray(fonts) ? fonts : this.get("fonts"),
				i     = 0,
				ii    = fonts.length,
				
				//Get all safe fonts in lowercase
				safe  = Y.Array(SAFE_FONTS).map(LOWERCASE_MAP),
				apis  = [],
				
				parts = [],
				k	  = 0,
				kk    = 0,
				
				load  = [],
				temp  = '',
				uri   = "http://fonts.googleapis.com/css?family=";
			
			//Find which ones are not in the safe font list
			for (; i<ii; i++) {
				//Split "Arial, Verdana" into two items
				if (fonts[i].family || (fonts[i].title && !fonts[i].apis)) {
					parts = (fonts[i].family || fonts[i].title || '').replace(/\s*,\s*/g, ',').replace(/["']/, '').split(',');
				} else {
					parts = fonts[i].apis.replace(/:[^|]+/g, '').replace(/\+/g, ' ').split('|');
				}
				
				for (k=0,kk=parts.length; k<kk; k++) {
					//If any of the part is not in the safe list, then load from Google Fonts
					if (parts[k] && safe.indexOf(parts[k].toLowerCase()) == -1) {
						
						//Convert into format which is valid for uri
						if (fonts[i].apis) {
							load.push(fonts[i].apis);
						} else {
							temp = (fonts[i].family || fonts[i].title || '').replace(/\s*,\s*/g, ',').replace(/["']/, '').replace(/\s+/g, '+').replace(/,/g, '|');
							if (temp) load.push(temp);
						}
						
						break;
					}
				}
			}
			
			//
			var link = this.one('link[href^="' + uri + '"]');
			
			if (load.length) {
				if (link) {
					//Update
					link.setAttribute("href", uri + load.join('|'));
				} else {
					//Add
					link = Y.Node.create('<link href="' + uri + load.join('|') + '" rel="stylesheet" type="text/css" />');
					this.one("head").append(link);
				}
				
				this.fontsURI = uri + load.join('|');
			} else if (link) {
				//We don't have any fonts, remove link
				link.remove();
				
				this.fontsURI = '';
			}
		},
		
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
				cache = this.cssRulesColorPropertyCache;
			
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
					replaced = style.replace(/(#[0-9ABCDEF]+|rgb(a)?\([0-9\.\,\s]+\))/gi, function (a) { return colorMix(a, color) });
					
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
		 * ---------------------------------- API ---------------------------------
		 */
		
		
		/**
		 * Returns URI which was used to get font CSS file
		 * 
		 * @return Fonts CSS file URI
		 * @type {String}
		 */
		getFontRequestURI: function () {
			return this.fontsURI;
		},
		
		
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
	
	Supra.DesignIframe = DesignIframe;
	
	//Since this widget has Supra namespace, it doesn"t need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["widget"]});