YUI.add('supra.page-content-properties', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager,
		Action = Manager.Action;
	
	
	/*
	 * Container action
	 * Used to insert form into LayoutRightContainer, automatically adjusts layout and
	 * shows / hides other LayoutRightContainer child actions when action is shown / hidden
	 */
	
	//Add as right bar child
	Manager.getAction('LayoutRightContainer').addChildAction('PageContentSettings');
	
	new Action({
		// Unique action name
		NAME: 'PageContentSettings',
		// No need for template
		HAS_TEMPLATE: false,
		// Load stylesheet
		HAS_STYLESHEET: false,
		// Form instance
		form: null,
		
		// Render action container
		render: function () {
			var node = Y.Node.create('<div></div>');
			this.getPlaceHolder().append(node);
			this.set('srcNode', new Y.NodeList(node));
		},
		
		// Hide
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			//Hide action
			Manager.getAction('LayoutRightContainer').unsetActiveAction(this.NAME);
			
			//Hide form
			if (this.form) this.form.hide();
			this.form = null;
		},
		
		// Execute action
		execute: function (form) {
			Manager.getAction('LayoutRightContainer').setActiveAction(this.NAME);
			
			//Set form
			if (form) {
				if (this.form) this.form.hide();
				this.form = form;
				form.show();
			}
		}
	});
	
	/*
	 * Properties plugin
	 */
	function Properties () {
		Properties.superclass.constructor.apply(this, arguments);
		
	}
	
	Properties.NAME = 'page-content-properties';
	Properties.NS = 'properties';
	Properties.ATTRS = {
		/*
		 * Property values
		 */
		'data': {
			'value': {}
		},
		
		/*
		 * List of editable properties
		 */
		'properties': {
			'value': {}
		},
		
		/*
		 * Form Y.Node instance
		 */
		'form': {
			'value': null
		},
		
		/*
		 * Automatically show form when content is being edited
		 */
		'showOnEdit': {
			'value': false
		}
	};
	
	Y.extend(Properties, Y.Plugin.Base, {
		
		_node_content: null,
		
		destructor: function () {
			var form = this.get('form');
			form.destroy();
		},
		
		initializer: function (config) {
			var data = this.get('data');
			
			if (!data || !('type' in data)) return;
			
			var type = data.type,
				block = Manager.Blocks.getBlock(type);
			
			if (!block) return;
			this.set('properties', block.properties);
			
			//Create right bar container action if it doesn't exist
			var PageContentSettings = Manager.getAction('PageContentSettings');
			PageContentSettings.execute();
			PageContentSettings.hide();
			
			//Bind to editing-start and editing-end events
			if (this.get('showOnEdit')) {
				this.get('host').on('editing-start', this.showPropertiesForm, this);
			}
			
			//Hide form when editing ends
			this.get('host').on('editing-end', this.hidePropertiesForm, this);
			
			//Properties form
			var form_config = {'autoDiscoverInputs': false, 'inputs': []},
				properties = this.get('properties');
			
			for(var i=0, ii=properties.length; i<ii; i++) {
				form_config.inputs.push(properties[i]);
			}
			
			var form = new Supra.Form(form_config);
				form.render(Manager.PageContentSettings.getContainer());
				form.get('boundingBox').addClass('yui3-form-properties');
				form.hide();
			
			this.set('form', form);
			
			
			//Form heading
			var heading = Y.Node.create('<h2>' + Y.Lang.escapeHTML(block.title) + ' block properties</h2>');
			form.get('contentBox').insert(heading, 'before');
			
			
			//Buttons
			var buttons = Y.Node.create('<div class="yui3-form-buttons"></div>');
			form.get('contentBox').insert(buttons, 'before');
			
			//Save button
			var btn = new Supra.Button({'label': 'Apply', 'style': 'mid-blue'});
				btn.render(buttons).on('click', this.savePropertyChanges, this);
			
			//Cancel button
			var btn = new Supra.Button({'label': 'Close', 'style': 'mid'});
				btn.render(buttons).on('click', this.cancelPropertyChanges, this);
				
			//Delete button
			var btn = new Supra.Button({'label': 'Delete', 'style': 'mid-red'});
				btn.render(buttons).on('click', this.deleteContent, this);
			
		},
		
		/**
		 * Save changes
		 */
		savePropertyChanges: function () {
			// @TODO Save properties
			SU.Manager.PageContentSettings.hide();
			this.get('host').fire('properties:save');
		},
		
		/**
		 * CancelSave changes
		 */
		cancelPropertyChanges: function () {
			// @TODO Revert property changes
			SU.Manager.PageContentSettings.hide();
			this.get('host').fire('properties:cancel');
		},
		
		/**
		 * Delete content
		 */
		deleteContent: function () {
			//Trigger event; plugins or other contents may use this
			this.fire('delete');
			
			//Remove content
			var host = this.get('host');
			var parent = host.get('parent') || host.get('parent');
			parent.removeChild(host);
		},
		
		/**
		 * Show properties form
		 */
		showPropertiesForm: function () {
			Manager.getAction('PageContentSettings').execute(this.get('form'));
		},
		
		/**
		 * Hide properties form
		 */
		hidePropertiesForm: function () {
			this.get('form').hide();
			Manager.getAction('PageContentSettings').hide();
		}
	});
	
	Manager.PageContent.PluginProperties = Properties;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['widget', 'plugin', 'supra.button', 'supra.form']});