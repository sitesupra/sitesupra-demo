YUI.add('supra.dom', function(Y) {
	//Invoke strict mode
	"use strict";
	
	//If already defined, then exit
	if (Y.DOM.removeFromDOM) return;
	
	/**
	 * Removes element from DOM to restore its position later
	 * 
	 * @param {Object} node
	 * @return Point information aboute node and its position
	 * @type {Object}
	 */
	Y.DOM.removeFromDOM = function (node) {
		var node = (node.nodeType ? new Y.Node(node) : node);
		var where = '';
		var ref = node.ancestor();
		var tmp = node.previous();
		
		if (tmp) {
			ref = tmp;
			where = 'after';
		} else {
			tmp = node.next();
			if (tmp) {
				where = 'before';
				ref = tmp;
			}
		}
		
		tmp = Y.Node.getDOMNode(node);
		tmp.parentNode.removeChild(tmp);
		
		return {
			'node': node,
			'where': where,
			'ref': ref
		}
	};
	
	Y.DOM.restoreInDOM = function (point) {
		point.ref.insert(point.node, point.where);
	};
	
}, YUI.version);