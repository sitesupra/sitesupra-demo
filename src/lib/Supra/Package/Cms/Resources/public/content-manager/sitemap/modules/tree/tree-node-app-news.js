//Invoke strict mode
"use strict";

YUI().add('website.sitemap-tree-node-app-news', function (Y) {
	
	//Shortcuts
	var Action = Supra.Manager.getAction('SiteMap');
	
	
	/**
	 * News application tree node
	 */
	function Node(config) {
		Node.superclass.constructor.apply(this, arguments);
	}
	
	Node.NAME = 'TreeNodeAppNews';
	Node.APP = 'news';
	Node.CSS_PREFIX = 'su-tree-node';
	Node.ATTRS = {};
	
	Y.extend(Node, Action.TreeNodeApp, {
		
		/**
		 * Attach event listeners
		 * 
		 * @private
		 */
		'bindUI': function () {
			Node.superclass.bindUI.apply(this, arguments);
			
			//Prevent adding new children directly inside News application
			this.on('child:add', function (e) {
				e.node.set('droppablePlaces', {'inside': true, 'before': false, 'after': false});
			}, this);
			
			this.on('child:before-add', function (e, setter) {
				var data = setter.data;
				
				if (data.type == 'page') {
					// Page should be added to the actual list
					
					if (!this.get('expanded')) {
						this.expand();
						
						if (this.get('loading')) {
							// If children are loading, then wait till it's finished
							// because we don't have a child in this case
							this.once('expanded', function (e) {
								this.get('tree').insertData(data, this.item(0), 'inside');
							}, this);
							
							return false;
						}
					}
					
					setter.target = this.item(0);
				} else if (data.type != 'group') {
					// Only groups are allowed as direct children of news app
					return false;
				}
			}, this);
		},
		
		/**
		 * Render children tree nodes
		 * 
		 * @private
		 */
		'_renderChildren': function () {
			if (this.get('childrenRendered')) return;
			Node.superclass._renderChildren.apply(this, arguments);
			
			//Prevent adding new children directly inside News application
			this.children().forEach(function (node) {
				node.set('droppablePlaces', {'inside': true, 'before': false, 'after': false});
			});
		}
	});
	
	
	Action.TreeNodeApp.News = Node;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['website.sitemap-tree-node-app']});