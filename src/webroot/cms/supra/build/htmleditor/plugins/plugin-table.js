YUI().add('supra.htmleditor-plugin-table', function (Y) {
	
	var defaultConfiguration = {
	};
	
	SU.HTMLEditor.addPlugin('table', defaultConfiguration, {
		
		/**
		 * Insert table
		 */
		insertTable: function () {
			
		},
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor) {
			// Add command
			htmleditor.addCommand('inserttable', Y.bind(this.insertTable, this));
			
			// When double clicking on link show popup
			var container = htmleditor.get('srcNode');
			
			var toolbar = htmleditor.get('toolbar');
			var button = toolbar ? toolbar.getButton('inserttable') : null;
			if (button) {
				
				//When un-editable node is selected disable toolbar button
				htmleditor.on('editingAllowedChange', function (event) {
					button.set('disabled', !event.allowed);
				});
			}
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {}
		
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});