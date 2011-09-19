YUI.add("supra.languagebar", function (Y) {
	
	/*
	 * Shortcuts
	 */
	var Template = Supra.Template;
	
	/*
	 * Regular expression to match language and context in locale string
	 */
	var REGEX_MATCH_LOCALE = /^([a-z]+)_([a-z]+)$/i;
	
	/*
	 * Templates
	 */
	var TEMPLATE_CONTENT = '<div class="yui3-languagebar-content">\
								<span class="label"></span> <a class="selected"><span class="title"></span><img src="/cms/lib/supra/img/flags/16x11/blank.png" alt="" /></a>\
							</div>';
	
	/* Language list template */
	var TEMPLATE = Template.compile(
			'<ul class="contexts">' +
			'	{% for context in contexts %}' +
			'	<li>' +
			'		<div class="context-title">{{ context.title|escape }}</div>' +
			'		<ul class="langs">' +
			'			{% for lang in context.languages %}' +
			'				<li>' +
			'					<a data-locale="{{ lang.id|escape }}">' +
			'						<img src="/cms/lib/supra/img/flags/16x11/{{ lang.flag }}.png" alt="" />' +
			'						<span>{{ lang.title|escape }}</span>' +
			'					</a>' +
			'				</li>' +
			'			{% endfor %}' +
			'		</ul>' +
			'	</li>' +
			'	{% endfor %}' +
			'</ul>'
		);
	
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
			return Y.Node.create(TEMPLATE({
				'contexts': this.get('contexts')
			}));
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
		getLanguageByLocale: function (locale) {
			var contexts = this.get('contexts'),
			lang = null;
			
			for(var i=0,ii=contexts.length; i<ii; i++) {
				lang = this._find(contexts[i].languages, locale);
				if (lang) return lang;
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
				icon = this.splitLocale(locale)[1],
				node = null;
			
			if (lang) {
				node = this.get('srcNode').one('.title');
				if (node) node.set('text', lang.title);
				
				node = this.get('srcNode').one('img');
				if (node) node.set('src', '/cms/lib/supra/img/flags/16x11/' + icon + '.png');
				
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
		}
	});
	
	Supra.LanguageBar = LanguageBar;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, { requires:['supra.tooltip'] });