// Modules
Supra.setModuleGroupPath('iconsidebar', Supra.Manager.Loader.getActionFolder('IconSidebar') + '/modules');

Supra.addModule('iconsidebar.data', {
	path: 'data.js',
	requires: ['base', 'supra.datatype-icon']
});
Supra.addModule('iconsidebar.iconlist', {
	path: 'iconlist.js',
	requires: ['widget', 'supra.scrollable']
});

Supra('anim', 'dd-drag', 'iconsidebar.data', 'iconsidebar.iconlist', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager,
		Action = Manager.Action,
		Loader = Manager.Loader;
	
	//Create Action class
	new Action(Action.PluginLayoutSidebar, {
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'IconSidebar',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		/**
		 * Layout container action NAME
		 * @type {String}
		 * @private
		 */
		LAYOUT_CONTAINER: 'LayoutLeftContainer',
		
		
		/**
		 * Widget list
		 * @type {Object}
		 * @private
		 */
		widgets: {
			// Slideshow
			'slideshow': null,
			
			// Search form
			'search': null,
			
			// Icon list data object
			'data': null,
			
			// Icon list widget
			'list': null
		},
		
		/**
		 * Sidebar icon list data object
		 * @type {Object}
		 * @private
		 */
		data: null,
		
		/**
		 * Data has been loaded
		 * @type {Boolean}
		 * @private
		 */
		loaded: false,
		
		/**
		 * Icon select options
		 * @type {Object}
		 */
		options: {},
		
		
		
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 * 
		 * @private
		 */
		initialize: function () {},
		
		/**
		 * Render widgets
		 * 
		 * @private
		 */
		render: function () {
			//Toolbar buttons
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			//Create search box
			this.renderSearch();
			
			//Slideshow
			this.renderSlideshow();
			
			//Create icon list
			this.renderIconList();
			this.loadIconList();
			
			//Back, Close and App buttons
			this.renderHeader();
		},
		
		
		/* -------------------- Search -------------------- */
		
		
		/**
		 * Render search box
		 * 
		 * @private
		 */
		renderSearch: function () {
			var input  = null,
				search = this.widgets.search = new Supra.Form({
					'inputs': [
						{'type': 'String', 'id': 'searchQuery', 'name': 'query', 'label': '', 'blurOnReturn': true}
					]
				});
			
			search.render(this.one('.sidebar-content'));
			
			input = search.getInput('query');
			input.addClass('search');
			input.plug(Supra.Input.String.Clear);
			
			search.on('submit', this.searchIcons, this);
			input.on('input', this.onSearchInputEvent, this);
		},
		
		/**
		 * Handle search input
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		onSearchInputEvent: function (e) {
			if (this.searchTimer) {
				this.searchTimer.cancel();
			}
			
			this.searchTimer = Y.later(250, this, this.searchIcons);
		},
		
		/**
		 * Search icons
		 */
		searchIcons: function () {
			var search = this.widgets.search,
				query  = search.getInput('query').get('value'),
				list   = this.widgets.list;
			
			//Search
			if (!list.get('categoryFilter') && list.get('keywordFilter') && !query) {
				this.openMain();
			} else if (query && !list.get('keywordFilter') && !list.get('categoryFilter')) {
				this.openSearch(query);
			} else {
				list.set('keywordFilter', query);
			}
			
			//Cancel timer
			if (this.searchTimer) {
				this.searchTimer.cancel();
				this.searchTimer = null;
			}
		},
		
		
		/* -------------------- Slideshow -------------------- */
		
		
		/**
		 * Render slideshow
		 * 
		 * @private
		 */
		renderSlideshow: function () {
			var slideshow = this.widgets.slideshow = new Supra.Slideshow();
			slideshow.render(this.one('.sidebar-content'));
			
			slideshow.addSlide({
				'id': 'categories'
			})
			slideshow.addSlide({
				'id': 'icons',
				'scrollable': false // Custom scrolling
			})
		},
		
		/**
		 * Render categories
		 */
		renderCategoryList: function () {
			var categories = this.widgets.data.getCategories(),
				i          = 0,
				ii         = categories.length,
				button     = null,
				node       = Y.Node.create('<div class="categories"></div>');
			
			this.widgets.slideshow.getSlide('categories').one('.su-slide-content').append(node);
			
			for (; i<ii; i++) {
				button = new Supra.Button({
					'label': categories[i].title
				});
				
				button.addClass('button-section');
				button.render(node);
				button.on('click', this.openCategory, this, categories[i].id);
			}
		},
		
		/**
		 * Open category
		 * 
		 * @param {Object} event Event facade object
		 * @param {String} category Category ID
		 * @private
		 */
		openCategory: function (event, category) {
			this.widgets.slideshow.set('slide', 'icons');
			
			this.widgets.list.set('visible', true);
			this.widgets.list.set('categoryFilter', category);
			this.widgets.list.reset();
			
			this.set('title', this.widgets.data.getCategory(category).title);
			this.get('backButton').show();
		},
		
		/**
		 * Open general search
		 * 
		 * @private
		 */
		openSearch: function (query) {
			var list = this.widgets.list;
			
			this.widgets.slideshow.set('slide', 'icons');
			
			list.set('visible', true);
			list.set('keywordFilter', query);
			list.reset();
			
			this.get('backButton').show();
		},
		
		/**
		 * Open main
		 * 
		 * @private
		 */
		openMain: function () {
			var list      = this.widgets.list,
				search    = this.widgets.search,
				slideshow = this.widgets.slideshow;
			
			slideshow.set('slide', 'categories');
			list.set('visible', false);
			list.set('categoryFilter', '');
			list.set('keywordFilter', '');
			search.getInput('query').set('value', '');
			
			this.set('title', Supra.Intl.get(['iconsidebar', 'insert_icon']));
			this.get('backButton').hide();
		},
		
		
		/* -------------------- Icon list -------------------- */
		
		
		/**
		 * Create icon library list
		 * 
		 * @private
		 */
		renderIconList: function () {
			var data      = null,
				list      = null,
				slideshow = this.widgets.slideshow;
			
			data = this.widgets.data = new Supra.IconSidebarData();
			
			list = this.widgets.list = new Supra.IconSidebarIconList({
				'data': data,
				'visible': false
			});
			
			list.render(slideshow.getSlide('icons').one('.su-slide-content'));
			list.on('select', this.insert, this);
		},
		
		/**
		 * Load icon list
		 * 
		 * @private
		 */
		loadIconList: function () {
			Supra.io(this.getDataPath('load')).done(this.loadIconListComplete, this);
		},
		
		/**
		 * Icon loading completed
		 * 
		 * @param {Object} data Icon data
		 * @private
		 */
		loadIconListComplete: function (data) {
			this.widgets.data.set('iconBaseUrl', data.path);
			this.widgets.data.set('data', data.icons);
			
			this.widgets.list.reset();
			
			this.renderCategoryList();
			
			this.loaded = true;
			this.one('.sidebar-content').removeClass('loading');
			
			//Open icon
			this.openIcon(this.options.item);
		},
		
		/**
		 * Create buttons
		 * 
		 * @private
		 */
		renderHeader: function () {
			this.get('controlButton').on('click', this.close, this);
			this.get('backButton').on('click', this.openMain, this);
		},
		
		/**
		 * Show single icon
		 * 
		 * @private
		 */
		openIcon: function (id) {
			if (!this.loaded || !id) return;
			var icon = this.widgets.data.getIcon(id && id.id || id);
			
			if (icon) {
				this.widgets.search.getInput('query').set('value', icon.title);
				this.widgets.list.set('active', icon.id);
			} else {
				this.widgets.list.set('active', null);
			}
		},
		
		
		/* -------------------- API -------------------- */
		
		
		/**
		 * Returns icon data object
		 * 
		 * @returns {Object} Data object
		 */
		dataObject: function () {
			return this.widgets.data;
		},
		
		
		/* -------------------- Manager -------------------- */
		
		/**
		 * Hide
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			
			this.openMain();
			
			//Show previous buttons
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			//Retore editor toolbar
			if (this.options.retoreEditorToolbar) {
				this.options.retoreEditorToolbar = false;
				Manager.getAction('EditorToolbar').execute();
			}
		},
		
		/**
		 * Hide media sidebar and call close callback
		 */
		close: function () {
			this.hide();
			
			if (Y.Lang.isFunction(this.options.onclose)) {
				this.options.onclose({
					'icon': null
				});
			}
		},
		
		/**
		 * Hide media sidebar and call select and close callbacks
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		insert: function (event) {
			var icon = new Y.DataType.Icon(event.icon);
			
			this.hide();
			
			if (Y.Lang.isFunction(this.options.onselect)) {
				this.options.onselect({
					'icon': icon
				});
			}
			if (Y.Lang.isFunction(this.options.onclose)) {
				this.options.onclose({
					'icon': icon
				});
			}
		},
		
		/**
		 * Execute action
		 */
		execute: function (options) {
			this.show();
			
			//Set options
			this.options = Supra.mix({
				'onselect': null,
				'onclose': null,
				'hideToolbar': false,
				'item': null
			}, options || {}, true);
			
			//Open icon
			this.openIcon(this.options.item);
			
			//Hide toolbar
			if (this.options.hideToolbar) {
				//Hide editor toolbar
				if (Manager.getAction('EditorToolbar').get('visible')) {
					this.options.retoreEditorToolbar = true;
					Manager.getAction('EditorToolbar').hide();
				}
				
				//Hide buttons
				Manager.getAction('PageToolbar').setActiveAction(this.NAME);
				Manager.getAction('PageButtons').setActiveAction(this.NAME);
			}
		}
	});
	
});