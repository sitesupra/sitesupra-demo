//Invoke strict mode
"use strict";

YUI().add('website.sitemap-plugin-page-edit', function (Y) {
	
	//Shortcuts
	var Manager = Supra.Manager,
		Action = Manager.getAction('SiteMap');
	
	
	/**
	 * Page edit settings form
	 */
	function Plugin () {
		Plugin.superclass.constructor.apply(this, arguments);
	};
	
	Plugin.NAME = 'PluginSitemapPageEdit';
	Plugin.NS = 'page_edit';
	
	Y.extend(Plugin, Y.Plugin.Base, {
		/**
		 * TreeNode which currently user is editing
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
			/* Duplicate page button */
			'buttonDuplicate': null,
			/* Delete page button */
			'buttonDelete': null
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
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		
		/**
		 * Attach to event listeners, etc.
		 * 
		 * @constructor
		 * @private
		 */
		'initializer': function () {
			this._widgets = {};
			this.get('host').on('page:edit', this._showPanel, this);
		},
		
		/**
		 * Create edit form popup
		 * 
		 * @private
		 */
		'_createPanel': function () {
			var container = this.get('host').get('boundingBox').closest('.su-sitemap').one('.su-sitemap-edit'),
				widgets = this._widgets,
				panel = null;
			
			widgets.panel = panel = new Supra.Panel({
				'srcNode': container,
				'autoClose': true,
				'arrowVisible': true,
				'zIndex': 2,
				'closeOnEscapeKey': true
			});
			
			//Overwrite validate click
			panel.validateClick = function (event) {
				if (this.get('autoClose')) {
					var target = event.target.closest('div.su-panel'),
						align = null;
					
					if (target && target.compareTo(this.get('boundingBox'))) {
						return;
					}
					
					target = event.target,
					align = this.get('alignTarget');
					
					if (align && target && target.closest(align)) {
						return;
					}
					
					this.hide();
				}
			};
			
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
			widgets.buttonDuplicate = new Supra.Button({'srcNode': buttons.item(0), 'style': 'small'});
			widgets.buttonDelete = new Supra.Button({'srcNode': buttons.item(1), 'style': 'small-red'});
			
			widgets.buttonDuplicate.on('click', this.duplicatePage, this);
			widgets.buttonDelete.on('click', this.deletePage, this);
			
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
			}
			
			inputs.title.on('input', this._onPagePropertyQuickChange, this);
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
		'_showPanel': function (e) {
			this._node = e.node;
			
			if (!this._widgets.panel) {
				this._createPanel();
			}
			
			//Set form values
			this.setFormValues(this._node.get('data'));
			
			//Panel position and style
			var target = this._node.getWidget('buttonEdit').get('boundingBox'),
				panel = this._widgets.panel;
			
			if (target === panel.get('alignTarget')) {
				this._widgets.panel.show();
			} else {
				target.closest('.item').addClass('hover');
			
				panel.set('alignTarget', target);
				panel.set('alignPosition', this._getAlignPosition(target));
				
				panel.fadeIn();
			}
		},
		
		/**
		 * On page property change save data and update tree
		 * 
		 * @private
		 */
		'_onPagePropertyChange': function (e) {
			var id = e.target.get('id'),
				value = e.target.get('value'),
				original = null,
				node = this._node,
				data = node.get('data'),
				post_data = {},
				url = null;
			
			if (data[id] != value) {
				//Update node attribute
				if (this._nodeDataMapping[id]) {
					node.set(this._nodeDataMapping[id], value);
				}
				
				post_data.page_id = data.id;
				post_data.locale = this.get('host').get('locale');
				post_data[id] = value;
				
				original = data[id];
				data[id] = value;
				
				if (data.type === 'group') {
					url = Supra.Url.generate('cms_pages_group_save');
				} else if (data.type === 'template') {
					url = Supra.Url.generate('cms_pages_template_save');
				} else {
					url = Supra.Url.generate('cms_pages_page_save');
				}

				//Save data
				Manager.Page.updatePage(url, post_data, function (response, success) {
					if (success) {
						//Update data
						Supra.mix(data, response);
						
						if (id == 'path') {
							//Need to update this and all children full_path's
							node.updateFullPath();
						}
					} else {
						//Revert changes
						data[id] = original;
						
						if (this._nodeDataMapping[id]) {
							node.set(this._nodeDataMapping[id], data[id]);
						}
						
						//If form is open for the same node restore input value
						if (this._node === node) {
							this._widgets.form.getInput(id).set('value', data[id]);
						}
					}
				}, this);
			}
		},
		
		/**
		 * On page property change update UI
		 * 
		 * @private
		 */
		'_onPagePropertyQuickChange': function (e) {
			if (!this._node) return; // most likely initialization stage
			
			var input = e.target,
				id    = input.get('id'),
				value = input.get('value'),
				node  = this._node;
			
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
		},
		
		/**
		 * Delete selected page without confirmation
		 */
		'_deletePage': function (node) {
			var data = node.get('data'),
				is_page = node.isInstanceOf('TreeNode') ? true : false,
				
				page_id = data.id,
				locale = this.get('host').get('locale'),
				mode = this.get('host').get('mode'),
				
				target = null,
				target_fn = null;
			
			if (data.type == 'group') {
				//Virtual folder
				target = Manager.getAction('Page');
				target_fn = 'deleteVirtualFolder';
			} else if (mode == 'pages') {
				//Page
				target = Manager.getAction('Page');
				target_fn = 'deletePage';
			} else {
				//Template
				target = Manager.getAction('Template');
				target_fn = 'deleteTemplate';
			}
			
			//Send request
			target[target_fn](page_id, locale, function (data, success) {
				
				if (!is_page) {
					//Enable Data Grid
					node.host.set('disabled', false);
				}
				
				if (success) {
					//Hide panel
					if (this._node === node) {
						this._widgets.panel.hide();
					}
					
					//Fire event (reloads recycle bin)
					this.get('host').fire('page:delete', {'node': null, 'data': node.get('data')});
					
					//Remove data
					this.get('host').get('data').remove(node);
					this.get('host').remove(node);
					node.destroy();
					
				} else {
					//Hide panel
					if (this._node === node) {
						this._widgets.panel.hide();
					}
					
					//Restore all interactions with node
					if (is_page) {
						node.set('loading', false);
						node.set('dndLocked', false);
						
						node.getWidget('buttonEdit').set('disabled', false);
						node.getWidget('buttonOpen').set('disabled', false);
					}
				}
				
				//Restore previous widget state
				if (this._widgets.form) {
					this._widgets.form.set('disabled', false);
					this._widgets.buttonDelete.set('loading', false);
					this._widgets.buttonDuplicate.set('disabled', false);
				}
			}, this);
			
			//Disable all buttons and inputs
			if (this._node === node) {
				this._widgets.form.set('disabled', true);
				this._widgets.buttonDelete.set('loading', true);
				this._widgets.buttonDuplicate.set('disabled', true);
			} else {
				//Prevent all interactions with node
				if (is_page) {
					node.set('loading', true);
					node.set('dndLocked', true);
					
					node.getWidget('buttonEdit').set('disabled', true);
					node.getWidget('buttonOpen').set('disabled', true);
				} else {
					//Disable Data Grid
					node.host.set('disabled', true);
				}
			}
		},
		
		/**
		 * Handle duplicate request response
		 */
		'_duplicatePage': function (data, status, node, post_data) {
			if (status) {
				this._widgets.panel.hide();
				
				//Create node
				var data = Supra.mix(
					{},
					Supra.Manager.SiteMap.tree.util.getDefaultNodeData(),
					data
				);
				
				//Insert after
				var duplicatedNode = this.get('host').insert(data, node, 'after');
				
				// skip permissions loading, assume, that if user has rights to clone page,
				// then he has rights to edit cloned instance of page
				duplicatedNode.set('selectable', true);
			}
			
			this._widgets.form.set('disabled', false);
			this._widgets.buttonDelete.set('disabled', false);
			this._widgets.buttonDuplicate.set('loading', false);
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
				input = null,
				mode = this.get('host').get('mode');
			
			input = form.getInput('title');
			input.set('value', data.title);
			
			input = form.getInput('path');
			
			if (data.type == 'group') {
				input.set('visible', false);
				this._widgets.buttonDuplicate.set('visible', false);
			} else {
				this._widgets.buttonDuplicate.set('visible', true);
				
				if (mode == 'pages') {
					input.set('value', data.path);
					input.set('path', data.full_path.replace(data.path + '/', ''));
				}
				
				if (mode != 'pages' || this._node.get('root')) {
					input.set('visible', false);
				} else {
					input.set('visible', true);
				}
				
			}
			
			// Hide duplicate for root page
			if (mode == 'pages' && this._node.get('root')) {
				this._widgets.buttonDuplicate.set('visible', false);
			}
			
			// Hide delete for nodes with children
			//if (this._node.get('expandable')) {
			//	this._widgets.buttonDelete.set('visible', false);
			//} else {
			this._widgets.buttonDelete.set('visible', true);
			//}
		},
		
		/**
		 * Delete selected page
		 */
		'deletePage': function (node) {
			var node = (node && node.isInstanceOf && (node.isInstanceOf('TreeNode') || node.isInstanceOf('DataGridRow')) ? node : this._node),
				data = node.get('data'),
				message_id = '';
			
			if (this.get('host').get('mode') == 'pages') {
				if (data.localization_count > 1) {
					message_id = 'message_delete_page_all'
				} else {
					message_id = 'message_delete_page';
				}
			} else {
				message_id = 'message_delete_template';
			}
			
			Manager.executeAction('Confirmation', {
				'message': Supra.Intl.get(['sitemap', message_id]),
				'useMask': true,
				'buttons': [
					{'id': 'delete', 'label': '{# buttons.yes #}', 'click': function () { this._deletePage(node); }, 'context': this},
					{'id': 'no', 'label': '{# buttons.no #}'}
				]
			});
		},
		
		/**
		 * Duplicate selected page
		 */
		'duplicatePage': function (node) {
			var node = (node && node.isInstanceOf && node.isInstanceOf('TreeNode') ? node : this._node),
				data = node.get('data'),
				mode = this.get('host').get('mode'),
				post_data = {},
				action = null,
				fn = null,
				
				title = data.title.replace(/\s\(\d+\)$/, ''),
				path = (data.path || '').replace(/\-\d+$/, ''),
				index = 0;
			
			//Find new title and path
			index = this.get('host').page_add._getTitlePathIndex(this._node, title, path);
			index = index ? index : 1;
			
			title += ' (' + (index + 1) + ')';
				
			if (mode == 'pages') {
				post_data.path = path + '-' + (index + 1);
			}
			
			
			if (mode == 'pages') {
				action = Manager.getAction('Page');
				fn = 'duplicatePage';
			} else {
				action = Manager.getAction('Template');
				fn = 'duplicateTemplate';
			}
			
			post_data.action = 'duplicate';
			post_data.title = title;
			post_data.page_id = data.id;
			post_data.locale = this.get('host').get('locale');
			
			action[fn](post_data, function (d, s) { this._duplicatePage(d, s, node, post_data); }, this);
			
			this._widgets.form.set('disabled', true);
			this._widgets.buttonDelete.set('disabled', true);
			this._widgets.buttonDuplicate.set('loading', true);
		},
		
		/**
		 * Hide edit panel
		 */
		'hide': function () {
			if (this._widgets.panel && this._node) {
				this._widgets.panel.hide();
			}
		}
		
	});
	
	Action.PluginPageEdit = Plugin;
	
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.input']});