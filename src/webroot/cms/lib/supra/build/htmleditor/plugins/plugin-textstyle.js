YUI().add('supra.htmleditor-plugin-textstyle', function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_BASIC, Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH],
		
		/* List of document commands */
		commands: ['bold', 'italic', 'underline', 'strikethrough']
	};
	
	/*
	 * Handle BOLD, ITALIC, UNDERLINE and STRIKETHROUGH commands
	 */
	Supra.HTMLEditor.addPlugin('textstyle', defaultConfiguration, {
		
		/**
		 * List of commands
		 * @type {Array}
		 */
		commands: null,
		
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
		 * Bind command to a button
		 * 
		 * @param {String} command
		 */
		bindButton: function (command) {
			var htmleditor = this.htmleditor,
				doc = htmleditor.get('doc'),
				toolbar = htmleditor.get('toolbar'),
				button = toolbar ? toolbar.getButton(command) : null;
			
			if (button) {
				this.buttons[command] = button;
			}
		},
		
		/**
		 * When selection changes update button states
		 * @param {Object} event
		 */
		handleSelectionChange: function (event) {
			var htmleditor = this.htmleditor,
				allowEditing = htmleditor.editingAllowed && !htmleditor.selection.collapsed,
				down,
				buttons = this.buttons,
				id;
			
			for(id in buttons) {
				try {
					down = htmleditor.get('doc').queryCommandState(id);
				} catch (err) {
					//If selected content is not 'contenteditable' error is thrown
					down = false;
				}
				
				buttons[id].set('disabled', !allowEditing);
				buttons[id].set('down', down);
			}
		},
		
		/**
		 * When editing allowed changes update button states 
		 * @param {Object} event
		 */
		handleEditingAllowChange: function (event) {
			var id,
				disabled = !event.allowed,
				buttons = this.buttons;
			
			for(id in buttons) {
				buttons[id].set('disabled', disabled);
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
			
			// Commands
			if (configuration && Y.Lang.isArray(configuration.commands)) {
				var commands = this.commands = configuration.commands;
			} else {
				//If there are no commands, then plugin is useless
				return false;
			}
			
			// Bind commands to buttons
			var i = 0,
				imax = commands.length,
				execCallback = Y.bind(this.exec, this);
			
			for(; i < imax; i++) {
				htmleditor.addCommand(commands[i], execCallback);
				this.bindButton(commands[i]);
			}
			
			//When un-editable node is selected disable toolbar button
			htmleditor.on('editingAllowedChange', this.handleEditingAllowChange, this);
			
			//When selection changes update buttons
			htmleditor.on('selectionChange', this.handleSelectionChange, this);
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