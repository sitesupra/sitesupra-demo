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
				
				//var node = new Y.Node(this.htmleditor.getSelectedElement());
				//if (!node.test('LI') && !node.ancestor('LI')) {
					
					if (Y.UA.gecko) {
						//this.htmleditor.insertHTML('<P></P>');
						//@TODO
					} else if (Y.UA.webkit) {
						
						// Cursor is at the end of the inline node?
						// Create block level element, not inline (eg. <a class="button" />)
						var selected  = this.htmleditor.getSelectedElement(),
							inline    = Supra.HTMLEditor.ELEMENTS_INLINE,
							tagName   = '',
							node      = null,
							length    = 0;
						
						if (selected) {
							if (this.htmleditor.isCursorAtTheEndOf()) {
								node    = this.htmleditor.getSelectedElement('p, li, td, th');
								tagname = node ? node.tagName : 'P';
								
								if (tagname == 'TD' || tagname == 'TH') {
									// If inside TD or TH then insert <br />
									this._onBrKeyDown(event);
								} else if (tagname == 'LI') {
									// If inside LI then insert new li if there is non-selected content
									// inside this li, otherwise insert P after content
									 
									if (this.htmleditor.isNodeEmpty(node)) {
										if (this.htmleditor.getLastChild(node.parentNode) === node) {
											// Empty LI and it's last in the list, insert paragraph after list 
											this.insertHTML('P', node.parentNode);
											node.parentNode.removeChild(node);
											event.halt();
										} else {
											// Empty LI, but not last in the list, split list into two
											var doc  = this.htmleditor.get('doc'),
												list = doc.createElement(node.parentNode.tagName),
												p    = doc.createElement('P'),
												tmp  = null;
											
											this.htmleditor.insertAfter(list, node.parentNode);
											this.htmleditor.insertAfter(p, node.parentNode);
											
											while (node.nextSibling) {
												list.appendChild(node.nextSibling);
											}
											
											node.parentNode.removeChild(node);
											
											// Move cursor to P
											this.htmleditor.setSelection({'start': p, 'end': p, 'start_offset': 0, 'end_offset': 0});
											
											event.halt();
										}
									} else if (this.htmleditor.isAllNodeSelected(node)) {
										// All LI is selected, remove it and insert P after list
										this.insertHTML('P', node.parentNode);
										node.parentNode.removeChild(node);
										event.halt();
									} else {
										// Not empty LI, default behaviour of inserting LI is ok
									}
								} else {
									if (!this.htmleditor.selection.collapsed) {
										this.htmleditor.replaceSelection('');
									}
									this.insertHTML(tagname);
									event.halt();
								}
							} else {
								console.log('NOT AT THE END!');
							}
						}
					}
				
				//}
			}
		},
		
		
		/**
		 * Insert after selection
		 * @param {Object} html
		 * @private
		 */
		insertHTML: function (tagname, target) {
			if (!tagname || this.htmleditor.get('disabled')) return;
			
			var html      = '<' + tagname + '></' + tagname + '>',
				selected  = target ? target : this.htmleditor.selection.end,
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
			var SUP_BLOCK = {'P': 'P', 'H1': 'H1', 'H2': 'H2', 'H3': 'H3', 'H4': 'H4', 'H5': 'H5', 'UL': 'UL', 'OL': 'OL'},
				PAR_BLOCK = {'P': 'P', 'H1': 'H1', 'H2': 'H2', 'H3': 'H3', 'H4': 'H4', 'H5': 'H5', 'UL': 'UL', 'OL': 'OL', 'DIV': 'DIV'};
			
			if (SUP_BLOCK[tagname] && PAR_BLOCK[selected.tagName] && selected !== srcNode) {
				// Trying to insert P into H1, H1 into P, etc.
				// Don't allow that, insert this tag after selected element
				if (selected.nextSibling) {
					selected.parentNode.insertBefore(node, selected.nextSibling);
				} else {
					selected.parentNode.appendChild(node);
				}
			} else {
				if (reference && reference.nextSibling) {
					selected.insertBefore(node, reference.nextSibling);
				} else {
					selected.appendChild(node);
				}
			}
			
			//this.htmleditor.selectNode(node);
			
			this.htmleditor.setSelection({
				'start': node,
				'end': node,
				'start_offset': 0,
				'end_offset': node.childNodes.length
			});
		},
		
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor, configuration) {
			window.htmleditor = htmleditor;
			
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