//Invoke strict mode
"use strict";

YUI.add("iconsidebar.data", function (Y) {
	
	var TITLE_TRANSFORMATIONS = [
		['Foldr ', 'Folder '],
		['Cld ', 'Cloud '], ['Cldownload', 'Cloud download'], ['Clupload', 'Cloud upload'],
		['Cir ', 'Circle '],
		['Cl ', 'Circle '],
		[' arw ', ' arrow '], [' arrw ', ' arrow '], [' cir ', ' circle '], [/\sver$/, ' vertical'], [/\shr$/, ' horizontal'], [' arwmask ', ' arrow mask '],
		['downleft', 'down left'], ['downright', 'down right'], ['upleft', 'up left'], ['upright', 'up right'],
		['Brw ', 'Browser '],
		['Bbq ', 'Barbecue '], ['Washingm ', 'Washing machine '],
		[' dia ', ' diagram '], [' cirframe', ' circle frame'], [' halfcir', 'half circle'],
		[' brac ', ' bracket '], [/\sarrow([a-z])/, ' arrow $1'], [/\sf\s([0-9])/, ' f$1'], [' prnts ', ' parentheses '],
		['Loadingalt ', 'Loading alternative '], ['Loadingcr ', 'Loading circle '],
		['Con ', 'Controller '], ['kybrd', 'keyboard'], ['Com ', 'Computer '], [' api', ' API'], [' hdd', ' HDD'], [' ssd', ' SSD'], [' hdplus', ' HDPluss'], [' sdk', ' SDK'], [/\shd$/, ' HD'], [/\ssd$/, ' SD'], [' xbox ', ' XBox '], [' ps 2', ' PS2'], [' ps 3', ' PS3'], [' psbuttons', ' PS buttons'], ['Wifi', 'WiFi '],
		['Adb ', 'Adobe '], ['x 33 dsmax', '3D Studio Max'],
		['Db ', 'Database '], ['Dbs ', 'Database system '],
		['Tl ', 'Tool '], ['Tlheal', 'Tool heal patch'], ['bgeraser', 'background eraser'], ['patclone', 'pattern clone'], ['magiceraser', 'magic eraser'], ['addanchor', 'add anchor'], ['delanchor', 'delete anchor'], ['customshape', 'custom shape'],
		[' bl ', ' dark '], [/\salt$/, ' alternative'], ['key alternative', 'key alt'], ['Lm ', 'Locus monumenti '],
		['Mailb ', 'Mail dark '],
		['Mapmarker', 'Map marker'], [/\sfav$/, ' favourite'],
		['Mpl ', 'Media player '], [' vol ', ' volume '], [/\svol$/, ' volume'], [' stback', ' stop back'], [' stfor', ' stop forward'],
		['Med ', 'Medicine '], ['Medi ', 'Medieval '], ['Mil ', 'Military '], ['Wtr ', 'Weather '], ['Rlg ', 'Religion '], ['Rlgd', 'Religion dark '],
		['Cur ', 'Currency '], ['Mobshop', 'Mobile shoping'], ['Hng ', 'Hang '],
		[/^Xmas/, 'Xmas '], ['Os ', 'OS '], ['Lin ', 'Linux '],
		['Vc ', 'Version control '],
		['Kub ', ''], ['Oxp ', ''],
		[' bcloud', ' dark cloud'], [' bsun', ' dark sun'], [' bthunder', ' dark thunder']
		
	];
	
	"....".replace(/^.*\/([^\/]+)$/, '$1');
	
	
	/**
	 * Icon data handler
	 */
	function Data (config) {
		Data.superclass.constructor.apply(this, arguments);
		
		this.icons = [];
		this.iconsHash = {};
		this.categories = [];
		this.categoriesHash = {};
		this.init.apply(this, arguments);
	}
	
	Data.NAME = 'google-fonts';
	
	Data.ATTRS = {
		// Url where icon files can be found
		'iconBaseUrl': {
			'value': ''
		},
		
		// Icon data
		'data': {
			'value': {},
			'setter': '_setData'
		}
	};
	
	Y.extend(Data, Y.Base, {
		
		/**
		 * Icon list
		 * @type {Array}
		 * @private
		 */
		icons: null,
		
		/**
		 * Icons hashed by IDs
		 * @type {Object}
		 * @private
		 */
		iconsHash: null,
		
		/**
		 * List of categories
		 * @type {Array}
		 * @private
		 */
		categories: null,
		
		/**
		 * Categories hashed by IDs
		 * @type {Object}
		 * @private
		 */
		categoriesHash: null,
		
		
		/* ------------------------------ Filter ------------------------------ */
		
		
		/**
		 * Returns all icons matching keyword and category
		 * 
		 * @param {String} keyword Keyword
		 * @param {String} category Category id, optional
		 * @returns {Array} List of icons matching keyword and category
		 */
		getFilteredByKeyword: function (keyword, category) {
			var categories = this.categoriesHash,
				cat_title  = '',
				
				keywords = keyword.toLowerCase().replace(/[^a-zA-Z0-9]+/, ' ').replace(/[ ]{2,}/, ' ').split(' '),
				k        = 0,
				kk       = keywords.length,
				
				icons    = this.icons,
				i        = 0,
				ii       = icons.length,
				
				index    = 0,
				score    = 0,
				matches  = [],
				scores   = {},
				
				criteria = false;
			
			for (; k<kk; k++) {
				keyword = keywords[k];
				if (keyword.length < 2) continue;
				criteria = true;
				
				for (i=0; i<ii; i++) {
					if (category && Y.Array.indexOf(icons[i].category, category) == -1) continue;
					
					index = icons[i].keywords.indexOf(keyword);
					if (index !== -1) {
						if (index == 0 || icons[i].keywords[index-1] == ' ' || icons[i].keywords[index-1] == '(') {
							// Word starts with this keyword
							score = 4;
						} else {
							// Is part of word
							score = 3;
						}
						
						if (icons[i].id in scores) {
							scores[icons[i].id] += score;
						} else {
							scores[icons[i].id] = score;
							matches.push(icons[i]); 
						}
					}
					
					cat_title = icons[i].category_titles.toLowerCase();
					index = cat_title.indexOf(keyword);
					if (index !== -1) {
						if (index == 0 || cat_title[index-1] == ' ') {
							// Word starts with this keyword
							score = 2;
						} else {
							// Is part of word
							score = 1;
						}
						
						if (icons[i].id in scores) {
							scores[icons[i].id] += score;
						} else {
							scores[icons[i].id] = score;
							matches.push(icons[i]); 
						}
					}
					
				}
			}
			
			if (!criteria && category) {
				// All keywords were too short to do search
				return this.getFilteredByCategory(category);
			} else {
				// Order matches by score
				matches.sort(function (a, b) {
					var a_score = scores[a.id],
						b_score = scores[b.id];
					
					return (a_score > b_score ? -1 : (a_score < b_score ? 1 : 0));
				});
				
				return matches;
			}
		},
		
		/**
		 * Returns all icons from category
		 * 
		 * @param {String} category Category ID
		 * @returns {Array} List of icons in category
		 */
		getFilteredByCategory: function (category) {
			var categories = this.categories,
				i = 0,
				ii = categories.length;
			
			for (; i<ii; i++) {
				if (categories[i].id === category) {
					return categories[i].icons;
				}
			}
			
			return [];
		},
		
		
		/* ------------------------------ Data ------------------------------ */
		
		
		/**
		 * Returns icon data by ID
		 * 
		 * @param {String} id Icon ID
		 * @returns {Object} Icon data
		 */
		getIcon: function (id) {
			return this.iconsHash[id] || null;
		},
		
		/**
		 * Returns category by ID
		 * 
		 * @param {String} id Category ID
		 * @returns {Object} Category data
		 */
		getCategory: function (id) {
			return this.categoriesHash[id] || null;
		},
		
		/**
		 * Returns all categories
		 * 
		 * @returns {Array} Category list
		 */
		getCategories: function () {
			return this.categories;
		},
		
		/**
		 * Parse data and extract categories and icons
		 * 
		 * @param {Object} data Data
		 * @private
		 */
		_parseData: function (data) {
			var icons      = [],
				hashes     = {},
				categories = [],
				cat_hashes = {},
				
				cat_id     = null,
				category   = null,
				title      = null,
				
				items      = null,
				id         = null,
				item       = null,
				i          = 0,
				ii         = 0,
				
				base_url   = this.get('iconBaseUrl'),
				
				reg_title  = /[^a-zA-Z0-9]+/g,
				reg_num    = /([a-zA-Z])([0-9])/g,
				reg_braces = /([0-9]\s)([0-9]+)/,
				
				transform = TITLE_TRANSFORMATIONS,
				t         = 0,
				tt        = transform.length;
			
			for (cat_id in data) {
				title = cat_id.replace(reg_title, ' ');
				title = title.substr(0, 1).toUpperCase() + title.substr(1);
				
				category = {
					'id': cat_id,
					'title': title,
					'icons': []
				};
				cat_hashes[cat_id] = category;
				categories.push(category);
				
				items = data[cat_id];
				
				for (i=0, ii=items.length; i<ii; i++) {
					id = item = items[i];
					
					if (hashes[id]) {
						// Already exists (was in another category)
						hashes[id].category.push(category.id);
						hashes[id].category_titles += ' ' + category.title;
					} else {
						title = item.replace(reg_title, ' ');        // pie2-1 -> pie2 1
						title = title.replace(reg_num, '$1 $2');      // pie2 1 -> pie 2 1
						title = title.replace(reg_braces, '$1 ($2)'); // pie 2 1 -> pie 2 (1)
						title = title.substr(0, 1).toUpperCase() + title.substr(1);
						
						// Transform title replacing gibberish
						for (t=0; t<tt; t++) {
							title = title.replace(transform[t][0], transform[t][1]);
						}
						
						hashes[id] = {
							'id': id,
							'title': title,
							'category': [category.id],
							'category_titles': category.title,
							'keywords': title.toLowerCase(),
							'svg_path': base_url + '/svg/' + id + '.svg',
							'icon_path': base_url + '/png/' + id + '.png'
						};
						
						icons.push(hashes[id]);
					}
					
					category.icons.push(hashes[id]);
				}
			}
			
			this.icons = icons;
			this.iconsHash = hashes;
			this.categories = categories;
			this.categoriesHash = cat_hashes;
		},
		
		
		/* ------------------------------ Attributes ------------------------------ */
		
		
		/**
		 * Data attribute setter
		 * 
		 * @param {Object} data 
		 * @returns {Array}
		 * @private
		 */
		_setData: function (data) {
			this._parseData(data);
			return data;
		}
		
	});
	
	Supra.IconSidebarData = Data;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['base', 'supra.datatype-icon']});