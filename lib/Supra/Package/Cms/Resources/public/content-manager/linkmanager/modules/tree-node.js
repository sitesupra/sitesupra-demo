YUI.add('linkmanager.sitemap-linkmanager-node', function (Y) {
	//Invoke strict mode
	"use strict";
	
	function LinkMapTreeNode (config) {
		LinkMapTreeNode.superclass.constructor.apply(this, [config]);
	};
	
	LinkMapTreeNode.NAME = 'tree-node';
	
	LinkMapTreeNode.ATTRS = {
		'defaultChildType': {
			'value': LinkMapTreeNode
		}
	};
	
	Y.extend(LinkMapTreeNode, Supra.TreeNode, {
		CONTENT_TEMPLATE: '<div class="tree-node">\
			  					<div><span class="toggle hidden"></span><span class="remove"></span><span class="img"><img src="/public/cms/supra/img/tree/none.png" /></span> <label></label></div>\
			  				</div>\
			  				<ul class="tree-children">\
			  				</ul>',
		
		bindUI: function () {
			LinkMapTreeNode.superclass.bindUI.apply(this, arguments);
			
			this.get('boundingBox').one('span.remove').on('click', this.unsetSelectedState, this);
		},
		
		/**
		 * Unset selected state
		 */
		unsetSelectedState: function (evt) {
			this.getTree().set('selectedNode', null);
			evt.halt();
		}
	});
	
	Supra.LinkMapTreeNode = LinkMapTreeNode;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};

}, YUI.version, {'requires': ['supra.tree', 'supra.tree-node']});