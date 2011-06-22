/**
 * Block level element formatting (UL, OL)
 */
YUI().add('supra.htmleditor-plugin-lists', function (Y) {
	
	var defaultConfiguration = {
		lists: ['ul', 'ol']
	};
	
	SU.HTMLEditor.addPlugin('lists', defaultConfiguration, {
		
		lists:  null,
		commands: {'ul': 'insertunorderedlist', 'ol': 'insertorderedlist'},
		
		buttons: {},
		
		/**
		 * Execute command
		 * 
		 * @param {Object} data
		 * @param {String} command
		 */
		exec: function (data, command) {
			if (command in this.commands) {
				return this.htmleditor.get('doc').execCommand(this.commands[command], false, null);
			} else {
				return false;
			}
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
			var allowEditing = this.htmleditor.editingAllowed;
			
			var node = this.htmleditor.getSelectedElement(),
				down = false,
				buttons = this.buttons,
				selected = null,
				i = null;
			
			while(node) {
				if (node.tagName in buttons) {
					selected = node.tagName;
					break;
				}
				node = node.parentNode;
			}
			
			for(i in buttons) {
				buttons[i].set('down', i == selected);
				buttons[i].set('disabled', !allowEditing);
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
			if (!configuration) return;
			
			this.lists = (Y.Lang.isArray(configuration.lists) ? configuration.lists : []);
			this.buttons = {};
			
			// Add command
			var lists = this.lists,
				i = 0,
				imax = lists.length,
				execCallback = Y.bind(this.exec, this);
				
			for(; i < imax; i++) {
				this.htmleditor.addCommand(lists[i], execCallback);
				this.bindButton(lists[i]);
			}
			
			//When un-editable node is selected disable toolbar button
			this.htmleditor.on('editingAllowedChange', this.handleEditingAllowChange, this);
			this.htmleditor.on('nodeChange', this.handleNodeChange, this);
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