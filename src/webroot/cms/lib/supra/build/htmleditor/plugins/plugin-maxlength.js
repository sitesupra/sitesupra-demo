YUI().add('supra.htmleditor-plugin-maxlength', function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_STRING, Supra.HTMLEditor.MODE_TEXT]
	};
	
	Supra.HTMLEditor.addPlugin('paragraph-maxlength', defaultConfiguration, {
		
		/**
		 * Prevent keys if new content will be added and maxlength has been reached
		 * 
		 * @private
		 */
		_onKey: function (event) {
			var keyCode = event.keyCode,
				charCode = event.charCode || event.keyCode,
				editor = this.htmleditor,
				maxLength = editor.get('maxLength');
			
			if (maxLength && !event.stopped && !event.altKey && !event.ctrlKey && !event.metaKey) {
				if (editor.insertCharacterCharCode(charCode)) {
              		if (editor.getContentCharacterCount() >= maxLength) {
              			event.halt();
              		}
              	} 
			}
		},
		
		/**
		 * Check content length after paste
		 * and remove characters if content length exceeds maxLength 
		 * 
		 * @private 
		 */
		_afterPaste: function (event) {
			var editor    = this.htmleditor,
				maxlength = editor.get('maxLength'),
				srcNode   = null,
				nodes     = null,
				count     = 0,
				remove    = 0,
				text      = '';
			
			if (maxlength) {
				srcNode = editor.get('srcNode');
				count = editor.getContentCharacterCount();
				
				if (count >= maxlength) {
					remove = count - maxlength;
					nodes = srcNode.get('childNodes').getDOMNodes();
					
					for (var i=nodes.length-1; i>=0; i--) {
						if (nodes[i].nodeType == 1) {
							// BR tag, remove it
							nodes[i].parentNode.removeChild(nodes[i]);
							remove--;
						} else if (nodes[i].nodeType == 3) {
							// Text node
							text = nodes[i].textContent;
							if (text.length) {
								if (text.length > remove) {
									// Truncate text
									nodes[i].textContent = text.substr(0, text.length - remove);
									remove = 0;
								} else {
									// Remove node
									remove -= text.length;
									nodes[i].parentNode.removeChild(nodes[i]);
								}
							}
						}
						
						if (remove == 0) {
							// We have removed needed amount of characters
							return;
						}
					}
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
			htmleditor.on('keyDown', this._onKey, this);
			htmleditor.on('afterPaste', this._afterPaste, this);
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