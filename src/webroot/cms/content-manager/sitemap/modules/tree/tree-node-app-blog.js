//Invoke strict mode
"use strict";

YUI().add('website.sitemap-tree-node-app-blog', function (Y) {
	
	//Shortcuts
	var Action = Supra.Manager.getAction('SiteMap');
	
	
	/**
	 * Blog application tree node
	 */
	function Node(config) {
		Node.superclass.constructor.apply(this, arguments);
	}
	
	Node.NAME = 'TreeNodeAppBlog';
	Node.APP = 'blog';
	Node.CSS_PREFIX = 'su-tree-node';
	Node.ATTRS = {};
	
	Y.extend(Node, Action.TreeNodeApp, {
		
		/**
		 * Not expandable
		 * 
		 * @private
		 */
		'renderUI': function () {
			Node.superclass.renderUI.apply(this, arguments);
			
			this.set('expandable', false); // always
		},
		
		/**
		 * Attach event listeners
		 * 
		 * @private
		 */
		'bindUI': function () {
			Node.superclass.bindUI.apply(this, arguments);
			
			//Prevent adding new children directly inside Blog application
			this.on('child:add', function (e) {
				e.node.set('droppablePlaces', {'inside': true, 'before': false, 'after': false});
			}, this);
			
			this.on('child:before-add', function (e, setter) {
				var data = setter.data;
				
				//Only page can be added as child
				if (data.type == 'page') {
					
					// Create page
					Supra.Manager.Page.createPage({
						'locale': this.get('tree').get('locale'),
						'published': false,
						'scheduled': false,
						'type': 'page',
						'parent_id': this.get('data').id,
						
						'title': '',
						'template': '',
						'path': ''
					}, function (data) {
						
						// Open page
						var params = {
							'data': data,
							'node': this
						};
						
						if (this.get('tree').fire('page:select', params)) {
							this.set('selected', true);
						}
						
					}, this);
				}
				
				// Prevent page from actually beeing added
				return false;
			}, this);
		},
		
		/**
		 * Handle element toggle click
		 * Show or hide children
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		'_onToggleClick': function (e) {
			if (!e.target.closest('.edit') && !e.target.closest('.highlight')) {
				// Open blog manager
				var data = this.get('data');
				
				Supra.Manager.executeAction('Blog', {
					'parent_id': data.id,
					'node': this
				});
			}
		},
		
		/**
		 * Render children tree nodes
		 * 
		 * @private
		 */
		'_renderChildren': function () {
			// Blog doesn't have children in SiteMap, to see blog children
			// user must visit Blog manager
			if (this.get('childrenRendered')) return;
			this.set('childrenRendered', true);
		}
	});
	
	
	Action.TreeNodeApp.Blog = Node;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['website.sitemap-tree-node-app']});