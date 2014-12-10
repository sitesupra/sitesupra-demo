/**
 * Main manager action, initiates all other actions
 */
Supra(

	'router',

function (Y) {
	//Invoke strict mode
	"use strict";

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;

	/**
	 * Default page toolbar buttons
	 * @type {Object}
	 */



	var DEFAULT_TOOLBAR = {
		'Root': [
			{
				'id': 'sitemap',
				'title': Supra.Intl.get(['sitemap', 'button']),
				'icon': 'supra/img/toolbar/icon-sitemap.png',
				'action': 'Root',
				'actionFunction': 'routeSiteMapSave',
				'type': 'button'	/* Default is 'toggle' */
			}
		],
		'Page': [
			{
				'id': 'blockbar',
				'title': Supra.Intl.get(['insertblock', 'button']),
				'icon': 'supra/img/toolbar/icon-insert.png',
				'action': 'PageInsertBlock',
				'permissions': ['block', 'insert']
			},
			{
				'id': 'history',
				'title': Supra.Intl.get(['history', 'button']),
				'icon': 'supra/img/toolbar/icon-history.png',
				'action': 'PageHistory'
			},
			{
				'id': 'blocksview',
				'title': Supra.Intl.get(['blocks', 'button']),
				'icon': 'supra/img/toolbar/icon-blocks.png',
				'action': 'BlocksView',
				'actionFunction': 'setType'
			},
			{
				'id': 'placeholderview',
				'title': Supra.Intl.get(['placeholders', 'button']),
				'icon': 'supra/img/toolbar/icon-placeholders.png',
				'action': 'BlocksView',
				'actionFunction': 'setType'
			},
			{
				'id': 'settings',
				'title': Supra.Intl.get(['settings', 'button_page']),
				'icon': 'supra/img/toolbar/icon-settings.png',
				'action': 'PageSettings',
			}
		]
	};

	// In portal site we need 'Design manager' button
	if (Supra.data.get(['site', 'portal']) && Supra.data.get(['design'])) {
		DEFAULT_TOOLBAR.Page.unshift({
			'id': 'design',
			'type': 'button',
			'title': Supra.Intl.get(['designmanager', 'button']),
			'icon': 'supra/img/toolbar/icon-design.png',
			'action': 'PageDesignManager'
		});
	}

	/**
	 * Default buttons
	 * @type {Object}
	 */
	var DEFAULT_BUTTONS = {
		'Root': []
	};

	DEFAULT_BUTTONS.Root = [{
		'id': 'edit',
		'callback': function () {
			if (Manager.Page.isPage()) {
				Manager.Page.lockPage();
			} else {
				Manager.Template.lockTemplate();
			}
		}
	}, {
		'id': 'unlock',
		'visible': false,
		'callback': function () {
			//Force page unlock
			if (Manager.Page.isPage()) {
				Manager.Page.unlockPage(true);
			} else {
				Manager.Template.unlockTemplate(true);
			}

		}
	}];

	Supra.Manager.getAction('PageToolbar').set('buttons', DEFAULT_TOOLBAR);
	Supra.Manager.getAction('PageButtons').set('buttons', DEFAULT_BUTTONS);

	//Create Action class
	new Action({

		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Root',

		/**
		 * @TODO: can we hardcode it like this?
		 */
		ROUTE_NAME: 'cms_pages',

		/**
		 * Dependancies
		 * @type {Array}
		 */
		DEPENDANCIES: ['Page', 'Template', 'PageHeader', 'PageToolbar', 'PageButtons', 'PageContent'],

		/**
		 * Action doesn't have stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: false,

		/**
		 * Action doesn't have template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: false,


		ROUTE_SITEMAP:		'/h/sitemap',
		ROUTE_TEMPLATES:	'/h/templates',
		ROUTE_PAGE:			'/h/page/:page_id',
		ROUTE_PAGE_EDIT:	'/h/page/:page_id/edit',
		ROUTE_PAGE_CONT:	'/h/page/:page_id/edit/:block_id',

		ROUTE_PAGE_R: 		/^\/h\/page\/([^\/]+)$/,
		ROUTE_PAGE_EDIT_R: 	/^\/h\/page\/([^\/]+)\/edit$/,
		ROUTE_PAGE_CONT_R: 	/^\/h\/page\/([^\/]+)\/edit\/([^\/]+)$/,


		/**
		 * Router instance
		 */
		router: null,


		/**
		 * Y.Router routers
		 */
		initialize: function () {

			this.router = new Y.Router({
				'root': Supra.Url.generate(this.ROUTE_NAME)
			});

			//Overwrite routing save to make sure paths are not written twice
			this.router.save = function (path) {
				//Get route path without trailing slash
				var router_path = this.getPath();
				if (router_path.substr(-1, 1) == '/') router_path = router_path.substr(0, router_path.length - 1);

				if (router_path != path) {
					return Y.Router.prototype.save.apply(this, arguments);
				}
			};

			//Routes
			this.router.route('/', 					this.bind(this.routePage, this));
			this.router.route(this.ROUTE_SITEMAP,   this.bind(this.routeSitemap, this));
			this.router.route(this.ROUTE_TEMPLATES, this.bind(this.routeTemplates, this));
			this.router.route(this.ROUTE_PAGE, 		this.bind(this.routePage, this));
		},



		/**
		 * Load page
		 */
		routePage: function (req) {
			//Close sitemap if needed
			if (Manager.getAction('SiteMap').get('visible')) {
				Manager.getAction('SiteMap').hide();
			} else {
				this.execute();
			}

			//Load page
			var page_id = req.params.page_id,
				page_data = Manager.Page.getPageData();

			if (page_id && ( ! page_data || page_id != page_data.id)) {
				//Open page; evt.data is in format  {'id': 1}
				Manager.getAction('Page').execute({'id': page_id});
			}

			//Make sure other routes are also executed
			req.next();
		},

		/**
		 * Open sitemap
		 */
		routeSitemap: function (req) {
			Supra.Manager.executeAction('SiteMap', {'mode': 'pages'});

			//Make sure other routes are also executed
			req.next();
		},

		/**
		 * Open sitemap
		 */
		routeTemplates: function (req) {
			Supra.Manager.executeAction('SiteMap', {'mode': 'templates'});

			//Make sure other routes are also executed
			req.next();
		},

		/**
		 * Change route to sitemap
		 */
		routeSiteMapSave: function () {
			var page_data = Supra.Manager.Page.getPageData();
			if (page_data && page_data.type == 'template') {
				this.router.save(this.ROUTE_TEMPLATES);
			} else {
				this.router.save(this.ROUTE_SITEMAP);
			}
		},

		/**
		 * Returns route path without trailins slash
		 */
		getRoutePath: function () {
			var path = this.router.getPath();

			//Trim trailing slash
			if (path.substr(-1, 1) == '/') {
				path = path.substr(0, path.length-1);
			}

			return path;
		},



		/**
		 * Bind Actions together
		 */
		render: function () {
			this.addChildAction('Page');

			//Show loading screen until content is loaded (last executed action)
			Y.one('body').addClass('loading');

			Supra.Manager.getAction('PageContent').after('iframeReady', function () {
				Y.one('body').removeClass('loading');
			});

			//On page unload destroy everything???
			Y.on('beforeunload', function () {
			    this.destroy();
			}, this);

			//Load page after execute
			this.on('render', function () {
				//Search in path "/r/page/:page_id"
				var is_site = Supra.data.get(['site', 'portal']),
					page_id = this.getRoutePath().match(this.ROUTE_PAGE_R) ||
							  this.getRoutePath().match(this.ROUTE_PAGE_EDIT_R) ||
							  this.getRoutePath().match(this.ROUTE_PAGE_CONT_R);

				if (page_id) {
					//Extracted from path
					page_id = {'id': page_id[1]};
				} else if (!is_site) {
					//From data, but only if not portal site
					page_id = Supra.data.get(['page', 'id'], null);
					if (page_id) page_id = {'id': page_id};
				}

				//If there is no page ID or /h/sitemap is in path, then open SiteMap
				var router_path = this.getRoutePath();

				if (router_path == this.ROUTE_SITEMAP || router_path == this.ROUTE_TEMPLATES) {
					var mode = 'pages';
					if (router_path == this.ROUTE_TEMPLATES) {
						mode = 'templates';
					}

					Supra.Manager.executeAction('SiteMap', {'mode': mode});
				} else if (!page_id) {
					// Save path to /h/sitemap, otherwise /cms/content-manager path is visible
					// for sitemap, which will break Site Map when for example dashboard is closed
					this.router.save(this.ROUTE_SITEMAP);
				} else {
					Supra.Manager.executeAction('Page', page_id);
					Supra.Manager.executeAction('Template');

					//Remove loading style
					Y.one('body').removeClass('loading');
				}
			});

			this.bindSiteMap();
			this.bindBlogManager();
		},

		/**
		 * Bind SiteMap action to Page
		 */
		bindSiteMap: function () {
			//When page is selected in sitemap load it
			Manager.getAction('SiteMap').on('page:select', function (evt) {
				//Only if page is localized
				if (evt.data.localized) {
					//Change path
					this.router.save(this.ROUTE_PAGE.replace(':page_id', evt.data.id));
				}
			}, this);
		},

		/**
		 * Bind SiteMap action to Page
		 */
		bindBlogManager: function () {
			//When page is selected in sitemap load it
			Manager.getAction('Blog').on('page:select', function (evt) {
				//Only if page is localized
				if (evt.data.localized) {
					//Change path
					this.router.save(this.ROUTE_PAGE.replace(':page_id', evt.data.id));
				}
			}, this);
		},


		/**
		 * Execute action
		 */
		execute: function () {
			var toolbar = Manager.getAction('PageToolbar'),
				buttons = Manager.getAction('PageButtons'),
				content = Manager.getAction('PageContent');

			if (toolbar.get('created')) {
				toolbar.setActiveAction(this.NAME);
			}
			if (buttons.get('created')) {
				buttons.setActiveAction(this.NAME);
			}

			if (content.get('created')) {
				content.stopEditing();
			}
		}

	});

});