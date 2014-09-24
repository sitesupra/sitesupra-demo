Supra(function (Y) {
	
	//Shortcut
	var Action = Supra.Manager.Action;
	
	/*
	 * Create Action class
	 *     Action.PluginPanel will create panel around content
	 *     Action.PluginForm will create form
	 *     Action.PluginFooter will create footer and bind it to form and panel
	 */
	new Action(Action.PluginPanel, Action.PluginForm, Action.PluginFooter, {
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
		 * Set configuration/properties
		 */
		initialize: function () {
			
			//Set 'path'
			this.form.setInput({'id': 'path', 'path': '/main/'});
			
			//Create inputs
			var select_values = [{'id': 'a', 'title': 'Value A'}, {'id': 'b', 'title': 'Value B'}];
			
			//Generate some more inputs with JS
			this.form.addInput({'id': 'jsstring', 'label': 'JS String', 'type': 'String'});
			this.form.addInput({'id': 'jscheckbox', 'label': 'JS Checkbox', 'type': 'Checkbox'});
			this.form.addInput({'id': 'jspath', 'label': 'JS Path', 'type': 'Path', 'path': '/main/'});
			this.form.addInput({'id': 'jsselect', 'label': 'JS Select', 'type': 'Select', 'values': select_values, 'value': 'a'});
			this.form.addInput({'id': 'jsselectb', 'label': 'JS Select buttons', 'type': 'SelectList', 'values': select_values, 'value': 'a'});
			
			//Update width
			this.panel.set('width', '500px');
			
			//Add 'reset' button
			this.footer.addButton({
				'id': 'reset',
				'label': 'Reset'
			});
		},
		
		/**
		 * On input value change output debug
		 * 
		 * @param {Object} event Event
		 * @private
		 */
		onInputChange: function (event) {
			var node = this.one('#debug'),
				value = typeof event.newVal != 'undefined' ? event.newVal : event.target.get('value'),
				output = '<p>' + event.target.get('id') + ':' + event.type + '  =>  ' + value + '</p>';
			
			node.append(Y.Node.create(output));
		},
		
		/**
		 * After widgets are rendered add event listeners
		 */
		render: function () {
			
			//On input change do small debug
			Y.each(this.form.getInputs(), function (input) {
				input.on('change', this.onInputChange, this);
				input.on('valueChange', this.onInputChange, this);
			}, this);
			
			//On 'reset' button click reset form
			this.footer.getButton('reset').on('click', this.form.resetValues, this.form);
			
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
		}
	});
	
});
