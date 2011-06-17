YUI.add('supra.tree-dragable', function(Y) {
	
	function TreeDragable (config) {
		TreeDragable.superclass.constructor.apply(this, arguments);
	}
	
	TreeDragable.NAME = 'tree-dragable';
	
	TreeDragable.ATTRS = {
		'defaultChildType': {  
            value: Supra.TreeNodeDragable
        }
	};
	
	Y.extend(TreeDragable, Supra.Tree, {
		_renderTreeUIChild: function (data, i) {
			var isDragable = (data && 'isDragable' in data ? data.isDragable : true);
			var isDropTarget = (data && 'isDropTarget' in data ? data.isDropTarget : true);
			var node = this.add({'data': data, 'label': data.title, 'icon': data.icon, 'isDropTarget': isDropTarget, 'isDragable': isDragable}, i);
		}
	});
	
	Supra.TreeDragable = TreeDragable;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.tree', 'supra.tree-node-dragable']});