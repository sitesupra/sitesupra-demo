SU('supra.tabs', function (Y) {

	/**
	 * Default tabs
	 * @type {Object}
	 */
	var TABS_DEFAULT = {
		'settings': {
			'title': 'Settings',
			'icon': '/cms/content-manager/images/icon-settings.png',
			'action': 'PageTopSettings'
		},
		'blockbar': {
			'title': 'Insert',
			'icon': '/cms/content-manager/images/icon-add.png',
			'action': 'PageTopBlocks'
		},
		'historybar': {
			'title': 'History',
			'icon': '/cms/content-manager/images/icon-history.png',
			'action': 'PageTopHistory'
		}
	};

	//Shortcut
	var Manager = SU.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'PageTopBar',
		
		/**
		 * No need for template
		 * @type {Boolean}
		 */
		HAS_TEMPLATE: false,
		
		/**
		 * Change place holder to page panel content
		 */
		PLACE_HOLDER: SU.Manager.Page.getPluginWidgets('PluginPanel', true).shift().get('contentBox'),
		
		/**
		 * Currently selected action
		 */
		active_action: null,
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			//Hide content until all widgets are rendered
			this.getPlaceHolder().addClass('hidden');
			
			//Check 'tabs' attribute for additional tab configuration
			var tab_config = Supra.mix({}, TABS_DEFAULT, this.get('tabs') || {});
			this.set('tabs', tab_config);
			
			//Create tabs
			var tabs = this.tabs = new Supra.Tabs({'toggleEnabled': true, 'style': 'dark', 'buttonStyle': 'mid-large'});
			
			for(var id in tab_config) {
				if (Y.Lang.isObject(tab_config[id])) {
					tabs.addTab({"id": id, "title": tab_config[id].title, "icon": tab_config[id].icon});
				}
			}
			
			//On change
			this.tabs.on('activeTabChange', function (event) {
				if (event.newValue) {
					var tabId = event.newValue;
					var config = this.get('tabs');
					var actionId = config[tabId].action;
					var action = SU.Manager.getAction(actionId);
					var content = this.tabs.getTabContent(tabId);
					
					//Insert into tab content
					action.set('placeHolderNode', content);
					
					//Restore everything like it was before previous action was called
					if (this.active_action !== null && this.active_action != actionId) {
						var old_action = SU.Manager.getAction(this.active_action);
						if (old_action.restore) old_action.restore();
					}
					this.active_action = actionId;
					
					if (!Loader.isLoading(actionId) && !Loader.isLoaded(actionId)) {
						action.on('resize', function () {
							this.tabs.fire('resize');
						}, this);
					}
					
					Manager.executeAction(actionId);
				}
			}, this);
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			this.tabs.render(this.getPlaceHolder());
			
			//Show content
			this.getPlaceHolder().removeClass('hidden');
			
			//Show tabs when action is shown / hidden
			this.on('visibleChange', function (evt) {
				this.tabs.set('visible', evt.newVal);
			}, this);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {}
	});
	
});