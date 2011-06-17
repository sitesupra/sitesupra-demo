Supra('action-panel', 'action-form', function (Y) {
	
	//Shortcut
	var Action = Supra.Manager.Action;
	
	//Create Action class
	new Action(Action.PluginPanel, Action.PluginForm, {
		/**
		 * Name is used to identify action.
		 * When action is created Supra.Manager.Action.SampleForm will be set.
		 * When action is executed Supra.Manager.SampleForm will be created.
		 * 
		 * Name is also used to reference action:
		 * 		Supra.Manager.executeAction('SampleForm', {"id": 12});
		 * 
		 * @type {String}
		 */
		NAME: 'SampleForm',
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 */
		initialize: function () {
			//URIs
			this.form
				.setUrlLoad('/getUser')
				.setUrlSave('/saveSave');
			
			//Set field types, because they don't match defaults
			this.form
				.setField('id',   {'type': 'string'})
				.setField('name', {'type': 'name'});
		},
		
		/**
		 * Reset action state
		 */
		reset: function () {},
		
		/**
		 * Execute action
		 */
		execute: function () {}
	});
	
});
