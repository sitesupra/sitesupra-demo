//Invoke strict mode
"use strict";

/**
 * Custom modules
 */
(function () {
	var STATIC_PATH = Supra.Manager.Loader.getStaticPath(),
		APP_PATH = Supra.Manager.Loader.getActionBasePath('Blog');
	
	Supra.setModuleGroupPath('blog', STATIC_PATH + APP_PATH + '/modules');
	
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
			
			'formsAuthor': null,
			'footersAuthor': null,
			
			'formComments': null,
			
			'datagridTags': null,
			
			'formTemplates': null
		},
		
		/**
		 * Last loaded data info
		 */
		data: null,
		
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
		 * Action execution options
		 * @type {Object}
		 * @private
		 */
		options: {
			'standalone': false,
			'parent_id': '',
			'sitemap_element': null
		},
		
		
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
			input.addClass('search');
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
				this.widgets.settingsSlideshow.syncUI();
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
				'requestURI': this.getDataPath('posts'),
				
				// Url params (GET parameters)
				'requestParams': {
					'locale': this.locale,
					'parent_id': this.options.parent_id,
					'query': ''
				},
				
				// Don't load data when created, will call .reset() manually
				'autoLoad': false,
				
				// ID column
				'idColumn': ['id'],
				
				// Data properties which doesn't have column
				'dataColumns': [
					{'id': 'id'},
					{'id': 'page_id'},
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
						'id': 'status',
						'title': '',
						'formatter': this.formatColumnStatus
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
					this.deleteBlogPost(event.row.page_id);
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
			
			this.renderFormTags();
			this.renderFormTemplates();
			this.renderFormComments();
			
		},
		
		/**
		 * Render form
		 * 
		 * @private
		 */
		renderForm: function (name) {
			var container = Y.one('#tabContent' + name),
				node = null;
			
			node = container.one('form');
			if (node) {
				var form = this.widgets['form' + name] = new Supra.Form({
					'srcNode': node
				});
				
				form.render();
				form.on('submit', this.saveForm, this, {'name': name});
			}
			
			node = container.one('div.footer');
			if (node) {
				var footer = this.widgets['footer' + name] = new Supra.Footer({
					'srcNode': node
				});
				
				footer.render();
			}
		},
		
		/**
		 * Render tags form
		 * 
		 * @private
		 */
		renderFormTags: function () {
			var datasource = new Y.DataSource.Function({
				'source': Y.bind(function () {
					var data = this.data,
						tags = [],
						i    = 0,
						ii   = 0;
					
					if (data.tags) {
						for (ii = data.tags.length; i<ii; i++) {
							tags.push({'name': data.tags[i]});
						}
					}
					
					return tags;
				}, this)
			});
			
			datasource.plug(Y.Plugin.DataSourceArraySchema, {
				schema: {
					resultFields: ['name']
				}
			});
			
			var datagrid = new Supra.DataGrid({
				
				'dataSource': datasource,
				
				'clickable': false,
				
				// Not scrollable, this is controlled by slideshow
				'scrollable': false,
				
				// ID column
				'idColumn': ['name'],
				
				// All columns
				'columns': [
					{
						'id': 'name',
						'title': Supra.Intl.get(['blog', 'settings', 'tags', 'name']),
					}, {
						'id': 'delete',
						'title': '',
						'formatter': this.formatColumnDelete
					}
				],
				
				'srcNode': this.one('div.taggrid'),
				'style': 'list'
			});
			
			datagrid.render();
			datagrid.addClass('su-datagrid-dark');
			
			datagrid.on('row:click', function (event) {
				//On delete click...
				if (event.element.test('a.delete-icon')) {
					this.deleteBlogTag(event.row.data.name);
					return false;
				}
			}, this);
			
			this.widgets.datagridTags = datagrid;
		},
		
		/**
		 * Render comments form
		 * 
		 * @private
		 */
		renderFormComments: function () {
			this.renderForm('Comments');
			this.widgets.formComments.getInput('moderation_enabled').on('change', function () {
				this.saveForm({
					'target': this.widgets.formComments
				}, {
					'uri': this.getDataPath('../settings/save-comments'),
					'name': null
				});
			}, this);
		},
		
		/**
		 * Render templates form
		 * 
		 * @private
		 */
		renderFormTemplates: function () {
			this.renderForm('Templates');
			
			var uri = Supra.Manager.getAction('PageSettings').getDataPath('templates'),
				form = this.widgets.formTemplates,
				input = form.getInput('template');
			
			input.set('loading', true);
			
			Supra.io(uri).done(function (templates) {
				this.updatingUI = true;
				
				var form = this.widgets.formTemplates,
					input = form.getInput('template');
				
				input.set('loading', false);
				input.set('showEmptyValue', false);
				input.set('values', templates);
				
				this.updatingUI = false;
			}, this);
			
			input.on('change', function () {
				this.saveForm({
					'target': this.widgets.formTemplates
				}, {
					'uri': this.getDataPath('../settings/save-templates'),
					'name': null
				});
			}, this);
		},
		
		/**
		 * Save form values
		 */
		saveForm: function (e, params) {
			if (this.updatingUI) return;
			
			var name	= params.name,
				form	= e.target,
				footer	= this.widgets['footer' + name],
				
				uri		= params.uri || this.getDataPath('../settings/save'),
				data	= {
					'parent_id': this.options.parent_id
				};
			
			if (name) {
				data[name.toLowerCase()] = form.getSaveValues('name');
			} else {
				Supra.mix(data, form.getSaveValues('name'));
			}
			
			form.set('disabled', true);
			
			if (footer) {
				footer.getButton('save').set('loading', true);
			}
			
			Supra.io(uri, {
				'method': 'post',
				'data': data,
				'context': this,
				'on': {
					'complete': function () {
						form.set('disabled', false);
						
						if (footer) {
							footer.getButton('save').set('loading', false);
						}
					}
				}
			});
		},
		
		/**
		 * Save author form values
		 */
		saveAuthorForm: function (e, params) {
			if (this.updatingUI) return;
			
			var index  = params.index,
				form   = this.widgets.formsAuthor[index],
				footer = this.widgets.footersAuthor[index],
				
				uri    = this.getDataPath('../settings/save-authors'),
				data   = null;
			
			// Data
			data = Supra.mix({
				'parent_id': this.options.parent_id
			}, form.getSaveValues('name'));
			
			delete(data.avatar);
			
			// Disable form to prevent multiple calls
			form.set('disabled', true);
			
			if (footer) {
				footer.getButton('save').set('loading', true);
			}
			
			Supra.io(uri, {
				'method': 'post',
				'data': data,
				'context': this,
				'on': {
					'complete': function () {
						form.set('disabled', false);
						
						if (footer) {
							footer.getButton('save').set('loading', false);
						}
					},
					'success': function () {
						if (footer) {
							footer.hide();
						}	
					}
				}
			});
		},
		
		/**
		 * Render author forms, for each author one form
		 * 
		 * @param {Array} authors List of authors
		 * @private
		 */
		renderAuthorForms: function (authors) {
			var forms = this.widgets.formsAuthor || [],
				form = null,
				footers = this.widgets.footersAuthor || [],
				footer = null,
				node = null,
				input = null,
				i = 0,
				ii = forms.length,
				count = authors.length,
				
				template = this.one('#tabContentAuthor form.hidden');
			
			if (count < ii) {
				// Destroy unneeded forms
				for (i=count; i<ii; i++) {
					node = forms[i].get('boundingBox');
					footers[i].destroy();
					forms[i].destroy();
					forms[i] = null;
					node.remove(true);
				}
				
				forms = forms.slice(0, count);
				footers = footers.slice(0, count);
			} else if (count > ii) {
				// Create new forms
				for (i=ii; i<count; i++) {
					node = template.cloneNode(true);
					node.removeClass('hidden');
					template.ancestor().appendChild(node);
					
					node.all('[for], [name]').each(function (node, index) {
						var attr = node.getAttribute('for');
						if (attr) {
							node.setAttribute('for', attr + '_' + i);
						}
						attr = node.getAttribute('name');
						if (attr) {
							node.setAttribute('id', attr + '_' + i);
						}
					});
					
					form = new Supra.Form({
						'srcNode': node
					});
					
					footer = new Supra.Footer({
						'srcNode': node.one('div.footer')
					});
					
					form.render();
					footer.render();
					
					input = form.getInput('name');
					input.addClass('input-name');
					input.on('input', footer.show, footer);
					
					input = form.getInput('about');
					input.addClass('input-about');
					input.on('input', footer.show, footer);
					
					form.on('submit', this.saveAuthorForm, this, {'index': i});
					
					forms.push(form);
					footers.push(footer);
				}
			}
			
			// Set values
			for (i=0, ii=authors.length; i<ii; i++) {
				forms[i].setValues(authors[i], 'name');
				footers[i].hide();
				
				// Avatar
				node = forms[i].get('boundingBox').one('em.avatar img');
				node.setAttribute('src', authors[i].avatar || '/cms/lib/supra/img/avatar-default-48x48.png');
			}
			
			this.widgets.formsAuthor = forms;
			this.widgets.footersAuthor = footers;
			
			// Update scroll
			this.widgets.settingsSlideshow.syncUI();
		},
		
		
		/* ------------------------------------ Data ------------------------------------ */
		
		
		/**
		 * Load Blog settings
		 * 
		 * @private 
		 */
		loadData: function () {
			var uri = this.getDataPath('../settings/load');
			
			Supra.io(uri, {
				'data': {
					'parent_id': this.options.parent_id
				}
			}).done(this.setSettingsData, this);
		},
		
		/**
		 * Set settings data
		 * 
		 * @private
		 */
		setSettingsData: function (data) {
			this.updatingUI = true;
			
			this.data = data;
			
			// Author
			this.renderAuthorForms(data.authors);
			
			// Tags
			this.widgets.datagridTags.reset();
			
			// Comments
			this.widgets.formComments.setValues(data.comments);
			
			// Templates
			this.widgets.formTemplates.setValues(data.templates);
			
			this.widgets.settingsSlideshow.syncUI();
			this.updatingUI = false;
		},
		
		/**
		 * Add new blog post
		 */
		addBlogPost: function () {
			this.widgets.buttonNewPost.set('loading', true);
			
			//var uri = Manager.getAction('Page').getDataPath('create');
			var uri = this.getDataPath('create');
			
			Supra.io(uri, {
				'data': {
					'locale': this.locale,
					'published': false,
					'scheduled': false,
					'type': 'page',
					'parent_id': this.options.parent_id,
					
					'title': '',
					'template': '',
					'path': ''
				},
				'method': 'post',
				'context': this,
				'on': {
					'success': function (data) {
						this.openBlogPost(data);
					},
					'complete': function () {
						this.widgets.buttonNewPost.set('loading', false);
					}
				}
			});
		},
		
		/**
		 * Delete blog tag
		 * 
		 * @param {String} name Tag name
		 */
		deleteBlogTag: function (name) {
			Manager.executeAction('Confirmation', {
				'message': Supra.Intl.get(['blog', 'settings', 'tags', 'delete_tag']),
				'useMask': true,
				'buttons': [
					{
						'id': 'delete',
						'label': Supra.Intl.get(['buttons', 'yes']),
						'click': function () { this.deleteBlogTagConfirmed(name); },
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
		 * @param {String} name Blog tag name
		 * @private
		 */
		deleteBlogTagConfirmed: function (name) {
			//Delete record
			if (name) {
				var uri = this.getDataPath('../settings/delete-tag'),
					post_data = {
						'name': name,
						'parent_id': this.options.parent_id
					};
				
				Supra.io(uri, {
					'data': post_data,
					'method': 'post',
					'context': this,
					'on': {
						'success': function () {
							this.widgets.datagridTags.remove(name);
						}
					}
				});
			}
			
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
				var uri = Manager.getAction('Page').getDataPath('delete'),
					post_data = {
						'id': record_id,
						'locale': this.locale,
						'action': 'delete',
						'parent_id': this.options.parent_id
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
			var data = null;
			
			if (typeof record_id === 'string') {
				data = this.widgets.datagrid.getRowByID(record_id).getData();
			} else {
				data = record_id;
				record_id = data.id;
			}
			
			if (this.options.standalone) {
				// Open content manager action
				var url = Manager.Loader.getStaticPath() + Manager.Loader.getActionBasePath('SiteMap') + '/h/page/' + record_id;
				
				Y.Cookie.set('supra_language', this.locale);
				document.location = url;
			} else {
				// Close recycle bin
				this.hideRecycleBin();
				
				if (this.options.sitemap_element) {
					// Animate sitemap
					Supra.Manager.SiteMap.animate(this.options.sitemap_element, false).done(function () {
						this._openBlogPost(data);
					}, this);
					this.options.sitemap_element = null;
				} else {
					// No animation, since we don't have needed element
					this._openBlogPost(data);
				}
			}
		},
		
		_openBlogPost: function (data) {
			this.hide();
			
			Supra.data.set('locale', this.locale);
			this.fire('page:select', {
				'data': data
			});
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
			
			//Recycle bin is disabled in blog settings
			toolbar.getActionButton('blog_recycle_bin').set('disabled', id == 'blog_settings');
			
			//Add buttons to toolbar
			this.widgets.slideshow.set('slide', id);
			
			//Make sure scrollbar is correct
			if (id == 'blog_settings') {
				this.widgets.settingsSlideshow.syncUI();
			}
		},
		
		/**
		 * Toggle recycle bin
		 * 
		 * @private
		 */
		toggleRecycleBin: function () {
			var toolbar = Manager.getAction('PageToolbar'),
				button  = toolbar.getActionButton('blog_recycle_bin');
			
			if (!button.get('down')) {
				this.showRecycleBin();
			} else {
				this.hideRecycleBin();
			}
		},
		
		/**
		 * Show recycle bin
		 * 
		 * @private
		 */
		showRecycleBin: function () {
			var toolbar = Manager.getAction('PageToolbar'),
				button  = toolbar.getActionButton('blog_recycle_bin'),
				action  = Supra.Manager.getAction('SiteMapRecycle'),
				buttonNewPost = this.widgets.buttonNewPost;
			
			action.execute({
				'type': 'Blog',
				'parent_id': this.options.parent_id,
				'onclose': function () {
					toolbar.getActionButton('blog_recycle_bin').set('down', false);
					toolbar.getActionButton('blog_posts').set('disabled', false);
					toolbar.getActionButton('blog_settings').set('disabled', false);
					buttonNewPost.show();
				}
			});
			
			buttonNewPost.hide();
			toolbar.getActionButton('blog_recycle_bin').set('down', true);
			toolbar.getActionButton('blog_posts').set('disabled', true);
			toolbar.getActionButton('blog_settings').set('disabled', true);
		},
		
		/**
		 * Hide recycle bin
		 * 
		 * @private
		 */
		hideRecycleBin: function () {
			var toolbar = Manager.getAction('PageToolbar'),
				action  = Supra.Manager.getAction('SiteMapRecycle'),
				buttonNewPost = this.widgets.buttonNewPost;
			
			toolbar.getActionButton('blog_posts').set('disabled', false);
			toolbar.getActionButton('blog_settings').set('disabled', false);
			
			action.hide();
			buttonNewPost.show();
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
			
			//Animate sitemap
			if (this.options.sitemap_element) {
				Supra.Manager.SiteMap.animate(this.options.sitemap_element, true, 'blog');
			}
			
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
			this.options = Supra.mix({
				'standalone': false,
				'parent_id': '',
				'sitemap_element': null
			}, options || {});
			
			this.show();
			
			if (!this.options.standalone) {
				this.locale = Manager.getAction('SiteMap').languageSelector.get('value');
				this.widgets.languageSelector.set('value', this.locale, {'silent': true});
			}
			
			this.widgets.datagrid.requestParams.set('locale', this.locale);
			this.widgets.datagrid.requestParams.set('parent_id', this.options.parent_id);
			this.widgets.datagrid.reset();
			
			// Settings
			this.loadData();
		}
	});
	
});