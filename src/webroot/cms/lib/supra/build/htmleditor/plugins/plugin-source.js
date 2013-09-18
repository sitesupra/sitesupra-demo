YUI().add('supra.htmleditor-plugin-source', function (Y) {
	
	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH]
	};
	
	Supra.HTMLEditor.addPlugin('source', defaultConfiguration, {
		
		showSourceEditor: function () {
			
			this.htmleditor.resetSelection();
			this.htmleditor.fire('nodeChange', {});
			
			if (Manager.getAction('PageContentSettings')) {
				Manager.PageContentSettings.hide();
			}
			
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
			this.htmleditor._changed();
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
			
			// Toolbar button
			button.set("visible", true);
			
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
	
}, YUI.version, {'requires': ['supra.manager', 'supra.htmleditor-base']});