//Invoke strict mode
"use strict";


YUI.add('website.tree-node-permissions', function(Y) {
	
	function TreeNodePermissions (config) {
		TreeNodePermissions.superclass.constructor.apply(this, arguments);
		
		this.init.apply(this, arguments);
	}
	
	TreeNodePermissions.NAME = 'tree-node-draggable';
	TreeNodePermissions.ATTRS = {
		/**
		 * Inside node, before and after can be dropped 
		 * other nodes
		 */
		'isDropTarget': {
			value: false
		},
		
		/**
		 * Tree node shouldn't be selectable
		 */
		'selectable': {
			value: false
		},
		
		/**
		 * Child class
		 */
		'defaultChildType': {  
            value: TreeNodePermissions
        }
	};
	
	Y.extend(TreeNodePermissions, Supra.TreeNodeDraggable, {
		ROOT_TYPE: TreeNodePermissions,
		
		renderUI: function () {
			//Overwrite attribute values
			this.set('isDropTarget', false);
			this.set('selectable', false);
			
			TreeNodePermissions.superclass.renderUI.apply(this, arguments);
		}
	});
	
	
	Supra.TreeNodePermissions = TreeNodePermissions;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.tree-node-draggable']});