//Invoke strict mode
"use strict";

YUI().add('website.sitemap-settings', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager,
		Action = Manager.Action;
	
	
	
	/**
	 * Page settings form
	 */
	function Plugin () {
		Plugin.superclass.constructor.apply(this, arguments);
	};
	
	Plugin.NAME = 'PluginSitemapSettings';
	
	Y.extend(Plugin, Action.PluginBase, {
		
		initialized: false,
		
		initialize: function () {},
		
		button_delete: null,
		
		initializeWidgets: function () {
			if (this.initialized) return;
			this.initialized = true;
			
			this.panel = new Supra.Panel({
				'srcNode': this.host.one('.sitemap-settings').removeClass('sitemap-settings'),
				'arrowPosition': ['L', 'C'],
				'arrowVisible': true,
				'constrain': SU.Manager.SiteMap.one(),
				'zIndex': 2
			});
			this.panel.get('boundingBox').addClass('sitemap-settings');
			this.panel.render(document.body);
			
			//On language change hide panel
			this.host.languagebar.on('localeChange', this.panel.hide, this.panel);
			
			//On document click hide panel
			var evt = null;
			var fn = function (event) {
				var target = event.target.closest('div.sitemap-settings');
				if (!target) this.hide();
			};
			
			//When panel is hidden remove 'click' event listener from document
			this.panel.on('visibleChange', function (event) {
				if (event.newVal) {
					if (evt) evt.detach();
					evt = Y.one(document).on('click', fn, this.panel);
				} else if (evt) {
					evt.detach();
					this.onPropertyPanelHide();
				}
			}, this);
			
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
					{'id': 'layout', 'type': 'String', 'useReplacement': true},
					{'id': 'path', 'type': 'Path', 'useReplacement': true}
				]
			});
			form.render(contbox);
			
			//On input change save value
			var inputs = form.getInputs();
			for(var id in inputs) {
				inputs[id].on('change', this.onPagePropertyChange, this);
			}
			
			//Delete button
			
			var btn = new Supra.Button({'srcNode': contbox.one('button'), 'style': 'mid-red'});
				btn.render();
				btn.on('click', this.deletePage, this);
			
			if (!Supra.Authorization.isAllowed(['page', 'delete'], true)) {
				btn.hide();
			}
			
			this.button_delete = btn;
		},
		
		/**
		 * Set property values
		 * 
		 * @param {Object} data
		 * @private
		 */
		setPropertyValues: function (data) {
			this.host.property_data = data;
			
			var flowmap = this.host.flowmap,
				type = this.host.getType(),
				node = flowmap.getNodeById(data.id),
				all_data = flowmap.getIndexedData(),
				path_input = this.form.getInput('path'),
				template_input = this.form.getInput('template'),
				layout_input = this.form.getInput('layout');
			
			if ((type != 'templates' && node && node.isRoot()) || data.path == '') {
				//Root page or application page without a path
				this.button_delete.set('disabled', true);
				path_input.set('disabled', true);
			} else {
				//Template or not a root page
				this.button_delete.set('disabled', false);
				path_input.set('disabled', false);
			}
			
			this.form.setValues(data, 'id');
			
			if (type == 'templates') {
				//Update delete button label
				this.button_delete.set('label', Supra.Intl.get(['sitemap', 'delete_template']));
				
				path_input.hide();
				template_input.hide();
				
				if (node && node.isRoot()) {
					layout_input.show();
				} else {
					layout_input.hide();
				}
			} else {
				//Update delete button label
				this.button_delete.set('label', Supra.Intl.get(['sitemap', 'delete_page']));
				
				var item = all_data[data.parent],
					fullpath = [],
					path = '';
					
				while(item) {
					// Skip empty path
					if (item.path != '') {
						fullpath.push(item.path);
					}
					item = all_data[item.parent];
				}
				fullpath.push('');
				
				path = fullpath.reverse().join('/');
				path = path && path != '/' ? path + '/' : '/';
				path = path + data.basePath;

				path_input.show();
				path_input.set('path', path);
				
				template_input.show();
				layout_input.hide();
			}
		},
		
		/**
		 * Open property panel
		 * 
		 * @param {Object} data Property form data
		 * @private
		 */
		showPropertyPanel: function (target, data, newpage) {
			this.initializeWidgets();
			
			//If there are focused inputs, then don't change
			var inputs = this.form.getInputs();
			for(var id in inputs) {
				if (inputs[id].get('focused')) return;
			}
			
			//Enable/disable template input
			inputs.template.set('disabled', !newpage);
			
			//Position panel
			this.panel.set('align', {'node': target, 'points': [Y.WidgetPositionAlign.LC, Y.WidgetPositionAlign.RC]});
			
			//Position arrow
			if (target) {
				this.setPropertyValues(data);
				
				this.panel.show();
				this.panel.set('arrowAlign', target);
			}
		},
		
		/**
		 * On property panel hide save all values if item is temporary
		 */
		onPropertyPanelHide: function () {
			if (this.host.property_data && this.host.property_data.temporary) {
				//Only root template can be temporary
				var old_data = this.host.property_data,
					old_id = old_data.id,
					data = this.form.getValues('name', true);
				
				data = {
					'layout': data.layout,
					'title': data.title,
					'icon': old_data.icon,
					'parent': old_data.parent,
					'published': old_data.published,
					'scheduled': old_data.scheduled
				};
				
				Manager.getAction('Template').createTemplate(data, function (data, status) {
					var treenode = this.host.flowmap.getNodeById(old_id);
					var node_data = treenode.get('data');
					
					data.temporary = false;
					Supra.mix(old_data, data);
					Supra.mix(node_data, data);
					
					var data_indexed = this.host.flowmap.getIndexedData();
					delete(data_indexed[old_id]);
					data_indexed[data.id] = data;
					
					//Unset data
					this.host.property_data = null;
				}, this);
			}
		},
		
		/**
		 * On page property change save value
		 */
		onPagePropertyChange: function (event) {
			//If page is temporary then there is no real ID this page
			if (this.host.property_data.temporary) {
				return;
			}
			
			var input = event.target,
				input_id = input.get('id'),
				input_value = input.get('saveValue'),
				type = this.host.getType(),
				target = null,
				target_fn = null,
				post_data = {
					'page_id': this.host.property_data.id,
					'locale': Manager.SiteMap.languagebar.get('locale')
				};
			
			post_data[input_id] = input_value;
			
			if (type == 'templates') {
				target = Manager.getAction('Template');
				target_fn = 'updateTemplate';
			} else {
				target = Manager.getAction('Page');
				target_fn = 'updatePage';
			}
			
			//Call Page.updatePage or Template.updateTempalate
			target[target_fn](post_data, function (data) {
				
				var treenode = this.host.flowmap.getNodeById(post_data.page_id);
				if (input_id == 'title') {
					treenode.get('boundingBox').one('label').set('text', input_value);
					treenode.syncUISize();
				}
				
				//Save data changes
				var node_data = treenode.get('data');
				node_data[input_id] = input_value;
				this.host.property_data[input_id] = input_value;
				
			}, this);
		},
		
		/**
		 * Delete selected page
		 * 
		 * @private
		 */
		deletePage: function () {
			if (!this.host.property_data) return;
			
			if (this.host.getType() == 'templates') {
				var message_id = 'message_delete_template';
			} else {
				var message_id = 'message_delete_page';
			}
			
			Manager.executeAction('Confirmation', {
				'message': Supra.Intl.get(['sitemap', message_id]),
				'useMask': true,
				'buttons': [
					{'id': 'delete', 'label': 'Yes', 'click': this.deletePageConfirm, 'context': this},
					{'id': 'no', 'label': 'No'}
				]
			});
		},
		
		/**
		 * After user confirmed page deletion collect page data and
		 * delete it
		 * 
		 * @private
		 */
		deletePageConfirm: function () {
			//Send request to server
			var page_id = this.host.property_data.id,
				locale = this.host.languagebar.get('locale'),
				type = this.host.getType(),
				target = null,
				target_fn = null;
			
			if (type == 'templates') {
				target = Manager.getAction('Template');
				target_fn = 'deleteTemplate';
			} else {
				target = Manager.getAction('Page');
				target_fn = 'deletePage';
			}
			
			target[target_fn](page_id, locale, function () {
				//Hide properties
				this.panel.hide();
				this.host.property_data = null;
				
				//Reload tree
				this.host.flowmap.reload();
				this.host.setLoading(true);
				
				//Reload recycle bin
				var recycle = Manager.getAction('SiteMapRecycle');
				if (recycle.get('visible')) {
					recycle.load();
				}
			}, this);
		}
		
	});
	
	Action.PluginSitemapSettings = Plugin;
	

	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.panel', 'supra.input', 'website.input-template']});
