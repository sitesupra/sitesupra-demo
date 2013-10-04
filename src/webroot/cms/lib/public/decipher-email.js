(function (win) {
	
	var rot_map = null,
		doc  = win.document,
		body = doc.body,
		state = document.readyState,
		
		// HTMLElement is for IE8+, Element is for IE8
		ELEMENT_CONSTRUCTOR = typeof HTMLElement !== 'undefined' ? HTMLElement : Element;
	
	/**
	 * Create character map for rot13
	 */
	function init () {
		if (rot_map) return;
		
		var map = rot_map = {};
        	s   = "abcdefghijklmnopqrstuvwxyz",
        	i   = 0,
        	ii  = s.length;
        
        for (; i<ii; i++) map[s.charAt(i)] = s.charAt((i+13)%26);
        for (; i<ii; i++) map[s.charAt(i).toUpperCase()] = s.charAt((i+13)%26).toUpperCase();
	}
	
	/**
	 * Rotate string
	 * 
	 * @param {String} str
	 * @returns {String} String where characters are rotated according to algorithm
	 */
	function rot13 (str) {
		init();
		
		var map = rot_map,
			s = '',
			b = '',
			i = 0, ii = str.length;
		
		for (; i<ii; i++) {
			b = str.charAt(i);
			s += ((b>='A' && b<='Z') || (b>='a' && b<='z') ? map[b] : b);
		}
		
		return s;
	}
	
	/**
	 * Returns all text nodes which are inside given node
	 * 
	 * @param {HTMLElement} node
	 * @returns {Array} Array with all text nodes
	 */
	function getTextNodes (node) {
		var result = [],
			nodes  = node.childNodes,
			i      = 0,
			ii     = nodes.length;
		
		for (; i<ii; i++) {
			if (nodes[i].nodeType == 1) { // HTMLElement
				result = result.concat(getTextNodes(nodes[i]));
			} else if (nodes[i].nodeType == 3) { // TextNode
				result.push(nodes[i]);
			}
		}
		
		return result;
	}
	
	/**
	 * Converts email to readable form
	 * 
	 * @param {HTMLElement|Array|String} node Node, list of nodes or string
	 * @param {String} attr Assume this as data-email attribute value
	 */
	function decipherEmail (node, attr) {
		if (node instanceof ELEMENT_CONSTRUCTOR) {
			// Single node
			var attr = attr || node.getAttribute('data-email');
			if (!attr) return;
			
			// Text
			if (attr.indexOf('text') !== -1) {
				var texts = getTextNodes(node),
					i     = 0,
					ii    = texts.length;
				
				for (; i<ii; i++) {
					if (texts[i].textContent) {
						texts[i].textContent = rot13(texts[i].textContent);
					} else if (texts[i].innerText) {
						texts[i].innerText = rot13(texts[i].innerText);
					} else if (texts[i].nodeValue) { // IE8
						texts[i].nodeValue = rot13(texts[i].nodeValue);
					} 
				}
			}
			
			// Href
			if (attr.indexOf('href') !== -1) {
				node.setAttribute('href', rot13(node.getAttribute('href')));
			}
			
		} else if (node && node.length) {
			// List of nodes
			var i  = 0,
				ii = node.length;
			
			for (; i<ii; i++) {
				decipherEmail(node[i], attr);
			}
			
			return;
		} else if (typeof node === 'string') {
			return rot13(node);
		} else {
			return;
		}
	}
	
	/**
	 * Traverse tree and look for nodes with data-email attribute
	 */
	function traverseDOMForEmails () {
		var $ = win.jQuery;
		
		if ($) {
			decipherEmail($('[data-email]'));
		} else if (doc.querySelectorAll) {
			// Modern browsers
			decipherEmail(doc.querySelectorAll('[data-email]'));
		} else {
			// Old browsers, check all elements for attribute
			var nodes = doc.getElementsByTagName('*'),
				i     = 0,
				ii    = nodes.length;
			
			for (; i<ii; i++) {
				if (nodes[i].getAttribute('data-email')) {
					decipherEmail(nodes[i]);
				}
			}
		}
	}
	
	/**
	 * Traverse DOM when document is ready
	 */
	if (state == 'complete' || state == 'interactive') {
		traverseDOMForEmails();
	} else {
		// Wait till everything is loaded
		if (win.addEventListener) {
			win.addEventListener('load', traverseDOMForEmails, false);
		} else if (body.attachEvent) {
			win.attachEvent('onload', traverseDOMForEmails);
		}
	}
	
	win.decipherEmail = decipherEmail;
	
})(window);