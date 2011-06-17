SU('supra.form', function (Y) {

	//Shortcut
	var Action = SU.Manager.Action;
	
	/**
	 * Page preview action
	 * Shows preview/thumbnail of page
	 * 
	 * @alias SU.Manager.PagePreview
	 */
	new Action(Action.PluginPanel, Action.PluginForm, Action.PluginFooter, {
		
		NAME: 'PageNew',
		
		/**
		 * PageNew action has stylesheet
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 */
		initialize: function () {
			
			// Form settings
			this.form.setInput({'id': 'address', 'type': 'Path'})
					 .setInput({'id': 'template', 'type': 'Template', 'templateRequestUri': this.getDataPath()});
			
			// Panel settings
			this.panel.setCloseVisible(true)
					  .setArrowPosition([SU.Panel.ARROW_L, SU.Panel.ARROW_C])
					  .setArrowVisible(true)
					  .set('constrain', document.body);
		},
		
		/**
		 * Adjust panel position
		 * 
		 * @param {Object} data Page data
		 * @private
		 */
		setPanelPosition: function (node) {
			//Align property node value should be HTMLElement, not Y.Node
			var node = Y.Node.getDOMNode(node);
			
			//Align with button
    		this.panel.set('align', {'node': node, 'points': [Y.WidgetPositionAlign.LC, Y.WidgetPositionAlign.RC]});
			
			//Change style
			this.panel.setAttrs({
				'arrowAlign': node
			});
		},
		
		/**
		 * Execute action
		 * 
		 * @param {String} path Path to image
		 * @param {Object} node Target node
		 */
		execute: function (node) {
			//Align panel to the middle of the selected node
			this.setPanelPosition(node);
			
			//Focus title
			this.form.getInput('title').focus();
		}
		
	});
	
});
