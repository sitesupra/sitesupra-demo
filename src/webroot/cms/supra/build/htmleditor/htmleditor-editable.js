//Invoke strict mode
"use strict";

YUI().add('supra.htmleditor-editable', function (Y) {
	
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
			var nodes = [].slice.call(this.get('doc').body.getElementsByTagName('*'), 0),
				node;
			for(var i=0,ii=nodes.length; i<ii; i++) {
				node = nodes[i];
				if (Y.DOM.hasClass(node, 'su-uneditable')) {
					node.editable = false;
				} else if (Y.DOM.hasClass(node, 'su-editable')) {
					node.editable = true;
				}
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
			switch(charCode) {
				case 33:	//Page up
				case 34:	//Page down
				case 35:	//End
				case 36:	//Home
				case 37:	//Left
				case 38:	//Up
				case 39:	//Right
				case 40:	//Down
				case 91:	//Left windows
				case 92:	//Right windows
				case 93:	//Context
					return true;
			}
			return false;
		}

	});


	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});