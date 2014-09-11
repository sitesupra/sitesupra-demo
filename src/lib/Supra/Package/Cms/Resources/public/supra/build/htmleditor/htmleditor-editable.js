YUI().add('supra.htmleditor-editable', function (Y) {
	//Invoke strict mode
	"use strict";
	
	Y.mix(Supra.HTMLEditor.prototype, {
		/**
		 * Current element editing is allowed
		 * @type {Boolean}
		 */
		editingAllowed: true,
		
		/**
		 * Disable content editing
		 * 
		 * @param {HTMLElement} node HTML element which shouldn't be editable
		 */
		disableNodeEditing: function (node) {
			node = Y.Node.getDOMNode(node);
			
			node.editable = false;
			Y.DOM.addClass(node, 'su-uneditable');
			Y.DOM.removeClass(node, 'su-editable');
		},
		
		/**
		 * Enable content editing
		 * 
		 * @param {HTMLElement} node HTML element which shouldn't be editable
		 */
		enableNodeEditing: function (node) {
			node = Y.Node.getDOMNode(node);
			
			node.editable = true;
			Y.DOM.removeClass(node, 'su-uneditable');
			Y.DOM.addClass(node, 'su-editable');
		},
		
		/**
		 * Return true if node is editable, otherwise false
		 * 
		 * @param {HTMLElement} node
		 * @return True if node is editable
		 * @type {Boolean}
		 */
		isEditable: function (node) {
			var rootNode = Y.Node.getDOMNode(this.get('srcNode'));
			node = Y.Node.getDOMNode(node);
			
			while(node && node !== rootNode && !('editable' in node)) {
				node = node.parentNode;
			}
			return node ? node.editable !== false : true;
		},
		
		/**
		 * Return true if selection is editable, otherwise false
		 * Checks all nodes between selection start and end node
		 * 
		 * @param {Object} selection Selection object
		 * @return True if selection is editable
		 * @type {Boolean}
		 */
		isSelectionEditable: function (selection) {
			//If only one node is selected, then 
			if (selection.start == selection.end) return this.isEditable(selection.start);
			
			var rootNode = Y.Node.getDOMNode(this.get('srcNode')),
				node = selection.start,
				endNode = selection.end,
				skipNextChildren = false;
			
			/*
			 * Returns next node which should be checked
			 */
			function getNextNode(node, rootNode) {
				//Previous getNextNode returned parent, so children were
				//already traversed
				var skipChildren = skipNextChildren;
					skipNextChildren = false;
				
				if (node === rootNode) return null;
				if (node.firstChild && !skipChildren) {
					return node.firstChild;
				}
				if (!node.parentNode) return null;
				if (node.nextSibling) return node.nextSibling;
				skipNextChildren = true;
				return node.parentNode;
			}
			
			//Traverse to end node and see if any node in the way is not editable
			while(true) {
				if ('editable' in node && !node.editable) return false;
				
				if (node === endNode) break;
				
				node = getNextNode(node);
				if (!node) break;
			}
			
			//Traverse up to root node and see if any node is/isn't editable
			node = selection.end;
			while(node && node !== rootNode) {
				if ('editable' in node) return node.editable;
				node = node.parentNode;
			}
			
			return true;
		},
		
		/**
		 * Restore editable states from classnames
		 * Used after setHTML call
		 */
		restoreEditableStates: function () {
			var srcNode = this.get('srcNode').getDOMNode(),
				nodes = null,
				i = 0,
				ii = 0;
			
			nodes = srcNode.querySelectorAll('.su-uneditable');
			for (i=0, ii=nodes.length; i<ii; i++) {
				nodes[i].editable = false;
			}
			
			nodes = srcNode.querySelectorAll('.su-editable');
			for (i=0, ii=nodes.length; i<ii; i++) {
				nodes[i].editable = true;
			}
		},
		
		/**
		 * Returns true if key pressed is navigation key and will not
		 * modify content
		 * 
		 * @param {Number} charCode
		 * @return If key will modify content
		 * @type {Boolean}
		 */
		navigationCharCode: function (charCode) {
			//32 - Space, 8 - backspace, 13 - return
			if (charCode == 32 || charCode == 8 || charCode == 13) return false;
			
			// before 40 are navigation keys
			// 91	- Left windows
			// 92	- Right windows
			// 93	- Context
			if (charCode <= 40 || charCode == 91 || charCode == 92 || charCode == 93) return true;
			
			return false;
		},
		
		/**
		 * Returns true if key pressed will insert new character and will modify
		 * content
		 * 
		 * @param {Number} charCode
		 * @return If key will modify content by adding something
		 * @type {Boolean}
		 */
		insertCharacterCharCode: function (charCode) {
			//32 - Space, 8 - backspace, 13 - return, 46 - delete
			if (charCode == 8 || charCode == 46) {
				return false;
			} else if (charCode == 32 || charCode == 13) {
				return true;
			}
			
			// before 40 are navigation keys
			// 91	- Left windows
			// 92	- Right windows
			// 93	- Context
			if (charCode <= 40 || charCode == 91 || charCode == 92 || charCode == 93) return false;
			
			return true;
		},
		
		/**
		 * Disable object resizing using handles
		 */
		disableObjectResizing: function () {
			try {
				this.get('doc').execCommand("enableInlineTableEditing", false, false);
				this.get("doc").execCommand("enableObjectResizing", false, false);
			} catch (err) {}
			
			if (Y.UA.ie) {
				//Prevent resizestart event
				this.get("srcNode").getDOMNode().onresizestart = function (e) {
					return false;
				};
			}
		}

	});


	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});