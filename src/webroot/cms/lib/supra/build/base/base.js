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
			} else {
				var node = Y.one(selector),
					self = this;
				
				while(self && !node.compareTo(self)) {
					self = self.ancestor();
				}
				
				return self;
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
		}
	};
	
	//Extend Node and NodeList
	Y.Base.mix(Y.Node, [NodeExtension]);
	Y.Base.mix(Y.NodeList, [NodeExtension]);
	
}, YUI.version, {'requires': ['base', 'node']});