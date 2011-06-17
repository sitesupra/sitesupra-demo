SU('supra.footer', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	
	//Create Action class
	new Action(Action.PluginFooter, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'PageBottomBar',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Change place holder to page panel content
		 */
		PLACE_HOLDER: SU.Manager.Page.getPluginWidgets('PluginPanel', true).shift().get('contentBox'),
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			//Hide content until all widgets are rendered
			this.getPlaceHolder().addClass('hidden');
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			//Show content
			this.getPlaceHolder().removeClass('hidden');
			
			//Bubble events from widget to action
			this.bubbleEvents(this.footer, ['cancel', 'save']);
			
			//Show footer when action is shown / hidden
			this.on('visibleChange', function (evt) {
				this.footer.set('visible', evt.newVal);
			}, this);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {}
	});
	
});