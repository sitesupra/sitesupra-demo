/**
 * Block level element formatting (H1, H2, H3, H4, H5, H6, P)
 */
YUI().add('supra.htmleditor-plugin-formats', function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH],
		
		/* Available formats */
		formats: ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p']
	};
	
	Supra.HTMLEditor.addPlugin('formats', defaultConfiguration, {
		
		/**
		 * List of formats (from configuration)
		 * @type {Object}
		 */
		formats: null,
		
		/**
		 * List of buttons
		 * @type {Object}
		 */
		buttons: null,
		
		/**
		 * Execute command
		 * 
		 * @param {Object} data
		 * @param {String} command Command
		 * @private
		 */
		exec: function (data, command) {
			var htmleditor = this.htmleditor,
				doc = this.htmleditor.get('doc'),
				selection = null;
			
			if (Y.UA.ie) {
				//If selection length is 0 then IE fails to change node
				selection = htmleditor.selection;
				
				if (selection.start == selection.end && selection.start_offset == selection.end_offset) {
					if (selection.end_offset == selection.end.length) {
						selection.start_offset--;
					} else {
						selection.end_offset++;
					}
				}
				
				htmleditor.setSelection(selection);
			}
			
			//Change to P, H1, H2, ...
			doc.execCommand('formatblock', false, '<' + command + '>');
			
			//Trigger "change" event on editor
			htmleditor._changed();
			
			return true;
		},
		
		/**
		 * Returns current format
		 * 
		 * @return Current format
		 * @type {String}
		 */
		getCurrentFormat: function () {
			var htmleditor = this.htmleditor,
				selectedElement = null,
				//currentFormat is empty string (false in Safari) or "H1", "P", ...
				//in IE currentFormat is "Normal" even for elements without tag or "Heading 1", ...
				currentFormat = null;
				
			try {
				currentFormat = htmleditor.get('doc').queryCommandValue('formatblock');
				
				//Normalize IE value
				if (Y.UA.ie) {
					currentFormat = currentFormat.replace('Heading ', 'H');
					
					//Check for P tag
					if (currentFormat == 'Normal') {
						selectedElement = htmleditor.getSelectedElement('P');
						if (selectedElement) {
							currentFormat = 'P';
						}
					}
				}
			} catch (err) {
				//If selected text is not 'contenteditable' then error is thrown
				currentFormat = '';
			}
			
			if (currentFormat) currentFormat = currentFormat.toUpperCase();
			return currentFormat;
		},
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor, configuration) {
			// Formats
			if (configuration && Y.Lang.isArray(configuration.formats)) {
				var formats = this.formats = configuration.formats;
			} else {
				//If there are no formats, then plugin is useless
				return false;
			}
			
			// Add command
			var i = 0,
				imax = formats.length,
				execCallback = Y.bind(this.exec, this);
				
			for(; i < imax; i++) {
				this.htmleditor.addCommand(formats[i], execCallback);
			}
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {
			delete(this.buttons);
			delete(this.formats);
		}
		
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});