SU('supra.editor-toolbar', function (Y) {

	//Shortcut
	var Action = SU.Manager.Action;
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'EditorToolbar',
		
		/**
		 * No template for toolbar
		 * @type {Boolean}
		 */
		HAS_TEMPLATE: false,
		
		/**
		 * Style toolbar
		 * @type {Boolean}
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * List of buttons
		 * @type {Object}
		 */
		buttons: {},
		
		/**
		 * Tab instance
		 * @type {Object}
		 * @see Supra.Tabs
		 */
		tabs: {},
		
		/**
		 * Change place holder to document body
		 * @type {HTMLElement}
		 * @private
		 */
		PLACE_HOLDER: SU.Manager.Page.getPluginWidgets('PluginPanel', true).shift().get('contentBox'),
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 * @private
		 */
		initialize: function () {
			
			this.toolbar = new Supra.EditorToolbar();
			
		},
		
		/**
		 * Returns Supra.EditorToolbar instance
		 * 
		 * @return Toolbar instance
		 * @type {Object}
		 */
		getToolbar: function () {
			return this.toolbar;
		},
		
		/**
		 * Render widgets
		 * 
		 * @private
		 */
		render: function () {
			
			this.toolbar.render(this.getPlaceHolder());
			this.toolbar.hide();
			
			this.on('visibleChange', function (evt) {
				this.toolbar.set('visible', evt.newVal);
			}, this);
		}
	});
	
});