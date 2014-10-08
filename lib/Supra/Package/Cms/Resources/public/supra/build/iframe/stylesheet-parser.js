YUI().add("supra.iframe-stylesheet-parser", function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Convert arrays to object, because object lookup is much faster
	var toObject = function (arr) { var o={},i=0,ii=arr.length; for(;i<ii;i++) o[arr[i]]=true; return o; };
	
	//Tag groups
	var tmp = null;
	var GROUPS = [
		{
			'id': 'text',
			'tags': (tmp = ['H1', 'H2', 'H3', 'H4', 'H5', 'P', 'B', 'EM', 'U', 'S', 'A']),
			'tagsObject': toObject(tmp)
		},
		{
			'id': 'list',
			'tags': (tmp = ['UL', 'OL', 'LI']),
			'tagsObject': toObject(tmp)
		},
		{
			'id': 'table',
			'tags': (tmp = ['TABLE', 'TR', 'TD', 'TH']),
			'tagsObject': toObject(tmp)
		},
		{
			'id': 'image',
			'tags': (tmp = ['IMG']),
			'tagsObject': toObject(tmp)
		}
	];
	
	
	/*
	 * Iframe stylesheet parser, extracts CSS definitions
	 */
	function StylesheetParser (config) {
		StylesheetParser.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	StylesheetParser.NAME = "StylesheetParser";
	
	StylesheetParser.ATTRS = {
		"selectorPrefix": {
			//CSS selector prefix which should be extracted
			"value": "#su-style-dropdown"
		},
		
		"iframe": {
			//Iframe object, required if win and doc attributes are ommited
			"value": null
		},
		"win": {
			//Window object, required if iframe attribute is ommited
			"value": null
		},
		"doc": {
			//Document object, required if iframe attribute is ommited
			"value": null
		}
	};
	
	Y.extend(StylesheetParser, Y.Base, {
		
		/**
		 * All found selectors
		 */
		selectors: null,
		
		
		
		/**
		 * Constructor
		 * @constructor
		 */
		initializer: function () {
			var iframe = this.get("iframe");
			if (iframe) {
				iframe.on("ready", this.reset, this)
			}
			this.reset();
		},
		
		/**
		 * Destructor, clean up
		 */
		destructor: function () {
			
		},
		
		/**
		 * Reset all info
		 */
		reset: function () {
			var iframe = this.get("iframe"),
				win = null,
				doc = null;
			
			if (iframe) {
				win = iframe.getDOMNode().contentWindow;
				doc = win.document;
				
				this.set("win", win);
				this.set("doc", doc);
			}
			
			this.selectors = null;
		},
		
		
		/* ------------------------------- API ----------------------------- */
		
		/**
		 * Returns all selectors
		 * 
		 * @return All selectors
		 * @type {Array}
		 */
		getSelectors: function () {
			return this.selectors || this.collectStyleSelectors();
		},
		
		/**
		 * Returns all selectors grouped by tag names
		 * 
		 * @return All selectors grouped by tag names
		 * @type {Object}
		 */
		getSelectorsGrouped: function () {
			var container = this.htmleditor.get('srcNode'),
				result = {},
				selectors = this.getSelectors(),
				selector = null,
				i = 0,
				imax = selectors.length;
			
			for(; i < imax; i++) {
				selector = selectors[i];
				if (!result[selector.tag]) result[selector.tag] = [];
				result[selector.tag].push(selector);
			}
			
			return result;
		},
		
		/**
		 * Returns all selectors by tag
		 * 
		 * @param {String} tag Tag name
		 * @param {Boolean} include_global If selectors doesn't have specific tag then include it also
		 * @return Selectors matching tag
		 * @type {Array}
		 */
		getSelectorsByTag: function (tag, include_global) {
			var result = [],
				selectors = this.getSelectors(),
				i = 0,
				imax = selectors.length,
				selector;
				
			for(; i < imax; i++) {
				selector = selectors[i];
				if (selector.tag == tag || (!selector.tag && include_global)) {
					result.push(selector);
				}
			}
			
			return result;
		},
		
		/**
		 * Returns all selectors which match given node or if node is ancestor of that element
		 * 
		 * @param {Object} container Container element
		 * @return List of all matching selectors grouped by tag name
		 * @type {Object}
		 */
		getSelectorsByNodeMatch: function (container) {
			var result = {},
				selectors = this.getSelectors(),
				selector = null,
				i = 0,
				imax = selectors.length;
				
			for(; i < imax; i++) {
				selector = selectors[i];
				if (!selector.path || container.test(selector.path) || container.test(selector.path + ' *')) {
					if (!result[selector.tag]) result[selector.tag] = [];
					result[selector.tag].push(selector);
				}
			}
			
			return result;
		},
		
		
		/* ------------------------------- PARSER ----------------------------- */
		
		
		/**
		 * Traverse stylesheets and extract definitions matching selectorPrefix
		 * 
		 * @return List of selectors
		 * @type {Object}
		 */
		collectStyleSelectors: function () {
			var result = [],
				rules,
				doc = new Y.Node(this.get('doc')),
				links = doc.all('link[rel="stylesheet"], style[type="text/css"]'),
				link = null,
				prefix = this.get("selectorPrefix");
			
			if (links) {
				for(var i=0,ii=links.size(); i<ii; i++) {
					link = links.item(i).getDOMNode();
					if (link.sheet) {
						try {
							rules = link.sheet.cssRules;
						} catch (err) {
							rules = null; //"Insecure operation" error
						}
						if(rules) {
							for(var k=0,kk=rules.length; k<kk; k++) {
								if (rules[k].selectorText && rules[k].cssText.indexOf(prefix) != -1) {
									result.push(rules[k].cssText);
								}
							}
						}
					}
				}
			}
			
			var style = doc.all('style[type="text/css"]'),
				regex = new RegExp(Y.Escape.regex(prefix) + " [^}]*}", "g"),
				css,
				match;
			
			if (style) {
				for(var i=0,ii=style.size(); i<ii; i++) {
					css = style.item(i).get('innerHTML');
					match = css.match(regex);
					
					if (match) {
						result = result.concat(match);
					}
				}
			}
			
			result = this.parseStyleSelectors(result);
			this.selectors = result;
			return result;
		},
		
		/**
		 * Parse collected style selectors and extract info
		 * 
		 * @param {Array} result List of selectors
		 * @return List of selector information
		 * @type {Object}
		 */
		parseStyleSelectors: function (result) {
			var i = 0,
				imax = result.length,
				prefix = this.get("selectorPrefix"),
				selector,
				match,
				list = [],
				tmp = null,
				//               PREFIX    TAG      .   CLASSNAME      [attrs][attr]       { styles }
				regex_normal = /(.+\s)?([a-z0-9]+)?\.([a-z0-9\-\_]+)\s?((\[[^\]]+\])+)?\s?\{([^\}]*)\}/i,
				//               PREFIX    TAG        [attr][attr]   .   CLASSNAME         { styles }
				regex_reverse = /(.+\s)?([a-z0-9]+)?((\[[^\]]+\])+)?\.([a-z0-9\-\_]+)\s?\{([^\}]*)\}/i;
			
			for(; i < imax; i++) {
				selector = result[i].replace(prefix + ' ', '');
				
				//Format is .selector tag.classname[attribute]{css}
				//match is: 0 - all selector, 1 - prefix, 2 - tag, 3 - classname, 4 - attributes, 5 - styles
				match = selector.match(regex_normal);
				
				//Need to support also: .selector tag[attribute].classname{css}
				if (match && !match[1] && !match[2] && !match[4] && !match[5]) {
					match = selector.match(regex_reverse);
					
					//Fix incorrect indexes
					tmp = match[4]; match[4] = match[5]; match[5] = tmp;
					tmp = match[3]; match[3] = match[4]; match[4] = tmp;
				}
				
				if (match) {
					tmp = match[2] ? match[2].toUpperCase() : '';
					list.push({
						'path': match[1] ? match[1].replace(/^\s+|\s+$/g, '') : null,
					    'tag': tmp,
					    'group': this.getGroupByTag(tmp),
					    'classname': match[3],
					    'attributes': this.parseSelectorAttributes(match),
					    'style': match[6]
					});
				}
			}
			
			return list;
		},
		
		/**
		 * Parse and return CSS selector attribute values
		 * 
		 * @param {Array} match Selector match
		 * @return Object with attribute names and values
		 * @type {Object}
		 */
		parseSelectorAttributes: function (match) {
			var attr = match[4],
				data,
				ret = {},
				trim = /^("|')|("|')$/g;
			
			if (attr) {
				//Convert [...][...] into ...,...
				attr = attr.replace(/\]\s*\[/g, ',').replace('[', '').replace(']', '');
				attr = attr.split(',');
				for(var i=0,ii=attr.length; i<ii; i++) {
					data = attr[i].split('=');
					ret[data[0]] = data.length > 1 ? data[1].replace(trim, '') : '';
				}
			}
			
			if (!ret.title) {
				ret.title = (match[2] ? match[2] : '') + match[3];
			}
			
			return ret;
		},
		
		/**
		 * Returns group ID by tag
		 * 
		 * @param {String} tag Tag name
		 * @return Group ID
		 * @type {String}
		 */
		getGroupByTag: function (tag) {
			var groups = GROUPS,
				i = 0,
				ii = groups.length;
			
			for(; i<ii; i++) {
				if (groups[i].tagsObject[tag]) return groups[i].id;
			}
			
			return null;
		}
		
	});
	
	Supra.IframeStylesheetParser = StylesheetParser;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {"requires": ["base"]});