Supra('action-panel', 'action-datagrid', function (Y) {
	
	//Shortcut
	var Action = Supra.Manager.Action;
	
	//Create Action class
	new Action(Action.PluginPanel, Action.PluginDataGrid, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Root',
		
		/**
		 * Automatically load template and insert before initializing action
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 */
		initialize: function () {
			//Don't show close button
			this.panel
				.setCloseVisible(false);
			
			//Set request URI
			this.datagrid
				.setRequestUri('/json/getUserList');
			
			//Set columns
			this.datagrid
				.addColumn({'id': 'id', 'label': 'ID'})
				.addColumn({'id': 'name', 'label': 'Name'});
			
			//Handle row click
			this.datagrid
				.on('row:click', function (data) {
					//Data is like {"id": 1, "name": "John Doe"}
					Supra.Manager.executeAction('SampleForm', data);
				});
		}
	});
	
});