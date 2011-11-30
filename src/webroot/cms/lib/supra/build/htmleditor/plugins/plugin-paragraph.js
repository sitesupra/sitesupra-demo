YUI().add('supra.htmleditor-plugin-paragraph', function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [SU.HTMLEditor.MODE_SIMPLE, SU.HTMLEditor.MODE_RICH],
		
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
	
	SU.HTMLEditor.addPlugin('paragraph', defaultConfiguration, {
		
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
						//var tagName = node
						//@TODO
					}
					
					//event.halt();
				}
			}
		},
		
		
		/**
		 * Insert after selection
		 * @param {Object} html
		 */
		insertHTML: function (html) {
			if (!html || this.get('disabled')) return;
			
			
			/*
			var target = this.selection.end,
			    html = SU.Y.Node.create(html),
				nodes = SU.Y.Node.getDOMNode(html),
				inline = false;
			
			nodes = nodes.nodeType != 11 ? [nodes] : nodes.childNodes;
			
			if (target.nextSibling) {
				target = target.nextSibling;
				for(var i=nodes.length-1; i>=0; i--) {
					target.parentNode.insertBefore(nodes[i],target);
				}
			} else {
				target = target.parentNode;
				for(var i=0,ii=nodes.length; i<ii; i++) {
					target.appendChild(nodes[i]);
				}
			}
			*/
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