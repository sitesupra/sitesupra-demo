//Invoke strict mode
"use strict";

YUI().add('supra.htmleditor-selection', function (Y) {
	
	Y.mix(Supra.HTMLEditor.prototype, {
		
		/**
		 * Current selection
		 * @type {Object}
		 */
		selection: null,
		
		selectedElement: null,
		
		/**
		 * Path to currently selected element
		 * @type {Array}
		 */
		path: null,
		
		/**
		 * Returns path to currently selected element
		 * 
		 * @return Path to element
		 * @type {Array}
		 */
		getSelectionPath: function () {
			if (this.path) return this.path;
			if (!this.selection) return null;
			
			var path = [], node = this.selection.start, container = Y.Node.getDOMNode(this.get('srcNode'));
			
			while(node && node !== container) {
				if (node.nodeType == 1) path.push(node);
				node = node.parentNode;
			}
			
			this.path = path;
			return path;
		},
		
		/**
		 * Restore selection
		 * 
		 * @param {Object} selection
		 */
		setSelection: function (selection) {
			if (this.get('disabled')) return;
			
			var doc = this.get('doc');
			var win = this.get('win');
			
			if (win.getSelection) {
				//Standard compatible browsers
				var sel = win.getSelection();
				var range = sel.rangeCount ? sel.getRangeAt(0) : doc.createRange();
				
				range.setStart(selection.start, selection.start_offset);
				range.setEnd(selection.end, selection.end_offset);
				
				sel.removeAllRanges();
				sel.addRange(range);
				
				this._resetSelection();
			} else if (doc.selection) {
				//IE < 9
				//@TODO
				
				this._resetSelection();
			}
		},
		
		/**
		 * Returns true if selection is collapsed
		 * 
		 * @return True if collapsed, otherwise false
		 * @type {Boolean}
		 */
		selectionIsCollapsed: function () {
			return this.selection.collapsed;
		},
		
		/**
		 * Returns element in which cursor is positioned
		 * Optionally searching for closest (parent) element matching selector
		 * 
		 * @param {String} selector Optional. Will return first element matching selector
		 * @return HTMLElement or null
		 * @type {HTMLElement}
		 */
		getSelectedElement: function (selector) {
			if (this.selectedElement) {
				//Find closest element matching selector
				if (selector) {
					var node = new Y.Node(this.selectedElement),
						container = Y.Node.getDOMNode(this.get('srcNode'));
					
					//Don't traverse up more than container	
					while (node && !node.compareTo(container)) {
						if (node.test(selector)) return Y.Node.getDOMNode(node);
						node = node.get('parentNode');
					}
					
					return null;
				}
				
				return this.selectedElement;
			}
			
			var selection = this.selection,
				container = Y.Node.getDOMNode(this.get('srcNode'));
			
			if (!selection) return null;
			
			var node = selection.end || selection.start;
			
			//Find HTMLElement
			while(node && node !== container) {
				if (node.nodeType != 1) {
					node = node.parentNode;
				} else {
					this.selectedElement = node;
					return node;
				}
			}
			
			this.selectedElement = (node === container ? container : null);
			if (selector && this.selectedElement) {
				return this.getSelectedElement(selector);
			} else {
				return this.selectedElement;
			}
		},
		
		/**
		 * Returns selection
		 * 
		 * @return Selection
		 * @type {Object}
		 */
		getSelection: function () {
			var doc = this.get('doc'),
				win = this.get('win');
			
			if (win.getSelection) {
				var sel = win.getSelection();
				var srcNode = Y.Node.getDOMNode(this.get('srcNode'));
				
				//If there is no selection, then report root node
				if (!sel.rangeCount) {
					sel = {
						start: srcNode,
						start_offset: 0,
						end: srcNode,
						end_offset: 0,
						collapsed: true
					};
					
					//Update also actual selection
					this.setSelection(sel);
					return sel;
				}
				
				var range = sel.getRangeAt(0);
				var start_container = range.startContainer, start_offset = range.startOffset,
					end_container = range.endContainer, end_offset = range.endOffset,
					start_el = start_container,
					end_el = end_container;
				
				//Check if selection is under root node
				while(start_el && start_el !== srcNode) start_el = start_el.parentNode;
				while(end_el && end_el !== srcNode) end_el = end_el.parentNode;
				
				//Selection is not under root node
				if (!start_el || !end_el) {
					sel = {
						start: srcNode,
						start_offset: 0,
						end: srcNode,
						end_offset: 0,
						collapsed: true
					};
					
					//Update also actual selection
					this.setSelection(sel);
					return sel;
				}
				
				//nodeType 3 is text
				//WebKit sometimes reports child node start_container (with offset same as length) or end_container
				//(with offset 0), which is incosistent with FF where parent node is reported
				//if (Y.UA.webkit) {
					/*
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
					*/
				//}
				
				//If only one node is selected and there is no actual selection,
				//then change start_container and end_container to that node
				if (start_container == end_container && end_offset - start_offset == 1) {
					var node = start_container.childNodes[start_offset];
					if (node && node.nodeType == 1) {
						start_container = end_container = node;
						start_offset = end_offset = 0;
					}
				}
				
			} else if (doc.selection) {
				//IE < 9
				var range = doc.selection.createRange();
				var end_container,
					start_container = end_container = range.parentElement();
					start_offset = 0,
					end_offset = range.text.length;
			}
			
			return {
				start: start_container,
				start_offset: start_offset,
				end: end_container,
				end_offset: end_offset,
				collapsed: (start_container == end_container && start_offset == end_offset)
			};
		},
		
		/**
		 * Select node
		 * 
		 * @param {HTMLElement} node
		 */
		selectNode: function (node) {
			var doc = this.get('doc');
			var win = this.get('win');
			
			if (win.getSelection) {
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
			} else if (doc.selection) {
				//IE < 9
				var range = doc.body.createTextRange();
				range.moveToElementText(node);
				range.select();
			}
			
			this._resetSelection();
		},
		
		/**
		 * Replace selection or wrap selection in tag
		 * 
		 * @param {String} tagName
		 * @param {String} str
		 */
		replaceSelection: function (str, wrapTagName) {
			if (this.get('disabled')) return;
			
			var doc = this.get('doc');
			var win = this.get('win');
			
			if (win.getSelection) {
				//Standard compatible browsers
				var str = (str ? str : win.getSelection().toString());
				var node, nodelist;
				
				if (wrapTagName) {
					node = doc.createElement(wrapTagName);
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
					while(nodelist.lastChild) {
						node = nodelist.lastChild;
						first = nodelist.lastChild;
						range.insertNode(nodelist.lastChild);
					}
					if (first) range.setStartAfter(first);
				}
				
				sel.removeAllRanges();
				sel.addRange(range);
				
				this._resetSelection();
				
				return node;
			} else if (doc.selection) {
				//IE < 9
				var sel = doc.selection;
				var str = (str ? str : doc.selection.createRange().htmlText);
				var range = sel.createRange();
				
				if (wrapTagName) {
					range.pasteHTML('<' + wrapTagName + '>' + str + '</' + wrapTagName + '>');
				} else {
					range.pasteHTML(str);
				}
				
				this._resetSelection();
				
				return null;
			}
		},
		
		/**
		 * Find nodes matching selector in selection or node in which cursor
		 * is positioned if it matches selector
		 * 
		 * @param {Object} selection Selection object
		 * @param {String} selector CSS selector
		 * @return Found nodes
		 * @type {Object}
		 */
		findNodesInSelection: function (selection, selector) {
			if (!this.selection) return new Y.NodeList(node || []);
			
			//@TODO Use correction selection
			
			var node = new Y.Node(this.getSelectedElement());
			
			while(node && !node.test(selector)) {
				node = node.get('parentNode');
			}
			
			var nodelist = new Y.NodeList(node || []);
			return nodelist;
		},
		
		/**
		 * Returns selected text
		 * 
		 * @return Selected text
		 * @type {String}
		 */
		getSelectionText: function () {
			var doc = this.get('doc'),
				win = this.get('win');
			
			if (win.getSelection) {
				return win.getSelection().toString();
			} else if (doc.selection) {
				//IE < 9
				return doc.selection.createRange().htmlText;
			}
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
		 * Reset selection variable cache
		 * 
		 * @param {Object} selection Optional. Selection object
		 */
		_resetSelection: function (selection) {
			this.selectedElement = null;
			this.selection = selection || this.getSelection();
			this.path = null;
		}
		
	});


	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});