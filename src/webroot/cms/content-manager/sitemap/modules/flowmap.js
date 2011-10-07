//Invoke strict mode
"use strict";

YUI().add('website.sitemap-flowmap', function (Y) {

	/*
	 * Shortcuts
	 */
	var C = Y.ClassNameManager.getClassName,
		Action = SU.Manager.getAction('SiteMap');
	
	/*
	 * Generated HTML:
	 * 		<ul class="tree">
	 * 			<li id="tree0_node0">
	 * 				<div class="tree-node tree-node-published">
	 * 					<span class="img"><img src="/cms/lib/supra/img/tree/home.png" /></span> <label>Home</label>
	 * 				</div>
	 * 				<ul class="tree-children">
	 * 					<li id="tree0_node1">
	 *		 				<div class="tree-node tree-node-scheduled">
	 * 							<span class="img"><img src="/cms/lib/supra/img/tree/page.png" /></span> <label>Page</label>
	 * 
	 * 						</div>
	 *					</li>
	 * 				</ul>
	 * 			</li>
	 * 		</ul>
	 * 
	 */
	
	/**
	 * Tree view
	 * Events:
	 *   node-click  -  when one of the nodes is clicked
	 * 
	 * @param {Object} config
	 */
	function FlowMap (config) {
		FlowMap.superclass.constructor.apply(this, arguments);
	}
	FlowMap.NAME = 'flowmap';
	Y.extend(FlowMap, Supra.Tree);
	
	
	Action.FlowMap = Y.Base.create('flowmap', FlowMap, [], {
		
	}, {
		
		ATTRS: {
			'defaultChildType': {  
	            value: Action.FlowMapItem
	        }
		},
		
		GUID: 1,
		
		CLASS_NAME: C('flow-map'),
		
		HTML_PARSER: {}
	});
	

	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.tree']});
