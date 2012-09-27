YUI.add('supra.tree-draggable', function(Y) {
	//Invoke strict mode
	"use strict";
	
	function TreeDraggable (config) {
		TreeDraggable.superclass.constructor.apply(this, arguments);
	}
	
	TreeDraggable.NAME = 'tree-draggable';
	
	TreeDraggable.ATTRS = {
		/**
		 * Default children class
		 * @type {Function}
		 */
		'defaultChildType': {  
            value: Supra.TreeNodeDraggable
		},
		
		/**
		 * Node to which all drag proxies should be added to
		 * @type {Object}
		 */
		'dragProxyParent': {
			value: null
		}
	};
	
	Y.extend(TreeDraggable, Supra.Tree, {
		_renderTreeUIChild: function (data, i) {
			var isDraggable = (data && 'isDraggable' in data ? data.isDraggable : true);
			var isDropTarget = (data && 'isDropTarget' in data ? data.isDropTarget : true);
			this.add({'data': data, 'label': data.title, 'icon': data.icon, 'isDropTarget': isDropTarget, 'isDraggable': isDraggable}, i);
		}
	});
	
	Supra.TreeDraggable = TreeDraggable;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.tree', 'supra.tree-node-draggable']});