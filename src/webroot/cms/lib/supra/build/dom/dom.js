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
	
	if (document.body.classList) {
		var addClass, removeClass, hasClass;
		var hasClassResults = window.hasClassResults = [];
		
		Y.DOM.hasClass = function (node, className) {
			if (node && node.classList && className && className.indexOf) {
				if (className.indexOf(' ') !== -1) {
					className = className.split(' ');
					for (var i=0, ii=className.length; i<ii; i++) {
						if (!node.classList.contains(className[i])) return false;
					}
					return true;
				} else {
					return node.classList.contains(className);
				}
			}
			return true;
		};
		Y.DOM.addClass = function (node, className) {
			if (node && node.classList && className && className.indexOf) {
				if (className.indexOf(' ') !== -1) {
					className = className.split(' ');
					for (var i=0, ii=className.length; i<ii; i++) {
						if (className[i]) node.classList.add(className[i]);
					}
				} else {
					node.classList.add(className);
				}
			}
		};
		Y.DOM.removeClass = function (node, className) {
			if (node && node.classList && className && className.indexOf) {
				if (className.indexOf(' ') !== -1) {
					className = className.split(' ');
					for (var i=0, ii=className.length; i<ii; i++) {
						if (className[i]) node.classList.remove(className[i]);
					}
				} else {
					node.classList.remove(className);
				}
			}
		};
		Y.DOM.replaceClass = function (node, oldC, newC) {
			Y.DOM.removeClass(node, oldC);
			Y.DOM.addClass(node, newC);
		};
		Y.DOM.toggleClass = function (node, className, force) {
			var add = (force !== undefined) ? force :
	                !(hasClass(node, className));
	
	        if (add) {
	            addClass(node, className);
	        } else {
	            removeClass(node, className);
	        }
		};
		
		hasClass = Y.DOM.hasClass;
		addClass = Y.DOM.addClass;
		removeClass = Y.DOM.removeClass;
	}
	
}, YUI.version ,{requires:['dom-core']});

YUI.Env.mods['dom-base'].details.requires.push('supra.dom');
