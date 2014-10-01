//Invoke strict mode
"use strict";

YUI().add('website.sitemap-tree-util', function (Y) {
	
	//Shortcuts
	var Action = Supra.Manager.getAction('SiteMap');
	
	//Constants
	var TREENODE_TEMPLATE_DATA = {
		'children': [],
		'icon': 'page',
		'id': null,
		'layout': '',
		'preview': '/public/cms/supra/img/sitemap/preview/blank.jpg',
		'title': 'New template',
		'type': 'page',
		'published': false,
		'scheduled': false,
		'global': true,
		'localized': true,
		'localization_count': null,
		'unpublished_draft': false
	};
	
	var TREENODE_PAGE_DATA = {
		'children': [],
		'icon': 'page',
		'id': null,
		'full_path': null,
		'path': 'new-page',
		'preview': '/public/cms/supra/img/sitemap/preview/blank.jpg',
		'template': '',
		'title': 'New page',
		'type': 'page',
		'published': false,
		'scheduled': false,
		'global': true,
		'localized': true,
		'localization_count': null,
		'unpublished_draft': false
	};
	
	var TREENODE_PAGE_GROUP_DATA = Supra.mix({}, TREENODE_PAGE_DATA, {
		'type': 'group',
		'icon': 'group',
		'preview': '/public/cms/supra/img/sitemap/preview/group.png'
	});
	
	var TREENODE_PAGE_APP_DATA = Supra.mix({}, TREENODE_PAGE_DATA, {
		'type': 'application',
		'application_id': '',
		'collapsed': false
	});
	
	
	
	/**
	 * Page edit settings form
	 */
	function Plugin () {
		Plugin.superclass.constructor.apply(this, arguments);
	};
	
	Plugin.NAME = 'PluginTreeUtilities';
	Plugin.NS = 'util';
	
	Y.extend(Plugin, Y.Plugin.Base, {
		
		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		
		
		
		
		
		
		/**
		 * ------------------------------ API ------------------------------
		 */
		
		/**
		 * Creates proxy tree node
		 * 
		 * @return Proxy tree node
		 * @type {Object}
		 */
		'createProxyNode': function (data, groups) {
			var tree = this.get('host'),
				mode = tree.get('mode'),
			
				data = Supra.mix({}, this.getDefaultNodeData(data.type), data),
				node = null;
			
			node = tree._createNode({
				'identifier': data.id || Y.guid(),
				'data': data,
				
				'draggable': true,
				'droppable': false,
				
				'editable': false,
				'publishable': false,
				
				'type': data.type,
				
				'depth': 0
			});
			
			node.render(document.body);
			
			//Set groups
			if (groups) {
				node._dnd.set('groups', groups);
			}
			
			return node;
		},
		
		/**
		 * Returns default tree node data based on current tree mode
		 * and on node type
		 * 
		 * @param {String} type Node type, "page" or "group" or "application"
		 * @return Default data
		 * @type {Object}
		 * @private
		 */
		'getDefaultNodeData': function (type) {
			if (this.get('host').get('mode') == 'pages') {
				if (type == 'group') {
					return TREENODE_PAGE_GROUP_DATA;
				} else if (type == 'application') {
					return TREENODE_PAGE_APP_DATA;
				} else {
					return TREENODE_PAGE_DATA;
				}
			} else {
				return TREENODE_TEMPLATE_DATA;
			}
		}
		
		
	});
	
	Action.PluginTreeUtilities = Plugin;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['website.sitemap-tree']});