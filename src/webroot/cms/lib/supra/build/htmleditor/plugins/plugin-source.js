YUI().add('supra.htmleditor-plugin-source', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [SU.HTMLEditor.MODE_SIMPLE, SU.HTMLEditor.MODE_RICH]
	};
	
	SU.HTMLEditor.addPlugin('source', defaultConfiguration, {
		
		showSourceEditor: function () {
			Manager.executeAction('PageSourceEditor', {
				'html': this.htmleditor.getHTML(),
				'callback': Y.bind(this.updateSource, this)
			});
		},
		
		/**
		 * Update source
		 * 
		 * @param {String} html HTML code
		 */
		updateSource: function (html) {
			this.htmleditor.setHTML(html);
		},
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor, configuration) {
			var sourceeditor = Manager.getAction('PageSourceEditor'),
				toolbar = htmleditor.get('toolbar'),
				button = toolbar ? toolbar.getButton('source') : null;
			
			// Add command
			htmleditor.addCommand('source', Y.bind(this.showSourceEditor, this));
			
			if (button) {
				//When un-editable node is selected disable mediasidebar toolbar button
				htmleditor.on('editingAllowedChange', function (event) {
					button.set('disabled', !event.allowed);
				});
			}
			
			//On editor disable hide source editor
			htmleditor.on('disable', this.hideMediaSidebar, this);
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