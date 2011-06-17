/**
 * Block level element formatting (H1, H2, H3, H4, H5, H6, P)
 */
YUI().add('supra.htmleditor-plugin-formats', function (Y) {
	
	var defaultConfiguration = {
		formats: ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p']
	};
	
	SU.HTMLEditor.addPlugin('formats', defaultConfiguration, {
		
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
		
		
		exec: function (data, command) {
			this.htmleditor.get('doc').execCommand('formatblock', false, command);
			return true;
		},
		
		bindButton: function (format) {
			var htmleditor = this.htmleditor;
			var toolbar = htmleditor.get('toolbar');
			var button = toolbar ? toolbar.getButton(format) : null;
			if (button) {
				this.buttons[format.toUpperCase()] = button;
			}
		},
		
		/**
		 * When node changes update button states
		 * @param {Object} event
		 */
		handleNodeChange: function (event) {
			var htmleditor = this.htmleditor,
				allowEditing = htmleditor.editingAllowed,
				buttons = this.buttons,
				down,
				format,
				//currentFormat is empty string (or false in Safari) or "H1", "P", ...
				currentFormat = htmleditor.get('doc').queryCommandValue('formatblock');
				if (currentFormat) currentFormat = currentFormat.toUpperCase();
			
			for(format in buttons) {
				down = (currentFormat == format ? true : false);
				
				buttons[format].set('disabled', !allowEditing);
				buttons[format].set('down', down);
			}
		},
		
		/**
		 * When editing allowed changes update button states 
		 * @param {Object} event
		 */
		handleEditingAllowChange: function (event) {
			var i,
				disabled = !event.allowed,
				buttons = this.buttons;
			
			for(i in buttons) {
				buttons[i].set('disabled', disabled);
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
			this.buttons = {};
			
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
				this.bindButton(formats[i]);
			}
			
			//When un-editable node is selected disable toolbar button
			this.htmleditor.on('editingAllowedChange', this.handleEditingAllowChange, this);
			this.htmleditor.on('nodeChange', this.handleNodeChange, this);
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