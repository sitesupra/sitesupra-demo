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
			
			this.set('expandable', true); // always
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
			// if application list is empty - panel won't be renderen, 
			// and there will be nothing to destroy
			if (this._panelDnd) {
				this._panelDnd.destroy();
			}
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
				filter = null,
				filter_input = null,
				datagrid = null;
			
			//Panel
			widgets.panel = panel = new Supra.Panel({
				'autoClose': false,
				'arrowVisible': true,
				'zIndex': 2
			});
			panel.render(container);
			panel.addClass('ui-light');
			panel.get('contentBox').setStyle('height', 350);
			
			//Up
			this._panelDnd = new Y.DD.Drop({
				'node': panel.get('contentBox'),
				'groups': ['default', 'new-page', 'restore-page']
			});
			
			this._panelDnd.set('treeNode', this);
			
			//Filter form
			widgets.filter = filter = new Supra.Form({
				'style': 'horizontal',
				'inputs': [
					{'type': 'String', 'id': 'filterQuery', 'name': 'query', 'label': ''}
				]
			});
			
			filter.addClass('filters');
			filter.render(panel.get('contentBox'));
			
			filter.on('submit', this.filter, this);
			
			filter_input = filter.getInput('filterQuery');
			filter_input.on('input', this.onFilterInputEvent, this);
			filter_input.plug(InputStringClear);
			
			//Datagrid
			widgets.datagrid = datagrid = new Supra.DataGrid({
				'requestURI': this.get('tree').get('requestURI'),
				'requestParams': {
					'parent_id': this.get('data').id,
					'locale': this.get('tree').get('locale'),
					'query': ''
				},
				
				'style': 'list',
				'tableHeadingVisible': false,
				
				//We will set total number of records manually in 'requestTotalRecords' 
				'requestMetaLocator': {},
				'requestDataLocator': 'data',
				'requestTotalRecords': this.get('data').children_count,
				
				'dataColumns': [
					{'id': 'id'}, {'id': 'master_id'}, {'id': 'icon'}, {'id': 'preview'}, {'id': 'template'}, {'id': 'global'}, {'id': 'localized'}, {'id': 'active'}, {'id': 'localization_count'}, {'id': 'full_path'}, {'id': 'type'}, {'id': 'scheduled'}, {'id': 'unpublished_draft'}, {'id': 'published'}, {'id': 'path'}, {'id': 'basePath'}, {'id': 'localizations'}
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
					},
					{
						'id': 'status',
						'title': '',
						'formatter': function (col_id, value, data) {
							if (data.type == 'page' && data.state == "temporary") {
								return '';
							} else if ( ! data.localized) {
								return '<div class="status-icon"><div class="status-not-localized">' + Supra.Intl.get(['sitemap', 'status_not_created']) + '</div></div>';
							} else if (data.type == 'page' && data.scheduled) {
								return '<div class="status-icon"><div class="status-scheduled">' + Supra.Intl.get(['sitemap', 'status_scheduled']) + '</div></div>';
							} else if (data.type == 'page' && !data.published) {
								return '<div class="status-icon"><div class="status-draft">' + Supra.Intl.get(['sitemap', 'status_draft']) + '</div></div>';
							}
							return '';
						}
					},
					{
						'id': 'delete',
						'title': '',
						'formatter': function (col_id, value, data) {
							if (data.type == 'page' && data.state == "temporary") {
								return '';
							} else if ( ! data.localized) {
								return '';
							} else {
								return '<a class="delete-icon"></div>';
							}
						}
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
			datagrid.on('row:click', function (event) {
				//Don't do anything while highlighted
				if (this.get('highlighted')) return;
				
				//On delete click...
				if (event.element.test('a.delete-icon')) {
					return Action.tree.page_edit.deletePage(event.row);
				}
				
				var params = {
					'data': event.data,
					'node': event.row
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
		},
		
		
		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		
		/**
		 * Timer for checking when was last input
		 * @type {Object}
		 * @private
		 */
		'filterTimer': null,
		
		/**
		 * Filter content
		 * 
		 * @private
		 */
		'filter': function () {
			var filter = this._widgets.filter,
				datagrid = this._widgets.datagrid,
				query = filter.getInput('filterQuery').get('value');
			
			if (datagrid.requestParams.get('query') != query) {
				datagrid.requestParams.set('query', query);
				datagrid.reset();
			}
			
			//Cancel timer
			if (this.filterTimer) {
				this.filterTimer.cancel();
				this.filterTimer = null;
			}
		},
		
		/**
		 * When filter input value changes set timer
		 * @param {Object} e
		 */
		'onFilterInputEvent': function (e) {
			if (this.filterTimer) {
				this.filterTimer.cancel();
			}
			
			this.filterTimer = Y.later(250, this, this.filter);
		}
	});
	
	
	Action.TreeNodeList = Node;
	
	
	
	/**
	 * Plugin for String input to clear content on icon click
	 */
	function InputStringClear () {
		InputStringClear.superclass.constructor.apply(this, arguments);
	};
	
	InputStringClear.NAME = 'InputStringClear';
	InputStringClear.NS = 'clear';
	
	Y.extend(InputStringClear, Y.Plugin.Base, {
		
		/**
		 * Clear icon/button
		 * 
		 * @type {Object}
		 * @private 
		 */
		nodeClear: null,
		
		/**
		 * Attach to event listeners, etc.
		 * 
		 * @constructor
		 * @private
		 */
		'initializer': function () {
			this.nodeClear = Y.Node.create('<a class="clear"></a>');
			this.nodeClear.on('click', this.clearInputValue, this);
			this.get('host').get('inputNode').insert(this.nodeClear, 'after');
		},
		
		/**
		 * Clear input value
		 * 
		 * @private
		 */
		'clearInputValue': function () {
			this.get('host').set('value', '');
		}
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['website.sitemap-tree-node-app', 'supra.datagrid', 'supra.datagrid-loader']});