YUI().add('supra.htmleditor-dom', function (Y) {
	//Invoke strict mode
	"use strict";
	
	Y.mix(Supra.HTMLEditor.prototype, {
		
		/**
		 * Find next node sibling which is not empty
		 * 
		 * @param {HTMLElement|Text} node  
		 */
		nextSibling: function (node) {
			if (!node.nextSibling) return null;
			node = node.nextSibling;
			
			while (node) {
				if (!this.isNodeEmpty(node)) {
					return node;
				}
				
				node = node.nextSibling;
			}
			
			return null;
		},
		
		/**
		 * Find previous node sibling which is not empty
		 * 
		 * @param {HTMLElement|Text} node  
		 */
		previousSibling: function (node) {
			if (!node.nextSibling) return null;
			node = node.previousSibling;
			
			while (node) {
				if (!this.isNodeEmpty(node)) {
					return node;
				}
				
				node = node.previousSibling;
			}
			
			return null;
		},
		
		/**
		 * Insert node before reference element
		 * 
		 * @param {HTMLElement|Text} node Node to insert
		 * @param {HTMLElement|Text} reference Reference node, before which to insert node
		 */
		insertBefore: function (node, reference) {
			var parent = reference.parentNode;
			parent.insertBefore(node, reference);
		},
		
		/**
		 * Insert node after reference element
		 * 
		 * @param {HTMLElement|Text} node Node to insert
		 * @param {HTMLElement|Text} reference Reference node, after which to insert node
		 */
		insertAfter: function (node, reference) {
			var parent = reference.parentNode,
				next = reference.nextSibling;
			
			if (next) {
				parent.insertBefore(node, next);
			} else {
				parent.appendChild(node);
			}
		},
		
		/**
		 * Insert node as first child of parent
		 * 
		 * @param {HTMLElement|Text} node Node to insert
		 * @param {HTMLElement} parent Parent element
		 */
		insertPrepend: function (node, parent) {
			if (parent && parent.nodeType == 1) {
				var reference = parent.firstChild;
				if (reference) {
					parent.insertBefore(node, reference);
				} else {
					parent.appendChild(node);
				}
			}
		},
		
		/**
		 * Returns node or closest ancestor matching selector
		 * Will not look further than editor srcNode
		 * 
		 * @param {String} selector CSS selector
		 * @return Y.Node matching selector
		 * @type {Object}
		 */
		closest: function (node, selector) {
			if (typeof selector == 'string') {
				var node = Y.Node(node),
					src  = this.get('srcNode');
				
				while(node && !node.compareTo(src)) {
					if (node.test(selector)) return node.getDOMNode();
					node = node.ancestor();
				}
				
				return null;
			} else {
				var node   = Y.Node(node),
					target = Y.Node(selector),
					src    = this.get('srcNode');
				
				if (!target) return null;
				
				while(node && !node.compareTo(src)) {
					if (node.compareTo(target)) return node.getDOMNode();
					node = node.ancestor();
				}
				
				return null;
			}
		},
		
		/**
		 * Returns all child elements of node
		 * 
		 * @param {HTMLElement} node Node to check
		 */
		getChildElements: function (node) {
			var children = node.childNodes,
				i = 0,
				ii = children.length,
				out = [];
			
			for (; i<ii; i++) {
				if (children[i].nodeType == 1) {
					out.push(children[i]);
				}
			}
			
			return out;
		},
		
		/**
		 * Returns last non-empty child of node
		 * 
		 * @param {HTMLElement} node Node to check
		 */
		getLastChild: function (node, include_br) {
			if (!node.lastChild) return null;
			
			var children = node.childNodes,
				i = 0,
				ii = children.length,
				tag = null;
			
			for (i = ii-1; i >= 0; i--) {
				if (children[i].nodeType == 1) {
					// Element
					if (children[i].tagName != 'BR' || include_br) {
						return children[i];
					} else if (tag) {
						return tag;
					} else {
						tag = children[i];
					}
				} else if (children[i].nodeType == 3 && children[i].textContent.match(/[^\r\n\s]/)) {
					// Text
					return tag ? tag : children[i];
				}
			}
			
			return null;
		},
		
		/**
		 * Returns first non-empty child of node
		 * 
		 * @param {HTMLElement} node Node to check
		 */
		getFirstChild: function (node, include_br) {
			if (!node.firstChild) return null;
			
			var children = node.childNodes,
				i = 0,
				ii = children.length,
				tag = null;
			
			for (; i<ii; i++) {
				if (children[i].nodeType == 1) {
					// Element
					if (children[i].tagName != 'BR' || include_br) {
						return children[i];
					} else if (tag) {
						return tag;
					} else {
						tag = children[i];
					}
				} else if (children[i].nodeType == 3 && children[i].textContent.match(/[^\r\n\s]/)) {
					// Text
					return tag ? tag : children[i];
				}
			}
			
			return null;
		},
		
		/**
		 * Returns true if node is first non-empty child
		 * 
		 * @param {HTMLElement} node Node to check
		 */
		isFirstChild: function (node, include_br) {
			return node === this.getFirstChild(node.parentNode, include_br);
		},
		
		/**
		 * Returns true if node is empty, otherwise false
		 * 
		 * @param {HTMLElement} node Node to check
		 * @returns {Boolean} True if node is empty
		 */
		isNodeEmpty: function (node) {
			var length = this.getNodeLength(node),
				children = null,
				i = 0,
				ii = 0;
			
			if (length == 0) {
				return true;
			} else if (length <= 3) {
				children = node.childNodes;
				for (ii=children.length; i<ii; i++) {
					if (children[i].nodeType == 1) {
						// Text element
						if (children[i].textContent.replace(/[\r\n\s]+$/, '').length) {
							// Something else than new lines
							return false;
						}
					} else if (children[i].nodeType == 3) {
						// HTML element
						if (children[i].tagName != 'BR') {
							// Tag other than BR
							return false;
						}
					}
				}
				return true;
			}
			return false;
		},
		
		/**
		 * Returns text length without ending whitespaces if node is textNode, otherwise children count
		 * without last whitespace and <br />
		 * 
		 * @param {HTMLElement} node Node which length to return
		 * @returns {Number} Filtered node length
		 */
		getNodeLength: function (node) {
			if (node.nodeType == 1) {
				// HTML element, filter out begining and ending whitespace text nodes
				var children = node.childNodes,
					i = 0,
					ii = children.length,
					length = 0,
					lastTag = null;
				
				for (; i<ii; i++) {
					if (children[i].nodeType == 1) {
						length++;
						lastTag = children[i].tagName;
					} else if ((i == 0 || i == ii - 1) && !children[i].textContent.replace(/[\r\n\s]+$/, '').length) {
						// This is first or last text node and it's empty
					} else {
						length++;
						lastTag = null;
					}
				}
				
				// Last tag before ending was BR, remove it from length
				if (lastTag == 'BR') {
					length--;
				}
				return length;
			} else if (node.nodeType == 3) {
				// Text element
				var length = node.length,
					text   = null;
				
				if (length) {
					// Don't count new lines
					length = node.textContent.replace(/[\r\n\s]+$/, '').length;
				}
				
				return length;
			}
			
			return 0;
		},
		
		/**
		 * Returns index of child in childNodes 
		 * @param {Object} child
		 * @return Child index
		 * @type {Number}
		 */
		getChildNodeIndex: function (child) {
			var p = child.parentNode;
			if (p) {
				for(var i=0,ii=p.childNodes.length; i<ii; i++) {
					if (p.childNodes[i] === child) return i;
				}
			}
			return null;
		},
		
		/**
		 * Returns common ancestor for two elements
		 * 
		 * @param {HTMLElement|Text} a First element
		 * @param {HTMLElement|Text} b Second element
		 */
		getCommonAncestor: function (a, b) {
			var container = Y.Node.getDOMNode(this.get('srcNode')),
				ancestors = {}, id;
				
			while (a && a !== container) {
				id = a._yuid || (a._yuid = Y.guid());
				ancestors[id] = a;
				a = a.parentNode;
			}
			while (b && b !== container) {
				if (b._yuid && b._yuid in ancestors) {
					return ancestors[b._yuid];
				}
				b = b.parentNode;
			}
			
			// Container is common ancestor
			return container;
		},
		
		/**
		 * Returns all nodes between a and b
		 * 
		 * @param {Node} a Node to search from
		 * @param {Node} b Node to search till
		 * @returns {Array} List of nodes
		 */
		getNodesInBetween: function (a, b) {
			var container = Y.Node.getDOMNode(this.get('srcNode')),
				common_ancestor = null,
				contains_start = [],
				contains_end = [],
				start_ancestor = null,
				end_ancestor = null,
				include = true;
			
			// Find common ancestor
			common_ancestor = this.getCommonAncestor(a, b);
			
			// Find nodes going up and forward from 'a' till we find common ancestor
			// + will add
			// - will not add
			//
			// <div>         - common ancestor
			//   <p>         - some of the children are added, no need to add parent. This will become "start_ancestor"
			//     <em />    - sibling, but before 'a', not added to results
			//     <b>       - child is added, so will not add this element
			//       text    + assume 'a' is this, this element is added to results
			//     </b>
			//     text      + sibling after 'a', add
			//     <em>      + 
			//       text    - parent is already added, no need to add a child
			//     </em>
			//   </p>
			//   <p>         - on first pass we go up till common ancestor, so will never traverse this element 
			//     text      -
			//   </p>
			//   <ul>        -
			//     <li>
			//       text    - assume 'b' is this node
			//     </li>
			//   </ul>
			//   <p></p>     - will not add
			// </div>
			//
			node = a;
			while (node && node !== common_ancestor) {
				if (node.parentNode === common_ancestor) {
					// we don't want to go to nextSibling in this case, because
					// we don't know where to stop (don't know which is b ancestor)
					if (include || !contains_start.length) {
						contains_start.push(node);
					}
					start_ancestor = node;
					break;
				}
				
				if (include || !contains_start.length) {
					contains_start.push(node);
				}
				
				start_ancestor = node;
				
				if (node.nextSibling) {
					node = node.nextSibling;
					include = true;
				} else {
					node = node.parentNode;
					
					// we don't want to include ancestor, only next siblings
					// because we already have included children
					include = false;
				}
			}
			
			// Find nodes going up and backward from 'b' till we find "start_ancestor"
			// + will add
			// - will not add
			// 
			// <div>         - common ancestor
			//   <p>         - this is the last element checked and since it matches start_ancestor we stop lookup
			//     <em />    -
			//     <b>       -
			//       text    -
			//     </b>
			//     text      -
			//     <em>      - 
			//       text    -
			//     </em>
			//   </p>
			//   <p>         + sibling 
			//     text      - parent already added
			//   </p>
			//   <ul>        - child already added
			//     <li>      + added, because sibling
			//       text    - parent already added
			//     </li>
			//     <li>
			//       text    + assume 'b' is this node 
			//     </li>
			//   </ul>
			//   <p></p>     - will not add, after 'b'
			// </div>
			//
			node = b;
			while (node && node !== common_ancestor && node !== start_ancestor) {
				if (include || !contains_end.length) {
					contains_end.push(node);
				}
				if (node.previousSibling) {
					node = node.previousSibling;
					include = true;
				} else {
					node = node.parentNode;
					// we don't want to include ancestor, only previous siblings
					// because we already have included children
					include = false;
				}
			}
			
			// If nothing was found, then return common element
			if (!contains_start.length && !contains_end.length) {
				return [common_ancestor];
			}
			
			return Y.Array.unique(contains_start.concat(contains_end.reverse()));
		},
		
		/**
		 * Remove node from dom without removing its content
		 * 
		 * @param {Object} node Node to remove
		 */
		unwrapNode: function (node) {
			if (node && node.nodeType == 1 && node.parentNode) {
				while(node.firstChild) {
					node.parentNode.insertBefore(node.firstChild, node);
				}
				node.parentNode.removeChild(node);
			}
		},
		
		/**
		 * Split element into two elements at given position, returns second element
		 * 
		 * @returns {HTMLElement} Returns second element
		 */
		splitAt: function (node, offset) {
			var siblings = null,
				clone = null,
				tmp = null,
				i = 0,
				ii = 0,
				srcNode = this.get('srcNode').getDOMNode(),
				
				insertAfter = this.insertAfter,
				
				moveSiblings = function (node) {
					if (!node.parentNode) return null;
					
					var ref_parent = node.parentNode,
						
						parent = node.parentNode.cloneNode(false), // false - not recursive
						next = node.nextSibling,
						tmp = node;
					
					parent.removeAttribute('id');
					
					while (tmp) {
						parent.appendChild(tmp);
						tmp  = next;
						next = tmp ? tmp.nextSibling : null;
					}
					
					insertAfter(parent, ref_parent);
					
					return parent;
				};
			
			var SPLIT_TAGS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'blockquote', 'q', 'li', 'div', 'article', 'aside', 'details', 'figcaption', 'footer', 'header', 'hgroup', 'nav', 'section'];
			
			if (offset && node.nodeType == 3) {
				// Text node
				tmp = this.get('doc').createTextNode(node.textContent.substr(offset) || '');
				node.textContent = node.textContent.substr(0, offset);
				
				insertAfter(tmp, node);
				node = tmp;
			} else if (node.nodeType == 1) {
				if (offset) {
					tmp = node.childNodes[offset];
					if (tmp) node = tmp.nextSibling;
				} else if (node.childNodes.length) {
					//tmp = node.childNodes[0];
				}
			}
			
			while (node) {
				if (node === srcNode || node.parentNode === srcNode) {
					return node;
				}
				
				node = moveSiblings(node);
				
				if (node.tagName && Y.Array.indexOf(SPLIT_TAGS, node.tagName.toLowerCase()) != -1) {
					// Element which can be split parent found, stop
					return node;
				}
				
			}
		},
		
		/**
		 * Returns true if node is visible, otherwise false
		 * 
		 * @param {Object} node Node which to check
		 * @param {Boolean} [quick_check] Optional, check only classnames, not actual styles
		 * @private
		 */
		isNodeVisible: function (node, quick_check) {
			var style;
			
			while (node && node.get('tagName') !== 'BODY') {
				if (quick_check) {
					if (node.hasClass('hidden') || node.hasClass('supra-hidden')) {
						return false;
					}
				} else {
					style = node.getStyle('display');
					
					if (style === 'none') {
						return false;
					}
				}
				
				node = node.get('parentNode');
			}
			
			return true;
		}
		
	});


	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});