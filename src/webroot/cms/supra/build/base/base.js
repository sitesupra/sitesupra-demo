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
		}
	};
	
	//Extend Node and NodeList
	Y.Base.mix(Y.Node, [NodeExtension]);
	Y.Base.mix(Y.NodeList, [NodeExtension]);
	
}, YUI.version, {'requires': ['base', 'node']});