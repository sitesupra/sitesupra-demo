//Invoke strict mode
"use strict";

YUI().add('website.sitemap-tree-node-app', function (Y) {
	
	//Shortcuts
	var Action = Supra.Manager.getAction('SiteMap');
	
	
	/**
	 * Application tree node
	 */
	function TreeNodeApp(config) {
		TreeNodeApp.superclass.constructor.apply(this, arguments);
	}
	
	TreeNodeApp.NAME = 'TreeNodeApp';
	TreeNodeApp.CSS_PREFIX = 'su-tree-node';
	TreeNodeApp.ATTRS = {
		'application_id': {
			'value': null
		}
	};
	
	Y.extend(TreeNodeApp, Action.TreeNode, {
		/**
		 * Render UI
		 * 
		 * @private
		 */
		'renderUI': function () {
			TreeNodeApp.superclass.renderUI.apply(this, arguments);
			
			this.set('application_id', this.get('data').application_id);
			
			//Application specific classname
			this.get('boundingBox').addClass(this.getClassName(this.get('data').application_id));
		},
		
		/**
		 * Instead of expanding children show list popup
		 * if there is any
		 * 
		 * @private
		 */
		'_setExpandedExpand': function () {
			var returnValue = TreeNodeApp.superclass._setExpandedExpand.apply(this, arguments);
			
			var children = this.children(),
				i = 0,
				size = children.length;
			
			for(; i<size; i++) {
				if (children[i].isInstanceOf('TreeNodeList')) {
					children[i].expand(); break;
				}
			}
			
			return returnValue;
		}
	});
	
	
	Action.TreeNodeApp = TreeNodeApp;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['website.sitemap-tree-node']});