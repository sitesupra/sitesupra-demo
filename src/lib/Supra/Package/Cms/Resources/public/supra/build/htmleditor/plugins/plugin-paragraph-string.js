YUI().add('supra.htmleditor-plugin-paragraph-string', function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_STRING]
	};
	
	Supra.HTMLEditor.addPlugin('paragraph-string', defaultConfiguration, {
		
		/**
		 * Prevent return key
		 */
		_onReturnKey: function (event) {
			if (!event.stopped && event.keyCode == 13 && !event.altKey && !event.ctrlKey) {
				event.preventDefault();
			}
		},
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor, configuration) {
			htmleditor.get('srcNode').on('keydown', Y.bind(this._onReturnKey, this));
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