//Invoke strict mode
"use strict";

YUI().add('website.sitemap-delete-page', function (Y) {
	
	//Shortcuts
	var Action = Supra.Manager.getAction('SiteMap');
	
	/*
	 * Recycle bin with drag and drop support
	 */
	function DeletePage(config) {
		DeletePage.superclass.constructor.apply(this, arguments);
	}
	
	DeletePage.NAME = 'DeletePage';
	DeletePage.CSS_PREFIX = 'su-delete-page';
	DeletePage.ATTRS = {};
	
	Y.extend(DeletePage, Y.Widget, {
		
		/**
		 * Drop target
		 * @type {Object}
		 * @private
		 */
		'_dnd': null,
		
		
		/**
		 * Render UI
		 * 
		 * @private
		 */
		'renderUI': function () {
			this.get('boundingBox').addClass('block-inset');
		},
		
		/**
		 * Bind UI events
		 * 
		 * @private
		 */
		'bindUI': function () {
			var dnd = this._dnd = new Y.DD.Drop({
				'node': this.get('boundingBox'),
				'groups': ['delete']
			});
			
			dnd.on('drop:hit', this._nodeDrop, this);
			
			//On click open recycle bin action
			this.get('boundingBox').on('click', this._toggleRecycleBin, this);
			
			//Recycle bin
			var recycle = Supra.Manager.getAction('SiteMapRecycle'),
				sitemap = Supra.Manager.getAction('SiteMap');
			
			recycle.after('execute', function () {
				//When sidebar is shown update view, show arrows if needed
				this.tree.get('view').checkOverflow();
			}, sitemap);
			
			recycle.after('hide', function () {
				//When sidebar is hidden update view, hide arrows if needed
				this.tree.get('view').checkOverflow();
			}, sitemap);
		},
		
		/**
		 * Sync UI state with widget attribute states
		 * 
		 * @private
		 */
		'syncUI': function () {
			
		},
		
		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		/**
		 * Open recycle bin action
		 * 
		 * @private
		 */
		'_toggleRecycleBin': function () {
			var action = Supra.Manager.getAction('SiteMapRecycle');
			if (action.get('visible')) {
				action.hide();
			} else {
				action.execute();
			}
		},
		
		/**
		 * On drop delete page
		 * 
		 * @private
		 */
		'_nodeDrop': function (e) {
			var node = e.drag.get('treeNode');
			if (node && node.get('editable')) {
				Action.tree.page_edit.deletePage(node);
			}
		}
		
		
		/**
		 * ------------------------------ API ------------------------------
		 */
		
		
	});
	
	
	Action.DeletePage = DeletePage;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['widget', 'dd']});