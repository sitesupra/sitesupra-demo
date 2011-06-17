/*
 * Supra.Editor NOT USED
 */


var EditorIndex = 1;

YUI().add('supra.editor', function (Y) {
	
	function Editor () {
		Editor.superclass.constructor.apply(this, arguments);
	}
	
	Editor.NAME = 'editor';
	Editor.CLASS_NAME = Y.ClassNameManager.getClassName(Editor.NAME);
	Editor.ATTRS = {
		'doc': {
			value: null
		},
		'win': {
			value: null
		},
		'srcNode': {
			value: null
		},
		'toolbar': {
			value: null
		},
		'disabled': {
			value: false,
			setter: '_setDisabled'
		},
		'data': {
			value: {}
		}
	};
	
	Editor.WHITE_LIST_TAGS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'b', 'em', 'span', 'small', 'sub', 'sup', 'a', 'img', 'br', 's', 'strike', 'u', 'blockquote', 'q', 'big', 'table', 'tbody', 'tr', 'td', 'thead', 'th', 'ul', 'ol', 'li', 'div', 'dl', 'dt', 'dd', 'col', 'colgroup', 'caption'];
	Editor.ELEMENTS_INLINE = {'b': 'b', 'i': 'i', 'span': 'span', 'em': 'em', 'sub': 'sub', 'sup': 'sup', 'small': 'small', 'font': 'font', 'strong': 'strong', 's': 's', 'strike': 'strike', 'a': 'a', 'u': 'u', 'img': 'img', 'br': 'br', 'q': 'q', 'big': 'big'};
	
	Y.extend(Editor, Y.Base, {
		
		_last_focused_node: null,
		_last_focused_tag: null,
		_last_button: null,
		
		_tag_plugins: {},
		_tab_plugins: {},
		
		syncUI: function () {
			
		},
		
		/**
		 * Returns focused node
		 * 
		 * @return Focused node
		 * @type {HTMLNode}
		 */
		getFocusedNode: function () {
			var pos = this.getCursorPosition();
			if (!pos) return null;
			
			var node = pos.end || pos.start;
			
			while(node) {
				if (node.nodeType != 1) {
					node = node.parentNode;
				} else {
					return node;
				}
			}
			
			return null;
		},
		
		/**
		 * Returns index of child in childNodes 
		 * @param {Object} child
		 * @return Child index
		 * @type {Number}
		 */
		_getChildIndex: function (child) {
			var p = child.parentNode;
			if (p) {
				for(var i=0,ii=p.childNodes.length; i<ii; i++) {
					if (p.childNodes[i] === child) return i;
				}
			}
			return null;
		},
		
		/**
		 * Returns text length if node is textNode, otherwise children count
		 * @param {Object} node
		 */
		_getNodeLength: function (node) {
			if (node.nodeType == 3) return node.length;
			return node.childNodes.length;
		},
		
		/**
		 * Returns cursor position
		 * 
		 * @return Cursor position
		 * @type {Object}
		 */
		getCursorPosition: function () {
			if (this.get('disabled')) return null;
			
			var sel = this.get('win').getSelection();
			var range = sel.getRangeAt(0);
			var start_container = range.startContainer, start_offset = range.startOffset,
				end_container = range.endContainer, end_offset = range.endOffset;
			
			//nodeType 3 is text
			//WebKit sometimes reports child node start_container (with offset same as length) or end_container
			//(with offset 0), which is incosistent with FF where parent node is reported
			if (Y.UA.webkit) {
				if (this._getNodeLength(start_container) == start_offset) {
					if (start_container.nextSibling) {
						start_offset = this._getChildIndex(start_container) + 1;
						start_container = start_container.parentNode;
					}
				}
				if (end_offset == 0) {
					if (end_container.previousSibling) {
						end_offset = this._getChildIndex(end_container);
						end_container = end_container.parentNode;
					}
				}
			}
			
			//If only 1 node is selected, then change start_container and end_container
			//to that node
			if (start_container == end_container && end_offset - start_offset == 1) {
				var node = start_container.childNodes[start_offset];
				if (node && node.nodeType == 1) {
					start_container = end_container = node;
					start_offset = end_offset = 0;
				}
			}
			
			return {
				start: start_container,
				start_offset: start_offset,
				end: end_container,
				end_offset: end_offset
			};
		},
		
		/**
		 * When focus node changes update toolbar button "down" state
		 * and fire nodeChange event
		 */
		_handleFocusChange: function () {
			if (this.get('disabled')) return;
			
			var last = this._last_focused_node;
			var latest = this.getFocusedNode();
			
			if (last !== latest) {
				var tag = (latest ? latest.tagName.toUpperCase() : null);
				this.fire('nodeChange', {oldNode: last, newNode: latest, newTag: tag});
				
				var tb = this.get('toolbar'), btn;
				var btn = [];
				
				if (this._last_button) {
					for(var i=0,ii=this._last_button.length; i<ii; i++) {
						this._last_button[i].set('down', false);
					}
				}
				
				var tabs = this.get('toolbar').tabs;
				var active_tab = tabs.get('activeTab');
				
				if (tag in this._tag_plugins) {
					var plugin = this._tag_plugins[tag];
					
					if (active_tab != plugin) {
						
						//Hide all tabs, except one associated with TAG
						for(var i in this._tab_plugins) {
							if (i == plugin) {
								tabs.showTab(i);
								tabs.set('activeTab', i);
							} else {
								tabs.hideTab(i);
							}
						}
						
						this.get('toolbar')[plugin].fire('show');
						
					}
				} else {
					
					//Hide all tabs
					for(var i in this._tab_plugins) {
						tabs.hideTab(i);
					}
					
				}
				
				if (!(tag in this._tag_plugins)) {
					
					while (latest && latest.style) {
						var tag = latest.tagName.toUpperCase();
						
						if (tag == 'B' || tag == 'STRONG' || (tag == 'SPAN' && latest.style.fontWeight == 'bold')) 
							btn[btn.length] = tb.getButton('bold');
						else if (tag == 'S' || (tag == 'SPAN' && latest.style.textDecoration == 'line-through')) 
							btn[btn.length] = tb.getButton('strikethrough');
						else if (tag == 'EM' || tag == 'I' || (tag == 'SPAN' && latest.style.fontStyle == 'italic')) 
							btn[btn.length] = tb.getButton('italic');
						else if (tag == 'U' || (tag == 'SPAN' && latest.style.textDecoration == 'underline')) 
							btn[btn.length] = tb.getButton('underline');
						
						latest = latest.parentNode;
					}
				}
				
				this._last_button = btn;
				
				for(var i=0,ii=this._last_button.length; i<ii; i++) {
					this._last_button[i].set('down', true);
				}
			}
		},
		
		/**
		 * Handle mouse click inside editor
		 * @param {Object} e
		 */
		_handleClick: function (e) {
			//Clicking on image should change selection to it
			if (e.target.get('tagName') == 'IMG') {
				var node = Y.Node.getDOMNode(e.target);
				this._setSelection(node);
			}
		},
		
		_setSelection: function (node) {
			var doc = this.get('doc');
			var win = this.get('win');
			var sel = win.getSelection();
			
			//WebKit may report empty selection
			var range = (sel.rangeCount ? sel.getRangeAt(0) : doc.createRange());
			
			if (node) {
				range.selectNode(node);
			} else {
				var c = Y.Node.getDOMNode(this.get('srcNode')).lastChild;
				if (c) range.setStartAfter(c);
			}
			
			sel.removeAllRanges();
			sel.addRange(range);
			
			this._handleFocusChange();
		},
		
		/**
		 * Replace selection or wrap selection in tag
		 * 
		 * @param {String} tagName
		 * @param {String} str
		 */
		_replaceSelection: function (tagName, str) {
			if (this.get('disabled')) return;
			
			var doc = this.get('doc');
			var win = this.get('win');
			
			if (doc.selection) {
				//IE
				var sel = doc.selection;
				var str = (str ? str : doc.selection.createRange().htmlText);
				var range = sel.createRange();
				
				if (tagName) {
					range.pasteHTML('<' + tagName + '>' + str + '</' + tagName + '>');
				} else {
					range.pasteHTML(str);
				}
				
				return null;
			} else {
				//Standard compatible browsers
				var str = (str ? str : win.getSelection().toString());
				var node, nodelist;
				
				if (tagName) {
					node = doc.createElement(tagName);
					node.innerHTML = str;
				} else {
					//Create TextNode with &nbsp; (non-breaking space) as content
					//Can't use createTextNode, because &amp; is automatically escaped
					var nodelist = doc.createElement('I');
						nodelist.innerHTML = str;
				}
				
				var sel = win.getSelection();
				var range = sel.getRangeAt(0);
				range.deleteContents();
				
				if (node) {
					range.insertNode(node);
					range.setStartAfter(node);
				} else if (nodelist) {
					var first = null;
					for(var i=0,ii=nodelist.childNodes.length; i<ii; i++) {
						if (i == 0) first = nodelist.childNodes[i];
						range.insertNode(nodelist.childNodes[i]);
					}
					if (first) range.setStartAfter(first);
				}
				
				sel.removeAllRanges();
				sel.addRange(range);
				
				return node;
			}
		},
		
		bindUI: function () {
			this.get('toolbar').on('command', function (evt) {
				this.exec(evt.command);
			}, this);
			
			this.get('srcNode').on('mouseup', this._handleFocusChange, this);
			this.get('srcNode').on('keyup', this._handleFocusChange, this);
			
			this.get('srcNode').on('click', this._handleClick, this);
		},
		
		renderUI: function () {
			
			var srcNode = this.get('srcNode');
			
			//Convert <b> <em> <i> into <span>
			//because FF doesn't understand them correctly
			srcNode.set('innerHTML', this.uncleanHTML(srcNode.get('innerHTML')));
			
			this.set('disabled', this.get('disabled'));
			
		},
		
		render: function () {
			this.renderUI();
			this.bindUI();
			this.syncUI();
		},
		
		/**
		 * Execute command
		 * 
		 * @param {String} action
		 */
		exec: function (action, data) {
			var disabled = this.get('disabled');
			if (disabled) return;
			
			var allowed = this.fire('exec', {'action': action});
			if (allowed !== false) {
			
				switch (action) {
					case 'bold':
					case 'italic':
					case 'underline':
					case 'strikethrough':
						this.get('doc').execCommand(action, false, null);
						break;
					case 'insertimage':
						var latest = this.getFocusedNode();
						if (latest && latest.tagName == 'IMG') {
						
							var plugin = this.getPluginByTag('IMG');
							
							if (plugin) {
								plugin.changeImage(data);
							}
						
						} else {
							var img = '';
							for (var i = 0, ii = data.sizes.length; i < ii; i++) {
								if (data.sizes[i].id == '200x200') 
									img = data.sizes[i].external_path;
							}
							var data = Supra.mix({}, data, {
								'img': img
							});
							
							this._replaceSelection(null, '<img src="' + data.img + '" alt="' + Y.Lang.escapeHTML(data.description) + '" />');
							this.setData('img[src="' + data.img + '"]', data);
						}
						
						break;
				}
			}
			
			this._handleFocusChange();
		},
		
		/**
		 * Converts html into format browser understands
		 * @param {Object} html
		 */
		uncleanHTML: function (html) {
			//Convert <strong> into <b>
			html = html.replace(/<(\/?)strong([^>]*)>/g, '<$1b$2>');
			
			//Convert <i> into <em>
			html = html.replace(/<(\/?)i((\s[^>]+)?)>/g, '<$1em$2>');
			
			//Convert B, EM, U, S into SPAN
			var tag2span = {
				'b': 'font-weight: bold',
				'em': 'font-style: italic',
				'u': 'text-decoration: underline',
				's': 'text-decoration: strike-through'
			};
			var expression = null;
			
			for(var i in tag2span) {
				expression = new RegExp("<" + i + "(\s[^>]*)?>", "ig");
				html = html.replace(expression, '<span style="' + tag2span[i] + ';">');
				
				expression = new RegExp("<\/" + i + "(\s[^>]*)?>", "ig");
				html = html.replace(expression, '</span>');
			}
			
			return html;
		},
		
		/**
		 * Converts browser generated markup into valid html
		 * @param {Object} html
		 */
		cleanHTML: function (html) {
			//Convert <span> into B, EM, U, S
			var span2tag = {
				'b': 'font-weight:\\s?bold',
				'em': 'font-style:\\s?italic',
				'u': 'text-decoration:\\s?underline',
				's': 'text-decoration:\\s?strike-through'
			};
			
			//Convert <span style="..."> into tag
			var matches = [], offset = 0, match_s = 0, match_e = 0, sub_match = 0, last_match = 0;
			while(true) {
				
				match_e = html.indexOf('</span>');
				if (match_e == -1) break;
				
				match_s = html.lastIndexOf('<span', match_e);
				
				if (match_s != -1) {
					var search = html.substring(match_s, match_e + 7);
					var replacement = search;
					var tag = null;
					
					for(var i in span2tag) {
						var expression = new RegExp("<span [^>]*style=(\"|')" + span2tag[i] + ";", "i");
						if (search.match(expression)) { tag = i; break; }
					}
					
					if (tag) {
						replacement = replacement.replace(/<span[^>]*>/i, '<' + tag + '>').replace(/<\/span>/i, '</' + tag + '>');
					} else {
						replacement = replacement.replace(/<span[^>]*>/i, '<_SPAN_>').replace(/<\/span>/i, '</_SPAN_>');
					}
					
					html = html.replace(search, replacement);
				} else {
					break;
				}
			}
			
			//Fix spans
			html = html.replace(/<(\/?)_SPAN_>/g, '<$1span>');
			
			//Convert <strong> into <b>
			html = html.replace(/<(\/?)strong([^>]*)>/g, '<$1b$2>');
			
			//Convert <i> into <em>
			html = html.replace(/<(\/?)i((\s[^>]+)?)>/g, '<$1em$2>');
			
			//Remove tags, which are not white-listed
			var list = Editor.WHITE_LIST_TAGS.join('|');
			var regexp = new RegExp('/<[^(' + list + ')][^>]*>/', 'ig');
			html = html.replace(regexp, '');
			
			return html;
		},
		
		/**
		 * Returns cleaned up html
		 * 
		 * @return HTML
		 * @type {String}
		 */
		getHTML: function () {
			var html = this.get('srcNode').get('innerHTML');
			return this.cleanHTML(html);
		},
		
		/**
		 * Returns meta data
		 * 
		 * @param {String} key
		 */
		getData: function (key) {
			if (key) {
				var data = this.get('data') || {};
				return (key in data ? data[key] : null);
			}
			return {};
		},
		
		/**
		 * Set meta data
		 * 
		 * @param {String} key
		 * @param {Object} value
		 */
		setData: function (key, value) {
			if (typeof value == 'undefined') {
				this.set('data', key || {});
			} else {
				var data = this.get('data') || {};
				data[key] = value;
				this.set('data', data);
			}
		},
		
		getDataIdByNode: function (node) {
			var tag = node.tagName.toUpperCase();
			var id = null;
			
			if (tag == 'IMG') {
				var src = node.getAttribute('src').replace(document.location.protocol + '//' + document.location.host, '');
				id = 'img[src="' + src + '"]';
			} else {
				id = node.getAttribute('id');
			}
			
			return id;
		},
		
		/**
		 * Return meta data associated with given node
		 * 
		 * @param {HTMLElement} node
		 * @return Data
		 * @type {Object}
		 */
		getDataByNode: function (node) {
			var tag = node.tagName.toUpperCase();
			var id = null;
			var data = null;
			
			if (tag == 'IMG') {
				var src = node.getAttribute('src').replace(document.location.protocol + '//' + document.location.host, '');
				id = 'img[src="' + src + '"]';
			} else {
				id = node.getAttribute('id');
			}
			
			return {
				'id': id,
				'data': id ? this.getData(id) : null
			};
		},
		
		/**
		 * Set meta data and associate it to node
		 *
		 * @param {HTMLElement} node
		 * @param {Object} data
		 */
		setDataByNode: function (node, data) {
			var tag = node.tagName.toUpperCase();
			var id = null;
			
			if (tag == 'IMG') {
				var src = node.getAttribute('src').replace(document.location.protocol + '//' + document.location.host, '');
				id = 'img[src="' + src + '"]';
			} else {
				id = node.getAttribute('id');
				if (!id) {
					id = 'node' + (EditorIndex++);
					node.setAttribute('id', id);
				}
			}
			
			if (id) {
				this.setData(id, data);
			}
		},
		
		/**
		 * Removes data entry
		 * 
		 * @param {String} key
		 */
		removeData: function (key) {
			var data = this.get('data') || {};
			if (key in data) delete(data[key]);
			this.set('data', data);
		},
		
		/**
		 * Handle event when editor is enabled (focused)
		 */
		onEnable: function () {
			//Index all plugins: TAG -> plugin namespace
			this._tag_plugins = {};
			//Index all plugins with tabs: plugin namespace -> plugin namespace
			this._tab_plugins = {};
			
			var tb = this.get('toolbar');
			
			for(var p in tb._plugins) {
				var definition = tb._plugins[p];
				if ('TAB' in definition) {
					this._tab_plugins[p] = p;
				}
				if ('TAGS' in definition) {
					for(var i=0, ii=definition.TAGS.length; i<ii; i++) {
						this._tag_plugins[definition.TAGS[i].toUpperCase()] = p;
					}
				}
			}
			
			this.get('toolbar').set('editor', this);
		},
		
		/**
		 * Returns plugin responsible for given tag
		 * @param {Object} tag
		 */
		getPluginByTag: function (tag) {
			tag = tag.toUpperCase();
			if (tag in this._tag_plugins) {
				return this.get('toolbar')[this._tag_plugins[tag]];
			}
			return null;
		},
		
		/**
		 * Enable/disable editor
		 * 
		 * @param {Boolean} value
		 * @private
		 */
		_setDisabled: function (value) {
			if (value) {
				this.get('srcNode').setAttribute('contentEditable', false);
			} else {
				this.get('srcNode').setAttribute('contentEditable', true);
				
				if (Y.UA.webkit) {
					//Focus and deselect all text 
					this._setSelection(null);
				} else {
					//Focus
					this.get('srcNode').focus();
				}
				
				this.onEnable();
			}
			
			return !!value;
		}
		
	});
	
	Supra.Editor = Editor;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version);
