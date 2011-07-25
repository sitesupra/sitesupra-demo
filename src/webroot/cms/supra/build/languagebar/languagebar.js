YUI.add("supra.languagebar", function (Y) {
	
	/*
	 * Regular expression to match language and context in locale string
	 */
	var REGEX_MATCH_LOCALE = /^([a-z]+)_([a-z]+)$/i;
	
	/*
	 * Templates
	 */
	var TEMPLATE_CONTENT = '<div class="yui3-languagebar-content">\
			  					<span class="label"></span> <a><span class="title"></span><img src="/cms/supra/img/flags/16x11/blank.png" alt="" /></a>\
			  				</div>';
	
	var TEMPLATE_LIST = '<ul class="contexts">{contexts}</ul>';
	
	var TEMPLATE_CONTEXT = '<li>\
								<div class="context-title">{title}</div>\
								<ul class="langs">{languages}</ul>\
							</li>';
	
	var TEMPLATE_LANGUAGE = '<li>\
								<a data-locale="{language}_{context}">\
									<img src="/cms/supra/img/flags/16x11/{language}.png" alt="" />\
									<span>{title}</span>\
								</a>\
							 </li>';
	
	/**
	 * Class for handling content languages
	 * 
	 */
	function LanguageBar (config) {
		LanguageBar.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	LanguageBar.NAME = 'languagebar';
	
	LanguageBar.ATTRS = {
		/**
		 * Context/language list
		 * @type {Array}
		 */
		'contexts': {
			value: null
		},
		/**
		 * Current locale, format: LANGUAGE_CONTEXT
		 * @type {String}
		 */
		'locale': {
			value: '',
			setter: '_setLocale'
		},
		
		/**
		 * Language bar label text
		 * @type {String}
		 */
		'localeLabel': {
			value: '',
			setter: '_setLocaleLabel'
		}
	};
	
	LanguageBar.HTML_PARSER = {
	};
	
	Y.extend(LanguageBar, Y.Widget, {
		/**
		 * Content template
		 * @type {String}
		 * @private
		 */
		CONTENT_TEMPLATE: TEMPLATE_CONTENT,
		
		/**
		 * Panel
		 * @private
		 */
		panel: null,
		
		
		/**
		 * Show language dropdown
		 * 
		 * @private
		 */
		showDropdown: function () {
			//Create panel
			if (!this.panel) {
				this.panel = new Supra.Tooltip({
					'alignTarget': this.get('srcNode').one('a'),
					'alignPosition': 'T',
					'zIndex': 1
				});
				this.panel.render(this.get('contentBox'));
				
				//Create items
				var content = this.panel.get('contentBox');
				content.all('ul').remove();
				content.append(this.renderUIList());
				
				//On body click hide panel
				var body = new Y.Node(document.body);
				body.on('click', this.panel.hide, this.panel);
			}
			
			this.panel.show();
		},
		
		/**
		 * Render context/language list
		 * 
		 * @private
		 */
		renderUIList: function () {
			var contexts = this.get('contexts'),
				langs = null,
				html_contexts = [],
				html_langs = [],
				html = null;
			
			for(var i=0,ii=contexts.length; i<ii; i++) {
				langs = contexts[i].languages;
				html_langs = [];
				
				//Create language items
				for(var k=0,kk=langs.length; k<kk; k++) {
					html_langs.push(Y.substitute(TEMPLATE_LANGUAGE, {
						'title': langs[k].title,
						'language': langs[k].id,
						'context': contexts[i].id
					}));
				}
				
				html_contexts.push(Y.substitute(TEMPLATE_CONTEXT, {
					'title': contexts[i].title,
					'languages': html_langs.join('')
				}));
			}
			
			html = Y.substitute(TEMPLATE_LIST, {
				'contexts': html_contexts.join('')
			});
			
			return Y.Node.create(html);
		},
		
		/**
		 * Render UI
		 * 
		 * @private
		 */
		renderUI: function() {
			LanguageBar.superclass.renderUI.apply(this, arguments);
			
			//Set label, title, image
			this._setLocaleLabel(this.get('localeLabel'));
			this._setLocale(this.get('locale'));
		},
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			LanguageBar.superclass.bindUI.apply(this, arguments);
			
			var link = this.get('srcNode').one('a');
			if (link) link.on('click', this.showDropdown, this)
			
			this.get('contentBox').on('click', function (evt) {
				evt.stopPropagation();
			});
			
			this.get('contentBox').delegate('click', function (evt) {
				var target = evt.target.closest('a');
				this.set('locale', target.getAttribute('data-locale'));
				this.panel.hide();
			}, 'li a', this);
		},
		
		/**
		 * Split locale into context and language
		 * 
		 * @param {String} locale Locale id
		 * @return Array with context and locale
		 * @type {Array}
		 * @private
		 */
		splitLocale: function (locale) {
			if (Y.Lang.isString(locale)) {
				var m = locale.match(REGEX_MATCH_LOCALE);
				if (m) {
					return [m[2], m[1]];
				}
			}
			return null;
		},
		
		/**
		 * Returns language by locale
		 * @param {Object} locale
		 */
		getContextByLocale: function (locale) {
			var item = this.splitLocale(locale),
				contexts = this.get('contexts');
			return (item ? this._find(contexts, item[0]) : null);
		},
		
		/**
		 * Returns language by locale
		 * @param {Object} locale
		 */
		getLanguageByLocale: function (locale) {
			var item = this.splitLocale(locale);
			if (item) {
				var langs = this.getContextByLocale(locale);
				return langs ? this._find(langs.languages, item[1]) : null;
			}
			return null;
		},
		
		/**
		 * Setter for locale attribute.
		 * Validate locale and update UI
		 * 
		 * @param {String} locale
		 * @return New locale value
		 * @type {String}
		 * @private
		 */
		_setLocale: function (locale) {
			var oldVal = this.get('locale'),
				lang = this.getLanguageByLocale(locale),
				node = null;
			
			if (lang) {
				node = this.get('srcNode').one('.title');
				if (node) node.set('text', lang.title);
				
				node = this.get('srcNode').one('img');
				if (node) node.set('src', '/cms/supra/img/flags/16x11/' + lang.id + '.png');
				
				return locale;
			}
			
			return oldVal;
		},
		
		/**
		 * Setter for localeLabel attribute
		 * 
		 * @param {String} label
		 */
		_setLocaleLabel: function (label) {
			if (!Y.Lang.isString(label)) label = '';
			var node = this.get('srcNode').one('.label');
			if (node) {
				if (label) {
					node.removeClass('hidden');
					node.set('text', label);
				} else {
					node.addClass('hidden');
				}
			}
			return label;
		},
		
		/**
		 * Find array item by ID
		 * 
		 * @param {Array} arr Array to search
		 * @param {String} id Item ID
		 * @return Found item or null
		 * @type {Object}
		 * @private
		 */
		_find: function (arr, id) {
			if (arr) {
				for(var i=0,ii=arr.length; i<ii; i++) {
					if (arr[i].id == id) return arr[i];
				}
			}
			return null;
		},
	});
	
	Supra.LanguageBar = LanguageBar;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, { requires:['supra.tooltip'] });