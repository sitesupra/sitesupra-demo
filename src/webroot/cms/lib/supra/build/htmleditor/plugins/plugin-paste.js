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
				win			= htmleditor.get('win'),							// editor iframe window
				body		= doc.body,											// editor iframe body
				node		= doc.createElement('DIV'),							// temporary node
				scroll		= win.pageYOffset || doc.body.scrollTop ||
							  doc.documentElement.scrollTop || 0;				// scroll position
			
			/* Create node, which will be used as temporary storage for pasted value
			 * make sure it's outside the screen to prevent flickering
			 * but has same vertical position to prevent scrolling */
			node.style.position = 'absolute';
			node.style.width = '2000px';
			node.style.left = '-9000px';
			node.style.top = scroll + 'px';
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
			
			setTimeout(this.afterPaste, 0);
			
			this.placeHolder = node;
			this.previousSelection = selection;
		},
		
		/**
		 * After paste event retrieve pasted content, clean it and place
		 * it into content
		 */
		afterPaste: function () {
			if (this.previousSelection && this.placeHolder) {
				var htmleditor	= this.htmleditor,
					html		= this.placeHolder.innerHTML;
				
				//Process HTML to make sure there is no garbage code
				html = this.cleanPastedHTML(html);
				
				//Convert into format browser can understand and work with
				html = html ? htmleditor.uncleanHTML(html) : html;
				
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
						});
					} else {
						htmleditor.replaceSelection(html, null);
					}
				}
				
				//Remove placeholder since it's not needed anymore
				this.placeHolder.parentNode.removeChild(this.placeHolder);
				delete(this.placeHolder);
				
				//Content was changed
				this.htmleditor._changed();
			}
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