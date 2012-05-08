Supra('supra.input', function (Y) {
	
	//Shortcut
	var Action = Supra.Manager.Action;
	
	//Create Action class
	new Action(Action.PluginPanel, Action.PluginForm, Action.PluginFooter, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Popup',
		
		/**
		 * Action doesn't have a template
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: false,
		
		/**
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 */
		initialize: function () {
			//Show close button
			this.panel.setCloseVisible(true);
			
			//Set file upload
			this.form.setInput({
				'id': 'file',
				'type': 'Fileupload'
			});
		},
		
		execute: function () {
			//Show in the middle of the screen
			this.panel.centered();
		}
	});
	
});