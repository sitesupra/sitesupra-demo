YUI().add('supra.htmleditor-plugin-insertlink', function (Y) {
	
	var defaultConfiguration = {
	};
	
	SU.HTMLEditor.addPlugin('insertlink', defaultConfiguration, {
		
		/**
		 * Insert link around current selection
		 */
		insertLink: function (href) {
			//@TODO allow to pass 'href' as argument instead of showing prompt
			if (!this.htmleditor.editingAllowed) return;
			
			var htmleditor = this.htmleditor,
				selection = htmleditor.getSelection();
			
			//If in current selection is a link then edit it instead of creating new
			var nodes = htmleditor.findNodesInSelection(selection, 'a');
			
			if (nodes && nodes.size())
			{
				//Edit selected link
				this.editLink({
					'currentTarget': nodes.item(0)
				});
				
				//Confirm  that command was executed successfully 
				return true;
			}
			else if (selection.collapsed)
			{
				//Cancel if no text is selected
				return false;
			}
			else if (htmleditor.isSelectionEditable(selection))
			{
				//Create new link
				var callback = Y.bind(this.insertLinkConfirmed, this);
				this.prompt('/', selection, callback);
				
				//Confirm  that command was executed successfully
				return true;
			}
			
			return false;
		},
		
		/**
		 * After user entered value in prompt insert link
		 * 
		 * @param {Object} event
		 */
		insertLinkConfirmed: function (event) {
			if (event.button == 'ok') {
				var htmleditor = this.htmleditor;
				
				//Restore selection
				htmleditor.setSelection(event.data);
				
				//Insert link
				var text = this.htmleditor.getSelectionText(),
					href = this.normalizeHref(event.value),
					html = '<a href="' + href + '">' + text + '</a>';
					
				this.htmleditor.replaceSelection(html, null);
			}
		},
		
		/**
		 * Double clicking link must open prompt to enter new link url
		 * 
		 * @param {Object} event Event
		 */
		editLink: function (event) {
			var target = event.currentTarget;
			if (!this.htmleditor.editingAllowed || !this.htmleditor.isEditable(target)) return;
			
			//Get current value
			var href = this.normalizeHref(target.getAttribute('href')),
				callback = Y.bind(this.editLinkConfirmed, this);
			
			this.prompt(href, target, callback);
		},
		
		/**
		 * After user entered value in prompt change link
		 * 
		 * @param {Object} event
		 */
		editLinkConfirmed: function (event) {
			if (event.button == 'ok') {
				var href = event.value;
				
				if (href) {
					//Change href
					href = this.normalizeHref(href);
					event.data.setAttribute('href', href);
				} else {
					//Insert all link children nodes before link and remove <A>
					var node = event.data;
					node.insert(node.get('childNodes'), 'before').remove();
					
					//Trigger selection change event
					this.htmleditor.refresh();
				}
			}
		},
		
		/**
		 * Normalize link by removing domain
		 * 
		 * @param {String} href
		 * @return Normalized domain
		 * @type {String}
		 */
		normalizeHref: function (href) {
			var domain = document.location.protocol + '//' + document.location.host;
			return href.replace(domain, '') || '/';
		},
		
		/**
		 * Show link prompt
		 * 
		 * @param {String} href
		 * @param {Object} target
		 * @param {Function} callback
		 */
		prompt: function (href, data, callback) {
			if (!callback) return;
			
			//@TODO Replace with page selection when it's ready
			
			var value = prompt('Enter link address:', href);
			if (value !== null) {
				callback({
					'button': 'ok',
					'data': data,
					'value': value
				});
			}
		},
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor) {
			// Add command
			htmleditor.addCommand('insertlink', Y.bind(this.insertLink, this));
			
			// When double clicking on link show popup
			var container = htmleditor.get('srcNode');
			container.delegate('dblclick', Y.bind(this.editLink, this), 'a');
			
			var toolbar = htmleditor.get('toolbar');
			var button = toolbar ? toolbar.getButton('insertlink') : null;
			if (button) {
				
				//When un-editable node is selected disable toolbar button
				htmleditor.on('editingAllowedChange', function (event) {
					button.set('disabled', !event.allowed);
				});
				
				//If there is no text selection disable toolbar button
				htmleditor.on('selectionChange', function (event) {
					var allowEditing = false, down = false;
					
					//Check if cursor is inside link
					var node = this.getSelectedElement();
					if (node && node.tagName == 'A') {
						if (this.editingAllowed) allowEditing = true;
						down = true;
					} else if (this.editingAllowed) {
						//Check if there is text selection
						if (!this.selection.collapsed) {
							allowEditing = true;
						}
					}
					
					button.set('disabled', !allowEditing);
					button.set('down', down);
				});
			}
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