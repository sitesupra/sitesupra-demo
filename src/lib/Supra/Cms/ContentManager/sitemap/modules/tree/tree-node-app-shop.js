//Invoke strict mode
"use strict";

YUI().add('website.sitemap-tree-node-app-shop', function (Y) {
	
	//Shortcuts
	var Action = Supra.Manager.getAction('SiteMap');
	
	
	/**
	 * Shop application tree node
	 */
	function Node(config) {
		Node.superclass.constructor.apply(this, arguments);
	}
	
	Node.NAME = 'TreeNodeAppShop';
	Node.APP = 'shop';
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
				
				// Only page can be added as child, templates doesn't make sense
				// as shop application sub-pages. Also this should never happen
				// since it's not possible to create applications in templates mode (yet?)
				if (data.type == 'page') {
					
					this.openShopManager(this, {
						'new': true
					});
					
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
		'handleToggle': function (e) {
			if (!e.target.closest('.edit') && !e.target.closest('.highlight')) {
				this.openShopManager(this);
			}
		},
		
		/**
		 * Open shop manager
		 * 
		 * @param {Object} tree_node Tree node to use for animation
		 * @param {Object} params Additional parameters to send to shop manager
		 * @private
		 */
		'openShopManager': function (tree_node, params) {
			var static_path = Supra.Manager.Loader.getStaticPath(),
				app_path = '-local/shop',
				params_url = '',
				key,
				
				app = Supra.Manager.SiteMap.getApplicationData(this.constructor.APP),
				url = '';
			
			for (key in params) {
				params_url += key + (params[key] && params[key] !== true ? '=' + params[key] : '');
			}
			
			if (app && app.url) {
				url = app.url;
			} else {
				url = static_path + app_path;
			}
			
			document.location = url + (params_url ? '#' + params_url : '');
		},
		
		/**
		 * Render children tree nodes
		 * 
		 * @private
		 */
		'_renderChildren': function () {
			// Shop doesn't have children in SiteMap, to see shop products
			// user must visit Shop manager
			
			if (this.get('childrenRendered')) return;
			this.set('childrenRendered', true);
		}
	});
	
	
	Action.TreeNodeApp.Shop = Node;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['website.sitemap-tree-node-app']});