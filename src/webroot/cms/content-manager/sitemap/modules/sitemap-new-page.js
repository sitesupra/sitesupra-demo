//Invoke strict mode
"use strict";

YUI().add('website.sitemap-new-page', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager,
		Action = Manager.Action;
	
	
	
	/**
	 * New page settings form
	 */
	function Plugin () {
		Plugin.superclass.constructor.apply(this, arguments);
	};
	
	Plugin.NAME = 'PluginSitemapNewPage';
	
	Y.extend(Plugin, Action.PluginBase, {
		
		/**
		 * Initialization status
		 * @type {Boolean}
		 * @private
		 */
		initialized: false,
		
		/**
		 * "Create" button
		 * @type {Object}
		 * @private
		 */
		button_create: null,
		
		
		/**
		 * @constructor
		 */
		initialize: function () {},
		
		initializeWidgets: function () {
			if (this.initialized) return;
			this.initialized = true;
			
			this.panel = new Supra.Panel({
				'srcNode': this.host.one('.sitemap-new-page').removeClass('sitemap-new-page'),
				'arrowPosition': ['L', 'C'],
				'arrowVisible': true,
				'constrain': SU.Manager.SiteMap.one(),
				'zIndex': 2
			});
			this.panel.get('boundingBox').addClass('sitemap-new-page');
			this.panel.render(document.body);
			
			//On language change hide panel
			this.host.languagebar.on('localeChange', this.panel.hide, this.panel);
			
			//When panel is hidden remove 'click' event listener from document
			this.panel.on('visibleChange', this.handleVisibleChange, this);
			
			this.addWidget(this.panel);
			
			//When host action is hidden also hide panel
			this.host.on('visibleChange', function (event) {
				if (!event.newVal && event.newVal != event.prevVal) {
					if (this.panel.get('visible')) {
						this.panel.hide();
					}
				}
			}, this);
			
			//On tree node toggle hide panel
			this.host.flowmap.on('toggle', this.panel.hide, this.panel);
			
			//Create form
			var contbox = this.panel.get('contentBox');
			var form = this.form = new Supra.Form({
				'srcNode': contbox.one('form'),
				'autoDiscoverInputs': true,
				'inputs': [
					{'id': 'title', 'type': 'String', 'useReplacement': true},
					{'id': 'layout', 'type': 'String', 'useReplacement': true, 'label': SU.Intl.get(['sitemap', 'form_label_layout'])},
					{'id': 'path', 'type': 'Path', 'useReplacement': true}
				]
			});
			form.render(contbox);
			
			//On input change save value
			var inputs = form.getInputs();
			for(var id in inputs) {
				inputs[id].on('change', this.onPagePropertyChange, this);
			}
			
			//Save button
			var btn = this.button_create = new Supra.Button({'srcNode': contbox.one('button'), 'style': 'mid'});
				btn.render();
				btn.on('click', this.saveNewPage, this);
			
			//Cancel button
			var link = contbox.one('a.cancel');
				link.on('click', this.panel.hide, this.panel);
		},
		
		/**
		 * Check if mouse clicked inside panel, if not then hide it
		 */
		checkMouseClick: function (event) {
			var target = event.target.closest('div.sitemap-new-page');
			if (!target) this.hide();
		},
		
		/**
		 * Handle panel visibility change
		 */
		handleVisibleChange: function (event) {
			if (event.newVal) {
				if (this.visibility_evt) this.visibility_evt.detach();
				this.visibility_evt = Y.one(document).on('click', this.checkMouseClick, this.panel);
			} else {
				if (this.visibility_evt) {
					this.visibility_evt.detach();
				}
				
				if (this.host.property_data) {
					this.removeTemporaryNode();
				}
			}
		},
		
		/**
		 * Remove temporary tree node
		 */
		removeTemporaryNode: function () {
			var id = this.host.property_data.id,
				node = this.host.flowmap.getNodeById(id),
				parent = node.get('parent');
			
			//Remove node
			node.get('parent').remove(node.get('index'));
			
			//Remove data
			this.host.property_data = null;
			var data_indexed = this.host.flowmap.getIndexedData();
			delete(data_indexed[id]);
			
			//Update parent UI
			if (parent) parent.syncUI();
		},
		
		/**
		 * Set property values
		 * 
		 * @param {Object} data
		 * @private
		 */
		setNewPageValues: function (data) {
			this.host.property_data = data;
			
			var flowmap = this.host.flowmap,
				type = this.host.getType(),
				node = flowmap.getNodeById(data.id),
				all_data = flowmap.getIndexedData(),
				path_input = this.form.getInput('path'),
				template_input = this.form.getInput('template'),
				layout_input = this.form.getInput('layout');
			
			if ((type != 'templates' && node && node.isRoot()) || ! data.path) {
				//Root page or application page without a path
				path_input.set('disabled', true);
			} else {
				//Template or not a root page
				path_input.set('disabled', false);
			}
			
			this.form.setValues(data, 'id');
			
			if (type == 'templates') {
				//Update create button label
				this.button_create.set('label', Supra.Intl.get(['sitemap', 'create_template']));
				
				path_input.hide();
				template_input.hide();
				
				if (node && node.isRoot()) {
					layout_input.show();
				} else {
					layout_input.hide();
				}
			} else {
				//Update create button label
				this.button_create.set('label', Supra.Intl.get(['sitemap', 'create_page']));
				
				if (data.type != 'group') {
					var item = all_data[data.parent],
						fullpath = [],
						path = '';
						
					while(item) {
						// Skip empty path
						if (item.path) {
							fullpath.push(item.path);
						}
						item = all_data[item.parent];
					}
					fullpath.push('');
					
					path = fullpath.reverse().join('/');
					path = path && path != '/' ? path + '/' : '/';
					path = path + (data.basePath || '');
	
					path_input.show();
					path_input.set('path', path);
					
					template_input.show();
				} else {
					path_input.hide();
					template_input.hide();
				}
				
				layout_input.hide();
			}
		},
		
		/**
		 * Open property panel
		 * 
		 * @param {Y.Node} target Align target
		 * @param {Object} data Property form data
		 * @param {Boolean} newpage True if this is a new page and false if template
		 */
		showNewPagePanel: function (target, data, newpage) {
			this.initializeWidgets();
			
			//If there are focused inputs, then don't change
			var inputs = this.form.getInputs();
			for(var id in inputs) {
				if (inputs[id].get('focused')) return;
			}
			
			//Enable/disable template input
			inputs.template.set('disabled', !newpage);
			
			//Hide panel if it is already visible to remove old temporary node
			if (this.panel.get('visible')) {
				this.panel.hide();
			}
			
			//Position panel
			this.position(target);
			
			//Position arrow
			if (target) {
				this.setNewPageValues(data);
				
				this.panel.show();
				this.panel.set('arrowAlign', target);
			}
		},
		
		/**
		 * Reposition panel
		 * 
		 * @param {Y.Node} target Align target
		 */
		position: function (target) {
			this.panel.set('align', {'node': target, 'points': [Y.WidgetPositionAlign.LC, Y.WidgetPositionAlign.RC]});
		},
		
		/**
		 * On property panel hide save all values if item is temporary
		 */
		saveNewPage: function () {
			var old_data = this.host.property_data,
				old_id = old_data.id,
				data = this.form.getValues('name', true),
				postdata = null,
				type = this.host.getType(),
				page_type = old_data.type;
			
			postdata = {
				'locale': Manager.SiteMap.languagebar.get('locale'),
				'title': data.title,
				'icon': old_data.icon,
				'parent': old_data.parent,
				'published': old_data.published,
				'scheduled': old_data.scheduled,
				'type': old_data.type
			};
			
			if (type == 'templates') {
				postdata.layout = data.layout;
				
				target = Manager.getAction('Template');
				target_fn = 'createTemplate';
			} else {
				if (page_type != 'group') {
					postdata.template = data.template;
					postdata.path = data.path;
					
					if (page_type == 'application') {
						postdata.application_id = old_data.application_id;
					}
				}
				
				target = Manager.getAction('Page');
				target_fn = 'createPage';
			}
			
			this.button_create.set('loading', true);
			
			target[target_fn](postdata, function (data, status) {
				this.button_create.set('loading', false);
				
				if (status) {
					var treenode = this.host.flowmap.getNodeById(old_id);
					var all_data = this.host.flowmap.getIndexedData();
					var node_data = treenode.get('data');
					var children = data.children;
					
					if (children) {
						delete(data.children);
					}
					
					data.temporary = false;
					Supra.mix(old_data, data);
					Supra.mix(node_data, data);
					
					var data_indexed = this.host.flowmap.getIndexedData();
					delete(data_indexed[old_id]);
					data_indexed[data.id] = data;
					
					//Update item
					treenode.set('label', data.title);
					treenode.set('icon', data.icon);
					treenode.set('preview', data.preview);
					
					//Unset data
					this.host.property_data = null;
					
					//Hide panel
					this.panel.hide();
					
					//Add children
					if (children) {
						var index = 0;
						
						for(var i=0,ii=children.length; i<ii; i++) {
							children[i].parent = data.id;
							all_data[children[i].id] = children[i];
						}
						
						treenode.addChildren(children);
					}
				}
			}, this);
		}
		
	});
	
	Action.PluginSitemapNewPage = Plugin;
	

	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.panel', 'supra.input', 'website.input-template']});
