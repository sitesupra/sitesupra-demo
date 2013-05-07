YUI().add('supra.htmleditor-commands', function (Y) {
	//Invoke strict mode
	"use strict";
	
	Y.mix(Supra.HTMLEditor.prototype, {
		
		/**
		 * Plugin instances
		 */
		commands: {},
		
		/**
		 * Add command
		 * 
		 * @param {String} id
		 * @param {Function} callback
		 */
		addCommand: function (id, callback) {
			if (!(id in this.commands)) {
				this.commands[id] = [];
			}
			
			this.commands[id].push(callback);
		},
		
		/**
		 * Execute command
		 * 
		 * @param {String} action
		 */
		exec: function (command, data) {
			var disabled = this.get('disabled');
			if (disabled || !this.editingAllowed) return;
			
			if (command in this.commands) {
				var commands = this.commands[command],
					i=commands.length-1;
					
				for(; i >= 0; i--) {
					if (commands[i](data, command) === true) {
						//New node may have been added
						if (!this.refresh()) {
							//Or maybe only style changed
							this.fire('selectionChange');
							this.fire('nodeChange');
						};
						
						return true;
					}
				}
			}
			
			return false;
		}
	});

	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});