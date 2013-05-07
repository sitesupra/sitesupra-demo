YUI().add('supra.htmleditor-plugin-shortcuts', function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH]
	};
	
	Supra.HTMLEditor.addPlugin('shortcuts', defaultConfiguration, {
		
		/**
		 * Execute command
		 * 
		 * @param {Object} data
		 * @param {String} command
		 * @return True on success, false on failure
		 * @type {Boolean}
		 */
		exec: function (data, command) {
			var res = this.htmleditor.get('doc').execCommand(command, null, false);
			this.htmleditor._changed();
			this.htmleditor.refresh(true);
			return res;
		},
		
		/**
		 * Enable ctrl+b, ctrl+i, etc. shortcuts
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		handleShortcut: function (_, evt) {
			var htmleditor = this.htmleditor,
				allowEditing = htmleditor.editingAllowed && !htmleditor.selection.collapsed,
				res = false;
			
			if (allowEditing && !evt.altKey && (evt.ctrlKey || evt.metaKey)) {
				
				if (evt.keyCode == 66) {
					res = htmleditor.exec('bold');			// CTRL + B
				} else if (evt.keyCode == 73) {
					res = htmleditor.exec('italic');		// CTRL + I
				} else if (evt.keyCode == 85) {
					res = htmleditor.exec('underline');		// CTRL + U
				}/* else if (evt.keyCode == 76) {
					res = htmleditor.exec('insertlink');	// CTRL + L
				}*/
				
				if (res) {
					evt.preventDefault();
				}
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
			//Handle key shortcuts
			htmleditor.on('keyDown', this.handleShortcut, this);
		}
		
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});