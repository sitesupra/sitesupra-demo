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
		
		toggleSourceEditor: function () {
			var button = this.htmleditor.get('toolbar').getButton('source');
			if (button.get('down')) {
				Manager.executeAction('PageSourceEditor', {
					'html': this.htmleditor.getHTML(),
					'callback': Y.bind(this.updateSource, this)
				});
			} else {
				this.hideSourceEditor();
			}
		},
		
		/**
		 * Hide media library bar
		 */
		hideSourceEditor: function () {
			Manager.getAction('PageSourceEditor').hide();
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
			htmleditor.addCommand('source', Y.bind(this.toggleSourceEditor, this));
			
			if (button) {
				//When media library is shown/hidden make button selected/unselected
				sourceeditor.after('visibleChange', function (evt) {
					button.set('down', evt.newVal);
				});
				
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