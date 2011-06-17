SU('supra.form', function (Y) {
	
	//Shortcut
	var Action = SU.Manager.Action;
	
	//Create Action class
	new Action(Action.PluginPanel, Action.PluginForm, Action.PluginFooter, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Popup',
		
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