YUI.add('supra.base', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Extends Y.Base with additional functionality
	 */
	function BaseExtension () {
	};
	
	BaseExtension.prototype = {
		isInstanceOf: function (classname) {
			var classes = this._getClasses();
				classname = classname.toLowerCase();
				
			for(var i=0,ii=classes.length; i<ii; i++) {
				if (classes[i].NAME.toLowerCase() == classname) return true;
			}
			return false;
		}
	};
	
	//Extend base
	Y.Base.mix(Y.Base, [BaseExtension]);
	
	/**
	 * Extends Y.Node with additional functionality
	 */
	var node_prototype_fire = Y.Node.prototype.fire;
	
	function NodeExtension () {
	};
	
	NodeExtension.prototype = {
		isInstanceOf: function (classname) {
			var source = this,
				target = Y[classname] || Supra[classname];
			
			return target ? source instanceof target : false;
		},
		
		/**
		 * Returns node or closest ancestor matching selector
		 * 
		 * @param {String} selector CSS selector
		 * @return Y.Node matching selector
		 * @type {Object}
		 */
		closest: function (selector) {
			if (typeof selector == 'string') {
				return this.test(selector) ? this : this.ancestor(selector);
			} else if (selector) {
				var node = Y.one(selector),
					self = this;
				
				while(self && !node.compareTo(self)) {
					self = self.ancestor();
				}
				
				return self;
			} else {
				return null;
			}
		},
		
		/**
		 * Returns node width without padding and border
		 * 
		 * @returns {Number} Inner width
		 */
		getInnerWidth: function () {
			var width = this.get('offsetWidth'),
				padding = (parseInt(this.getStyle('paddingLeft'), 10) || 0) + (parseInt(this.getStyle('paddingRight'), 10) || 0),
				//margin = (parseInt(this.getStyle('marginLeft'), 10) || 0) + (parseInt(this.getStyle('marginRight'), 10) || 0),
				border = (parseInt(this.getStyle('borderLeftWidth'), 10) || 0) + (parseInt(this.getStyle('borderRightWidth'), 10) || 0);
			
			return Math.max(0, width - padding - border);
		},
		
		/**
		 * Returns node height without padding and border
		 * 
		 * @returns {Number} Inner height
		 */
		getInnerHeight: function () {
			var height = this.get('offsetHeight'),
				padding = (parseInt(this.getStyle('paddingTop'), 10) || 0) + (parseInt(this.getStyle('paddingBottom'), 10) || 0),
				//margin = (parseInt(this.getStyle('marginTop'), 10) || 0) + (parseInt(this.getStyle('marginBottom'), 10) || 0),
				border = (parseInt(this.getStyle('borderTopWidth'), 10) || 0) + (parseInt(this.getStyle('borderBottomWidth'), 10) || 0);
			
			return Math.max(0, height - padding - border);
		},
		
		/**
		 * Trigger event and if event is in custom DOM events, then
		 * bubble like other DOM events do it
		 * 
		 * @param {String} type Event name
		 * @param {Array} args Data for event facade object
		 */
		fire: function (type, args) {
			if (Y.Node.CUSTOM_DOM_EVENTS[type]) {
				var node = this.getDOMNode(),
					event; // The custom event that will be created
				
				if (!node) return true;

				if (document.createEvent) {
					event = document.createEvent('HTMLEvents');
					event.details = args;
					event.initEvent(type, true, true);
					
					try {
						event.type = type;
					} catch (e) {
						// IE throws an error
					}
					
					node.dispatchEvent(event);
				} else {
					event = document.createEventObject();
					event.eventType = type;
					event.type = type;
					
					node.fireEvent("on" + event.eventType, event);
				}
				
				return true; // @TODO Return false if event was cancelled
			} else {
				return node_prototype_fire.call(this, type, args);
			}
		},
		
		/**
	     * Returns style which is defined in CSS
	     * 
	     * @param {String} att The style property to get.
	     */
		getMatchedStyle: function (property) {
			return Y.DOM.getMatchedStyle(this, property);
		}
	};
	
	//List of event names, which should bubble like DOM events
	Y.Node.CUSTOM_DOM_EVENTS = {
		'contentresize': 1
	};
	
	//If event names won't be in the DOM_EVENTS, then listeners will not
	//capture emitted event
	Supra.mix(Y.Node.DOM_EVENTS, Y.Node.CUSTOM_DOM_EVENTS);
	
	//Extend Node and NodeList
	Y.Base.mix(Y.Node, [NodeExtension]);
	Y.Base.mix(Y.NodeList, [NodeExtension]);
	
}, YUI.version, {'requires': ['base', 'node', 'node-event-html5']});
