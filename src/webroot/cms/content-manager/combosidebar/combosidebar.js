/*
 * Sidebar for Supra.Input.ComboBox
 *
 * Example:
 *   Supra.Manager.executeAction('ComboSidebar', {
 *     // Callback function for event when sidebar is closed and values are selected
 *     onselect: ...
 *     
 *     // Callback function for event when sidebar is closed
 *     onclose: ...
 *     
 *     // While open current toolbar buttons will be hidden
 *     hideToolbar: true|false
 *   })
 *
 */
Supra('anim', 'dd-drag', 'supra.datalist', 'supra.scrollable', 'supra.tree-multiselect', 'supra.crud', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager,
		Action = Manager.Action;
	
	new Action(Action.PluginLayoutSidebar, {
		
		NAME: 'ComboSidebar',
		
		// No need for template
		HAS_TEMPLATE: true,
		
		// Load stylesheet
		HAS_STYLESHEET: true,
		
		/**
		 * Layout container action NAME
		 * @type {String}
		 * @private
		 */
		LAYOUT_CONTAINER: 'LayoutRightContainer',
		
		/**
		 * Configuration options
		 * @type {Object}
		 */
		options: {},
		
		/**
		 * Nodes and widgets
		 * @type {Object}
		 */
		nodes: {
			search: null,
			
			datalist: null,
			tree: null,
			treeScrollable: null,
			
			button_app: null
		},
		
		
		render: function () {
			//Toolbar buttons
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			//Create media list
			this.renderContent();
			
			//Close and App buttons
			this.renderHeader();
			this.renderFooter();
		},
		
		/**
		 * Create list, tree and search
		 * 
		 * @private
		 */
		renderContent: function () {
			var content = this.get('contentNode'),
				list = content.one('.list-box'),
				search;
				
			search = new Supra.Input.String({'label': '', 'srcNode': content.one('.search-box input')});
			search.render();
			search.addClass('search');
			search.plug(Supra.Input.String.Clear);
			
			search.on('input', Supra.throttle(this._filterItems, 150, this, true));
			
			this.nodes.search = search;
			this.nodes.list = list;
		},
		
		/**
		 * Create buttons
		 * 
		 * @private
		 */
		renderHeader: function () {
			this.get('controlButton').on('click', this.close, this);
			this.get('controlButton').on('click', this.hide, this);
		},
		
		/**
		 * Create footer button
		 */
		renderFooter: function () {
			var button = this.one('.sidebar-footer button'),
				osx = window.navigator.userAgent.indexOf('Mac OS') !== -1,
				description = osx ? Supra.Intl.get(['combosidebar', 'description_osx']) : Supra.Intl.get(['combosidebar', 'description']);
			
			this.one('.sidebar-footer .description').set('text', description);
			
			this.nodes.button_app = new Supra.Button({'srcNode': button});
			this.nodes.button_app.render();
			this.nodes.button_app.on('click', function (e) {
				// Open App and watch event when it's closed
				if (e.domEvent.metaKey || e.domEvent.ctrlKey) {
					// CTRL/CMD + click
					var url = this.source.configuration.getConfigValue('attributes.url');
					
					if (url) {
						window.open(url, '_blank');
					}
				} else {
					// Open CRUD app
					var id = this.source.configuration.getConfigValue('attributes.id'),
						source = this.source,
						options = this.options,
						value = this.value;
					
					Supra.Manager.executeAction('Crud', {
						'providerId': id,
						'standalone': false,
						'onclose': Y.bind(function () {
							// Reopen sidebar in the state it was before + reloads
							// the list or tree
							this.source = source;
							this.options = options;
							this.value = value;
							
							this.show();
							this.syncHeaderUI();
							this.syncContentUI();
							this.syncFooterUI();
						}, this)
					});
					
					this.hide();
				}
			}, this);
		},
		
		
		/**
		 * Update UI
		 */
		syncHeaderUI: function () {
			this.set('title', this.options.title);
		},
		
		syncFooterUI: function () {
			var content = this.get('contentNode');
			
			if (this.source.type === 'crud' && this.source.configuration && this.source.configuration.getConfigValue('ui_sideview_list.external')) {
				// Show App button and rename it
				content.addClass('has-footer');
				
				// Rename button
				var crud_title = this.source.configuration.getConfigValue('attributes.title'),
					button_title = Supra.Intl.get('combosidebar.open_app');
				
				this.nodes.button_app.set('label', button_title.replace('{app}', crud_title));
			} else {
				// Hide app button
				content.removeClass('has-footer');
			}
		},
		
		syncContentUI: function () {
			if (this.source.type === 'crud') {
				if (this.source.configuration) {
					this.get('contentNode').removeClass('loading');
					
					if (this.source.display === 'list') {
						this.get('contentNode').addClass('has-search');
						this.nodes.list.removeClass('ui-light-background');
						
						this.updateDataListUI();
					} else {
						// Tree
						this.get('contentNode').removeClass('has-search');
						this.nodes.list.addClass('ui-light-background');
						
						this.updateTreeUI();
					}
				} else {
					// Show loading icon
					this.get('contentNode').addClass('loading');
					this.nodes.list.removeClass('ui-light-background');
				}
			} else {
				this.get('contentNode').addClass('has-search').removeClass('loading');
				this.nodes.list.removeClass('ui-light-background');
				this.updateDataListUI();
			}
		},
		
		
		/* ------------------ DataList ------------------ */
		
		
		_filterItems: function () {
			var query = this.nodes.search.get('value').toLowerCase();
			var datalist = this.nodes.datalist;
			var tree = this.nodes.tree;
			var source = this.source;
			
			if (source.filter == query) {
				// Already filtered
				return;
			}
			
			if (source.type === 'array') {
				var data = Y.Array.map(source.data, function (item, index) {
					if (item.title.toLowerCase().indexOf(query) !== -1) return item;
				});
				
				datalist.set('data', data);
			} else if (source.type === 'crud' && source.display === 'tree') {
				// No filters for tree for now
				/*
				var url = source.url + (source.url.indexOf('?') !== -1 ? '&' : '?') + 'query=' + encodeURIComponent(query);
				tree.set('requestUri', url);
				tree.reload();
				*/
			} else {
				var url = source.url + (source.url.indexOf('?') !== -1 ? '&' : '?') + 'q=' + encodeURIComponent(query);
				datalist.set('dataSource', url);
			}
			
			source.filter = query;
		},
		
		updateDataListUI: function () {
			var datalist = this.nodes.datalist,
				treeScrollable = this.nodes.treeScrollable;
			
			if (treeScrollable) {
				treeScrollable.hide();
			}
			
			if (!datalist) {
				datalist = new Supra.DataList({
					'data': this.source.data,
					'dataSource': this.source.url,
					'dataTransform': Y.bind(this._transformDataListData, this),
					'scrollable': true,
					'listSelector': 'ul',
					'itemTemplate': 'comboSidebarItem',
					'itemEmptyTemplate': 'comboSidebarItemEmpty',
					'itemHeight': 36,
					'wrapperTemplate': 'comboSidebarWrapper'
				});
				
				datalist.on('itemRender', Y.bind(this._updateDataListUIRenderButton, this));
				
				datalist.render(this.nodes.list);
				this.nodes.datalist = datalist;
			} else {
				datalist.show();
				datalist.set('data', this.source.data);
				datalist.set('dataSource', this.source.url);
			}
		},
		
		/**
		 * Transform incoming datalist data to be compatible with DataList widget
		 *
		 * @param {Object} item Data item
		 * @returns {Object} Transformed data
		 * @private
		 */
		_transformDataListData: function (item) {
			var fields = this.source.fields,
				f = 0, ff = fields ? fields.length : 0;
			
			for (; f<ff; f++) {
				return {
					'id': item.id,
					'title': item[fields[f].id]
				};
			}
			
			// There are no fields defined, assume data is already in correct format
			return item;
		},
		
		_updateDataListUIRenderButton: function (e) {
			e.widgets.button = new Supra.Button({
				'srcNode': e.node.one('button'),
				'style': 'area-toggle',
				'type': 'toggle',
				'down': this._valueIndexOf(e.data.id) !== -1
			});
			e.widgets.button.render();
			
			if (Y.Lang.isFunction(this.options.onupdate)) {
				e.widgets.button.on('click', this._handleDataListValueChange, this, e.widgets.button, e.data);
			}
		},
		
		_handleDataListValueChange: function (e, button, data) {
			if (button.get('down')) {
				if (!this.options.multiple && this.value.length) {
					var prev_data = this.value[0];
					
					var item = this.nodes.datalist.getItemByProperty('id', prev_data.id);
					if (item) {
						item.widgets.button.set('down', false);
					}
					
					this._valueRemove(prev_data);
				}
				
				this._valueAdd(data);
				this.options.onupdate('add', data);
			} else {
				this._valueRemove(data);
				this.options.onupdate('remove', data);
			}
			
			if (!this.options.multiple) {
				
			}
		},
		
		
		/* ------------------ Tree ------------------ */
		
		
		updateTreeUI: function () {
			var datalist = this.nodes.datalist,
				tree = this.nodes.tree,
				scrollable = this.nodes.treeScrollable;
			
			if (datalist) {
				datalist.hide();
			}
			
			if (!tree) {
				var content = this.one('.list-box');
				
				scrollable = new Supra.Scrollable();
				scrollable.render(content);
				
				tree = new Supra.TreeMultiSelect({
					'requestUri': this.source.url,
					'groupNodesSelectable': false,
					'value': this.value
				});
				
				tree.render(scrollable.get('contentBox'));
				tree.get('boundingBox').on('contentresize', scrollable.syncUI, scrollable);
				tree.on('value-add', this._handleTreeListValueChange, this);
				tree.on('value-remove', this._handleTreeListValueChange, this);
				
				this.nodes.tree = tree;
				this.nodes.treeScrollable = scrollable;
			} else {
				scrollable.show();
				tree.set('requestUri', this.source.url);
				tree.reload();
				tree.set('value', this.value);
			}
		},
		
		_handleTreeListValueChange: function (e) {
			var node_data = e.details[0],
				data = {
					'id': node_data.id,
					'title': node_data.title
				};
			
			if (Y.Lang.isFunction(this.options.onupdate)) {
				if (e.type.indexOf('value-add') !== -1) {
					this.options.onupdate('add', data);
				} else {
					this.options.onupdate('remove', data);
				}
			}
		},
		
		
		/* ------------------ Value ----------------- */
		
		/**
		 * Add item to the value list
		 *
		 * @param {Object} item Item data
		 * @private
		 */
		_valueAdd: function (item) {
			if (this._valueIndexOf(item.id) === -1) {
				this.value.push(item);
			}
		},
		
		/**
		 * Remove item from the value list
		 *
		 * @param {Object} item Item data
		 * @private
		 */
		_valueRemove: function (item) {
			var index = this._valueIndexOf(item.id),
				value = this.value;
			
			if (index !== -1) {
				value.splice(index, 1);
			}
		},
		
		/**
		 * Returns true if there is a selected value with given id
		 */
		_valueIndexOf: function (id) {
			var value = this.value,
				i = 0,
				ii = value.length;
			
			for (; i<ii; i++) {
				if (value[i].id == id) return i;
			}
			
			return -1;
		},
		
		
		/* ------------------ Open / close sidebar functionality ----------------- */
		
		
		/**
		 * Returns info about source
		 *
		 * @param {String|Array} source Source
		 * @returns {Object} Source information
		 */
		getSourceInfo: function (source) {
			if (Y.Lang.isArray(source)) {
				return {
					'type': 'array',
					'data': source,
					'url': null,
					'filter': '',
					'fields': []
				};
			} else if (typeof source === 'string') {
				if (source[0] !== '/') {
					// CRUD id
					return {
						'type': 'crud',
						'id': source,
						'data': null,
						'url': null,
						'filter': '',
						'configuration': null,
						'fields': [],
						
						// promise
						'then': null
					};
				} else {
					// URL
					return {
						'type': 'url',
						'data': null,
						'url': source,
						'filter': '',
						'fields': []
					}
				}
			} else {
				// Invalid configuration
				this.close();
				this.hide();
			}
		},
		
		/**
		 * Set CRUD configuration and update UI
		 *
		 * @param {Object} configuration CRUD manager configuration
		 */
		setCrudConfiguration: function (configuration) {
			this.source.configuration = configuration;
			this.source.display = configuration.getConfigValue('ui_sideview_list.display');
			this.source.url = configuration.getDataPath('data' + this.source.display);
			this.source.fields = configuration.getConfigFields('ui_sideview_list.fields');
			this.syncContentUI();
			this.syncFooterUI();
		},
		
		/**
		 * Set options and update UI
		 *
		 * @param {Object} options Sidebar configuration options
		 */
		setOptions: function (options) {
			this.options = Supra.mix({
				'title': '',
				'onselect': null,
				'onclose': null,
				'onupdate': null,
				'hideToolbar': true,
				'values': [],
				'value': []
			}, options || {}, true);
			
			this.value = this.options.value;
			this.source = this.getSourceInfo(this.options.values);
			
			// Update UI
			this.syncHeaderUI();
			this.syncFooterUI();
			this.syncContentUI();
			
			// Load CRUD info
			if (this.source.type === 'crud') {
				Supra.Crud.getConfiguration(this.source.id).done(this.setCrudConfiguration, this);
			}
			
			//Hide toolbar
			if (this.options.hideToolbar) {
				//Hide editor toolbar
				if (Manager.getAction('EditorToolbar').get('visible')) {
					// Save if toolbar will have to be re-opened when closing ComboSidebar
					this.options.retoreEditorToolbar = true;
					Manager.getAction('EditorToolbar').hide();
				}
				
				//Hide buttons
				Manager.getAction('PageToolbar').setActiveAction(this.NAME);
				Manager.getAction('PageButtons').setActiveAction(this.NAME);
			}
		},
		
		/**
		 * Hide
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			
			//Show previous buttons
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			//Retore editor toolbar
			if (this.options.retoreEditorToolbar) {
				this.options.retoreEditorToolbar = false;
				Manager.getAction('EditorToolbar').execute();
			}
			
			//Cleanup
			if (this.nodes.datalist) {
				this.nodes.datalist.set('data', null);
				this.nodes.datalist.set('dataSource', null);
			}
			if (this.nodes.tree) {
				this.nodes.tree.removeAll();
			}
			if (this.nodes.search) {
				this.nodes.search.set('value', '');
			}
			
			this.value = null;
			this.source = null;
		},
		
		/**
		 * Call close callbacks
		 */
		close: function () {
			if (Y.Lang.isFunction(this.options.onselect)) {
				this.options.onselect(this.value);
			}
			
			if (Y.Lang.isFunction(this.options.onclose)) {
				this.options.onclose(this.value);
			}
		},
		
		execute: function (options) {
			if (this.get('visible')) {
				this.close();
			}
			
			this.show();
			
			//Set options
			this.setOptions(options);
		}
		
	});
		
});
