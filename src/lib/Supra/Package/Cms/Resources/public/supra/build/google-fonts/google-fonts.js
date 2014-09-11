YUI().add('supra.google-fonts', function (Y) {
	//Invoke strict mode
	'use strict';
	
	//Map function to lowercase all array items
	var LOWERCASE_MAP = function (str) {
		return String(str || '').toLowerCase();
	};
	
	
	/**
	 * Iframe content widget
	 */
	function GoogleFonts (config) {
		GoogleFonts.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	GoogleFonts.NAME = 'google-fonts';
	
	// Google font API uri
	GoogleFonts.API_URI = document.location.protocol + '//fonts.googleapis.com/css?family=';
	
	// URI to load list of google fonts
	GoogleFonts.SUPRA_FONT_URI = /* dynamic url + */ '/content-manager/fonts/list.json';
	
	//List of fonts, which doesn't need to be loaded from Google Web Fonts
	GoogleFonts.SAFE_FONTS = [
		'Arial', 'Tahoma', 'Helvetica', 'sans-serif', 'Arial Black', 'Impact',
		'Trebuchet MS', 'MS Sans Serif', 'MS Serif', 'Geneva', 'Comic Sans MS' /* trololol.... */,
		'Palatino Linotype', 'Book Antiqua', 'Palatino', 'Monaco', 'Charcoal',
		'Courier New', 'Georgia', 'Times New Roman', 'Times',
		'Lucida Console', 'Lucida Sans Unicode', 'Lucida Grande', 'Gadget',
		'monospace'
	];
	
	// List of non-google fonts for fonts list
	GoogleFonts.STANDARD_FONTS = [
		{'title': 'Arial, Helvetica',							'family': 'Arial, Helvetica, sans-serif'},
		{'title': 'Times New Roman, Times, serif',				'family': '"Times New Roman", Times, serif'},
		{'title': 'Georgia', 									'family': 'Georgia, serif'},
		{'title': 'Palatino Linotype, Book Antiqua, Palatino',	'family': '"Palatino Linotype", "Book Antiqua", Palatino, serif'},
		{'title': 'Impact, Charcoal', 							'family': 'Impact, Charcoal, sans-serif'},
		{'title': 'Lucida Sans Unicode, Lucida Grande',			'family': '"Lucida Sans Unicode", "Lucida Grande", sans-serif'},
		{'title': 'Tahoma, Geneva',								'family': 'Tahoma, Geneva, sans-serif'},
		{'title': 'Trebuchet MS, Helvetica',					'family': '"Trebuchet MS", Helvetica, sans-serif'},
		{'title': 'Verdana, Geneva',							'family': 'Verdana, Geneva, sans-serif'}
	];
	
	// List of google font subsets
	GoogleFonts.SUBSETS = [
		'latin', 'cyrillic-ext', 'latin-ext', 'cyrillic'
	];
	
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
			
			var new_uris = [],
				data = null,
				i = 0, ii = 0;
			
			data = this.getExistingLinkData();
			new_uris = GoogleFonts.getURI(fonts, {'exclude': data.fonts, 'split': true});
			
			if (new_uris.length) {
				for (i=0,ii=new_uris.length; i<ii; i++) {
					this.createLinkNode(new_uris[i]);
				}
			}
		},
		
		
		/*
		 * ---------------------------------- DOM ---------------------------------
		 */
		
		
		/**
		 * Returns link node
		 * 
		 * @param {Boolean} create Create link node if it doesn't exist
		 * @returns {Object} Google fonts link node
		 */
		getLinkNodes: function () {
			var doc = doc || this.get('doc'),
				head = null,
				links = null;
			
			if (!doc) return null;
			
			head = Y.Node(doc).one('head');
			if (!head) return null;
			
			links = head.all('link[href^="' + GoogleFonts.API_URI + '"]');
			return links ? links.getDOMNodes() : [];
		},
		
		/**
		 * Creates link node
		 */
		createLinkNode: function (uri) {
			var doc = doc || this.get('doc'),
				head = null,
				link = null;
			
			if (!doc) return null;
			
			head = Y.Node(doc).one('head');
			if (!head) return null;
			
			link = Y.Node.create('<link rel="stylesheet" type="text/css" href="' + (uri || '') + '" />');
			head.append(link);
			
			return link;
		},
		
		/**
		 * Returns existing data extracted from link
		 * Extracts fonts and subset from link
		 * 
		 * @returns {Object} Parsed link node
		 */
		getExistingLinkData: function () {
			var nodes = this.getLinkNodes(),
				uri  = '',
				subset = GoogleFonts.SUBSETS.join(','),
				fonts = [],
				i = 0,
				ii = nodes.length;
			
			for (; i<ii; i++) {
				uri = nodes[i].getAttribute('href');
				if (uri) {
					// Remove url, subset and styles
					uri = uri.replace(GoogleFonts.API_URI, '');
					uri = uri.replace(/&subset=([^&]+)/, '');
					//uri = uri.replace(/:[^|&?]*/g, '');
					
					// Fonts
					fonts = fonts.concat(uri.split('|'));
				}
			}
			
			return {'fonts': fonts, 'subset': subset};
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
	 * Load list of all fonts
	 * 
	 * @returns {Object} Supra.Deferred object
	 * @private
	 */
	GoogleFonts.loadFonts = function () {
		if (GoogleFonts.loadFonts._deferred) {
			return GoogleFonts.loadFonts._promise;
		}
		
		var deferred = new Supra.Deferred(),
			promise  = deferred.promise(),
			uri      = Supra.Manager.Loader.getDynamicPath() + GoogleFonts.SUPRA_FONT_URI;
		
		Supra.io(uri).then(
			function (fonts) {
				// Success, return standard + google fonts
				var formatted = [],
					i = 0,
					ii = fonts.length,
					family = '';
				
				for (; i<ii; i++) {
					family = fonts[i];
					formatted.push({
						'title': family,
						'family': family, //amily.indexOf(' ') != -1 ? '"' + family + '"' : family,
						'apis': family.replace(/ /g, '+') + ':300,300italic,regular,italic,700,700italic'
					});
				}
				
				deferred.resolve([[
					{
						'title': 'Standard fonts',
						'fonts': GoogleFonts.STANDARD_FONTS,
					},
					{
						'title': 'Google fonts',
						'fonts': formatted
					}
				]]);
			}, function () {
				// Failure, return only standard fonts
				deferred.resolve([[
					{
						'title': 'Standard fonts',
						'fonts': GoogleFonts.STANDARD_FONTS,
					}
				]]);
			});
		
		GoogleFonts.loadFonts._promise = promise;
		return promise;
	};
	
	/**
	 * Returns URI with all fonts
	 * 
	 * @return URI for <link /> element which will load all fonts
	 * @private
	 */
	GoogleFonts.getURI = function (fonts, options) {
		
		var fonts = Y.Lang.isArray(fonts) ? fonts : [],
			i = 0,
			ii = fonts.length,
			
			exclude = options && options.exclude ? options.exclude : [],
			e = 0,
			ee = exclude.length,
			
			//Get all safe fonts in lowercase
			safe  = Y.Array(GoogleFonts.SAFE_FONTS).map(LOWERCASE_MAP),
			apis  = [],
			
			parts = [], k = 0, kk = 0,
			
			load  = [],
			temp  = '',
			uri   = GoogleFonts.API_URI,
			subset = '&subset=' + GoogleFonts.SUBSETS.join(','),
			
			index = 0,
			
			include = false;
		
		//Find which ones are not in the safe font list
		for (; i<ii; i++) {
			include = true;
			
			// API name instead of object?
			if (typeof fonts[i] === 'string') {
				for (e=0; e<ee; e++) {
					if (exclude[e] == fonts[i]) {
						include = false;
						break;
					}
				}
				if (include) {
					load.push(fonts[i]);
				}
				
				continue;
			}
			
			for (e=0; e<ee; e++) {
				if (exclude[e] == fonts[i].apis || exclude[e] == (fonts[i].family || fonts[i].title).replace(/["']/, '').replace(/\s+/g, '+')) {
					include = false;
					break;
				}
			}
			
			if (!include) {
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
							temp = temp.substr(0, index);
						}
						
						load.push(temp);
					}
					
					break;
				}
			}
		}
		
		// Font list
		load = Y.Array.unique(load).sort();
		
		if (options && options.split) {
			var split = [];
			
			for (i=0, ii=Math.ceil(load.length/10); i<ii; i++) {
				split.push(uri + load.slice(i*10, i*10+10).join('|') + subset);
			}
			
			return split;
		} else {
			return (load.length ? uri + load.join('|') + subset : '');
		}
	}
	
	GoogleFonts.addFontsToHTML = function (html, fonts) {
		var uri = GoogleFonts.getURI(fonts),
			replaced = false,
			regex = new RegExp('(<link[^>]+href=)["\'][^"\']*?' + Y.Escape.regex(GoogleFonts.API_URI) + '[^"\']*?["\']', 'i'),
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