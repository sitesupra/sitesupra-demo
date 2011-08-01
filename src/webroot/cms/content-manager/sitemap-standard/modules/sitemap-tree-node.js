//Invoke strict mode
"use strict";


YUI.add('website.sitemap-tree-node', function (Y) {
	
	/**
	 * Sitemap tree node highlights published pages
	 * 
	 * @param {Object} config
	 */
	function SitemapTreeNode (config) {
		SitemapTreeNode.superclass.constructor.apply(this, arguments);
		
		this.init.apply(this, arguments);
	}
	
	SitemapTreeNode.NAME = 'tree-node-dragable';
	SitemapTreeNode.ATTRS = {
		/**
		 * Child class
		 */
		'defaultChildType': {  
            value: SitemapTreeNode
        }
	};
	
	Y.extend(SitemapTreeNode, Supra.TreeNodeDragable, {
		ROOT_TYPE: SitemapTreeNode,
		
		renderUI: function () {
			SitemapTreeNode.superclass.renderUI.apply(this, arguments);
			
			if (this.get('data').published) {
				this.get('boundingBox').addClass('tree-node-published');
			}
		}
	});
	
	
	Supra.SitemapTreeNode = SitemapTreeNode;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.tree-dragable']});