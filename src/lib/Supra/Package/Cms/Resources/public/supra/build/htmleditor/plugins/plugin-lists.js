/**
 * Block level element formatting (UL, OL)
 */
YUI().add('supra.htmleditor-plugin-lists', function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH],
		
		/* List types */
		lists: ['ul', 'ol']
	};
	
	Supra.HTMLEditor.addPlugin('lists', defaultConfiguration, {
		
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
				var res = this.htmleditor.get('doc').execCommand(this.commands[command], false, null);
				this.htmleditor._changed();
				return res;
			} else if (command == 'indent') {
				var res = this.htmleditor.get('doc').execCommand('indent', false, null);
				this.htmleditor._changed();
				return res;
			} else if (command == 'outdent') {
				var res = this.htmleditor.get('doc').execCommand('outdent', false, null);
				this.htmleditor._changed();
				return res;
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
				rootNode = this.htmleditor.get('srcNode').getDOMNode(),
				down = false,
				buttons = this.buttons,
				selected = null,
				i = null;
			
			while (node) {
				if (node.tagName == 'IMG') {
					// Image is special element, while image is selected
					// don't allow editing anything
					allowEditing = false;
					break;
				}
				if (node.tagName in buttons) {
					selected = node.tagName;
					break;
				}
				if (node === rootNode) break;
				node = node.parentNode;
			}
			
			for(i in buttons) {
				buttons[i].set('down', i == selected);
				buttons[i].set('disabled', !allowEditing);
			}
			
			buttons.INDENT.set('visible', !!selected);
			buttons.OUTDENT.set('visible', !!selected);
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
			var lists = ['indent', 'outdent'].concat(this.lists),
				i = 0,
				imax = lists.length,
				execCallback = Y.bind(this.exec, this),
				button;
			
			for(; i < imax; i++) {
				this.htmleditor.addCommand(lists[i], execCallback);
				this.bindButton(lists[i]);
			}
			
			// Show buttons
			lists = this.lists;
			for (i=0, imax=lists.length; i<imax; i++) {
				button = this.buttons[lists[i].toUpperCase()];
				if (button) {
					button.set('visible', true);
				}
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