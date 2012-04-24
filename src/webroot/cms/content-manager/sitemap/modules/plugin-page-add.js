//Invoke strict mode
"use strict";

YUI().add('website.sitemap-plugin-page-add', function (Y) {
	
	//Shortcuts
	var Manager = Supra.Manager,
		Action = Supra.Manager.getAction('SiteMap');
	
	
	/**
	 * Page edit settings form
	 */
	function Plugin () {
		Plugin.superclass.constructor.apply(this, arguments);
	};
	
	Plugin.NAME = 'PluginSitemapPageAdd';
	Plugin.NS = 'page_add';
	
	Y.extend(Plugin, Y.Plugin.Base, {
		/**
		 * TreeNode which currently user is editing
		 * 
		 * @type {Object}
		 * @private
		 */
		'_node': null,
		
		/**
		 * Children widget list
		 * 
		 * @type {Object}
		 * @private
		 */
		'_widgets': {
			/* Form */
			'form': null,
			/* Form container panel */
			'panel': null,
			/* Create page button */
			'buttonCreate': null,
			/* Cancel button */
			'buttonCancel': null
		},
		
		/**
		 * Data properties which match Node attributes
		 * Data key to node attribute map
		 * 
		 * @type {Object}
		 * @private 
		 */
		'_nodeDataMapping': {
			'title': 'label'
		},
		
		/**
		 * Data properties which match DataGridRow attributes
		 * Data key to node attribute map
		 * 
		 * @type {Object}
		 * @private 
		 */
		'_rowDataMapping': {
			'title': 'title'
		},
		
		/**
		 * Template data
		 * 
		 * @type {Array}
		 * @private
		 */
		'_templates': null,
		
		/**
		 * Templates currently are being loaded
		 * 
		 * @type {Boolean}
		 * @private
		 */
		'_templatesLoading': false,
		
		/**
		 * Fetched layouts
		 * 
		 * @type {Array}
		 * @private
		 */
		
		'_layouts': null,
		
		
	
		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		
		
		/**
		 * Attach to event listeners, etc.
		 * 
		 * @constructor
		 * @private
		 */
		'initializer': function () {
			this.get('host').on('page:add', function (e) {
				Y.later(16, this, function () {
					this._show(e);
				});
			}, this);
			
			this.get('host').on('load', this.hide, this);
			
			this.get('host').on('modeChange', function (e) {
				if (e.newVal != e.prevVal && e.newVal == 'pages') {
					//Mode changes from templates to pages, reload template list
					this._loadTemplates();
				}
			}, this);
			
			if (this.get('host').get('mode') == 'pages') {
				this._loadTemplates();
			}
		},
		
		/**
		 * Create edit form popup
		 * 
		 * @private
		 */
		'_createPanel': function () {
			var container = this.get('host').get('boundingBox').closest('.su-sitemap').one('.su-sitemap-add'),
				widgets = this._widgets,
				panel = null;
			
			widgets.panel = panel = new Supra.Panel({
				'srcNode': container,
				'autoClose': false,
				'arrowVisible': true,
				'zIndex': 1,
				'visible': false
			});
			
			//Bind event listeners
			panel.on('visibleChange', this._onVisibleChange, this);
			panel.on('alignTargetChange', this._onAlignTargetChange, this);
			
			//Create form
			this._createForm(container);
			
			//Render all widgets
			for(var i in widgets) {
				widgets[i].render();
			}
			
			//Listeners
			this._bindEventListeners();
		},
		
		/**
		 * Create form
		 * 
		 * @param {Object} container Container element
		 * @private
		 */
		'_createForm': function (container) {
			var widgets = this._widgets,
				buttons = container.all('button');
			
			//Buttons
			widgets.buttonCreate = new Supra.Button({'srcNode': buttons.item(0), 'style': 'small-blue'});
			widgets.buttonCancel = new Supra.Button({'srcNode': buttons.item(1), 'style': 'small'});
			
			widgets.buttonCreate.on('click', this.createPage, this);
			widgets.buttonCancel.on('click', this.hide, this);
			
			//Form
			widgets.form = new Supra.Form({
				'srcNode': container.one('form')
			});
		},
		
		/**
		 * Bind all event listeners
		 * 
		 * @private
		 */
		'_bindEventListeners': function () {
			//Inputs
			var widgets = this._widgets,
				inputs = widgets.form.getInputs(),
				id = null;
			
			for(id in inputs) {
				inputs[id].on('change', this._onPagePropertyChange, this);
				
				//On return key create page
				if (inputs[id].isInstanceOf('input-string'))	{
					inputs[id].get('inputNode').on('keydown', function (e) {
						if (e.keyCode == 13) this.createPage();
					}, this);
				}
			}
			
			//Fill template list
			this._fillTemplates();
		},
		
		/**
		 * When panel is hidden unset "hover" class from item
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		'_onVisibleChange': function (e) {
			if (e.newVal != e.prevVal && !e.newVal) {
				if (this._widgets.panel.get('alignTarget')) {
					this._widgets.panel.set('alignTarget', null);
					
					var node = this._node;
					if (node) {
						var view = this.get('host').get('view'),
							centerNode = null;
						
						if (this._node.isInstanceOf('TreeNode')) {
							if (!node.get('root')) {
								//Center parent
								if (node.get('parent').size() > 1) {
									centerNode = node.get('parent');
								} else {
									//Parent has only 1 child, which is this temporary node
									centerNode = node.get('parent').get('parent');
								}
							} else {
								//All tree
								centerNode = node.get('tree');
							}
							
							node.set('highlighted', false);
						} else if (node.isInstanceOf('DataGridRow')) {
							node.get('parent').get('parent').set('highlighted', false);
						}
						
						//Remove node and data
						node.get('tree').get('data').remove(node);
						node.get('tree').remove(node);
						node.destroy();
						this._node = null;
						
						view.set('disabled', false);
						
						//Center view on item
						if (centerNode) {
							view.center(centerNode);
						}
					}
				}
			}
		},
		
		/**
		 * When panel alignTarget changes "hover" class from old item
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		'_onAlignTargetChange': function (e) {
			if (e.newVal != e.prevVal && e.prevVal) {
				var item = e.prevVal.closest('.item');
				if (item) item.removeClass('hover');
			}
		},
		
		/**
		 * Returns align position which prevents panel from going out of screen
		 * 
		 * @param {Object} target Target element
		 * @return Align position
		 * @type {String}
		 * @private
		 */
		'_getAlignPosition': function (target) {
			var winWidth = Y.DOM.winWidth(),
				region = target.get('region'),
				space = winWidth - region.left - region.width;
			
			if (space < 270) {
				return 'R';
			} else {
				return 'L';
			}
		},
		
		/**
		 * Show popup for editing
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		'_show': function (e) {
			var node = this._node = e.node,
				view = this.get('host').get('view');
			
			if (!this._widgets.panel) {
				this._createPanel();
			}
			
			//Set form values
			this.setFormValues(node.get('data'));
			
			//Center item and disable draging
			if (node.isInstanceOf('TreeNode')) {
				//Show popup over TreeNode
				view.center(node, this.__showOverNode, this);
			} else {
				//Show popup over DataGrid row
				this.__showOverRow();
			}
			
			view.set('disabled', true);
		},
		
		/**
		 * Highlight node, show popup, disable other interactions
		 * 
		 * @private
		 */
		'__showOverNode': function () {
			var node = this._node,
				widgets = this._widgets,
				panel = widgets.panel;
			
			//Change node style
			node.set('highlighted', true);
			node.set('draggable', false);
			node.getWidget('buttonEdit').set('disabled', true);
			node.getWidget('buttonOpen').set('disabled', true);
			
			//Panel position and style
			var target = node.getWidget('buttonEdit').get('boundingBox');
			
			if (target === panel.get('alignTarget')) {
				panel.show();
			} else {
				target.closest('.item').addClass('hover');
			
				panel.set('alignTarget', target);
				panel.set('alignPosition', this._getAlignPosition(target));
				
				panel.fadeIn();
			}
		},
		
		/**
		 * Show popup, disable other interactions
		 * 
		 * @private
		 */
		'__showOverRow': function () {
			var node = this._node,
				widgets = this._widgets,
				panel = widgets.panel;
			
			//Change node style
			this._node.get('parent').get('parent').set('highlighted', true);
			
			//Panel position and style
			var target = node.getNode();
			
			if (target === panel.get('alignTarget')) {
				panel.show();
			} else {
				panel.set('alignTarget', target);
				panel.set('alignPosition', this._getAlignPosition(target));
				
				panel.fadeIn();
			}
		},
		
		/**
		 * On property change update tree node
		 * 
		 * @private
		 */
		'_onPagePropertyChange': function (e) {
			var input = e.target,
				id    = input.get('id'),
				value = input.get('value'),
				node  = this._node,
				data  = node.get('data');
			
			if (data[id] != value) {
				if (node.isInstanceOf('TreeNode')) {
					//Update node attribute
					if (this._nodeDataMapping[id]) {
						node.set(this._nodeDataMapping[id], value);
					}
				} else {
					if (this._rowDataMapping[id]) {
						node.set(this._rowDataMapping[id], value);
					}
				}
				
				data[id] = value;
			}
		},
		
		/**
		 * Search through siblings for pages which already has initial title or
		 * path to find next index
		 * If none of the siblings has this title or path then returns 0
		 * 
		 * @param {String} title Default page title
		 * @param {String} path Default page path
		 * @return Largest index used by one of the siblings or 0
		 * @type {Number}
		 * @private
		 */
		'_getTitlePathIndex': function (node, title, path) {
			var siblings = node.get('parent').get('children'),
				i = 0,
				ii = siblings.length,
				data = null,
				index = 0,
				match = null,
				regex_title = new RegExp('^' + Y.Escape.regex(title) + '(\\s\\((\\d+)\\))?$', 'i'),
				regex_path = new RegExp('^' + Y.Escape.regex(path) + '(\\-(\\d+))?$', 'i');
			
			for(; i<ii; i++) {
				if (siblings[i] !== node) {
					data = siblings[i].get('data');
					
					if (match = (data.title || '').match(regex_title)) {
						index = Math.max(index, match[2] ? parseInt(match[2], 10) : 1);
					}
					
					if (data.path && (match = (data.path || '').match(regex_path))) {
						index = Math.max(index, match[2] ? parseInt(match[2], 10) : 1);
					}
				}
			}
			
			return index;
		},
		
		/**
		 * Load template list
		 * 
		 * @private
		 */
		'_loadTemplates': function () {
			if (this._templatesLoading) return false;
			var uri = Manager.getAction('PageSettings').getDataPath('templates');
			
			this._templates = null;
			
			//Loading icon
			if (this._widgets.form && this._widgets.form.getInput('template')) {
				this._widgets.form.getInput('template').set('loading', true);
			}
			
			Supra.io(uri, {
				'data': {
					'locale': this.get('host').get('locale')
				},
				'context': this,
				'on': {
					'complete': this._loadTemplatesComplete
				}
			});
			
			this._loadLayouts();
		},
		
		'_loadLayouts': function () {
			var layoutsPath = Supra.Manager.Page.getDataPath('layouts');
					
			// Fetching all layouts from database
			Supra.io(layoutsPath, {
				'method': 'get',
				'context': this,
				'on': {
					'success': function (data) {
						var fetchedDataCount = data.length;

						if(fetchedDataCount != 0) {
							this._layouts = data;
							var select_layout_title = SU.Intl.get(['settings', 'select_layout']);
							this._layouts.unshift({id:'', title: select_layout_title});
						}
					}
				}
			});
		},
		
		
		/**
		 * Templates finished loading
		 * 
		 * @param {Array} data Template data
		 * @param {Boolean} status Response status
		 * @private
		 */
		'_loadTemplatesComplete': function (data, status) {
			if (status) {
				this._templates = data;
				this._templatesLoading = false;
			}
			
			this._fillTemplates();
		},
		
		/**
		 * Fill template dropdown with data
		 * 
		 * @private
		 */
		'_fillTemplates': function () {
			var templates = this._templates,
				form = this._widgets.form;
			
			if (templates && form && this.get('host').get('mode') == 'pages' && form.getInput('template')) {
				form.getInput('template').set('loading', false);
				form.getInput('template').set('values', templates);
				form.getInput('template').set('value', this._getAncestorTemplate);
			}
		},
		
		/**
		 * Returns template from closest ancestor which has one
		 * or empty string if none was found
		 * 
		 * @return Ancestors template ID
		 * @type {String}
		 * @private
		 */
		'_getAncestorTemplate': function () {
			var node = this._node,
				parent = node.get('parent'),
				tree = node.get('tree'),
				template = '';
			
			if (node.isInstanceOf('DataGridRow')) {
				parent = node = node.get('parent').get('parent');
				tree = node.get('tree');
			}
			
			if (node.get('root')) {
				return '';
			} else {
				while(!template && parent !== tree) {
					if (parent.get('type') != 'group') {
						template = parent.get('data').template;
					}
					parent = parent.get('parent');
				}
			}
			
			if (this._templates && this._templates.length) {
				return this._templates[0].id;
			}
			
			return template;
		},
		
		
		/**
		 * ------------------------------ API ------------------------------
		 */
		
		/**
		 * Set form values
		 * 
		 * @param {Object} data Form values
		 */
		'setFormValues': function (data) {
			var form = this._widgets.form,
				node = this._node,
				mode = node.get('tree').get('mode'),
				input = null,
				title = Supra.Intl.get(['sitemap', 'new_page']),
				title_regex = null,
				path = 'new-page',
				path_regex = null,
				index = 0,
				is_tree_node = node.isInstanceOf('TreeNode'),
				is_row_node = node.isInstanceOf('DataGridRow');
			
			if (this.get('host').get('mode') == 'pages') {
				form.getInput('title').set('label', Supra.Intl.get(['sitemap', 'new_page_label_title']));
				form.getInput('layout').set('visible', false);
				form.getInput('template').set('visible', true);
				
				if (node.get('root')) {
					//Root page doesn't have a path
					form.getInput('path').set('visible', false);
				} else {
					form.getInput('path').set('visible', true);
				}
			} else {
				title = Supra.Intl.get(['sitemap', 'new_template']);
				path = 'new-template';
				
				form.getInput('title').set('label', Supra.Intl.get(['sitemap', 'new_template_label_title']));
				form.getInput('path').set('visible', false);
				form.getInput('template').set('visible', false);
				
				//Only for root template user can set layout
				if (node.get('root')) {
						if(this._layouts.lenght == 0) {
							// throwing an error message
							Supra.Manager.executeAction('Confirmation', {
								'message': SU.Intl.get(['error', 'no_layouts']),
								'buttons': [{
									'id': 'ok', 
									'label': 'Ok'
								}]
							});

							// removing layout node
							form.getInput('layout').hide();
						} else {
							form.getInput('layout').set('values', this._layouts);
							form.getInput('layout').set('visible', true);
						}
						
				} else {
					form.getInput('layout').set('visible', false);
				}
			}
			
			if (data.type == 'group') {
				form.getInput('path').set('visible', false);
				form.getInput('template').set('visible', false);
				form.getInput('layout').set('visible', false);
			}
			
			//Find unique title and path which doesn't exist for any of the siblings
			title = data.title || title;
			path = data.path || path;
			
			index = this._getTitlePathIndex(node, title, path);
			
			if (index) {
				title += ' (' + (index + 1) + ')';
				path += '-' + (index + 1);
			}
			
			input = form.getInput('title');
			input.set('value', title);
			
			if (is_tree_node) {
				node.set('label', title);
			} else if (is_row_node) {
				node.set('title', title);
			}
			
			if (mode === 'pages') {
				input = form.getInput('path');
				input.set('value', path);
				
				input = form.getInput('template');
				input.set('value', this._getAncestorTemplate());
				
				if (is_row_node) {
					input.set('visible', false);
				}
			} else {
				input = form.getInput('layout');
				input.set('value', ''); //@TODO Set layout from parent
			}
		},
		
		/**
		 * Create a page
		 */
		'createPage': function () {
			var node   = this._node,
				treeNode = null,
				data   = node.get('data'),
				form   = this._widgets.form,
				mode   = this.get('host').get('mode'),
				out    = {},
				parent = null,
				next   = null,
				is_tree_node = false,
				is_row_node  = false;
			
			if (node.isInstanceOf('TreeNode')) {
				is_tree_node = true;
				treeNode = node;
			} else if (node.isInstanceOf('DataGridRow')) {
				is_row_node = true;
				treeNode = node.get('parent').get('parent');
			}
			
			out.locale = this.get('host').get('locale');
			out.title = data.title = form.getInput('title').get('value');
			out.type = data.type;
			out.parent_id = 0;
			
			next = node.next();
			
			if (next) {
				out.reference = next.get('data').id;
			}
			
			if (is_tree_node) {
				if (!node.get('root')) {
					out.parent_id = node.get('parent').get('data').id;
				}
			} else if (is_row_node) {
				out.parent_id = node.get('parent').get('parent').get('data').id;
			}
			
			if (data.type != 'group') {
				out.published = false;
				out.scheduled = false;
				
				if (data.type == 'application') {
					out.application_id = data.application_id;
				}
				
				if (mode == 'pages') {
					out.path = data.path = (node.get('root') ? '' : form.getInput('path').get('value'));
					out.template = data.template = form.getInput('template').get('value');
				} else {
					//Only for root templates can be set layout
					if (node.get('root')) {
						out.layout = data.layout = form.getInput('layout').get('value');
					} else {
						//Inherit from parent
						out.layout = node.get('parent').get('data').layout;
					}
				}
			}
			
			//Disable form
			form.set('disabled', true);
			this._widgets.buttonCreate.set('loading', true);
			this._widgets.buttonCancel.set('disabled', true);
			
			//Create
			var context	= Supra.Manager.Page,
				func	= context.createPage;
			
			if (mode != 'pages') {
				context = Supra.Manager.Template,
				func	= context.createTemplate;
			}
			
			func.call(context, out, function (data, status) {
				if (status) {
					//Update data
					if (is_tree_node) {
						node.get('tree').get('data').updateId(node.get('identifier'), data.id);
					}
					
					Supra.mix(node.get('data'), {
						'children': null,
						'children_count': 0
					}, data);
					
					//Success
					treeNode.getWidget('buttonOpen').set('disabled', false);
					treeNode.getWidget('buttonEdit').set('disabled', false);
					
					//Load permissions
					Supra.Permission.request([{'id': data.id, 'type': 'page'}], function (permissions) {
						if (is_tree_node) {
							if (permissions.page[data.id].edit_page) {
								node.set('editable', true);
								node.set('publishable', true);
								node.set('draggable', !('isDraggable' in data) || data.isDraggable);
								
								if (data.type != 'group') {
									node.set('selectable', true);
								}
							} else {
								node.set('editable', false);
								node.set('publishable', false);
								node.set('selectable', false);
							}
						}
					}, this);
					
					if (is_tree_node) {
						if (!node.get('root')) {
							//Center parent
							if (node.get('parent').size() > 1) {
								node.get('view').center(node.get('parent'));
							} else {
								//Parent has only 1 child, which is this temporary node
								node.get('view').center(node.get('parent').get('parent'));
							}
						}
						
						node.get('view').set('disabled', false);
						node.set('highlighted', false);
						node.set('state', 'draft');
						
					} else if (is_row_node) {
						node.get('parent').get('parent').set('highlighted', false);
						this.get('host').get('view').set('disabled', false);
					}
					
					this._node = null;
					this.hide();
				} else {
					//Failure, if panel was already closed remove node
					if (this._node !== node) {
						
						if (is_tree_node) {
							this.get('host').get('data').remove(node);
							this.get('host').remove(node);
						}
						
						node.destroy();
					}
				}
				
				this._widgets.form.set('disabled', false);
				this._widgets.buttonCreate.set('loading', false);
				this._widgets.buttonCancel.set('disabled', false);
			}, this);
		},
		
		/**
		 * Hide edit panel
		 */
		'hide': function () {
			if (this._widgets.panel) {
				this._widgets.panel.hide();
			}
		}
	});
	
	Action.PluginPageAdd = Plugin;
	
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.input']});