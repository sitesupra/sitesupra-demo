//Invoke strict mode
"use strict";

/**
 * Main manager action, initiates all other actions
 */
Supra(function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Root',
		
		/**
		 * Root action doesn't have template
		 * @type {Boolean}
		 */
		HAS_TEMPLATE: false,
		
		/**
		 * Bind Actions together
		 */
		render: function () {
			//this.addChildAction('Header');
			this.addChildAction('Page');
			
			//var header = Manager.getAction('Header'),
			var page = Manager.getAction('Page'),
				buttons = Manager.getAction('PageButtons'),
				toolbar = Manager.getAction('PageToolbar'),
				sitemap = Manager.getAction('SiteMap');
			
			//Show loading screen until content is loaded (last action)
			Y.one('body').addClass('loading');
			
			SU.Manager.getAction('PageContent').after('iframeReady', function () {
				Y.one('body').removeClass('loading');
			});
			
			/*
			//Add "Pages" to the header
			header.on('execute', function () {
				
				header.addItem('manager', {
					'type': 'label',
					'icon': '/cms/supra/img/apps/content_32x32.png',
					'title': 'Pages'
				});
				
				//Open page
				page.execute(Supra.data.get('page', {'id': 0}));
			});
			
			//Execute action
			header.execute();
			*/
			
			//Add empty button set
			buttons.on('render', function () {
				buttons.addActionButtons(this.NAME, []);
			}, this);
			toolbar.on('render', function () {
				toolbar.setActiveGroupAction(this.NAME);
			}, this);
			
			//On page unload destroy everything???
			Y.on('beforeunload', function () {
			    this.destroy();
			}, this);
			
			//On SiteMap page change reload
			sitemap.on('page:select', function (evt) {
				page.execute(evt.data);
			}, this);
			
			//Load page after execute
			this.on('render', function () {
				SU.Manager.executeAction('Page', Supra.data.get('page', {'id': 0}));
			});
		},
		
		execute: function () {
			var toolbar = Manager.getAction('PageToolbar'),
				buttons = Manager.getAction('PageButtons'),
				content = Manager.getAction('PageContent'),
				page = Manager.getAction('Page');
			
			if (toolbar.get('ready')) {
				toolbar.setActiveGroupAction(this.NAME);
			}
			if (buttons.get('ready')) {
				buttons.setActiveAction(this.NAME);
			}
			if (content.get('ready')) { 
				content.stopEditing();
			}
		}
	});
	
});