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
		 * Drag and drop groups
		 * @type {Array}
		 */
		'DND_GROUPS': [
			'new-page',
			'delete'
		],
		
		/**
		 * Groups which are allowed to be dropped here
		 * @type {Array}
		 */
		'DND_GROUPS_ALLOW': [
			'new-page'
		],
		
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
				var data = this.get('data'),
					deferred = null;
				
				// Start loading immediately
				Supra.Manager.loadAction('Blog');
				 
				// Arguments:
				//		node
				//		reverse animation
				//		origin
				deferred = Supra.Manager.SiteMap.animate(this.get('itemBox'), false, 'blog');
				
				deferred.done(function () {
					// Show blog when animation is done
					Supra.Manager.executeAction('Blog', {
						'parent_id': data.id,
						'node': this,
						'sitemap_element': this.get('itemBox')
					});
				}, this);
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