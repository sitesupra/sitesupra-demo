YUI().add('supra.htmleditor-plugin-paragraph', function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH],
		
		/*
		 * Selects whether pressing return inside a paragraph creates another paragraph or just inserts a <br> tag.
		 * By defaults it's disabled
		 */
		insertBrOnReturn: false
	};
	
	/*
	 * Regular expression to remove whitespace, BR and empty P tags from beginning of HTML
	 */
	var WHITESPACE_REGEX = /^(&nbsp;|\n|\r|\s|<\/?\s?br\s?\/?>)*<p[^>]*>(&nbsp;|\n|\r|\s|<\/?\s?br\s?\/?>)*<\/p>/i;
	
	Supra.HTMLEditor.addPlugin('paragraph', defaultConfiguration, {
		
		/**
		 * Handle keyDown in IE and WebKit browsers to insert BR
		 */
		_onBrKeyDown: function (event) {
			if (!event.stopped && event.keyCode == 13 && !event.shiftKey && !event.alyKey && !event.ctrlKey) {
				var editor = this.htmleditor,
					node = new Y.Node(editor.getSelectedElement());
				
				/*
				if (Y.UA.ie) {
                    if (!sel.anchorNode || (!sel.anchorNode.test(LI) && !sel.anchorNode.ancestor(LI))) {
                        sel._selection.pasteHTML('<br>');
                        sel._selection.collapse(false);
                        sel._selection.select();
                        event.halt();
                    }
                }
				*/
				
                if (Y.UA.webkit) {
                    if (!node.test('LI') && !node.ancestor('LI')) {
                        editor.get('doc').execCommand('insertlinebreak', null);
                        event.halt();
                    }
                }
			}
		},
		
		/**
		 * Remove whitespace, BR and empty P tags from beginning of HTML
		 */
		_removeWhitespaces: function (event, data) {
			data.html = data.html.replace(WHITESPACE_REGEX, '');
		},
		
		/**
		 * On return key insert paragraph
		 */
		_insertParagraph: function (event) {
			if (!event.stopped && event.keyCode == 13 && !event.shiftKey && !event.alyKey && !event.ctrlKey) {
				var node = new Y.Node(this.htmleditor.getSelectedElement());
				if (!node.test('LI') && !node.ancestor('LI')) {
					
					if (Y.UA.gecko) {
						//this.htmleditor.insertHTML('<P></P>');
						//@TODO
					} else if (Y.UA.webkit) {
						
						// Cursor is at the end of the inline node?
						// Create block level element, not inline (eg. <a class="button" />)
						var selected  = this.htmleditor.getSelectedElement(),
							inline    = Supra.HTMLEditor.ELEMENTS_INLINE;
						
						if (selected && selected.tagName.toLowerCase() in inline) {
							var selection = this.htmleditor.selection,
								end       = selection.end,
								length    = end.nodeType == 1 ? end.childNodes.length : end.length,
								tagname   = null;
							
							if (selection.end_offset == length) {
								tagname = this.htmleditor.getSelectedElement('p, li');
								tagname = tagname ? tagname.tagName : 'P';
								
								this.insertHTML('<' + tagname + '></'+ tagname + '>');
								event.halt();
							}
						}
					}
				}
			}
		},
		
		
		/**
		 * Insert after selection
		 * @param {Object} html
		 * @private
		 */
		insertHTML: function (html) {
			if (!html || this.htmleditor.get('disabled')) return;
			
			var selected  = this.htmleditor.selection.end,
				reference = null,
				inline    = Supra.HTMLEditor.ELEMENTS_INLINE,
				srcNode   = this.htmleditor.get('srcNode').getDOMNode(),
				node      = Y.Node.create(html).getDOMNode();
			
			// Find first non-inline element
			while (selected && selected !== srcNode) {
				if (selected.nodeType == 1) {
					// If element is not inline and tags are different (P, LI, UL)
					if (!inline[selected.tagName.toLowerCase()] && selected.tagName != node.tagName) {
						break;
					}
				}
				reference = selected;
				selected = selected.parentNode;
			}
			
			// Insert node
			if (reference && reference.nextSibling) {
				selected.insertBefore(node, reference.nextSibling);
			} else {
				selected.appendChild(node);
			}
			
			this.htmleditor.selectNode(node);
		},
		
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor, configuration) {
			if (configuration.insertBrOnReturn) {
	            try {
	                htmleditor.get('doc').execCommand('insertbronreturn', null, true);
	            } catch (bre) {};
	
	            if (Y.UA.ie || Y.UA.webkit) {
	                htmleditor.get('srcNode').on('keydown', Y.bind(this._onBrKeyDown, this));
	            }
			} else {
				/*
				 * On return key insert P
				 */
				htmleditor.get('srcNode').on('keydown', Y.bind(this._insertParagraph, this));
			}
			
			/*
			 * Remove whitespace from HTML
			 */
			htmleditor.on('getHTML', Y.bind(this._removeWhitespaces, this));
			htmleditor.on('setHTML', Y.bind(this._removeWhitespaces, this));
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