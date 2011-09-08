//Invoke strict mode
"use strict";

YUI().add('website.sitemap-settings', function (Y) {
	
	//Locale
	var LOCALE_DELETE_PAGE = 'Are you sure you want to delete selected page?';
	
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
					evt = Y.one(document).on('click', fn, this);
				} else if (evt) {
					evt.detach();
				}
			});
			
			this.addWidget(this.panel);
			
			//On tree node toggle hide panel
			this.host.flowmap.on('toggle', this.panel.hide, this.panel);
			
			//Create form
			var contbox = this.panel.get('contentBox');
			var form = this.form = new Supra.Form({
				'srcNode': contbox.one('form'),
				'autoDiscoverInputs': true,
				'inputs': [
					{'id': 'title', 'type': 'String', 'useReplacement': true},
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
			
			var all_data = this.host.flowmap.getIndexedData(),
				item = all_data[data.parent],
				fullpath = [];
			
			while(item) {
				fullpath.push(item.path);
				item = all_data[item.parent];
			}
			
			var path = fullpath.reverse().join('/');
				path = path && path != '/' ? path + '/' : '/';
			
			if (!data.path) {
				//Root page
				this.button_delete.set('disabled', true);
			} else {
				this.button_delete.set('disabled', false);
			}
			
			var path_input = this.form.getInput('path');
			this.form.setValues(data, 'id');
			
			path_input.set('path', path);
			path_input.set('disabled', !data.path);
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
		 * On page property change save value
		 */
		onPagePropertyChange: function (event) {
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
			
			//Call Page.updatePage or Template.updateTempalte
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
			
			Manager.executeAction('Confirmation', {
				'message': LOCALE_DELETE_PAGE,
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
	
}, YUI.version, {requires: ['supra.panel', 'supra.form', 'website.input-template']});
