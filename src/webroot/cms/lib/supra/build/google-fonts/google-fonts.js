YUI().add('supra.google-fonts', function (Y) {
	//Invoke strict mode
	'use strict';
	
	//List of fonts, which doesn't need to be loaded from Google Web Fonts
	var SAFE_FONTS = [
		'Arial', 'Tahoma', 'Helvetica', 'sans-serif', 'Arial Black', 'Impact',
		'Trebuchet MS', 'MS Sans Serif', 'MS Serif', 'Geneva', 'Comic Sans MS' /* trololol.... */,
		'Palatino Linotype', 'Book Antiqua', 'Palatino', 'Monaco', 'Charcoal',
		'Courier New', 'Georgia', 'Times New Roman', 'Times',
		'Lucida Console', 'Lucida Sans Unicode', 'Lucida Grande', 'Gadget',
		'monospace'
	];
	
	//Map function to lowercase all array items
	var LOWERCASE_MAP = function (str) {
		return String(str || '').toLowerCase();
	};
	
	var GOOGLE_FONT_API_URI = document.location.protocol + '//fonts.googleapis.com/css?family=';
	
	
	/**
	 * Iframe content widget
	 */
	function GoogleFonts (config) {
		GoogleFonts.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	GoogleFonts.NAME = 'google-fonts';
	
	GoogleFonts.ATTRS = {
		//Document element to which add fonts to
		'doc': {
			'value': document,
			'setter': '_setDoc'
		},
		
		//Google APIs font list
		'fonts': {
			'value': [],
			'setter': '_setFonts'
		}
	};
	
	Y.extend(GoogleFonts, Y.Base, {
		
		
		/**
		 * Initialization life cycle method
		 */
		initializer: function () {
			this.addFonts(this.get('fonts'));
		},
		
		/**
		 * Destruction life cycle method
		 */
		destructor: function () {
			// Nothing, leave LINK in document
		},
		
		
		/* ------------------------------------------- FONTS ------------------------------------------- */
		
		
		/**
		 * Load fonts from Google Fonts
		 * 
		 * @param {String} html HTML in which will be inserted <link />, if this is document then link is added to DOM <head />
		 * @private
		 */
		addFonts: function (fonts) {
			if (!fonts || !fonts.length) {
				return;
			}
			
			var existing = {},
				new_uri = '',
				old_uri = '',
				node = this.getLinkNode(true),
				data = null;
			
			if (!node) {
				return;
			}
			
			data = this.getExistingLinkData();
			new_uri = GoogleFonts.getURI(fonts.concat(data.fonts), data.subset);
			
			old_uri = node.getAttribute('href');
			
			if (old_uri != new_uri) {
				node.setAttribute('href', new_uri);
			}
		},
		
		
		/*
		 * ---------------------------------- DOM ---------------------------------
		 */
		
		
		/**
		 * Link node
		 */
		_node: null,
		
		/**
		 * Returns link node
		 * 
		 * @param {Boolean} create Create link node if it doesn't exist
		 * @returns {Object} Google fonts link node
		 */
		getLinkNode: function (create) {
			if (this._node) return this._node;
			
			var doc = doc || this.get('doc'),
				head = null,
				link = null;
			
			if (!doc) return null;
			
			head = Y.Node(doc).one('head');
			if (!head) return null;
			
			link = head.one('link[href^="' + GOOGLE_FONT_API_URI + '"]');
			
			if (link) {
				return this._node = link;
			} else if (!!create) {
				// Create link node
				link = this._node = Y.Node.create('<link rel="stylesheet" type="text/css" />');
				head.append(link);
				return link;
			}
			
			return null;
		},
		
		/**
		 * Returns existing data extracted from link
		 * Extracts fonts and subset from link
		 * 
		 * @returns {Object} Parsed link node
		 */
		getExistingLinkData: function () {
			var node = this.getLinkNode(),
				uri  = '',
				subset = '',
				fonts = [];
			
			if (!node) {
				return {'node': null, 'fonts': fonts, 'subset': subset};
			}
			
			uri = node.getAttribute('href');
			if (!uri) {
				return {'node': node, 'fonts': fonts, 'subset': subset};
			}
			
			// Find subset
			uri = uri.replace(GOOGLE_FONT_API_URI, '');
			uri = uri.replace(/&subset=([^&]+)/, function (all, str) {
				subset = str;
				return '';
			});
			
			// Fonts
			fonts = uri.split('|');
			
			return {'node': node, 'fonts': fonts, 'subset': subset};
		},
		
		
		/*
		 * ---------------------------------- ATTRIBUTES ---------------------------------
		 */
		
		
		/**
		 * Document object setter
		 * 
		 * @param {Object} doc Document object
		 * @returns {Object} New attribute value
		 * @private
		 */
		_setDoc: function (doc) {
			this._node = null;
			
			if (doc) {
				this.addFonts(this.get('fonts'));
			}
			
			return doc;
		},
		
		
		/**
		 * Load fonts from Google Fonts
		 * 
		 * @param {Array} fonts List of fonts
		 * @returns {Array} New attribute value
		 * @private
		 */
		_setFonts: function (fonts) {
			var fonts = (this.get('fonts') || []).concat(fonts),
				i = 0,
				ii = fonts.length,
				unique_arr = [],
				unique_hash = {},
				id = null;
			
			// Find unique
			for (; i<ii; i++) {
				id = fonts[i].apis || fonts[i].family;
				if (!(id in unique_hash)) {
					unique_hash[id] = true;
					unique_arr.push(fonts[i]);
				}
			}
			
			fonts = unique_arr;
			
			// Set
			this.addFonts(fonts);
			
			return fonts;
		}
	});
	
	Supra.GoogleFonts = GoogleFonts;
	
	
	/**
	 * Returns URI with all fonts
	 * 
	 * @return URI for <link /> element which will load all fonts
	 * @private
	 */
	GoogleFonts.getURI = function (fonts, subset) {
		var fonts = Y.Lang.isArray(fonts) ? fonts : [],
			i = 0, ii = fonts.length,
			
			//Get all safe fonts in lowercase
			safe  = Y.Array(SAFE_FONTS).map(LOWERCASE_MAP),
			apis  = [],
			
			parts = [], k = 0, kk = 0,
			
			load  = [],
			temp  = '',
			uri   = GOOGLE_FONT_API_URI,
			
			index = 0,
			subsets = subset ? subset.split(',') : [];
		
		//Find which ones are not in the safe font list
		for (; i<ii; i++) {
			// API name instead of object?
			if (typeof fonts[i] === 'string') {
				load.push(fonts[i]);
				continue;
			}
			
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
						temp = fonts[i].apis;
					} else {
						temp = (fonts[i].family || fonts[i].title || '').replace(/\s*,\s*/g, ',').replace(/["']/, '').replace(/\s+/g, '+').replace(/,/g, '|');
					}
					
					if (temp) {
						index = temp.indexOf('&subset=');
						if (index !== -1) {
							subsets = subsets.concat(temp.substr(index + 8).split(','));
							temp = temp.substr(0, index);
						}
						
						load.push(temp);
					}
					
					break;
				}
			}
		}
		
		// Unique subsets
		if (subsets.length) {
			subsets = Y.Array.unique(subsets);
			subsets = '&subset=' + subsets.join(',');
		} else {
			subsets = '';
		}
		
		// Font list
		load = Y.Array.unique(load).sort();
		
		return (load.length ? uri + load.join('|') : '') + subsets;
	}
	
	GoogleFonts.addFontsToHTML = function (html, fonts) {
		var uri = GoogleFonts.getURI(fonts);
			replaced = false,
			regex = new RegExp('(<link[^>]+href=)["\'][^"\']*?' + Y.Escape.regex(GOOGLE_FONT_API_URI) + '[^"\']*?["\']', 'i'),
			html = html.replace(regex, function (all, pre) {
				replaced = true;
				return pre + '"' + uri + '"';
			});
		
		if (!replaced) {
			//Insert
			html = html.replace(/<\/\s*head/i, '<link rel="stylesheet" href="' + uri + '" /></head');
		}
		
		return html;
	};
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['base']});