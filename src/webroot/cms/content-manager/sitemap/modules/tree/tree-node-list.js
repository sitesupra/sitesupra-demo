//Invoke strict mode
"use strict";

YUI().add('website.sitemap-tree-node-list', function (Y) {
	
	//Shortcuts
	var Action = Supra.Manager.getAction('SiteMap');
	
	
	/**
	 * News application tree node
	 */
	function Node(config) {
		this._widgets = {
			'panel': null
		};
		Node.superclass.constructor.apply(this, arguments);
	}
	
	Node.NAME = 'TreeNodeList';
	Node.CSS_PREFIX = 'su-tree-node';
	Node.ATTRS = {
		'type': {
			'value': 'list'
		}
	};
	
	Y.extend(Node, Action.TreeNodeApp, {
		/**
		 * Node width constant, used to calculate
		 * children offset
		 * For expanded item width is 490, for collapsed it's 120
		 * @type {Number}
		 */
		'WIDTH': 120,
		
		/**
		 * Drag and drop groups
		 * @type {Array}
		 */
		'DND_GROUPS': ['new-page', 'restore-page', 'new-template', 'restore-template'],
		
		/**
		 * Additional properties for new item
		 * @type {Object}
		 */
		'NEW_CHILD_PROPERTIES': {
			'date': Y.DataType.Date.reformat(new Date(), 'raw', 'in_date')
		},
		
		/**
		 * Popup widget list
		 * @type {Object}
		 * @private
		 */
		'_widgets': null,
		
		/**
		 * Drop object for news datagrid panel
		 * @type {Object}
		 * @private
		 */
		'_panelDnd': null,
		
		
		/**
		 * Custom style for list item
		 * 
		 * @private
		 */
		'renderUI': function () {
			Node.superclass.renderUI.apply(this, arguments);
			
			this.get('boundingBox').addClass(this.getClassName('list'));
		},
		
		/**
		 * Attach event listeners
		 * 
		 * @private
		 */
		/*
		'bindUI': function () {
			
		},
		*/
		
		/**
		 * Apply widget state to UI
		 * 
		 * @private
		 */
		'syncUI': function () {
			this.set('type', 'list');
			
			Node.superclass.syncUI.apply(this, arguments);
		},
		
		/**
		 * Clean up
		 * @private
		 */
		'destructor': function () {
			//Remove drag and drop
			this._panelDnd.destroy();
		},
		
		
		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		
		/**
		 * Create panel
		 * 
		 * @private
		 */
		'_createPanel': function () {
			var container = this.get('boundingBox'),
				widgets = this._widgets,
				panel = null,
				datagrid = null;
			
			//Panel
			widgets.panel = panel = new Supra.Panel({
				'autoClose': false,
				'arrowVisible': true,
				'zIndex': 2
			});
			panel.render(container);
			panel.get('contentBox').setStyle('height', 312);
			
			//Up
			this._panelDnd = new Y.DD.Drop({
				'node': panel.get('contentBox'),
				'groups': ['default', 'new-page', 'restore-page']
			});
			
			this._panelDnd.set('treeNode', this);
			
			//Datagrid
			widgets.datagrid = datagrid = new Supra.DataGrid({
				'requestURI': this.get('tree').get('requestURI'),
				'requestParams': {
					'parent_id': this.get('data').id,
					'locale': this.get('tree').get('locale')
				},
				
				'style': 'list',
				
				//We will set total number of records manually in 'requestTotalRecords' 
				'requestMetaLocator': {},
				'requestDataLocator': 'data',
				'requestTotalRecords': this.get('data').children_count,
				
				'dataColumns': [
					{'id': 'id'}, {'id': 'master_id'}, {'id': 'icon'}, {'id': 'preview'}, {'id': 'template'}, {'id': 'global'}, {'id': 'localized'}, {'id': 'localization_count'}, {'id': 'full_path'}, {'id': 'type'}, {'id': 'unpublished_draft'}, {'id': 'published'}, {'id': 'path'}, {'id': 'basePath'}, {'id': 'localizations'}
				],
				'columns': [
					{
						'id': 'date',
						'title': 'Date',
						'formatter': 'dateShort'
					},
					{
						'id': 'icon',
						'title': '',
						'formatter': function () { return '<img src="/cms/content-manager/sitemap/images/icon-news.png" height="22" width="20" alt="" />'; }
					},
					{
						'id': 'title',
						'title': 'Title',
						'formatter': 'ellipsis'
					}
				]
			});
			
			//For compatibility with tree node add 'parent' attribute
			datagrid.addAttrs({
				'parent': {'value': null}
			}, {
				'parent': this
			});
			
			datagrid.plug(Supra.DataGrid.LoaderPlugin, {});
			datagrid.render(panel.get('contentBox'));
			
			//Bind event listeners
			datagrid.on('row:click', function (e) {
				//Don't do anything while highlighted
				if (this.get('highlighted')) return;
				
				var params = {
					'data': e.data,
					'node': e.row
				};
				
				this.get('tree').fire('page:select', params);
			}, this);
		},
		
		/**
		 * Load children data
		 * 
		 * @private
		 */
		'_setExpandedLoad': function () {
			//Find expanded sibling and collapse it
			var parent = this.get('parent');
			if (parent) {
				parent.children().some(function (item) {
					if (item.get('expanded')) return item.set('expanded', false);
				}, this);
			}
			
			return this._setExpandedExpand();
		},
		
		/**
		 * Instead of expanding children show news list popup
		 * 
		 * @private
		 */
		'_setExpanded': function (expanded) {
			if (!this.get('rendered')) return false;
			if (!this.get('expandable')) return false;
			
			if (expanded != this.get('expanded')) {
				if (expanded) {
					if (!this._widgets.panel) {
						this._createPanel();
					}
					
					this._collapseSiblings();
					
					this.WIDTH = 490;
					this.get('boundingBox').addClass('expanded-list');
					this.get('parent').syncChildrenPosition();
					
					this._widgets.panel.show();	
					this._widgets.datagrid.handleChange();
					
					var view = this.get('tree').get('view');
					view.set('disabled', false);
					//Center panel
					view.center(this.get('parent'),
						this.get('tree').set('visibilityRootNode', this.get('parent'))
					);	
				} else {
					return this._setExpandedCollapse();
				}
			}
			
			return expanded;
		},
		
		/**
		 * Collapse children item
		 * Instead of hiding children hide news list popup
		 * 
		 * @private
		 */
		'_setExpandedCollapse': function () {
			
			this.WIDTH = 120;
			this.get('boundingBox').removeClass('expanded-list');
			this.get('parent').syncChildrenPosition();
			this._widgets.panel.hide();
			
			return false;
		}
	});
	
	
	Action.TreeNodeList = Node;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['website.sitemap-tree-node-app', 'supra.datagrid', 'supra.datagrid-loader']});