YUI.add('supra.page-content-properties', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager,
		Action  = Manager.Action;
	
	var ACTION_TEMPLATE = 
			'<div class="sidebar block-settings">' +
			'	<div class="sidebar-header">' +
			'		<button class="button-back hidden"><p>{# buttons.back #}</p></button>' +
			'		<img src="" class="hidden" alt="" />' +
			'		<button type="button" class="button-control"><p>{# buttons.done #}</p></button>' +
			'		<h2></h2>' +
			'	</div>' +
			'	<div class="sidebar-content has-header"></div>' +
			'</div>';
	
	var SLIDESHOW_MAIN_SLIDE = 'propertySlideMain';
	
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
			'value': {},
			'setter': '_attrDataSetter',
			'getter': '_attrDataGetter'
		},
		
		/*
		 * List of editable properties
		 */
		'properties': {
			'value': {}
		},
		
		/*
		 * Supra.Form instance
		 */
		'form': {
			'value': null
		},
		
		/**
		 * Supra.Slideshow instance, used for grouping inputs
		 */
		'slideshow': {
			'value': null
		},
		
		/**
		 * Show global block message 
		 */
		'showGlobalBlockMessage': {
			'value': false,
			'setter': '_uiShowGlobalBlockMessage'
		},
		
		/*
		 * Automatically show form when content is being edited
		 */
		'showOnEdit': {
			'value': true
		},
		
		/*
		 * Normal property has changed
		 */
		'changed': {
			'value': false
		}
	};
	
	Y.extend(Properties, Y.Plugin.Base, {
		
		/**
		 * Element for global block message
		 * @type {Object}
		 * @protected
		 */
		globalBlockMessageElement: null,
		
		/**
		 * Inline properties were found
		 * @type {Boolean}
		 * @protected
		 */
		hasInlineProperties: false,
		
		/**
		 * HTML properties were found
		 * @type {Boolean}
		 * @protected
		 */
		hasHTMLProperties: false,
		
		/**
		 * Form values are being updated
		 * @type {Boolean}
		 * @protected
		 */
		preventValueUpdateEventLoop: false,
		
		/**
		 * Supra.Form.PropertyElementLocator widget
		 * @type {Object}
		 * @protected
		 */
		formElementLocator: null,
		
		
		
		/**
		 * Remove event listeners, clean up
		 */
		destructor: function () {
			var form = this.get('form'),
				toolbar = Manager.EditorToolbar.getToolbar(),
				host = this.get('host');
			
			toolbar.unsubscribe('command', this.toolbarCommandAction, this);
			
			host.unsubscribe('editing-start', this.handleEditingStart, this);
			host.unsubscribe('editing-end', this.handleEditingEnd, this);
			
			form.destroy(true);
		},
		
		/**
		 * @constructor
		 */
		initializer: function (config) {
			//Create right bar container action if it doesn't exist
			var action = Manager.getAction('PageContentSettings');
			this.set('action', action);
			
			if (!action.get('executed')) {
				action.execute(null, {'first_init': true});
				action.hide();
			}
			
			//Properties form
			this.initializeProperties();
			var form = this.get('form');
			
			//Create empty GroupToolbar group in toolbar in case it will be needed
			this.createGroupToolbar();
			
			//Bind to editing-start and editing-end events
			this.get('host').on('editing-start', this.handleEditingStart, this);
			this.get('host').on('editing-end', this.handleEditingEnd, this);
			
			// Clicking on toolbar settings button should open block settings
			// Button is visible only if there are HTML inline inputs
			var toolbar = Manager.EditorToolbar.getToolbar();
			toolbar.on('command', this.toolbarCommandAction, this);
			
			//Start editing immediatelly
				//this.handleEditingStart();  // <- causes new block to loose editor toolbar after sidebar close
				//Supra.immediate(this, this.handleEditingStart); // <- causes invisible sidebar
				setTimeout(Y.bind(this.handleEditingStart, this), 50);
		},
		
		/**
		 * On editing start show toolbar and open settings form if needed
		 * 
		 * @protected
		 */
		handleEditingStart: function () {
			if (this.hasHtmlInputs()) {
				Manager.EditorToolbar.execute();
			} else {
				this.showPropertiesForm();
			}
		},
		
		/**
		 * On editing end hide toolbar and settings form
		 * 
		 * @protected
		 */
		handleEditingEnd: function () {
			//Hide editor toolbar
			Manager.EditorToolbar.hide();
			
			//Hide form when editing ends
			this.hidePropertiesForm();
		},
		
		/**
		 * Initialize properties form
		 */
		initializeProperties: function () {
			var form_config = {
					'autoDiscoverInputs': false, // form is generated dynamically, no inputs to search for
					'inputs': [],
					'style': 'vertical',
					'parent': this, // direct parent is this
					'root': this.get('host') // root parent is block
				},
				properties = this.get('properties'),
				slideshow,
				host = this.get('host'),
				host_node = host.getNode(),
				containerNode,
				
				locator = new Supra.Form.PropertyElementLocator({
					'iframe': this.get('host').get('super').get('iframe'),
					'rootNode': this.get('host').getNode()
				});
			
			//Find if there are inline/html properties
			this.formElementLocator  = locator;
			this.hasInlineProperties = locator.hasInlineInputs(properties, this.get('host').get('data').properties);
			this.hasHTMLProperties   = locator.hasHtmlInputs(properties, this.get('host').get('data').properties);
			
			//Slideshow is used for Collection and Set properties
			slideshow = this.initializeSlideshow();
			containerNode = slideshow.getSlide(SLIDESHOW_MAIN_SLIDE).one('.su-slide-content');
			
			form_config.inputs = this.getFormConfigProperties(properties);
			
			//On slideshow slide change update "Back" button
			slideshow.on('slideChange', this.onSlideshowSlideChange, this);
			
			//Create form
			form_config.slideshow = slideshow;
			var form = this.initializeForm(form_config);
			
			if (!host.isInstanceOf('page-content-list')) {	
				// Delete block button
				var btn = new Supra.Button({'label': Supra.Intl.get(['page', 'delete_block']), 'style': 'small-red'});
					btn.render(containerNode).on('click', this.deleteBlockAction, this);
				
				// Show message if this is a block and it's global
				if (host.getPropertyValue('locked') || host.isClosed()) {
					this.set('showGlobalBlockMessage', true);
				}
				
				// After inputs change 
				form.after('inputsChange', this.syncDeleteButtonPosition, this);
				
			}
		},
		
		/**
		 * Returns normalized form properties
		 *
		 * @param {Array} properties Form properties
		 * @returns {Array} Form properties
		 */
		getFormConfigProperties: function (properties) {
			var containerNode = this.get('slideshow').getSlide(SLIDESHOW_MAIN_SLIDE).one('.su-slide-content'),
				result = [];
			
			// Normalize configuration
			for(var i=0, ii=properties.length; i < ii; i++) {
				// Set 'name' for properties
				properties[i].name = properties[i].name || properties[i].id;
				
				if (Supra.Input.isContained(properties[i].type)) {
					//Set input container node to the main slide
					properties[i].containerNode = containerNode;
				}
				
				result.push(Supra.mix({}, properties[i]));
			}
			
			return result;
		},
		
		/**
		 * Recheck inline properties
		 */
		reinitializeProperties: function () {
			var properties = this.get('properties'),
				
				hadHTMLProperties = this.hasHTMLProperties,
				locator = this.formElementLocator;
			
			//Initialize properties
			this.hasInlineProperties = locator.hasInlineInputs(properties, this.get('data').properties);
			this.hasHTMLProperties = locator.hasHtmlInputs(properties, this.get('data').properties);
			
			if (hadHTMLProperties && !this.hasHTMLProperties) {
				// Make sure HTML editor toolbar will be closed when
				// form is closed
				var settings = this.get('action'),
					queue = settings.open_toolbar_on_hide;
				
				if (settings.get('visible') && queue.length) {
					queue[queue.length - 1] = false;
				}
			}
		},
		
		/**
		 * Create slideshow
		 */
		initializeSlideshow: function () {
			//Slideshow is used for grouping properties
			var slideshow = new Supra.Slideshow(),
				slide_id = null,
				slide = slideshow.addSlide({'id': SLIDESHOW_MAIN_SLIDE}),
				slide_main = slide;
			
			this.set('slideshow', slideshow);
			
			//Hide back button
			this.get('action').get('backButton').hide();
			
			return slideshow;
		},
		
		/**
		 * Add listener for form input
		 *
		 * @protected
		 */
		registerFormInput: function (e) {
			var input = e.input,
				id    = input.getHierarhicalName(),
				node,
				toolbar;
			
			input.on('change', this.onPropertyChange, this);
			input.on('input', this.onImmediatePropertyChange, this);
			input.after('focusedChange', this.afterInputBlur, this);
			
			if (input.isInstanceOf('input-html-inline')) {
				//Bind command to editor instead of toolbar, because toolbar is shared between editors
				input.getEditor().addCommand('settings', Y.bind(this.togglePropertiesForm, this));
			}
			if (input.isInstanceOf('input-collection')) {
				input.on('add', this.onCollectionChange, this);
				input.on('remove', this.onCollectionChange, this);
			}
			
			//When clicking on node enable corresponding editor
			node = this.formElementLocator.getInputElement(input);
			
			if (node) {
				node.on('mousedown', function (event, id) {
					if (this.get('editing')) {
						this.set('activeInlinePropertyName', id);
					}
				}, this.get('host'), id);
			}
		},
		
		/**
		 * Create form
		 */
		initializeForm: function (form_config) {
			var form = new Supra.Form(form_config),
				data = this.get('data').properties,
				slideshow = this.get('slideshow');
			
			this.formElementLocator.set('form', form);
			
			form.on('input:add', this.registerFormInput, this);
			form.on('visibleChange', this.onSidebarVisibileChange, this);
			
			form.render(this.get('action').get('contentInnerNode'));
			form.get('boundingBox').addClass('su-form-properties');
			form.hide();
			
			slideshow.render(form.get('contentBox'));
			
			this.preventValueUpdateEventLoop = true;
			form.setValues(data, 'id');
			this.preventValueUpdateEventLoop = false;
			
			// Show tooltip if it exists
			var block_type = this.get('host').getBlockType();
			
			if (Supra.Help.tipExists(block_type)) {
				Supra.Help.tip(block_type, {
					'append': slideshow.getSlide(SLIDESHOW_MAIN_SLIDE).one('.su-slide-content'),
					'position': 'relative'
				});
			}
			
			// Show tooltip from block configuration
			var configuration = this.get('host').getBlockInfo(),
				tooltip       = configuration.tooltip,
				buttons       = [],
				widget        = null;
			
			if (tooltip) {
				if (tooltip.button) {
					buttons.push({
						'label': tooltip.button.label || '',
						'action': tooltip.button.javascriptAction || '',
						'permissions': tooltip.button.javascriptPermissions || ''
					});
				}
				
				widget = new Supra.HelpTip({
					'title': tooltip.title || '',
					'description': tooltip.text || '',
					'buttons': buttons,
					'style': tooltip.style || '',
					'closeButtonVisible': false,
					'position': 'relative'
				});
				
				widget.render();
				slideshow.getSlide(SLIDESHOW_MAIN_SLIDE).one('.su-slide-content').append(widget.get('boundingBox'));
			}
			
			this.set('form', form);
			return form;
		},
		
		/**
		 * On Slideshow slide change show
		 */
		onSlideshowSlideChange: function (evt) {
			if (evt.newVal == SLIDESHOW_MAIN_SLIDE) {
				this.get('action').get('backButton').set('visible', false);
			} else {
				this.get('action').get('backButton').set('visible', true);
			}
		},
		
		
		/* ---------- On property changes trigger content events ----------- */
		
		
		/**
		 * On collection change check if there are any inline inputs possible
		 * and if there are then reload content
		 */
		onCollectionChange: function (e) {
			var reload = false,
				input = e.target,
				properties = input.get('properties'),
				i, ii;
			
			// Check if collection can have inline inputs, but we search only
			// through first level of a Set, because deeper nested collections will
			// trigger this callback too
			if (properties) {
				if (Supra.Input.isInline(properties.type)) {
					reload = true;
				} else if (properties.properties) {
					properties = properties.properties;
					
					for (i=0, ii=properties.length; i < ii; i++) {
						if (Supra.Input.isInline(properties[i].type)) {
							reload = true;
							break;
						}
					}
				}
			}
			
			if (reload) {
				this.get('host').reload();
			}
		},
		
		/**
		 * On property change update state
		 * 
		 * @param {Object} evt
		 */
		onPropertyChange: function (evt) {
			// If settings initial values, then we should trigger events
			if (this.preventValueUpdateEventLoop) return;
			
			//Trigger event
			var input = evt.target,
				id = input.get('id');
			
			Y.later(60, this, this.onPropertyChangeTriggerContentChange, [input, null, false]);
			
			//If Global block property changed, then show/hide global block message
			if (id == '__locked__') {
				var host = this.get('host');
				// Message should be visible only for blocks
				if (!host.isList()) {
					this.set('showGlobalBlockMessage', evt.newVal || evt.value || host.isClosed());
				}
			}
			
			//Update attributes
			if (this.get('changed')) return;
			this.set('changed', true);
		},
		
		/**
		 * On immediate propert change (change still in progress) trigger event
		 * 
		 * @param {Object} evt
		 */
		onImmediatePropertyChange: function (evt) {
			// If settings initial values, then we should trigger events
			if (this.preventValueUpdateEventLoop) return;
			
			//Trigger event
			this.onPropertyChangeTriggerContentChange(evt.target, evt.newVal, true);
		},
		
		/**
		 * When property value changes trigger content event
		 * If event is stoped then reload block content 
		 * 
		 * @param {Object} input Input object
		 * @param {Object} value Input value, if not specified then uses input value
		 * @param {Boolean} dirty If true then content is not reloaded if event is stoped
		 * @protected
		 */
		onPropertyChangeTriggerContentChange: function (input, value, dirty) {
			//We don't want to trigger for inline inputs for performance reasons
			//because inline input value changes very offten
			if (input.get('inline')) return;
			
			var host = this.get('host'),
				result = null;
			
			result = host.fireContentEvent('update', host.getNode().getDOMNode(), {
				'propertyName': 			input.get('name') || input.get('id'),
				'propertyHierarhicalName':	input.getHierarhicalName(),
				'propertyValue': 			(value === null || value === undefined ? input.get('value') : value),
				'propertyValueList': 		input.get('values') || null, // all values
				'supra': 					Supra
			});
			
			if (!dirty && result === false) {
				//Some property was recognized, but preview can't be updated without refresh
				input.set('loading', true);
				host.reload().done(function () {
					input.set('loading', false);
				}, this);
			}
		},
		
		
		
		
		/**
		 * Input blur
		 * 
		 * @param {Object} evt
		 */
		afterInputBlur: function (evt) {
			if (!evt.newVal && evt.newVal != evt.prevVal) {
				//Unset active property
				var input = evt.target,
					name  = input.getHierarhicalName(),
					host  = this.get('host');
				
				if (host.get('activeInlinePropertyName') == name) {
					host.set('activeInlinePropertyName', null);
				}
			}
		},
		
		/**
		 * Save changes
		 */
		savePropertyChanges: function () {
			// Property which affects inline content may have
			// changed, need to reload block content.
			if (!this.get('host').saving) {
				var button = this.get('action').get('controlButton');
				if (button) button.set('loading', true);
				
				this.get('host').reload().done(this.savePropertyChangesAfter, this);
			} else {
				this.savePropertyChangesAfter();
			}
			
		},
		
		savePropertyChangesAfter: function () {
			var button = this.get('action').get('controlButton');
			if (button) button.set('loading', false);
				
			this.get('host').fire('properties:save');
			
			this.set('changed', false);
			this.get('action').hide();
			
			//Reset slideshow position
			this.get('slideshow').set('slide', SLIDESHOW_MAIN_SLIDE);
		},
		
		/**
		 * Delete content
		 */
		deleteBlockAction: function () {
			var message = '',
				host    = this.get('host'),
				locked  = host.getPropertyValue('locked');
			
			if (locked || host.isClosed()) {
				message = Supra.Intl.get(['page', 'delete_block_global_confirmation']);
			} else {
				message = Supra.Intl.get(['page', 'delete_block_confirmation']);
			}
			
			Supra.Manager.executeAction('Confirmation', {
				'message': message,
				'useMask': true,
				'buttons': [
					{'id': 'delete', 'label': Supra.Intl.get(['buttons', 'yes']), 'context': this, 'click': function () {
						var host = this.get('host');
						var parent = host.get('parent');
						
						//Close form
						this.hidePropertiesForm();
						
						//Trigger event; plugins or other contents may use this
						this.fire('delete');
						
						//Remove content
						parent.removeChild(host);
						
					}},
					{'id': 'no'}
				]
			});
		},
		
		
		/* ------------------------ Sidebar form ------------------------ */
		
		
		/**
		 * Returns properties form title
		 * 
		 * @return Properties form title
		 * @type {String}
		 */
		getTitle: function () {
			var type = this.get('host').get('data').type,
				block = Manager.Blocks.getBlock(type),
				title = block.title;
			
			if (!title) {
				//Convert ID into title
				title = this.get('host').getId();
				title = title.replace(/[\-\_\.]/g, ' ');
				title = title.substr(0,1).toUpperCase() + title.substr(1);
			}
			
			return title;
		},
		
		/**
		 * Show properties form
		 */
		showPropertiesForm: function () {
			//Show form
			this.get('action').execute(this.get('form'), {
				'doneCallback': Y.bind(this.savePropertyChanges, this),
				'hideEditorToolbar': true,
				'properties': this,
				
				'scrollable': false,
				'title': this.getTitle()
			});
			
			this.get('host').fire('properties:show');
			this.get('form').show();
		},
		
		/**
		 * Hide properties form
		 */
		hidePropertiesForm: function (options) {
			this.get('form').hide();
			this.get('action').hide(options);
		},
		
		/**
		 * Toggle properties form
		 */
		togglePropertiesForm: function () {
			var toolbar = Manager.EditorToolbar.getToolbar();
			if (toolbar.getButton('settings').get('down')) {
				this.showPropertiesForm();
			} else {
				this.hidePropertiesForm();
			}
		},
		
		/**
		 * On sidebar visiblity change update toolbar
		 *
		 * @protected
		 */
		onSidebarVisibileChange: function (evt) {
			if (this.hasHtmlInputs()) {
				if (evt.newVal != evt.prevVal && !evt.newVal) {
					var action  = Manager.EditorToolbar,
						toolbar = action.getToolbar();
					
					toolbar.getButton('settings').set('down', false);
					
					if (!action.get('visible')) {
						// Some properties may have changed and now there are inline html inputs
						// so we show toolbar manually if it's not visible
						action.execute();
					}
				}
			}
		},
		
		
		/* --------------------------- Values --------------------------- */
		
		/**
		 * Returns all property values
		 * 
		 * @return Values
		 * @type {Object}
		 */
		getValues: function () {
			var form = this.get('form');
			if (form) {
				return form.getValues('id');
			} else {
				return {};
			}
		},
		
		/**
		 * Returns all non-inline property values
		 * 
		 * @return Values
		 * @type {Object}
		 */
		getNonInlineValues: function (values) {
			var values = values ? values : this.getValues(),
				properties = this.get('properties'),
				out = {},
				is_contained = false;
			
			for(var i=0,ii=properties.length; i < ii; i++) {
				is_contained = Supra.Input.isContained(properties[i].type);
				
				if (is_contained) {
					out[properties[i].id] = values[properties[i].id];
				}
			}
			
			return out;
		},
		
		setValues: function (values) {
			var form = this.get('form');
			
			if (form) {
				var page_data = Manager.Page.getPageData(),
					parent = this.get('host').get('parent'),
					
					advanced_set = form.getInput('__advanced__'),
					locked_input = advanced_set ? advanced_set.getInput('__locked__') : null;
				
				if (advanced_set) {
					if (page_data.type != 'page' && (!parent || !parent.get('data').closed)) {
						//Template blocks have "Global block" input
						//but if placeholder is closed (not editable), then don't show it
						advanced_set.set('visible', true);
						if (locked_input) locked_input.set('visible', true).set('disabled', false);
					} else {
						//If '__locked__'  is only input in the form then hide button
						if (Y.Object.size(advanced_set.getInputs()) <= 1) {
							advanced_set.set('visible', false);
						} else {
							advanced_set.set('visible', true);
							locked_input.set('visible', false).set('disabled', true);
						}
					}
				}
				
				this.preventValueUpdateEventLoop = true;
				form.setValuesObject(values, 'id');
				this.preventValueUpdateEventLoop = false;
			}
		},
		
		/**
		 * Returns all property values processed for saving
		 * 
		 * @return Values
		 * @type {Object}
		 */
		getSaveValues: function () {
			var form = this.get('form');
			if (form) {
				return form.getSaveValues('id');
			} else {
				return {};
			}
		},
		
		
		/* ------------------------- Toolbar ------------------------- */
		
		
		/**
		 * Handle toolbar command
		 * If command is settings, then toggle settings form
		 * 
		 * @param {Object} event Event object
		 * @protected
		 */
		toolbarCommandAction: function (event) {
			if (event.command === 'settings') {
				var inputs = this.get('form').getAllInputs('name'),
					active = this.get('host').get('activeInlinePropertyName');
				
				if (inputs[active] && !inputs[active].get('disabled')) {
					this.togglePropertiesForm();
				}
			}
		},
		
		/**
		 * Show block toolbar
		 * 
		 * @protected
		 */
		showGroupToolbar: function () {
			var group_id;

			if (this.hasHtmlInputs()) {
				group_id = 'EditorToolbar';
			} else {
				group_id = 'BlockToolbar';
			}
			
			Manager.PageToolbar.setActiveAction(group_id);
			Manager.PageButtons.setActiveAction(group_id);
		},
		
		/**
		 * Hide block toolbar
		 * 
		 * @protected
		 */
		hideGroupToolbar: function () {
			var group_id;
			
			if (this.hasHtmlInputs()) {
				group_id = 'EditorToolbar';
			} else {
				group_id = 'BlockToolbar';
			}
			
			Manager.PageToolbar.unsetActiveAction(group_id);
			Manager.PageButtons.unsetActiveAction(group_id);
		},
		
		/**
		 * Create block toolbar
		 * 
		 * @protected
		 */
		createGroupToolbar: function () {
			var NAME = 'BlockToolbar';
			
			if (!Manager.PageToolbar.hasActionButtons(NAME)) {
				Manager.PageToolbar.addActionButtons(NAME, []);
			}
			if (!Manager.PageButtons.hasActionButtons(NAME)) {
				Manager.PageButtons.addActionButtons(NAME, [{
					'id': 'done',
					'callback': Y.bind(function () {
						var active_content = Manager.PageContent.getContent().get('activeChild');
						if (active_content) {
							active_content.fire('block:save');
							return;
						}
					}, this)
				}]);
			}
		},
		
		
		/* ------------------------- Inputs ------------------------- */
		
		
		/**
		 * Returns true if there are inline inputs, otherwise false
		 * 
		 * @returns {Boolean} True if there are inline inputs
		 */
		hasInlineInputs: function () {
			return this.hasInlineProperties;
		},
		
		/**
		 * Returns true if there are inline HTML inputs, otherwise false
		 * 
		 * @returns {Boolean} True if there are html inputs
		 */
		hasHtmlInputs: function () {
			return this.hasHTMLProperties;
		},
		
		/**
		 * Returns all HTML inputs
		 */
		getHtmlInputs: function () {
			return Y.Array.filter(this.get('form').getAllInputs('array'), function (input) {
				return input.isInstanceOf('input-html-inline') && !input.isInstanceOf('input-string-inline');
			});
		},
		
		/**
		 * Returns all HTML inputs
		 */
		getInlineInputs: function () {
			return Y.Array.filter(this.get('form').getAllInputs('array'), function (input) {
				return input.constructor.IS_INLINE;
			});
		},
		
		
		/*
		 * ----------------------------- ATTRIBUTES -------------------------------
		 */
		
		
		/**
		 * Property data setter
		 * 
		 * @param {Object} data
		 * @protected
		 */
		_attrDataSetter: function (data) {
			var data = Supra.mix({}, data),
				values = []
			
			if (this.get('initialized')) {
				for (var name in data.properties) {
					values[name] = data.properties[name].value;
				}
				
				this.setValues(values);
			}
			
			return data;
		},
		
		/**
		 * Property data getter
		 * 
		 * @return Property data
		 * @type {Object]
		 * @protected
		 */
		_attrDataGetter: function (data) {
			var data = data || {},
				properties = {},
				values = null;
				
			values = this.getValues();
			for (var name in values) {
				properties[name] = {
					value: values[name]
				}
			}
			
			data.properties = properties;
			return data;
		},
		
		/**
		 * Show/hide global block message
		 * 
		 * @param {Boolean} show
		 */
		_uiShowGlobalBlockMessage: function (show) {
			var message = this.globalBlockMessageElement;
			
			if (!message) {
				message = Y.Node.create('<p class="description block-description">' + Supra.Intl.get(['page', 'description_block_global']) + '</p>');
				this.globalBlockMessageElement = message;
			}
			
			if (show) {
				this.get('slideshow').getSlide(SLIDESHOW_MAIN_SLIDE).one('.su-slide-content').appendChild(message);
			} else {
				message.remove();
			}
			
			return show;
		}
		
	});
	
	Manager.PageContent.PluginProperties = Properties;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['widget', 'plugin', 'supra.button', 'supra.input', 'supra.input-inline-html', 'supra.input-inline-string', 'supra.slideshow']});
