//Invoke strict mode
"use strict";

/**
 * Custom modules
 */
(function () {
	var STATIC_PATH = Supra.Manager.Loader.getStaticPath(),
		APP_PATH = Supra.Manager.Loader.getActionBasePath('Blog');
	
	Supra.setModuleGroupPath('blog', STATIC_PATH + APP_PATH + '/modules');
	
	// Input plugin to clear input value on icon click
	Supra.addModule('blog.input-string-clear', {
		path: 'input-string-clear.js',
		requires: [
			'supra.form', 'plugin'
		]
	});
	
	// Datagrid plugin to enable drag and drop post restore
	Supra.addModule('blog.datagrid-restore', {
		path: 'datagrid-plugin-restore.js',
		requires: [
			'plugin', 'dd-drop', 'supra.datagrid'
		]
	});
})();

/**
 * Main manager action, initiates all other actions
 */
Supra(
	
	'supra.button-group',
	'supra.slideshow',
	'supra.datagrid',
	'supra.datagrid-loader',
	'supra.datagrid-sortable',
	
	'blog.datagrid-restore',
	'blog.input-string-clear',
	
function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	//Toolbar buttons
	var TOOLBAR_BUTTONS = [
	    {
	        'id': 'blog_posts',
			'title': Supra.Intl.get(['blog', 'toolbar', 'posts']),
			'icon': '/cms/lib/supra/img/toolbar/icon-blog-posts.png',
			'type': 'tab'
	    },
		{
	        'id': 'blog_settings',
			'title': Supra.Intl.get(['blog', 'toolbar', 'settings']),
			'icon': '/cms/lib/supra/img/toolbar/icon-blog-settings.png',
			'type': 'tab'
	   },
	   {
	   		'id': 'blog_recycle_bin',
	   		'title': Supra.Intl.get(['blog', 'toolbar', 'recycle_bin']),
	   		'icon': '/cms/lib/supra/img/toolbar/icon-recycle.png',
	   		'type': 'button'
	   }
	];
	
	//Create Action class
	new Action(Manager.Action.PluginContainer, Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: "Blog",
		
		/**
		 * Action doesn't have stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Action doesn't have template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		/**
		 * Dependancies
		 * @type {Array}
		 */
		DEPENDANCIES: ["LayoutContainers"],
		
		/**
		 * Widgets list
		 * @type {Object}
		 * @private
		 */
		widgets: {
			'slideshow': null,
			
			// List
			'filter': null,
			'buttonNewPost': null,
			'datagrid': null,
			'languageSelector': null,
			
			// Settings
			'settingsTabs': null,
			'settingsSlideshow': null,
			
			'formAuthor': null,
			'footerAuthor': null,
			
			'formTags': null,
			'footerTags': null
		},
		
		/**
		 * Selected locale
		 * @type {String}
		 * @private
		 */
		locale: null,
		
		/**
		 * Standalone, not opened by another manager through ajax
		 * @type {Boolean}
		 * @private
		 */
		standalone: false,
		
		/**
		 * Timer used to reload datagrid automatically while user is typing
		 * @type {Object}
		 * @private
		 */
		filterTimer: null,
		
		
		/**
		 * @constructor
		 */
		initialize: function () {
			//Set locale
			this.locale = Supra.data.get('locale');
			
			this.widgets.slideshow = new Supra.Slideshow({
				'srcNode': this.one('.blog-slideshow')
			});
			
			this.widgets.filter = new Supra.Form({
				'style': 'horizontal',
				'inputs': [
					{'type': 'String', 'id': 'filterQuery', 'name': 'query', 'label': ''}
				]
			});
			
			this.renderPostsView();
			this.renderSettingsView();
		},
		
		/**
		 * Render widgets
		 */
		render: function () {
			this.renderToolbar();
			
			//Hide loading icon
			Y.one("body").removeClass("loading");
			
			//Posts/settings slideshow
			this.widgets.slideshow.render();
			
			//Filter
			var filter = this.widgets.filter,
				input  = null;
			
			filter.render(this.one('.filters'));
			filter.on('submit', this.filterPosts, this);
			
			input = filter.getInput('filterQuery');
			input.on('input', this.onFilterInputEvent, this);
			input.plug(Supra.Input.String.Clear);
			
			//On resize update slideshow slide position
			Y.on('resize', Y.bind(function () {
				
				this.widgets.slideshow.syncUI();
				this.widgets.settingsSlideshow.syncUI();
				
			}, this), window);
			
			//Render post list toolbar
			this.widgets.buttonNewPost.render();
			
			//Render post list
			this.widgets.datagrid.render();
			
			//Render language bar
			this.renderLanguageBar();
			
			// Settings tabs and slideshow
			this.widgets.settingsTabs.render();
			this.widgets.settingsSlideshow.render();
			
			this.widgets.settingsTabs.on('selectionChange', function (evt) {
				this.widgets.settingsSlideshow.set('slide', evt.newVal[0].id);
			}, this);
		},
		
		
		/* ------------------------------------ Localization ------------------------------------ */
		
		
		/**
		 * Render language bar widget
		 * 
		 * @private
		 */
		renderLanguageBar: function () {
			//Get locales
			var contexts = Supra.data.get('contexts'),
				values = [],
				widget = null,
				visible = true;
			
			for(var i=0,ii=contexts.length; i<ii; i++) values = values.concat(contexts[i].languages);
			
			if (values.length <= 1 && Supra.data.get(["site", "portal"])) {
				visible = false;
			}
			
			//Create widget
			widget = this.widgets.languageSelector = new Supra.Input.SelectList({
				'label': '',
				'values': values,
				'value': this.locale,
				'visible': visible
			});
			
			widget.render(this.one('div.blog-languages'));
			
			widget.set('value', this.locale);
			widget.after('valueChange', this.handleLocaleChange, this);
			
			return widget;
		},
		
		/**
		 * Handle locale change
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		handleLocaleChange: function (e) {
			if (this.widgets.datagrid && !e.silent) {
				this.locale = e.newVal;
				this.widgets.datagrid.requestParams.set('locale', e.newVal);
				this.widgets.datagrid();
			}
		},
		
		
		/* ------------------------------------ Table ------------------------------------ */
		
		
		/**
		 * Render Posts tab view
		 * 
		 * @private
		 */
		renderPostsView: function () {
			this.widgets.datagrid = new Supra.DataGrid({
				// Url
				'requestURI': this.getDataPath('dev/posts.php'),
				
				// Url params (GET parameters)
				'requestParams': {
					'locale': this.locale,
					'parent_id': this.parent_id,
					'query': ''
				},
				
				// Don't load data when created, will call .reset() manually
				'autoLoad': false,
				
				// ID column
				'idColumn': ['id'],
				
				// Data properties which doesn't have column
				'dataColumns': [
					{'id': 'id'},
					{'id': 'localized'},
					{'id': 'scheduled'},
					{'id': 'published'}
				],
				
				// All columns
				'columns': [
					{
						'id': 'time',
						'title': Supra.Intl.get(['blog', 'columns', 'time']),
						'formatter': 'dateShort'
					}, {
						'id': 'icon',
						'title': '',
						'formatter': function () { return '<img src="/cms/content-manager/sitemap/images/icon-news.png" height="22" width="20" alt="" />'; }
					}, {
						'id': 'title',
						'title': Supra.Intl.get(['blog', 'columns', 'title'])
					}, {
						'id': 'author',
						'title': Supra.Intl.get(['blog', 'columns', 'author'])
					}, {
						'id': 'comments',
						'title': Supra.Intl.get(['blog', 'columns', 'comments']),
						'formatter': this.formatColumnComments
					}, {
						'id': 'status',
						'title': '',
						'formatter': this.formatColumnStatus
					}, {
						'id': 'delete',
						'title': '',
						'formatter': this.formatColumnDelete
					}
				],
				
				'srcNode': this.one('div.datagrid'),
				'style': 'list'
			});
			
			this.widgets.buttonNewPost = new Supra.Button({
				'srcNode': this.one('button')
			});
			
			this.widgets.datagrid.plug(Supra.DataGrid.LoaderPlugin, {
				'recordHeight': 40
			});
			this.widgets.datagrid.plug(Supra.DataGrid.SortablePlugin, {
				'columns': ['time', 'title', 'author', 'comments'],
				'column': 'time',
				'order': 'desc'
			});
			this.widgets.datagrid.plug(Supra.DataGrid.RestorePlugin, {
			});
			
			//Bind event listeners
			this.widgets.buttonNewPost.on('click', this.addBlogPost, this);
			
			this.widgets.datagrid.on('row:click', function (event) {
				//On delete click...
				if (event.element.test('a.delete-icon')) {
					this.deleteBlogPost(event.row.id);
					return false;
				}
				
				//On row click...
				this.openBlogPost(event.row.id);
			}, this);
		},
		
		/**
		 * Format time column
		 * 
		 * @param {String} id Column ID
		 * @param {String} value Column content
		 * @param {Object} data Row data
		 * @private
		 */
		formatColumnTime: function (id, value, data) {
			return Y.DataType.Date.reformat(value, 'in_datetime', 'out_datetime_short');
		},
		
		/**
		 * Format status column
		 * 
		 * @param {String} id Column ID
		 * @param {String} value Column content
		 * @param {Object} data Row data
		 * @private
		 */
		formatColumnStatus: function (id, value, data) {
			if (!data.localized) {
				return '<div class="status-icon"><div class="status-not-localized">' + Supra.Intl.get(['blog', 'posts', 'status_not_created']) + '</div></div>';
			} else if (data.scheduled) {
				return '<div class="status-icon"><div class="status-scheduled">' + Supra.Intl.get(['blog', 'posts', 'status_scheduled']) + '</div></div>';
			} else if (!data.published) {
				return '<div class="status-icon"><div class="status-draft">' + Supra.Intl.get(['blog', 'posts', 'status_draft']) + '</div></div>';
			} else {
				return '';
			}
		},
		
		/**
		 * Render action column content
		 * 
		 * @param {String} id Column ID
		 * @param {String} value Column content
		 * @param {Object} data Row data
		 * @param {Object} td Column node
		 * @private
		 */
		formatColumnDelete: function (id, value, data, td) {
			return '<a class="delete-icon"></div>';
		},
		
		/**
		 * Format comments column
		 * 
		 * @param {String} id Column ID
		 * @param {String} value Column content
		 * @param {Object} data Row data
		 * @private
		 */
		formatColumnComments: function (id, value, data) {
			return value.total ? '<span class="comments-icon' + (value.has_new ? ' comments-icon-new' : '') + '">' + value.total + '</span>' : '';
		},
		
		
		/**
		 * Filter posts by input query
		 * 
		 * @private
		 */
		filterPosts: function () {
			var filter = this.widgets.filter,
				datagrid = this.widgets.datagrid,
				query = filter.getInput('filterQuery').get('value');
			
			if (datagrid.requestParams.get('query') != query) {
				datagrid.requestParams.set('query', query);
				datagrid.reset();
			}
			
			//Cancel timer
			if (this.filterTimer) {
				this.filterTimer.cancel();
				this.filterTimer = null;
			}
		},
		
		/**
		 * On filter input change start timer which will trigger
		 * data reload
		 * 
		 * @private
		 */
		onFilterInputEvent: function () {
			if (this.filterTimer) {
				this.filterTimer.cancel();
			}
			
			this.filterTimer = Y.later(250, this, this.filterPosts);
		},
		
		
		/* ------------------------------------ Settings ------------------------------------ */
		
		
		renderSettingsView: function () {
			this.widgets.settingsTabs = new Supra.ButtonGroup({
				'srcNode': this.one('div.blog-settings div.nav-tabs')
			});
			this.widgets.settingsSlideshow = new Supra.Slideshow({
				'srcNode': this.one('div.blog-settings div.slideshow')
			});
			
			this.renderForm('Author');
			this.renderForm('Tags');
		},
		
		/**
		 * Render form
		 * 
		 * @private
		 */
		renderForm: function (name) {
			var container = Y.one('#tabContent' + name);
			
			var form = this.widgets['form' + name] = new Supra.Form({
				'srcNode': container.one('form')
			});
			
			var footer = this.widgets['footer' + name] = new Supra.Footer({
				'srcNode': container.one('div.footer')
			});
			
			form.render();
			footer.render();
			
			form.on('submit', this.saveForm, this, {'name': name});
		},
		
		/**
		 * Save form values
		 */
		saveForm: function (e, params) {
			var name	= params.name,
				form	= e.target,
				footer	= this.widgets['footer' + name],
				
				uri		= this.getDataPath('dev/save'),
				data	= {};
			
			data[name.toLowerCase()] = form.getSaveValues('name');
			
			form.set('disabled', true);
			footer.getButton('save').set('loading', true);
			
			Supra.io(uri, {
				'method': 'post',
				'data': data,
				'context': this,
				'on': {
					'complete': function () {
						form.set('disabled', false);
						footer.getButton('save').set('loading', false);
					}
				}
			});
		},
		
		
		/* ------------------------------------ Data ------------------------------------ */
		
		
		/**
		 * Load Blog settings
		 * 
		 * @private 
		 */
		loadData: function () {
			var uri = this.getDataPath('dev/settings');
			
			Supra.io(uri).done(this.setSettingsData, this);
		},
		
		/**
		 * Set settings data
		 * 
		 * @private
		 */
		setSettingsData: function (data) {
			// Author
			this.widgets.formAuthor.setValues(data.author);
			
			// Avatar
			this.one('em.avatar img').setAttribute('src', data.author.avatar || '/cms/lib/supra/img/avatar-default-48x48.png');
			
			// Tags
			this.widgets.formTags.setValues(data.tags);
		},
		
		/**
		 * Add new blog post
		 */
		addBlogPost: function () {
			this.widgets.buttonNewPost.set('loading', true);
			
			var uri = Manager.getAction('Page').getDataPath('create');
			
			Supra.io(uri, {
				'data': {
					'locale': this.locale,
					'published': false,
					'scheduled': false,
					'type': 'page',
					'parent_id': this.parent_id,
					
					'title': '',
					'template': '',
					'path': ''
				},
				'method': 'post',
				'context': this,
				'on': {
					'success': function (data) {
						this.openBlogPost(data.id);
					},
					'complete': function () {
						this.widgets.buttonNewPost.set('loading', false);
					}
				}
			});
		},
		
		/**
		 * Delete blog post
		 * 
		 * @param {String} id Blog post id
		 */
		deleteBlogPost: function (id) {
			Manager.executeAction('Confirmation', {
				'message': Supra.Intl.get(['blog', 'posts', 'delete_post']),
				'useMask': true,
				'buttons': [
					{
						'id': 'delete',
						'label': Supra.Intl.get(['buttons', 'yes']),
						'click': function () { this.deleteBlogPostConfirmed(id); },
						'context': this
					},
					{
						'id': 'no',
						'label': Supra.Intl.get(['buttons', 'no'])
					}
				]
			});
		},
		
		/**
		 * Delete post after confirmation
		 * 
		 * @param {String} record_id Blog post id
		 * @private
		 */
		deleteBlogPostConfirmed: function (record_id) {
			//Delete record
			if (record_id) {
				var uri = this.getDataPath('dev/delete'),
					post_data = {
						'id': record_id,
						'locale': this.locale,
						'action': 'delete'
					};
				
				Supra.io(uri, {
					'data': post_data,
					'method': 'post',
					'context': this,
					'on': {
						'success': function () {
							this.widgets.datagrid.remove(record_id);
							this.widgets.datagrid.loader.checkRecordsInView();
						}
					}
				});
			}
			
		},
		
		/**
		 * Open blog post for editing
		 * 
		 * @param {Object} record_id
		 */
		openBlogPost: function (record_id) {
			if (this.standalone) {
				// Open content manager action
				var url = Manager.Loader.getStaticPath() + Manager.Loader.getActionBasePath('SiteMap') + '/h/page/' + record_id;
				
				Y.Cookie.set('supra_language', this.locale);
				document.location = url;
			} else {
				var data = this.widgets.datagrid.getRowByID(record_id).getData();
				
				Supra.data.set('locale', this.locale);
				this.fire('page:select', {
					'data': data
				});
			}
		},
		
		
		/* ------------------------------------ Toolbar  ------------------------------------ */
		
		
		/**
		 * Add toolbar buttons
		 * 
		 * @private
		 */
		renderToolbar: function () {
			var toolbar = Manager.getAction('PageToolbar');
			
			//Add buttons to toolbar
			toolbar.addActionButtons(this.NAME, TOOLBAR_BUTTONS);
			toolbar.getActionButton('blog_posts').set('down', true);
			toolbar.getActionButton('blog_settings').set('down', false);
			
			toolbar.getActionButton('blog_posts').on('click', this.handleToolbarButton, this, 'blog_posts');
			toolbar.getActionButton('blog_settings').on('click', this.handleToolbarButton, this, 'blog_settings');
			
			toolbar.getActionButton('blog_recycle_bin').on('click', this.toggleRecycleBin, this);
			
			//Add side buttons
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'done',
				'context': this,
				'callback': function () {
					this.hide();
				}
			}]);
		},
		
		/**
		 * Handle toolbar button click
		 */
		handleToolbarButton: function (event, id) {
			var toolbar = Manager.getAction('PageToolbar'),
				buttons = ['blog_posts', 'blog_settings'],
				button = null,
				i = 0,
				ii = buttons.length;
			
			for (; i<ii; i++) {
				button = toolbar.getActionButton(buttons[i]);
				button.set('down', buttons[i] == id);
			}
			
			//Add buttons to toolbar
			this.widgets.slideshow.set('slide', id);
		},
		
		/**
		 * Toggle recycle bin
		 * 
		 * @private
		 */
		toggleRecycleBin: function () {
			var toolbar = Manager.getAction('PageToolbar'),
				button  = toolbar.getActionButton('blog_recycle_bin'),
				action  = Supra.Manager.getAction('SiteMapRecycle');
			
			if (!button.get('down')) {
				action.execute({
					'type': 'Blog',
					'parent_id': this.parent_id,
					'onclose': function () {
						button.set('down', false);
					}
				});
				
				button.set('down', true);
			} else {
				action.hide();
			}
		},
		
		
		/* ------------------------------------ Action  ------------------------------------ */
		
		
		/**
		 * Clean up everything
		 */
		cleanUp: function () {
			var datagrid = this.widgets.datagrid;
			
			datagrid.requestParams.set('offset', 0);
			datagrid.removeAllRows();
		},
		
		/**
		 * Animate dashboard out of view
		 */
		hide: function () {
			this.cleanUp();
			
			this.one().hide();
			Action.Base.prototype.hide.apply(this, arguments);
			
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			//Remove from header
			Manager.getAction('Header').unsetActiveApplication(this.NAME);
		},
		
		/**
		 * Animate dashboard into view
		 */
		show: function () {
			this.one().show();
			Action.Base.prototype.show.apply(this, arguments);
			
			if (!Manager.getAction('PageToolbar').inHistory(this.NAME)) {
				Manager.getAction('PageToolbar').setActiveAction(this.NAME);
				Manager.getAction('PageButtons').setActiveAction(this.NAME);
			}
			
			//Add to header, because this is full screen application
			Manager.getAction('Header').setActiveApplication(this.NAME);
		},
		
		/**
		 * Execute action
		 */
		execute: function (options) {
			this.standalone = (options && options.standalone);
			this.parent_id = (options && options.parent_id ? options.parent_id : '');
			this.show();
			
			if (!this.standalone) {
				this.locale = Manager.getAction('SiteMap').languageSelector.get('value');
				this.widgets.languageSelector.set('value', this.locale, {'silent': true});
			}
			
			this.widgets.datagrid.requestParams.set('locale', this.locale);
			this.widgets.datagrid.requestParams.set('parent_id', this.parent_id);
			this.widgets.datagrid.reset();
		}
	});
	
});