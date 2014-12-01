YUI().add('supra.htmleditor-nodes', function (Y) {
	//Invoke strict mode
	"use strict";
	
	function Nodes (_nodes, htmleditor) {
		// Allow using without 'new' keyword
		if (!(this instanceof Nodes)) return new Nodes(_nodes, htmleditor);
		
		var nodes = _nodes;
		
		if (typeof nodes === 'string') {
			nodes = [htmleditor.get('doc').createElement(nodes)];
		} else {
			nodes = Nodes.toArray(nodes, htmleditor.get('win'));
		}
		
		this.nodes = nodes;
		this.length = this.nodes.length;
		this.htmleditor = htmleditor;
	};
	
	Nodes.prototype = {
		/**
		 * List of nodes
		 * @type {Array}
		 * @private
		 */
		nodes: null,
		
		/**
		 * HTMLEditor instance
		 * @type {Object}
		 * @private
		 */
		htmleditor: null,
		
		/**
		 * Number of nodes in collection
		 * @type {Number}
		 */
		length: 0,
		
		/**
		 * Destructor
		 */
		destroy: function () {
			this.nodes = null;
			this.length = 0;
			this.htmleditor = null;
		},
		
		
		/* ------------------------- Search ------------------------- */
		
		
		/**
		 * Filter and find nodes, optionally ancestor and children nodes are also checked
		 * 
		 * Options:
		 *     filter - filter function where first argument is a node; CSS selector;
		 *              ":text" to find only Text nodes;
		 *              ":inline" to find only inline elements;
		 *              ":block" to find only block elements or
		 *              ":element" to find only elements
		 *     inAncestors - if true then ancestor elements will be checked too, default is true
		 *     inChildren  - if true then children elements will be checked too, default is true
		 *     
		 * 
		 * @param {Object|String|Function} options Options or filter
		 * @param {Boolean} as_array Returns array of nodes instead of Nodes object, default is false
		 * @returns {Array|Nodes} Nodes
		 */
		find: function (options, as_array) {
			var in_ancestors  = options && typeof options === 'object' && 'inAncestors' in options ? options.inAncestors : true,
				in_children   = options && typeof options === 'object' && 'inChildren' in options ? options.inChildren : true,
				
				container     = this.htmleditor.get('srcNode').getDOMNode(),
				
				filter        = this._resolveFilters(options),
				
				nodes         = this.nodes,
				node          = null,
				i             = 0,
				ii            = nodes.length,
				
				test          = this._testNode,
				
				matches       = [],
				visited       = [], // list of visited nodes
				
				subnodes      = null,
				subnode       = null,
				k             = 0,
				kk            = 0,
				
				matched       = false;
			
			for (; i<ii; i++) {
				node = nodes[i];
				
				if (test(node, filter)) {
					matches.push(node);
				} else {
					matched = false;
					
					// Traverse through children and test nodes for a match
					if (in_children) {
						subnodes = node.getElementsByTagName ? node.getElementsByTagName('*') : [];
						
						for (k=0,kk=subnodes.length; k<kk; k++) {
							subnode = subnodes[k];
							if (test(subnode, filter)) {
								matches.push(subnode);
								matched = true;
							}
						}
					}
					
					// Traverse up the ancestors and test nodes for a match
					if (!matched && in_ancestors) {
						while (node && node !== container) {
							if (test(node, filter)) {
								matches.push(node);
								break;
							}
							node = node.parentNode;
						}
					}
				}
			}
			
			matches = Y.Array.unique(matches);
			return as_array ? matches : new Nodes(matches, this.htmleditor);
		},
		
		/**
		 * Filter nodes
		 * 
		 * Options:
		 *     filter - filter function where first argument is a node; CSS selector;
		 *              ":text" to find only Text nodes;
		 *              ":inline" to find only inline elements;
		 *              ":block" to find only block elements or
		 *              ":element" to find only elements
		 *     inAncestors - if true then ancestor elements will be checked too, default is true
		 *     inChildren  - if true then children elements will be checked too, default is true
		 *     
		 * 
		 * @param {Object|String|Function} options Options or filter
		 * @param {Boolean} as_array Returns array of nodes instead of Nodes object, default is false
		 * @returns {Array|Nodes} Nodes
		 */
		filter: function (options, as_array) {
			var nodes         = this.nodes,
				node          = null,
				i             = 0,
				ii            = nodes.length,
				
				test          = this._testNode,
				filter        = this._resolveFilters(options),
				
				matches       = [];
			
			for (; i<ii; i++) {
				node = nodes[i];
				
				if (test(node, filter)) {
					matches.push(node);
				}
			}
			
			return as_array ? matches : new Nodes(matches, this.htmleditor);
		},
		
		/**
		 * Test if all nodes matches filter
		 * 
		 * Options:
		 *     filter - filter function where first argument is a node; CSS selector;
		 *              ":text" to find only Text nodes;
		 *              ":inline" to find only inline elements;
		 *              ":block" to find only block elements or
		 *              ":element" to find only elements
		 *     inAncestors - if true then ancestor elements will be checked too, default is true
		 *     inChildren  - if true then children elements will be checked too, default is true
		 *     
		 * 
		 * @param {Object|String|Function} options Options or filter
		 * @returns {Boolean} True if all nodes match, otherwise false
		 */
		test: function (options) {
			var nodes         = this.nodes,
				i             = 0,
				ii            = nodes.length,
				
				test          = this._testNode,
				filter        = this._resolveFilters(options);
			
			for (; i<ii; i++) {
				if (!test(nodes[i], filter)) return false;
			}
			
			return true;
		},
		
		/**
		 * Filter and find nodes, optionally ancestor and children nodes are also checked
		 * 
		 * Options:
		 *     filter - filter function where first argument is a node; CSS selector;
		 *              ":text" to find only Text nodes;
		 *              ":inline" to find only inline elements;
		 *              ":block" to find only block elements or
		 *              ":element" to find only elements
		 * 
		 * @param {Object|String|Function} options Options or filter
		 * @param {Boolean} as_array Returns array of nodes instead of Nodes object, default is false
		 * @returns {Array|Nodes} Nodes
		 */
		closest: function (filter, as_array) {
			return this.find({'filter': filter, 'inChildren': false}, as_array);
		},
		
		/**
		 * Returns parent node
		 * 
		 * Options:
		 *     filter - filter function where first argument is a node; CSS selector;
		 *              ":text" to find only Text nodes;
		 *              ":inline" to find only inline elements;
		 *              ":block" to find only block elements or
		 *              ":element" to find only elements
		 * 
		 * @param {Object|String|Function} options Options or filter
		 * @param {Boolean} as_array Returns array of nodes instead of Nodes object, default is false
		 * @returns {Array|Nodes} Nodes
		 */
		parent: function (filter, as_array) {
			var nodes = this.nodes,
				node,
				i = 0,
				ii = nodes.length,
				
				matches = [],
				
				test    = this._testNode,
				filter  = this._resolveFilters(options),
				
				container = this.htmleditor.get('srcNode').getDOMNode();
			
			for (; i<ii; i++) {
				node = nodes[i].parentNode;
				
				if (node && node !== container && test(node, filter, true)) {
					matches.push(node);
				}
			}
			
			matches = Y.Array.unique(matches);
			return as_array ? matches : new Nodes(matches, this.htmleditor);
		},
		
		/**
		 * Returns all siblings
		 * 
		 * Options:
		 *     filter - filter function where first argument is a node; CSS selector;
		 *              ":text" to find only Text nodes;
		 *              ":inline" to find only inline elements;
		 *              ":block" to find only block elements or
		 *              ":element" to find only elements
		 * 
		 * @param {Object|String|Function} options Options or filter
		 * @param {Boolean} as_array Returns array of nodes instead of Nodes object, default is false
		 * @returns {Array|Nodes} Nodes
		 */
		siblings: function (filter, as_array) {
			var nodes = this.nodes,
				nodes_ids = {},
				node,
				i = 0,
				ii = nodes.length,
				id,
				
				children,
				k = 0,
				kk = 0,
				
				parent_ids = {},
				
				matches = [],
				sibling_ids = {},
				
				test          = this._testNode,
				filter        = this._resolveFilters(options);
			
			// Find all node ids, will use to filter out nodes
			for (; i<ii; i++) {
				node = nodes[i];
				id = node._yuid || node._suid || (node._suid = Y.guid());
				nodes_ids[id] = 1;
			}
			
			for (i=0; i<ii; i++) {
				node = nodes[i].parentNode;
				id = node._yuid || node._suid || (node._suid = Y.guid());
				
				if (!parent_ids[id]) {
					// Traverse each parent children only once
					parent_ids[id] = 1;
					children = node.childNodes;
					
					for (k=0, kk=children.length; k<kk; k++) {
						node = children[k];
						id = node._yuid || node._suid || (node._suid = Y.guid());
						
						if (!nodes_ids[id] && !sibling_ids[id]) {
							sibling_ids[id] = 1;
							
							if (test(node, filter, true)) {
								matches.push(node);
							}
						}
					}
				}
			}
			
			matches = Y.Array.unique(matches);
			return as_array ? matches : new Nodes(matches, this.htmleditor);
		},
		
		/**
		 * Returns children elements
		 * 
		 * Options:
		 *     filter - filter function where first argument is a node; CSS selector;
		 *              ":text" to find only Text nodes;
		 *              ":inline" to find only inline elements;
		 *              ":block" to find only block elements or
		 *              ":element" to find only elements
		 * 
		 * @param {Object|String|Function} options Options or filter
		 * @param {Boolean} as_array Returns array of nodes instead of Nodes object, default is false
		 * @returns {Array|Nodes} Nodes
		 */
		children: function (filter, as_array) {
			var nodes = this.nodes,
				i = 0,
				ii = nodes.length,
				
				children,
				k = 0,
				kk = 0,
				
				matches = [],
				
				test          = this._testNode,
				filter        = this._resolveFilters(options);
			
			for (i=0, ii=nodes.length; i<ii; i++) {
				children = nodes[i].childNodes;
				
				if (children) {
					for (k=0, kk=children.length; k<kk; k++) {
						if (test(children[k], filter, true)) {
							matches.push(children[k]);
						}
					}
				}
			}
			
			matches = Y.Array.unique(matches);
			return as_array ? matches : new Nodes(matches, this.htmleditor);
		},
		
		/**
		 * Returns previous sibling
		 * 
		 * @param {Object|String|Function} options Options or filter
		 * @param {Boolean} as_array Returns array of nodes instead of Nodes object, default is false
		 * @returns {Array|Nodes} Nodes
		 */
		prev: function (options, as_array) {
			var nodes = this.nodes,
				i = 0,
				ii = nodes.length,
				node,
				
				matches = [],
				
				test = this._testNode,
				filter = this._resolveFilters(options);
			
			for (; i<ii; i++) {
				node = nodes[i].previousSibling;
				
				while (node && !test(node, filter, true)) {
					node = node.previousSibling;
				}
				if (node) {
					matches.push(node);
				}
			}
			
			matches = Y.Array.unique(matches);
			return as_array ? matches : new Nodes(matches, this.htmleditor);
		},
		
		/**
		 * Returns next sibling
		 * 
		 * @param {Object|String|Function} options Options or filter
		 * @param {Boolean} as_array Returns array of nodes instead of Nodes object, default is false
		 * @returns {Array|Nodes} Nodes
		 */
		next: function (options, as_array) {
			var nodes = this.nodes,
				i = 0,
				ii = nodes.length,
				node,
				id,
				nodes_ids = {},
				
				matches = [],
				
				test = this._testNode,
				filter = this._resolveFilters(options);
			
			// Find all node ids, will use to filter out nodes
			for (; i<ii; i++) {
				node = nodes[i];
				id = node._yuid || node._suid || (node._suid = Y.guid());
				nodes_ids[id] = 1;
			}
			
			for (i=0; i<ii; i++) {
				node = nodes[i].nextSibling;
				
				while (node) {
					id = node._yuid || node._suid || (node._suid = Y.guid());
					
					if (!(id in nodes_ids) && test(node, filter, true)) {
						matches.push(node);
						break;
					}
					
					node = node.nextSibling;
				}
			}
			
			matches = Y.Array.unique(matches);
			return as_array ? matches : new Nodes(matches, this.htmleditor);
		},
		
		/*
		next: function (options, as_array) {
			this.itterate(function (node, fn) {
				var next = node;
				
				while ((next = next.nextSibling) && !(res = fn(next))) {
					
				}
				
				var next = node.nextSibling, res;
				if (res = fn(next)) return next;
			});
			var nodes = this.traverse(this.nodes, function (node, fn) {
				return node.nextSibling;
			})
		},
		*/
		
		/**
		 * Returns all next siblings
		 * 
		 * @param {Object|String|Function} options Options or filter
		 * @param {Boolean} as_array Returns array of nodes instead of Nodes object, default is false
		 * @returns {Array|Nodes} Nodes
		 */
		nextAll: function (options, as_array) {
			var nodes = this.nodes,
				i = 0,
				ii = nodes.length,
				node,
				id,
				nodes_ids = {},
				
				matches = [],
				
				test = this._testNode,
				filter = this._resolveFilters(options);
			
			// Find all node ids, will use to filter out nodes
			for (; i<ii; i++) {
				node = nodes[i];
				id = node._yuid || node._suid || (node._suid = Y.guid());
				nodes_ids[id] = 1;
			}
			
			for (i=0; i<ii; i++) {
				node = nodes[i].nextSibling;
				
				while (node) {
					id = node._yuid || node._suid || (node._suid = Y.guid());
					
					if (!(id in nodes_ids) && test(node, filter, true)) {
						matches.push(node);
					}
					
					node = node.nextSibling;
				}
			}
			
			matches = Y.Array.unique(matches);
			return as_array ? matches : new Nodes(matches, this.htmleditor);
		},
		
		/**
		 * Group all nodes by parents
		 * 
		 * @returns {Array} List of groups
		 */
		getGroupedByParents: function () {
			var nodes = this.nodes,
				groups = {},
				result = [],
				i = 0,
				ii = nodes.length,
				parent,
				id;
			
			for (; i<ii; i++) {
				parent = nodes[i].parentNode;
				
				if (parent) {
					id = parent._yuid || parent._suid || (parent._suid = Y.guid());
					
					if (!(id in groups)) {
						groups[id] = result.length;
						result.push([]);
					}
					
					result[groups[id]].push(nodes[i]);
				}
			}
			
			for (i=0, ii=result.length; i<ii; i++) {
				result[i] = new Nodes(result[i], this.htmleditor);
			}
			
			return result;
		},
		
		/**
		 * Test if passes filters
		 * 
		 * @param {HTMLElement|Node|Text} node Node
		 * @param {Object} filter Filter parameters
		 * @param {Boolean} default_value Default return value when there is no filter
		 * @returns {Boolean} True if node passed test, otherwise false
		 * @private
		 */
		_testNode: function (node, filter, default_value) {
			var node_type = node.nodeType,
			 	tmp;
			 
			 if (filter.tag) {
			 	return node_type === 1 && node.tagName.toLowerCase() in filter.tag;
			 } else if (filter.type) {
			 	return node_type === filter.type;
			 } else if (filter.selector) {
			 	return node_type === 1 && Y.Selector.test(node, filter.selector);
			 } else if (filter.fn) {
			 	return !!filter.fn(node);
			 }
			 
			 return default_value ? true : false;
		},
		
		/**
		 * Checks filter options and returns object with filter options
		 * 
		 * @param {Object|String|Function} options Options
		 * @returns {Object} Object with type, selector, fn and tag
		 * @private
		 */
		_resolveFilters: function (options) {
			var filter_str    = null, // filter css selector
				filter_fn     = null, // filter function
				
				filter_tag    = null, // filter by tag name
				filter_type   = null; // filter by node type
			
			// Normalize arguments
			if (typeof options === 'function') {
				// filter function
				filter_fn = options;
			} else if (typeof options === 'string') {
				// selector
				filter_str = options;
			} else if (options && typeof options.filter === 'function') {
				filter_fn = options.filter;
			} else if (options && typeof options.filter === 'string') {
				filter_str = options.filter;
			} else if (options && typeof options.filter === 'object') {
				filter_tag = options.filter;
			}
			
			// Filter value may also be ":text", ":inline", ":block"
			if (typeof filter_str === 'string') {
				var nodes = this.nodes,
					i = 0,
					ii = nodes.length,
					matches = [];
				
				if (filter_str === ':element') {
					filter_type = 1;
					filter_str = null;
				} else if (filter_str === ':text') {
					// Shortcut to find all Text nodes
					filter_type = 3;
					filter_str = null;
				} else if (filter_str === ':inline') {
					// Find all inline elements
					filter_tag = Supra.HTMLEditor.ELEMENTS_INLINE;
					filter_str = null;
				} else if (filter_str === ':block') {
					// Find all block level elements
					filter_tag = Supra.HTMLEditor.ELEMENTS_BLOCK;
					filter_str = null;
				}
			}
			
			return {
				'type': filter_type,
				'selector': filter_str,
				'fn': filter_fn,
				'tag': filter_tag
			};
		},
		
		
		/* ------------------------- Setters ------------------------- */
		
		
		/**
		 * Populates using all selected nodes
		 * 
		 * @param {Object} selection Selection object
		 */
		fromSelection: function (selection) {
			var nodes = [];
			
			if (!selection || !selection.start) {
				nodes = [];
			} else if (selection.collapsed || selection.start === selection.end) {
				nodes = [selection.start];
			} else {
				nodes = this.htmleditor.getNodesInBetween(selection.start, selection.end);
			}
			
			this.nodes = nodes;
			this.length = nodes.length;
			return this;
		},
		
		/**
		 * Populates using all nodes starting from 'from' node to 'to' node
		 * 
		 * @param {Node} from From
		 * @param {Node} to To
		 */
		fromRange: function (from, to) {
			this.nodes = this.htmleditor.getNodesInBetween(from ,to);
			this.length = nodes.length;
			return this;
		},
		
		/**
		 * Insert nodes
		 * 
		 * @param {Node} node Node which to append
		 * @param {String} where Where to insert, 'before', 'after' or 'append'
		 */
		insert: function (node, where) {
			var item = (where === 'before' ? this.first(true) : this.last(true)),
				nodes = [];
			
			if (!item) return this;
			
			var nodes = Nodes.toArray(node, this.htmleditor.get('win')),
				i = 0,
				ii = nodes.length,
				parent = item.parentNode;
			
			if (where === 'before') {
				for (; i<ii; i++) parent.insertBefore(nodes[i], item);
			} else if (where === 'after') {
				if ((item = item.nextSibling)) {
					for (; i<ii; i++) parent.insertBefore(nodes[i], item);
				} else {
					for (; i<ii; i++) parent.appendChild(nodes[i]);
				}
			} else if (where === 'append') {
				for (; i<ii; i++) item.appendChild(nodes[i]);
			}
			
			return this;
		},
		
		/**
		 * Clone all nodes
		 */
		clone: function () {
			var nodes = this.nodes,
				cloned = [],
				i = 0,
				ii = nodes.length;
			
			for (; i<ii; i++) {
				cloned.push(nodes[i].cloneNode());
			}
			
			return new Nodes(cloned, this.htmleditor);
		},
		
		/**
		 * Remove from DOM
		 */
		remove: function () {
			var nodes = this.nodes,
				i = 0,
				ii = nodes.length,
				parent;
			
			for (; i<ii; i++) {
				parent = nodes[i].parentNode;
				if (parent) {
					parent.removeChild(nodes[i]);
				}
			}
			
			return this;
		},
		
		
		/* ------------------------- Getter ------------------------- */
		
		
		/**
		 * Returns list of nodes as array
		 * 
		 * @returns {Array} List of nodes
		 */
		toArray: function () {
			return this.nodes;
		},
		
		/**
		 * Returns node count
		 * 
		 * @returns {Number} Node count
		 */
		size: function () {
			return this.length;
		},
		
		/**
		 * Returns true if any of the nodes matches filter
		 * 
		 * Options:
		 *     filter - filter function where first argument is a node; CSS selector;
		 *              ":text" to find only Text nodes;
		 *              ":inline" to find only inline elements;
		 *              ":block" to find only block elements or
		 *              ":element" to find only elements
		 *     inAncestors - if true then ancestor elements will be checked too, default is true
		 *     inChildren  - if true then children elements will be checked too, default is true
		 * 
		 * @param {Object|String|Function} options Options or filter
		 * @returns {Boolean} True if any of the nodes matches, otherwise false
		 */
		is: function (options) {
			var filter        = this._resolveFilters(options),
				
				nodes         = this.nodes,
				i             = 0,
				ii            = nodes.length,
				
				test          = this._testNode;
			
			for (; i<ii; i++) {
				if (test(nodes[i], filter)) return true;
			}
			
			return false;
		},
		
		/**
		 * Itterate through each element
		 * 
		 * @param {Function} fn Itterator function
		 * @param {Object} context Optional function context
		 */
		each: function (fn, context) {
			var nodes = this.nodes,
				i = 0,
				ii = nodes.length;
			
			for (; i<ii; i++) {
				fn.call(context || this, nodes[i], i, this);
			}
			
			return this;
		},
		
		/**
		 * Returns first element from collection
		 * 
		 * @param {Boolean} as_node Returns node instead of node collection
		 * @returns {Node|HTMLElement|TextNode} Node
		 */
		first: function (as_node) {
			return this.item(0, as_node);
		},
		
		/**
		 * Returns last element from collection
		 * 
		 * @param {Boolean} as_node Returns node instead of node collection
		 * @returns {Node|HTMLElement|TextNode} Node
		 */
		last: function (as_node) {
			return this.item(this.length - 1, as_node);
		},
		
		/**
		 * Returns true if node contains another node
		 * 
		 * @param {Node} node Node which to append
		 * @returns {Boolean} True if contains given node, otherwise false
		 */
		contains: function (node) {
			var nodes = this.nodes,
				i = 0,
				ii = nodes.length,
				
				other = node.toArray(),
				k = 0,
				kk = other.length;
			
			for (; i<ii; i++) {
				for (k=0; k<kk; k++) {
					if (nodes[i] === other[k]) return true;
				}
			}
			
			return false;
		},
		
		/**
		 * Returns HTML node by index
		 * 
		 * @param {Number} index Node index
		 * @param {Boolean} as_node Returns node instead of node collection
		 * @returns {Node|HTMLElement|TextNode} Node
		 */
		item: function (index, as_node) {
			var nodes = this.nodes,
				count = nodes ? nodes.length : 0,
				node;
			
			if (count) {
				if (index < 0) {
					node = nodes[index + count];
				} else {
					node = nodes[index];
				}
			}
			
			return as_node ? node : new Nodes(node ? [node] : [], this.htmleditor); 
		}
	};
	
	Nodes.toArray = function (node, _win) {
		var win = _win || window;
		
		if (!node) {
			return [];
		} else if (Y.Lang.isArray(node)) {
			return node;
		} else if (node instanceof Nodes) {
			return node.toArray();
		} else if (node instanceof win.Node) {
			return [node];
		} else if (node instanceof win.NodeList) {
			return Y.Lang.toArray(node);
		} else {
			return [];
		}
	};
	
	
	Supra.HTMLEditor.Nodes = Nodes;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});