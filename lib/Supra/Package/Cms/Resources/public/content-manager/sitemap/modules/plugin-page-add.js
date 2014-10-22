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
			/* Layout list container node */
			'layoutList': null,
			/* Scrollable widget for layout list */
			'layoutScrollable': null,
			/* Template list container node */
			'templateList': null,
			/* Scrollable widget for template list */
			'templateScrollable': null,
			/* Create page button */
			'buttonCreate': null,
			/* Cancel button */
			'buttonCancel': null,
			/* Layout button */
			'buttonLayout': null,
			/* Template button */
			'buttonTemplate': null
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
		 * Layouts currently are being loaded
		 *
		 * @type {Boolean}
		 * @private
		 */
		'_layoutsLoading': false,

		/**
		 * Default page title when panel is opened
		 *
		 * @type {String}
		 * @private
		 */
		'_defaultTitle': '',



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

			this.get('host').on('localeChange', function (e) {
				if(e.newVal != e.prevVal && this.get('host').get('mode') == 'pages') {
					this._loadTemplates(e.newVal);
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
				'zIndex': 6, // above new item and recycle bin
				'visible': false,
				'closeOnEscapeKey': true
			});

			//Bind event listeners
			panel.on('visibleChange', this._onVisibleChange, this);
			panel.on('alignTargetChange', this._onAlignTargetChange, this);

			//Create form
			this._createForm(container);

			//Render all widgets
			for(var i in widgets) {
				if (widgets[i]) {
					widgets[i].render();
				}
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
				buttons = container.all('.middle button'),
				button_tpl = container.one('.template-section button'),
				button_lay = container.one('.layout-section button');

			//Buttons
			widgets.buttonTemplate = new Supra.Button({'srcNode': button_tpl});
			widgets.buttonLayout = new Supra.Button({'srcNode': button_lay});
			widgets.buttonCreate = new Supra.Button({'srcNode': buttons.item(0)});
			widgets.buttonCancel = new Supra.Button({'srcNode': buttons.item(1)});

			widgets.buttonTemplate.on('click', this.showTemplates, this);
			widgets.buttonLayout.on('click', this.showLayouts, this);
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
						if (e.keyCode == 13) {
							var input = Y.Widget.getByNode(e.target);
							this._onPagePropertyChange({"target": input});

							this.createPage();
						}
					}, this);
				}
			}

			inputs.title.on('focus', this._onTitleFocus, this);
			inputs.title.on('input', this._onPagePropertyChange, this);
			inputs.template.on('valueChange', this.hideTemplates, this);
			inputs.layout.on('valueChange', this.hideLayouts, this);

			//Fill template list and on template change update button
			this._widgets.buttonTemplate.plug(Supra.Button.PluginInput, {'input': inputs.template});
			this._fillTemplates();

			//On layout change update button
			this._widgets.buttonLayout.plug(Supra.Button.PluginInput, {'input': inputs.layout});
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
							var datagrid = node.get('parent');
						}

						//Remove node and data
						node.get('tree').get('data').remove(node);
						node.get('tree').remove(node);
						node.destroy();
						this._node = null;

						if (datagrid) {
							datagrid.handleChange();
						}

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

			if (space < 520) {
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
				panel = widgets.panel,
				datagrid = this._node.get('parent');

			//Change node style
			this._node.get('parent').get('parent').set('highlighted', true);

			//Panel position and style
			var target = node.getNode();
			
			datagrid.handleChange();
			if (datagrid.scrollable) {
				datagrid.scrollable.scrollInView(target);
			}

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
		 * @param {Object} e Event facade object
		 * @private
		 */
		'_onPagePropertyChange': function (e) {
			if (!this._node) return; // most likely initialization stage

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

				// Create path from title
				if (id === 'title') {
					var input_path = this._widgets.form.getInput('path');
					if (input_path.get('visible')) {
						input_path.set('value', Y.Lang.toPath(value));
					}
				}
			}
		},

		/**
		 * When title is focused remove default value
		 *
		 * @param {Object} e Event facade object
		 * @private
		 */
		'_onTitleFocus': function (e) {
			var input  = e.target,
				value  = input.get('value'),
				placeholder_value = this._defaultTitle;

			if (value === placeholder_value) {
				input.set('value', '');
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
		'_loadTemplates': function (locale) {

			if (this._templatesLoading) return false;
			var uri = Manager.getAction('PageSettings').getDataPath('templates');

			this._templates = null;
			this._templatesLoading = true;

			//Loading icon
			if (this._widgets.form && this._widgets.form.getInput('template')) {
				this._widgets.form.getInput('template').set('loading', true);
			}
			if (this._widgets.buttonTemplate) {
				this._widgets.buttonTemplate.set('loading', true);
			}

			Supra.io(uri, {
				'data': {
					'locale': (locale ? locale : this.get('host').get('locale'))
				},
				'context': this,
				'on': {
					'complete': this._loadTemplatesComplete
				}
			});

			this._loadLayouts();
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
				form = this._widgets.form,
				button = this._widgets.buttonTemplate;

			if (button) {
				button.set('loading', false);
			}

			if (templates && form && this.get('host').get('mode') == 'pages' && form.getInput('template')) {
				form.getInput('template').set('loading', false);

				form.getInput('template').set('showEmptyValue', false);
				form.getInput('template').set('values', templates);
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

			if (!this._node) {
				return false;
			}

			var node = this._node,
				parent = node.get('parent'),
				tree = node.get('tree'),
				dataObject = tree.get('data'),
				template = '',
				parentData = null;

			if (node.isInstanceOf('DataGridRow')) {
				parent = node = node.get('parent').get('parent');
				tree = node.get('tree');
			}

			if (node.get('root')) {
				return '';
			} else {
				while(!template && parent !== tree) {
					if (parent.get('type') != 'group') {

						// if we have reached list node of Page Application
						// then we will try to get template of first child of list
						if (parent.get('type') == 'list'
							&& parent.get('parent').get('type') == 'application')
						{
							parentData = parent.get('data');
							if (parentData.children_count) {

								if (dataObject.isLoading(parentData.id) || ! dataObject.isLoaded(parentData.id)) {
									tree.once('load:success:' + parentData.id, this._setAncestorTemplate, this);
								}
								if (dataObject.isLoading(parentData.id)) {
									return false;
								}
								if (!dataObject.isLoaded(parentData.id)) {
									dataObject.load(parentData.id);
									return false;
								}

								var children = parent.get('data').children;
								for(var i in children) {
									if (children[i].localized) {
										template = children[0].template;
										break;
									}
								}
							}

						} else {
							template = parent.get('data').template;
						}
					}
					parent = parent.get('parent');
				}
			}

			if (!template && this._templates && this._templates.length) {
				return this._templates[0].id;
			}

			return template;
		},

		'_loadLayouts': function () {
			if (this._layoutsLoading || this._layouts) return false;
			var layoutsPath = Supra.Manager.Page.getDataPath('layouts');

			this._layouts = null;
			this._layoutsLoading = true;

			//Loading icon
			if (this._widgets.form && this._widgets.form.getInput('layout')) {
				this._widgets.form.getInput('layout').set('loading', true);
			}
			if (this._widgets.buttonLayout) {
				this._widgets.buttonLayout.set('loading', true);
			}

			// Fetching all layouts from database
			Supra.io(layoutsPath, {
				'method': 'get',
				'context': this,
				'on': {
					'success': this._loadLayoutsComplete
				}
			});
		},

		/**
		 * Layouts finished loading
		 *
		 * @param {Array} data Layouts data
		 * @param {Boolean} status Response status
		 * @private
		 */
		'_loadLayoutsComplete': function (data, status) {
			if (status) {
				// Title and icon are fixed in fillLayoutList
				data.unshift({'id': '', 'title': '', 'icon': ''});

				this._layouts = data;
				this._layoutsLoading = false;
			}
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
				is_row_node = node.isInstanceOf('DataGridRow'),
				buttonTemplateNode = this._widgets.buttonTemplate.get('boundingBox').ancestor(),
				buttonLayoutNode = this._widgets.buttonLayout.get('boundingBox').ancestor();

			if (this.get('host').get('mode') == 'pages') {
				form.getInput('title').set('label', Supra.Intl.get(['sitemap', 'new_page_label_title']));

				buttonTemplateNode.removeClass('hidden');
				buttonLayoutNode.addClass('hidden');

				if (node.get('root')) {
					//Root page doesn't have a path
					form.getInput('path').hide();
				} else {
					form.getInput('path').show();
				}
			} else {
				title = Supra.Intl.get(['sitemap', 'new_template']);
				path = 'new-template';

				form.getInput('title').set('label', Supra.Intl.get(['sitemap', 'new_template_label_title']));
				form.getInput('path').hide();

				buttonTemplateNode.addClass('hidden');
				buttonLayoutNode.removeClass('hidden');

				//Layouts
				this.fillLayoutList(node);

			}

			if (data.type == 'group') {
				form.getInput('path').hide();
				buttonTemplateNode.addClass('hidden');
				buttonLayoutNode.addClass('hidden');
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

			this._defaultTitle = title;

			if (is_tree_node) {
				node.set('label', title);
			} else if (is_row_node) {
				node.set('title', title);
			}

			if (mode === 'pages') {
				input = form.getInput('path');
				input.set('value', path);

				this._setAncestorTemplate();

			} else {
				input = form.getInput('layout');
				input.set('value', ''); // Empty value is for "Use parent layout"
			}
		},

		'_setAncestorTemplate': function() {
			var form = this._widgets.form,
				input = form.getInput('template'),
				button = this._widgets.buttonTemplate,
				template = this._getAncestorTemplate(),
				templates = this._templates;

			// Check if template doesn't have a flag 'dont_use_as_default'
			for (var i=0,ii=templates.length; i<ii; i++) {
				if (templates[i].id == template) {
					if (templates[i].dont_use_as_default) {
						if (i < ii-1) {
							// Use next template
							template = templates[i+1].id;
						} else if (i) {
							// Use previous template
							template = templates[i-1].id;
						} else {
							// There are no any other template to choose from
						}
					}
					break;
				}
			}

			input.set('value', template);
		},

		/**
		 * Fill layout list
		 *
		 * @param {Object} input Layout input
		 * @param {Object} node Tree node
		 * @private
		 */
		'fillLayoutList': function (node) {
			var layouts = this._layouts,
				button  = this._widgets.buttonLayout,
				input   = this._widgets.form.getInput('layout');

			if(layouts.length == 0) {
				// throwing an error message
				Supra.Manager.executeAction('Confirmation', {
					'message': Supra.Intl.get(['error', 'no_layouts']),
					'buttons': [{
						'id': 'ok',
						'label': 'OK'
					}]
				});
			} else {
				if (node.get('root')) {
					layouts[0] = {
						'id': '',
						'title': Supra.Intl.get(['settings', 'select_layout']),
						'icon': '/public/cms/supra/img/sitemap/preview/layout.png'
					};
					input.set('showEmptyValue', false);
				} else {
					layouts[0] = {
						'id': '',
						'title': Supra.Intl.get(['settings', 'use_parent_layout']),
						'icon': '/public/cms/supra/img/sitemap/preview/layout.png'
					};
					input.set('showEmptyValue', true);
				}

				input.set('values', layouts);
				input.set('value', '');
			}
		},

		/**
		 * Show layout list
		 */
		'showLayouts': function () {
			var panel = this._widgets.panel,
				form = panel.get('contentBox'),
				container = this._widgets.layoutList,
				scrollable = this._widgets.layoutScrollable;

			if (!container) {
				form = panel.get('contentBox');
				container = this._widgets.layoutList = form.one('div.su-sitemap-layout-list');

				scrollable = this._widgets.layoutScrollable = new Supra.Scrollable({
					'srcNode': container.one('div.su-sitemap-layout-list-scrollable')
				});
				scrollable.render();

				form.insert(container, 'before');
			}

			if (container.hasClass('hidden')) {

				//  6 -> 259
				form.transition({
					'easing': 'ease-out',
					'duration': 0.3,
					'marginRight': '259px'
				});

				container.removeClass('hidden');
				scrollable.syncUI();
			}
		},

		/**
		 * Hide layout list
		 */
		'hideLayouts': function (quick) {
			var panel = this._widgets.panel,
				form = panel ? panel.get('contentBox') : null,
				container = this._widgets.layoutList;

			if (container && !container.hasClass('hidden')) {

				if (quick === true) {
					form.setStyle('marginRight', '6px');
					container.addClass('hidden');
				} else {
					//  259 -> 6
					form.transition({
						'easing': 'ease-out',
						'duration': 0.3,
						'marginRight': '6px'
					}, Y.bind(function () {
						container.addClass('hidden');
					}, this));
				}
			}
		},

		/**
		 * Show template list
		 */
		'showTemplates': function () {
			var panel = this._widgets.panel,
				form = panel.get('contentBox'),
				container = this._widgets.templateList,
				scrollable = this._widgets.templateScrollable;

			if (!container) {
				form = panel.get('contentBox');
				container = this._widgets.templateList = form.one('div.su-sitemap-template-list');

				scrollable = this._widgets.templateScrollable = new Supra.Scrollable({
					'srcNode': container.one('div.su-sitemap-template-list-scrollable')
				});
				scrollable.render();

				form.insert(container, 'before');
			}

			if (container.hasClass('hidden')) {

				//  6 -> 259
				form.transition({
					'easing': 'ease-out',
					'duration': 0.3,
					'marginRight': '259px'
				});

				container.removeClass('hidden');
				scrollable.syncUI();
			}
		},

		/**
		 * Hide template list
		 */
		'hideTemplates': function (quick) {
			var panel = this._widgets.panel,
				form = panel ? panel.get('contentBox') : null,
				container = this._widgets.templateList;

			if (container && !container.hasClass('hidden')) {

				if (quick === true) {
					form.setStyle('marginRight', '6px');
					container.addClass('hidden');
				} else {
					//  259 -> 6
					form.transition({
						'easing': 'ease-out',
						'duration': 0.3,
						'marginRight': '6px'
					}, Y.bind(function () {
						container.addClass('hidden');
					}, this));
				}
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
					out.layout = data.layout = form.getInput('layout').get('value');
				}
			}

			//Disable form
			form.set('disabled', true);
			this._widgets.buttonCreate.set('loading', true);
			this._widgets.buttonCancel.set('disabled', true);

			//Create
			var context	= Supra.Manager.Page,
				func	= data.type == 'group' ? context.createGroup : context.createPage;

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
						'children': data.children_count ? [] : null,
						'children_count': 0,
						'temporary': false
					}, data);

					//Make sure children is not loaded dynamically, since this is new page
					if (data.type == 'page' || data.type == 'group') {
						node.get('tree').get('data').setIsLoaded(data.id, true);
					} else if (data.children_count) {
						node.set('expandable', true);
						node.set('childrenRendered', false);
					}

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

					var dataObject = treeNode.get('tree').get('data'),
						parentData = dataObject.item(data.parent_id + (is_row_node ? '_list' : ''));

					if (parentData && !this.hasItemInArray(parentData.children, data.id)) {
						parentData.children_count++;
						parentData.children.unshift(node.get('data'));
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

		'hasItemInArray': function (array, id) {
			for (var i=0,ii=array.length; i<ii; i++) {
				if (array[i]._id === id) return true;
			}
			return false;
		},

		/**
		 * Hide edit panel
		 */
		'hide': function () {
			if (this._widgets.panel) {
				this._widgets.panel.hide();
			}

			this.hideTemplates(true /* quick */);
			this.hideLayouts(true /* quick */);
		}
	});

	Action.PluginPageAdd = Plugin;



	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn);this.fn = function () {};

}, YUI.version, {'requires': ['supra.input', 'transition']});