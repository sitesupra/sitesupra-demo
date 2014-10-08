YUI().add('supra.htmleditor-plugin-paste', function (Y) {
	
	var defaultConfiguration = {
		/* 
		 * Modes definition is missing, because plugin works in all modes
		 */
		
		/*
		 * If pasting from word remove spans
		 * SPAN is used for styles, but styles are removed, so no need for spans
		 */
		removeSpans: true,
		
		/*
		 * If pasting from word remove empty tags
		 */
		removeEmptyTags: true,
		
		/*
		 * If pasting from word remove following attributes
		 */
		removeAttributes: ['xmlns', 'style', 'lang', 'id', 'name', 'class', 'width', 'height', 'v:[a-z0-9\\-\\_]+', 'w:[a-z0-9\\-\\_]+']
	};
	
	var REMOVABLE_TAGS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'blockquote', 'q', 'li', 'div', 'article', 'aside', 'details', 'figcaption', 'footer', 'header', 'hgroup', 'nav', 'section', 'pre', 'code', 'font', 'b', 'strong', 'em', 'i', 'u', 'a'];
	
	Supra.HTMLEditor.addPlugin('paste', defaultConfiguration, {
		
		/**
		 * Configuration
		 * @type {Object}
		 */
		config: null,
		
		/**
		 * Temporary placeholder for pasted content
		 * @type {HTMLElement}
		 */
		placeHolder: null,
		
		/**
		 * Selection before paste event
		 * @param {Object} event
		 */
		previousSelection: null,
		
		/**
		 * Cancel event
		 * @param {Event} event
		 */
		cancelEvent: function (event) {
			if (event.preventDefault) event.preventDefault();
			event.returnValue = false;
			return false;
		},
		
		/**
		 * Handle paste event
		 * @param {Object} event
		 */
		onPaste: function (event) {
			event = event || window.event;
			if (!this.htmleditor.editingAllowed || this.htmleditor.get('disabled')) return this.cancelEvent(event);
			
			//Save current selection
			var htmleditor	= this.htmleditor,
				selection	= htmleditor.getSelection();
			
			//If there's no selection, then skip
			if (!selection || !selection.start) return this.cancelEvent(event);
				
			var srcNode		= Y.Node.getDOMNode(htmleditor.get('srcNode')),		// editor content node
				doc			= htmleditor.get('doc'),							// editor iframe document
				node		= doc.createElement('DIV');							// temporary node
			
			/* Create node, which will be used as temporary storage for pasted value
			 * make sure it's outside the screen to prevent flickering */
			node.contentEditable = true;
			node.style.position = 'fixed';
			node.style.width = '2000px';
			node.style.left = '-9000px';
			node.style.top = '0px';
			node.style.opacity = 0;
			node.innerHTML = '&nbsp;';
			
			srcNode.appendChild(node);
			
			//Change selection to new element (content will be pasted inside it)
			htmleditor.setSelection({
				'start': node,
				'end': node,
				'start_offset': 0,
				'end_offset': node.childNodes.length
			});
			
			Supra.immediate(this.afterPaste);
			
			this.placeHolder = node;
			this.previousSelection = selection;
		},
		
		/**
		 * After paste event retrieve pasted content, clean it and place
		 * it into content
		 */
		afterPaste: function () {
			var placeHolder = this.placeHolder,
				nodes = null,
				node  = null,
				tag   = null,
				
				htmleditor = null,
				html = null,
				
				children = null,
				
				tmp   = null,
				type  = null,
				split = true;
			
			if (this.previousSelection && placeHolder) {
				htmleditor	= this.htmleditor,
				html		= placeHolder.innerHTML;
				
				//Process HTML to make sure there is no garbage code
				html = this.cleanPastedHTML(html);
				
				//Convert into format browser can understand and work with
				html = html ? htmleditor.uncleanHTML(html) : html;
				
				//Check if we need to unwrap tag
				placeHolder.innerHTML = html;
				nodes = Y.Node(placeHolder).get('childNodes');
				
				//Table with one cell shouldn't be a table, unwrap
				//table leaving only cell content
				/*
				if (nodes.size() == 1) {
					node = nodes.item(0);
					
					if (node.get('nodeType') == 1 && node.get('tagName').toLowerCase() == 'table') {
						tmp = node.all('>tbody>tr>td,>tbody>tr>th,>tr>td,>tr>th');
						
						if (tmp.size() == 1) {
							// Only one TD or TH
							nodes = tmp.get('childNodes');
							node = tmp.item(0).getDOMNode();
							
							do {
								tmp = node; node = tmp.parentNode;
								htmleditor.unwrapNode(tmp);
							} while (node && node.tagName !== 'TABLE');
							
							html = placeHolder.innerHTML;
						}
					}
				}
				*/
				/*
				if (nodes.size() == 1 && nodes.item(0).test('table')) {
					// We are pasting table
					// Is user pasting inside another table?
					var sel_from = this.previousSelection.start,
						sel_end  = this.previousSelection.end;
					
					console.log(sel_from, sel_to);
				}
				*/
				
				//List with one item shouldn't be a list, unwrap
				//list leaving only content
				if (nodes.size() == 1 && nodes.item(0).test('ol, ul')) {
					node = nodes.item(0);
					children = node.get('childNodes'); // List of LI
					
					if (children.size() == 1){
						nodes = children.item(0).get('childNodes');
						htmleditor.unwrapNode(children.item(0).getDOMNode());
						htmleditor.unwrapNode(node.getDOMNode());
						html = placeHolder.innerHTML;
					} 
				}
				
				if (nodes.size() == 1) {
					node = nodes.item(0);
					type = node.get('nodeType');
					
					//If there is only a single tag in the list then unwrap it
					//if tag is in REMOVABLE_TAGS list
					if (type == 1) {
						tag = node.get('tagName').toLowerCase();
						
						if (Y.Array.indexOf(REMOVABLE_TAGS, tag) != -1) {
							htmleditor.unwrapNode(node.getDOMNode());
							html = placeHolder.innerHTML;
							split = false;
						}
					} else if (type == 3) {
						// Already a text, no need to split content
						split = false;
					}
				}
				
				if (split && nodes.size()) {
					// If content is not a simple text, then split existing tag and insert pasted nodes
					// where they should be
					this.afterPasteSplitInsert(nodes);
					return;
				}
				
				//Restore previous selection
				htmleditor.setSelection(this.previousSelection);
				this.previousSelection = null;
				
				//null or false <- prevent pasting
				if (html !== null && html !== false) {
					html = html || '';
					
					//Insert html 
					if (Y.UA.webkit) {
						//In webkit selection is set only after timeout
						Y.later(16, this, function () {
							htmleditor.replaceSelection(html, null);
							this.afterPasteFinalize();
						});
					} else {
						htmleditor.replaceSelection(html, null);
						this.afterPasteFinalize();
					}
				}
			}
		},
		
		/**
		 * Insert copied cell content into selected sell content
		 */
		afterPasteTableToTable: function (nodes) {
			
		},
		
		/**
		 * Insert content by splitting existing tag into two parts at cursor position
		 * and insert pasted content between them
		 */
		afterPasteSplitInsert: function (nodes) {
			var htmleditor	= this.htmleditor,
				insert = null;
			
			// 
			insert = Y.bind(function () {
				htmleditor.replaceSelection('');
				
				var parent = htmleditor.splitAt(this.previousSelection.start, this.previousSelection.start_offset),
					dom = nodes.getDOMNodes(),
					i = 0,
					ii = dom.length,
					first_element = null,
					last_element = null,
					prev_element = null,
					next_element = null;
				
				if (parent) {
					if (parent.tagName == 'LI' && dom[0].tagName != 'LI') {
						// List item, add to the beginning of it because pasting non-list items
						for (i=ii-1; i>=0; i--) {
							htmleditor.insertPrepend(dom[i], parent);
						}
					} else {
						for (; i<ii; i++) {
							htmleditor.insertBefore(dom[i], parent);
							first_element = first_element || dom[i];
							last_element = dom[i];
						}
					}
				}
				
				// Remove empty elements before pasted content
				while (first_element && first_element.previousElementSibling) {
					prev_element = first_element.previousElementSibling;
					
					if (prev_element && htmleditor.isNodeEmpty(prev_element)) {
						prev_element.parentNode.removeChild(prev_element);
					} else {
						first_element = null;
					}
				}
				
				// Remove empty elements after pasted content
				while (last_element && last_element.nextElementSibling) {
					next_element = last_element.nextElementSibling;
					
					if (next_element && htmleditor.isNodeEmpty(next_element)) {
						next_element.parentNode.removeChild(next_element);
					} else {
						last_element = null;
					}
				}
				
				// If reference node is empty then remove it
				// It's possible it has been removed already
				if (parent && parent.parentNode && htmleditor.isNodeEmpty(parent)) {
					parent.parentNode.removeChild(parent);
				}
				
				this.previousSelection = null;
				this.afterPasteFinalize();
			}, this);
			
			
			//Restore previous selection
			htmleditor.setSelection(this.previousSelection);
			
			if (Y.UA.webkit) {
				//In webkit selection is set only after timeout
				Y.later(16, this, insert);
			} else {
				insert();
			}
		},
		
		/**
		 * Clean up after paste
		 * @private
		 */
		afterPasteFinalize: function () {
			//Remove placeholder since it's not needed anymore
			this.placeHolder.parentNode.removeChild(this.placeHolder);
			delete(this.placeHolder);
			
			//
			this.htmleditor.fire('afterPaste');
			
			//Content was changed
			this.htmleditor._changed();
		},
		
		/**
		 * Clean pasted html
		 * @param {Object} html
		 */
		cleanPastedHTML: function (html) {
			var htmleditor = this.htmleditor,
				mode = htmleditor.get('mode');
			
			if (mode == Supra.HTMLEditor.MODE_STRING) {
				//Remove all tags
				html = html.replace(/<[^>]+>/g, '');
				
				//Calling, because plugins could be using 'cleanHTML' event
				html = htmleditor.cleanHTML(html);
			} else if (mode == Supra.HTMLEditor.MODE_TEXT || mode == Supra.HTMLEditor.MODE_BASIC) {
				
				if (html.indexOf('<') !== -1) {
					// Replace all block level ending tags with new lines
					var regex = new RegExp('(<br[^>]*>|<\\/(' + Supra.HTMLEditor.ELEMENTS_BLOCK_ARR.join('|') + ')[^>]*>)', 'ig');
					 
					// There are no new lines, 
					html = html.replace(/\n/g, '');
					html = html.replace(regex, '\n');
				}
				
				// Remove all tags
				html = html.replace(/<[^>]+>/g, '');
				
				// Remove whitespaces at the begining and at the end
				html = html.replace(/(^[\r\n\s]*|[\r\n\s]*$)/g, '');
				
				// Replace new lines with BRs
				html = html.replace(/\n/g, '<br />');
				
				//Calling, because plugins could be using 'cleanHTML' event
				html = htmleditor.cleanHTML(html);
			} else {
				
				//If content was pasted from MS Word, remove all MS tags/styles/comments
				html = this.cleanUpWordFormatting(html);
				
				//In case if content was copied from editor, then need to remove IDs, classes, etc.
				html = htmleditor.cleanHTML(html);
				
				//Remove script, style and link nodes
				html = html.replace(/<script[^>]*\/?>([\s\S]*?<\/script>)?/ig, '');
				html = html.replace(/<style[^>]*\/?>([\s\S]*?<\/style>)?/ig, '');
				html = html.replace(/<link[^>]*\/?>/ig, '');
				
				//WebKit may add BR, remove it
				html = html.replace(/<br\s?\/?>$/i, '');
				
				//Remove "su..." ids to prevent conflict
				html = html.replace(/id=("|')?su[0-9]+("|')?\s?/ig, '');
			}
			
			//Fire pasteHTML event and allow listeners to modify content
			var event = {'html': html};
			htmleditor.fire('pasteHTML', {}, event);
			
			return event.html;
		},
		
		/**
		 * Returns true if HTML is pasted from Microsoft Word
		 * 
		 * @param {String} html HTML which will be pasted
		 * @return True if html has Word formatting, otherwise false
		 * @type {Boolean}
		 */
		isContentFromWord: function (html) {
			if (html.indexOf('MsoNormal') != -1 || html.indexOf('WordDocument') != -1) return true;
			return false;
		},
		
		/**
		 * Remove MS Word formatting from html
		 * @param {String} html
		 * @return Cleaned html
		 * @type {String}
		 */
		cleanUpWordFormatting: function (html) {
			if (!this.isContentFromWord(html)) return html;
			
			//Remove comments
			html = html.replace(/<!--[\s\S]*?-->/ig, '');
			
			//Spans are not needed, because they don't have default style
			if (this.config.removeSpans) {
				html = html.replace(/<\/?span[^>]*>/ig, '');
			}
			
			//Since classname in word pasted text has semantic meaning, we need to
			//keep them to check after attributes are removed
			var remove_class_attr = false;
			if (Y.Array.indexOf(this.config.removeAttributes, 'class') != -1) {
				html = html.replace(/class="/g, 'class_su="');
				remove_class_attr = true;
			}
			
			//Content should be style using CSS files, not in-line styles
			//class-names don't match website classnames, should be removed also
			//lang attribute should be set on document, not in-line
			//v: and w: attributes are meaning-less
			if (this.config.removeAttributes && this.config.removeAttributes.length) {
				html = html.replace(new RegExp('\\s+(' + this.config.removeAttributes.join('|') + ')="[^"]*"', 'ig'), '');
			}
			
			//Remove empty tags
			if (this.config.removeEmptyTags) {
				html = html.replace(/<([^\s>]+)[^>]*><\/\1>/ig, '');
			}
			
			//Fix table headings by replacing <td><p class="TableHeading">...</p></td>
			//with <th><p>...</p></th>
			var attr = 'class';
			if (remove_class_attr) {
				attr = 'class_su';
			}
			
			html = html.replace(new RegExp('<td([^>]+)>\\s*<p ' + attr + '="TableHeading">(.*?)</p>\\s*<\/td>', 'gi'), '<th$1><p>$2</p><\/th>');
			
			if (remove_class_attr) {
				html = html.replace(new RegExp('\\s+' + attr + '="[^"]*"', 'ig'), '');
			}
			
			//Remove P tags from tables
			html = html.replace(/<(td|th)([^>]*)>\s*<p>(.*?)<\/p>/g, '<$1$2>$3');
			
			//Remove broken images
			html = html.replace(/<img[^>]+src="file:\/\/[^>]+>/ig, '');
			
			return html;
		},
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor, configuration) {
			//If there is no configuration, skip everything
			if (!configuration) return false;
			
			//Set configuration
			this.config = configuration;
			
			//Preserve execution context
			this.afterPaste = Y.bind(this.afterPaste, this);
			
			var node = Y.Node.getDOMNode(htmleditor.get('srcNode'));
			if (node) {
				//Using traditional method, because other methods doesn't work cross-browser
				node.onpaste = Y.bind(this.onPaste, this);
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