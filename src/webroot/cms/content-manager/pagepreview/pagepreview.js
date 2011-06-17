SU(function (Y) {

	//Shortcut
	var Action = SU.Manager.Action;
	
	/**
	 * Page preview action
	 * Shows preview/thumbnail of page
	 * 
	 * @alias SU.Manager.PagePreview
	 */
	new Action(Action.PluginPanel, {
		
		NAME: 'PagePreview',
		
		/**
		 * PageInfo action has stylesheet
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Image node, Y.Node
		 * @type {Object}
		 */
		node_image: null,
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 */
		initialize: function () {
			
			// Page preview image node
			this.node_image = this.getContainer().one('img');
			
			// Panel settings
			this.panel.setArrowPosition([SU.Panel.ARROW_L, SU.Panel.ARROW_C])
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
		execute: function (path, node) {
			//Align panel to the middle of the selected node
			this.setPanelPosition(node);
			
			//Change image
			this.node_image.set('src', path);
		}
		
	});
	
});
