//Invoke strict mode
"use strict";

YUI.add('supra.base', function (Y) {
	
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
		 * Add or remove classname
		 * 
		 * @param {String} classname CSS classname
		 * @param {Boolean} add Will add class if true and remove if false
		 */
		setClass: function (classname, add) {
			if (add) {
				this.addClass(classname);
			} else {
				this.removeClass(classname);
			}
			return this;
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
		}
	};
	
	//Extend Node and NodeList
	Y.Base.mix(Y.Node, [NodeExtension]);
	Y.Base.mix(Y.NodeList, [NodeExtension]);
	
}, YUI.version, {'requires': ['base', 'node']});