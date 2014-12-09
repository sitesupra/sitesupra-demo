YUI.add('supra.dom-style', function(Y) {
	//Invoke strict mode
	"use strict";
	
	// Cache styles and set only if styles actually changed
	var setStyleOriginal = Y.DOM.setStyle,
		re_unit = /width|height|top|left|right|bottom|margin|padding/i;
	
	/**
     * Sets a style property for a given element.
     * 
     * @method setStyle
     * @param {HTMLElement} An HTMLElement to apply the style to.
     * @param {String} att The style property to set. 
     * @param {String|Number} val The value. 
     */
    Y.DOM.setStyle = function(node, att, val, style) {
    	style = style || node.style;
    	
        var CUSTOM_STYLES = Y.DOM.CUSTOM_STYLES,
        	cached = node.cachedStyles;
        
        if (style) {
            if (val === null || val === '') { // normalize unsetting
                val = '';
            } else if (!isNaN(new Number(val)) && re_unit.test(att)) { // number values may need a unit
                val += Y.DOM.DEFAULT_UNIT;
            }
            
            if (att in CUSTOM_STYLES) {
                if (CUSTOM_STYLES[att].set) {
                	 if (!cached || cached[att] !== val) {
		            	if (!node.cachedStyles) node.cachedStyles = {};
		            	node.cachedStyles[att] = val;
		            	
		            	// Apply styles
	                    CUSTOM_STYLES[att].set(node, val, style);
		            }
                    return; // NOTE: return
                } else if (typeof CUSTOM_STYLES[att] === 'string') {
                    att = CUSTOM_STYLES[att];
                }
            } else if (att === '') { // unset inline styles
                att = 'cssText';
                val = '';
                node.cachedStyles = null;
                style[att] = val;
                return;
            }
            
            if (!cached || cached[att] !== val) {
            	if (!node.cachedStyles) node.cachedStyles = {};
            	node.cachedStyles[att] = val;
            	
            	// Apply styles
            	style[att] = val;
            }
        }
    };

    /**
     * Returns the current style value for the given property.
     * 
     * @method getStyle
     * @param {HTMLElement} An HTMLElement to get the style from.
     * @param {String} att The style property to get. 
     */
    Y.DOM.getStyle = function(node, att, style) {
        style = style || node.style;
        var CUSTOM_STYLES = Y.DOM.CUSTOM_STYLES,
            val = '',
        	cached = node.cachedStyles;

        if (style) {
            if (att in CUSTOM_STYLES) {
                if (CUSTOM_STYLES[att].get) {
                	if (cached && cached[att]) {
                		// From cache
                		return cached[att];
                	}
                	
                    return CUSTOM_STYLES[att].get(node, att, style); // NOTE: return
                } else if (typeof CUSTOM_STYLES[att] === 'string') {
                    att = CUSTOM_STYLES[att];
                }
            }
            
            if (cached && cached[att]) {
        		// From cache
        		return cached[att];
        	}
        	
            val = style[att];
            if (val === '') { // TODO: is empty string sufficient?
                val = Y.DOM.getComputedStyle(node, att);
            }
        }

        return val;
    };
    
    /**
     * Provides a normalized attribute interface.
     *  
     * @method setAttribute
     * @param {HTMLElement} el The target element for the attribute.
     * @param {String} attr The attribute to set.
     * @param {String} val The value of the attribute.
     */
    Y.DOM.setAttribute = function(el, attr, val, ieAttr) {
    	// Reset style cache
    	if (el && attr === 'style' && el.cachedStyles) {
    		el.cachedStyles = null;
    	}
    	
    	if (el && attr && el.setAttribute) {
            attr = Y.DOM.CUSTOM_ATTRIBUTES[attr] || attr;
            el.setAttribute(attr, val, ieAttr);
        }
    };
    
    /**
     * Returns style which is defined in CSS
     * 
     * @method getMatchedStyle
     * @param {HTMLElement} An HTMLElement to get the style from.
     * @param {String} property The style property to get.
     */
    Y.DOM.getMatchedStyle = function (elem, property) {
    	if (elem && elem.getDOMNode) elem = elem.getDOMNode();
    	if (!elem) return null;
    	
		// element property has highest priority
		var val = elem.style.getPropertyValue(property);
		
		// if it's important, we are done
		if(elem.style.getPropertyPriority(property))
			return val;
		
		// get matched rules
		var rules = getMatchedCSSRules(elem);
		if (!rules) return null;
		
		// iterate the rules backwards
		// rules are ordered by priority, highest last
		for(var i = rules.length; i --> 0;){
			var r = rules[i];
		
			var important = r.style.getPropertyPriority(property);
		
			// if set, only reset if important
			if(val == null || important){
				val = r.style.getPropertyValue(property);
		
				// done if important
				if(important)
					break;
			}
		}
		
		return val;
	};
	
	/**
	 * Reset cached styles
	 * 
	 * @param {Object} node
	 */
	Y.DOM.resetStyleCache = function (node) {
    	if (node && node.cachedStyles) {
    		node.cachedStyles = null;
    	}
    };
	
}, YUI.version ,{requires:['dom-core', 'dom-style']});

YUI.Env.mods['dom-base'].details.requires.push('supra.dom-style');
