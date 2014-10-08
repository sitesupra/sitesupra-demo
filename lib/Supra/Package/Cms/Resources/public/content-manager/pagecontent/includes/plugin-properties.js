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
		this._original_values = null;
		this._shared_properties = {};
	}
	
	Properties.NAME = 'page-content-properties';
	Properties.NS = 'properties';
	Properties.ATTRS = {
		/*
		 * Property values
		 */
		'data': {
			'value': {},
			'setter': '_setData',
			'getter': '_getData'
		},
		
		/*
		 * List of editable properties
		 */
		'properties': {
			'value': {}
		},
		
		/*
		 * Property groups
		 */
		'property_groups': {
			'value': null
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
		 * Supra.Button instance for delete button
		 */
		'buttonDelete': {
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
		'normalChanged': {
			'value': false
		},
		
		/*
		 * Inline property has changed
		 */
		'inlineChanged': {
			'value': false
		},
		
		/*
		 * PageContentSettings action config
		 */
		'pageContentSettingsConfig': {
			'value': null
		},
		
		/**
		 * Toolbar group to which "top" grouped input buttons should
		 * be added to
		 */
		'toolbarGroupId': {
			'value': null,
			'getter': '_getToolbarGroupId'
		}
	};
	
	Y.extend(Properties, Y.Plugin.Base, {
		
		_node_content: null,
		
		_global_block_message_node: null,
		
		_original_values: null,
		
		/**
		 * Groups which has type "top" has separate nodes where inputs are placed
		 * @type {Object}
		 * @private
		 */
		_group_nodes: null,
		
		/**
		 * Toolbar button IDs for groups
		 * @type {Array}
		 * @private
		 */
		_group_toolbar_buttons: null,
		
		/**
		 * Inline properties were found
		 * @type {Boolean}
		 * @private
		 */
		_has_inline_properties: false,
		
		/**
		 * HTML properties were found
		 * @type {Boolean}
		 * @private
		 */
		_has_html_properties: false,
		
		/**
		 * There are groups with type "top"
		 * @type {Boolean}
		 * @private
		 */
		_has_top_groups: null,
		
		/**
		 * Form values are being updated
		 * @type {Boolean}
		 * @private
		 */
		_updating_values: false,
		
		
		
		
		destructor: function () {
			var form = this.get('form');
			
			this.get('host').unsubscribe('block:save', this.onBlockSaveCancel, this);
			this.get('host').unsubscribe('block:cancel', this.onBlockSaveCancel, this);
			
			form.destroy();
		},
		
		initializer: function (config) {
			var data = this.get('host').get('data');
			if (!data || !('type' in data)) return;
			
			var type = data.type,
				//Make sure orginal block data is not modified
				block = Supra.mix({}, Manager.Blocks.getBlock(type), true);
			
			if (!block) return;
			this.set('properties', [].concat(block.properties || []));
			this.set('property_groups', [].concat(block.property_groups || []));
			
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
			
			//On block save/cancel update 'changed' attributes
			this.get('host').on('block:save', this.onBlockSaveCancel, this);
			this.get('host').on('block:cancel', this.onBlockSaveCancel, this);
			
			//Start editing immediatelly
				//this.handleEditingStart();  // <- causes new block to loose editor toolbar after sidebar close
				//Supra.immediate(this, this.handleEditingStart); // <- causes invisible sidebar
				setTimeout(Y.bind(this.handleEditingStart, this), 50);
		},
		
		/**
		 * On editing start show toolbar and open settings form if needed
		 * 
		 * @private
		 */
		handleEditingStart: function () {
			// preferred group exists only for new blocks
			var preferred_property_group = this.getPreferredGroup();
			
			// if there are html properties then don't show on edit, because
			// editor toolbar will be shown and HTML is immediately editable
			var show_on_edit = !this._has_html_properties;
			
			if (preferred_property_group) {
				this.showPropertiesForm(preferred_property_group);
			} else if (show_on_edit) {
				if (this.hasTopGroups()) {
					this.showGroupToolbar();
				} else {
					this.showPropertiesForm();
				}
			}
			
			this.showGroupToolbarButtons();
		},
		
		/**
		 * On editing end hide toolbar and settings form
		 * 
		 * @private
		 */
		handleEditingEnd: function () {
			if (this._has_html_properties) {
				if (this.hasTopGroups()) {
					this.hideGroupToolbar();
				}
			}
			
			//Hide form when editing ends
			this.hidePropertiesForm();
			this.hideGroupToolbarButtons();
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
				
				group_nodes = {},
				group = null,
				
				host = this.get('host'),
				host_node = host.getNode();
			
			//Initialize properties
			this._group_toolbar_buttons = [];
			this._group_nodes = group_nodes;
			this._has_inline_properties = false;
			this._has_html_properties = false;
			
			var host_properties = {
				'doc': host.get('doc'),
				'win': host.get('win'),
				'toolbar': Supra.Manager.EditorToolbar.getToolbar()
			};
			
			//Slideshow is used for grouping properties with type "sidebar"
			var slideshow = this.initializeSlideshow(),
				group_node = null,
				default_group_node = null,
				is_inline = false,
				is_contained = false;
			
			//Create default group
			default_group_node = this.createGroup('default', form_config);
			
			//Find inline properties
			for(var i=0, ii=properties.length; i<ii; i++) {
				is_inline = Supra.Input.isInline(properties[i].type);
				is_contained = Supra.Input.isContained(properties[i].type);
				
				// Save index for sorting
				properties[i]._sort_order = i+1;
				
				if (is_inline) {
					//Find inside container (#content_html_111) inline element (#content_html_111_html1)
					host_properties.targetNode = host_node.one('#' + host_node.getAttribute('id') + '_' + properties[i].id);
					
					if (host_properties.targetNode) {
						if (!is_contained) {
							host_properties.srcNode = host_properties.targetNode;
							host_properties.contentBox = host_properties.targetNode;
							host_properties.boundingBox = host_properties.targetNode;
							host_properties.containerNode = null;
							
							// If it's contained then don't consider as inline
							this._has_inline_properties = true;
						} else {
							// Find a group node for contained+inline input
							group = properties[i].group || 'default';
							group_node = group_nodes[group];
							
							if (!group_node) {
								group_node = this.createGroup(group, form_config, i+1);
							}
							
							host_properties.srcNode = null;
							host_properties.contentBox = null;
							host_properties.boundingBox = null;
							host_properties.containerNode = group_node;
						}
						
						form_config.inputs.push(Supra.mix({}, host_properties, properties[i]));
						
						if (properties[i].type === 'InlineHTML') {
							this._has_html_properties = true;
						}
					} else {
						//If there is no inline node, fail silently
					}
				}
			}
			
			//Process non-inline properties
			for(var i=0, ii=properties.length; i<ii; i++) {
				is_inline = Supra.Input.isInline(properties[i].type);
				is_contained = Supra.Input.isContained(properties[i].type);
				
				if (is_contained && !is_inline) {
					//Grouping
					group = properties[i].group || 'default';
					group_node = group_nodes[group];
					
					if (!group_node) {
						//createGroup adds node to the group_nodes
						group_node = this.createGroup(group, form_config, i+1);
					}
					
					// Filter layout properties
					if (properties[i].id === 'layout' && host.isInstanceOf('page-content-list')) {
						this.filterLayoutPropertyValue(properties[i]);
					}
					
					//Set input container node to that slide
					properties[i].containerNode = group_nodes[group];
					form_config.inputs.push(properties[i]);
				} else if (properties[i].group) {
					//Create a group node if needed
					group = properties[i].group || 'default';
					
					if (!group_nodes[properties[i].group]) {
						//createGroup adds node to the group_nodes
						this.createGroup(group, form_config, i+1);
					}
				}
			}
			
			// Sort all inputs and group buttons to keep order as it was in configuration
			form_config.inputs.sort(function (a, b) {
				if (!a._sort_order || !b._sort_order || a._sort_order == b._sort_order) {
					return 0;
				}
				if (a._sort_order > b._sort_order) {
					return 1;
				} else {
					return -1;
				}
			});
			
			//On slideshow slide change update "Back" button
			slideshow.on('slideChange', this.onSlideshowSlideChange, this);
			
			//Create form
			form_config.slideshow = slideshow;
			var form = this.initializeForm(form_config);
			//form.set('slideshow', slideshow);
			
			//Bind to change event
			var inputs = form.getInputs();
			for(var id in inputs) {
				inputs[id].on('change', this.onPropertyChange, this);
				inputs[id].on('input', this.onImmediatePropertyChange, this);
				inputs[id].after('focusedChange', this.afterInputBlur, this);
			}
			
			//Delete block button
			var btn = new Supra.Button({'label': Supra.Intl.get(['page', 'delete_block']), 'style': 'small-red'});
				btn.render(default_group_node).on('click', this.deleteContent, this);
			
			this.set('buttonDelete', btn);
			
			//Don't show delete button if block is closed or this is placeholder
			//if (host.isClosed() ||  || host.isInstanceOf('page-content-list')) {
			if (host.isInstanceOf('page-content-list')) {	
				btn.hide();
			}
			
			//Show message if this is a block and it's global
			if (!host.isInstanceOf('page-content-list')) {
				if (host.getPropertyValue('locked') || host.isClosed()) {
					this.set('showGlobalBlockMessage', true);
				}
			}
		},
		
		/**
		 * Recheck inline properties
		 */
		reinitializeProperties: function () {
			var properties = this.get('properties'),
				
				node,
				
				host = this.get('host'),
				host_node = host.getNode(),
				
				had_html_properties = this._has_html_properties;
			
			//Initialize properties
			this._has_inline_properties = false;
			this._has_html_properties = false;
			
			//Find inline properties
			for(var i=0, ii=properties.length; i<ii; i++) {
				if (Supra.Input.isInline(properties[i].type)) {
					//Find inside container (#content_html_111) inline element (#content_html_111_html1)
					node = host_node.one('#' + host_node.getAttribute('id') + '_' + properties[i].id);
					
					if (node) {
						if (!Supra.Input.isContained(properties[i].type)) {
							// If it's contained then don't consider as inline
							this._has_inline_properties = true;
						}
						
						if (properties[i].type === 'InlineHTML') {
							this._has_html_properties = true;
						}
					}
				}
			}
			
			if (had_html_properties && !this._has_html_properties) {
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
			
			//Bind to "Back" button
			this.get('action').get('backButton').on('click', function () {
				if (this.get('action').form === this.get('form')) {
					this.get('slideshow').scrollBack();
				}
			}, this);
			
			return slideshow;
		},
		
		/**
		 * Create form
		 */
		initializeForm: function (form_config) {
			var form = new Supra.Form(form_config),
				data = this.get('data').properties,
				slideshow = this.get('slideshow');
			
			form.render(this.get('action').get('contentInnerNode'));
			form.get('boundingBox').addClass('yui3-form-properties');
			form.hide();
			
			slideshow.render(form.get('contentBox'));
			
			this._updating_values = true;
			
			form.setValues(data, 'id');
			this._updating_values = false;
			
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
						'action': tooltip.button.javascriptAction || ''
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
		
		/**
		 * Destroy property and recreate it
		 *
		 * @param {String} id Property ID
		 * @param {Object} value New value
		 */
		resetProperty: function (id, value) {
			var form = this.get('form'),
				properties = this.get('properties'),
				i = 0,
				ii = properties.length,
				inputs = form.getInputs(),
				inputs_definition = form.inputs_definition,
				
				host = this.get('host'),
				host_node = host.getNode(),
				
				property = null,
				config = {},
				
				is_inline = true,
				is_contained = false;
			
			for(; i<ii; i++) {
				if (properties[i].id == id) {
					property = properties[i];
					break;
				}
			}
			
			if (property) {
				var srcNode = null;
				is_contained = Supra.Input.isContained(property.type);
				
				//Destroy old input if only inline
				if (!is_contained && id in inputs) {
					inputs[id].destroy();
				}
				
				//Check if input node exists
				srcNode = host_node.one('#' + host_node.getAttribute('id') + '_' + property.id);
				if (!srcNode) {
					//Save into plain values
					form.get('plainValues')[id] = value;
					
					delete(inputs[id]);
					return null;
				}
				
				if (!is_contained) {
					//Get input config
					config = Supra.mix({
						'doc': host.get('doc'),
						'win': host.get('win'),
						'toolbar': Supra.Manager.EditorToolbar.getToolbar(),
						'srcNode': srcNode,
						'contentBox': srcNode,
						'boundingBox': srcNode,
						'targetNode': srcNode,
						'parent': form,
						'root': this.get('host')
					}, property, {
						'value': value ? value : property.value
					});
					
					//Create new input 
					inputs[id] = form.factoryField(config);
					inputs[id].render();
					
					//Set config, because inputs without definitions will break form
					inputs_definition[id] = config;
				} else {
					// Contained input
					if (id in inputs) {
						inputs[id].set('targetNode', srcNode);
					}
				}
				
				//Restore value
				inputs[id].set('value', value);
				
				return inputs[id];
			}
			
			return null;
		},
		
		/**
		 * Input blur
		 * 
		 * @param {Object} evt
		 */
		afterInputBlur: function (evt) {
			if (evt.newVal != evt.prevVal) {
				//Unset active property
				var input = evt.target,
					id = input.get('id'),
					host = this.get('host');
				
				if (host.get('active_inline_property') == id) {
					host.set('active_inline_property', null);
				}
			}
		},
		
		/**
		 * On property change update state
		 * 
		 * @param {Object} evt
		 */
		onPropertyChange: function (evt) {
			
			// If settings initial values, then we should trigger events
			if (this._updating_values) return;
			
			var normalChanged = this.get('normalChanged'),
				inlineChanged = this.get('inlineChanged');
			
			//Trigger event
			var input = evt.target,
				id = input.get('id'),
				properties = this.get('properties');
			
			Y.later(60, this, this.onPropertyChangeTriggerContentChange, [input, null, false]);
			
			//If Global block property changed, then show/hide global block message
			if (id == '__locked__') {
				var host = this.get('host');
				// Message should be visible only for blocks
				if (!host.isInstanceOf('page-content-list')) {
					this.set('showGlobalBlockMessage', evt.newVal || evt.value || host.isClosed());
				}
			}
			
			//Update attributes
			if (normalChanged && inlineChanged) return;
			
			for(var i=0,ii=properties.length; i<ii; i++) {
				if (properties[i].id == id) {
					if (Supra.Input.isInline(properties[i].type)) {
						if (!inlineChanged) {
							this.set('inlineChanged', true);
						}
					} else {
						if (!normalChanged) {
							this.set('normalChanged', true);
						}
					}
					break;
				}
			}
		},
		
		/**
		 * On immediate propert change (change still in progress) trigger event
		 * 
		 * @param {Object} evt
		 */
		onImmediatePropertyChange: function (evt) {
			// If settings initial values, then we should trigger events
			if (this._updating_values) return;
			
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
		 * @private
		 */
		onPropertyChangeTriggerContentChange: function (input, value, dirty) {
			//We don't want to trigger for inline inputs for performance reasons
			//because inline input value changes very offten
			if (input.get('inline')) return;
			
			var host = this.get('host'),
				id = input.get('id'),
				value = (value === null || value === undefined ? input.get('value') : value),
				values = input.get('values') || null, // all values
				result = null;
			
			result = host.fireContentEvent('update', host.getNode().getDOMNode(), {'propertyName': id, 'propertyValue': value, 'propertyValueList': values, 'supra': Supra});
			
			// On list layout change automatically reload content
			if (id === 'layout' && host.isInstanceOf('page-content-list')) {
				result = false;
			}
			
			if (!dirty && result === false) {
				//Some property was recognized, but preview can't be updated without refresh
				
				input.set('loading', true);
				host.reloadContentHTML().done(function () {
					this.reinitializeProperties();
					input.set('loading', false);
				}, this);
			}
		},
		
		/**
		 * Save changes
		 */
		savePropertyChanges: function () {
			this._original_values = this.getValues();
			
			// Property which affects inline content may have
			// changed, need to reload block content.
			if (!this.get('host').saving) {
				var button = this.get('action').get('controlButton');
				if (button) button.set('loading', true);
				
				this.get('host').reloadContentHTML().done(function () {
					this.reinitializeProperties();
					this.savePropertyChangesAfter();
				}, this);
			} else {
				this.savePropertyChangesAfter();
			}
			
		},
		
		savePropertyChangesAfter: function () {
			var button = this.get('action').get('controlButton');
			if (button) button.set('loading', false);
				
			this.get('host').fire('properties:save');
			
			this.set('normalChanged', false);
			this.get('action').hide();
			
			//Reset slideshow position
			this.get('slideshow').set('slide', SLIDESHOW_MAIN_SLIDE);
		},
		
		/**
		 * On block save/cancel
		 */
		onBlockSaveCancel: function () {
			this.set('normalChanged', false);
			this.set('inlineChanged', false);
		},
		
		/**
		 * Delete content
		 */
		deleteContent: function () {
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
		showPropertiesForm: function (group_id) {
			//Show form
			this.get('action').execute(this.get('form'), {
				'doneCallback': Y.bind(this.savePropertyChanges, this),
				'hideEditorToolbar': true,
				'properties': this,
				
				'scrollable': false,
				'title': this.getTitle()
			});
			
			//Show only specific group
			this.showGroup(group_id || 'default');			
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
			
			for(var i=0,ii=properties.length; i<ii; i++) {
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
					locked_input = form.getInput('__locked__'),
					parent = this.get('host').get('parent'),
					advanced_button = form.getInput('advanced_button'),
					advanced_inputs = this.getPropertiesInGroup('advanced');
				
				if (page_data.type != 'page' && (!parent || !parent.get('data').closed)) {
					//Template blocks have "Global block" input
					//but if placeholder is closed (not editable), then don't show it
					locked_input.set('disabled', false).set('visible', true);
					advanced_button.set('visible', true);
				} else {
					//Pages don't have "locked" input
					locked_input.set('disabled', true).set('visible', false);
					
					//If '__locked__'  is only input in the form then hide button
					if (!advanced_inputs.length || (advanced_inputs.length == 1 && advanced_inputs[0].id == '__locked__')) {
						advanced_button.set('visible', false);
					} else {
						advanced_button.set('visible', true);
					}
				}
				
				this._updating_values = true;
				form.setValuesObject(values, 'id');
				this._updating_values = false;

				var input = null,
					template = Supra.Intl.get(['form', 'shared_property_description']),
					list = this._shared_properties,
					inputs = form.inputs,
					info;

				template = Supra.Template.compile(template);

				for (var name in list) {
					
					if (inputs[name]) {
						input = inputs[name];

						info = this.getSharedPropertyInfo(name);

						input.set('disabled', true);
						input.set('description', template(info));
					}
				}
			}
		},
		
		setNonInlineValues: function (values) {
			var values = this.getNonInlineValues(values);
			this.setValues(values);
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
				var values = form.getValues('id', true);
		
				for (var name in values) {
					//FIXME Hardcoded property name
					if (this.isPropertyShared(name) && name !== 'images') delete values[name];
				}

				return values;
			} else {
				return {};
			}
		},
		
		/**
		 * Returns all non-inline property values for saving
		 * 
		 * @return Values
		 * @type {Object}
		 */
		getNonInlineSaveValues: function (values) {
			var values = values ? values : this.getSaveValues(),
				properties = this.get('properties'),
				out = {},
				value = null,
				is_contained;
				
			for(var i=0,ii=properties.length; i<ii; i++) {
				is_contained = Supra.Input.isContained(properties[i].type);
				if (is_contained) {

					value = values[properties[i].id];
					
					// replace empty arrays with nulls
					if (value !== null && typeof(value) == 'object' && value.length === 0) {
						value = null;
					}

					out[properties[i].id] = value;		
				}
			}
					
			return out;
		},
		
		isPropertyShared: function (name) {
			if (name in this._shared_properties) {
				return true;
			}
			return false;
		},

		getSharedPropertyInfo: function (name) {
			var list = this._shared_properties;

			if ( ! (name in list)) {
				return {};
			}

			var localeTitle = list[name].locale,
				locale = Supra.data.getLocale(list[name].locale);

			if (locale && locale.title) {
				localeTitle = locale.title;
			}

			var info = Supra.mix({'localeTitle': localeTitle}, list[name]);

			return info;
		},
		
		/**
		 * Filter layout property values to have only those which are in 'allow_layouts' list
		 * 
		 * @param {Object} property Layout property info
		 * @private
		 */
		filterLayoutPropertyValue: function (property) {
			var data = this.get('host').get('data'),
				
				values = property.values,
				k = 0,
				kk = values ? values.length : 0,
				
				layouts = data.allow_layouts,
				i = 0,
				ii = layouts ? layouts.length : 0,
				
				new_values = [];
			
			if (ii && kk) {
				for (; i<ii; i++) {
					for (k=0; k<kk; k++) {
						if (layouts[i] == values[k].id) {
							new_values.push(values[k]);
						}
					}
				}
				
				property.values = new_values;
			}
		},
		
		
		/**
		 * ------------------------------ GROUPS --------------------------------
		 */
		
		
		/**
		 * Returns group definition
		 * 
		 * @param {String} group_id Group ID
		 * @return Group definition or null
		 * @type {Object}
		 */
		getGroupDefinition: function (group_id) {
			var groups = this.get('property_groups'),
				i = 0,
				ii = groups.length;
			
			for (; i<ii; i++) {
				if (groups[i].id === group_id) return groups[i];
			}
			
			return null;
		},
		
		/**
		 * Returns all properties in the group
		 * 
		 * @param {String} group_id Group ID
		 * @returns {Array} Properties in the group
		 */
		getPropertiesInGroup: function (group_id) {
			var properties = this.get('properties'),
				i = 0,
				ii = properties.length,
				in_group = [];
			
			for (; i<ii; i++) {
				if (properties[i].group && properties[i].group == group_id) {
					in_group.push(properties[i]);
				}
			}
			
			return in_group;
		},
		
		/**
		 * Returns true if there are top groups, otherwise false
		 * 
		 * @return True if there are top groups
		 * @type {Boolean}
		 */
		hasTopGroups: function () {
			if (this._has_top_groups === true || this._has_top_groups === false) return this._has_top_groups;
			
			var groups = this.get('property_groups'),
				i = 0,
				ii = groups.length;
			
			for (; i<ii; i++) {
				if (groups[i].type === 'top' && groups[i].id !== 'default') {
					this._has_top_groups = true;
					return true;
				}
			}
			
			this._has_top_groups = false;
			return false;
		},
		
		/**
		 * Returns preferred group if block is new
		 * otherwise null
		 * 
		 * @returns {String} Preferred property group if block is new
		 */
		getPreferredGroup: function () {
			if (this.get('host').get('new')) {
				return this.get('host').getBlockInfo().preferred_property_group;
			} else {
				return null;
			}
		},
		
		/**
		 * Returns group content node
		 * 
		 * @param {String} group_id Group ID
		 * @return Group content node or null
		 * @type {Object}
		 */
		getGroupContentNode: function (group_id) {
			return this._group_nodes[group_id || 'default'] || null;
		},
		
		/**
		 * Create group
		 * 
		 * @param {Object} definition Group definition
		 * @param {Object} form_config Form configuration to which add button to
		 * @param {Number} sort_order Sort order comparing to other inputs
		 */
		createGroup: function (definition, form_config, sort_order) {
			//Backward compatibility
			if (typeof definition === 'string') {
				var groups = this.get('property_groups'),
					group = this.getGroupDefinition(definition);
				
				if (!group) {
					if (definition === 'default') {
						definition = {
							'id': definition,
							'type': 'top',
							'icon': null, //'/cms/lib/supra/img/htmleditor/icon-settings.png',
							'label': Supra.Intl.get(['htmleditor', 'settings'])
						};
					} else {
						definition = {
							'id': definition,
							'type': 'sidebar',
							'icon': null,
							'label': definition
						};
					}
					
					groups.push(definition);
				} else {
					definition = group;
				}
			}
			
			if (!this._group_nodes[definition.id]) {
				var slideshow = this.get('slideshow'),
					slide_main = slideshow.getSlide(SLIDESHOW_MAIN_SLIDE).one('.su-slide-content'),
					
					slide_id = null,
					slide = null,
					
					node = null;
				
				if (definition.type === 'top') {
					//Create as node in main slide
					node = Y.Node.create('<div class="' + (definition.id !== 'default' ? 'hidden' : '') + '"></div>').appendTo(slide_main);
					
					//For default group create button only if there are other groups and
					//HTMLEditor toolbar is not visible (it already has a button)
					if (definition.id !== 'default' || (this.hasTopGroups() && !this.hasHtmlInputs())) {
						//Create toolbar button
						var button_id = this.get('host').getId() + '_' + definition.id.replace(/[^a-z0-9\-\_]/ig, '');
						
						this.createGroupButton(button_id, definition);
					}
					
					this._group_toolbar_buttons.push(button_id);
					this._group_nodes[definition.id] = node;
					return node;
				} else {
					//Create as slide
					slide_id = definition.id.replace(/[^a-z0-9\-\_]/ig, '');
					
					slide = slideshow.addSlide({
						'id': slide_id
					});
					
					if (form_config && definition.id !== 'default') {
						form_config.inputs.push({
							'id': slide_id + '_button',
							'label': definition.label,
							'type': 'Button',
							'slideshow': slideshow,
							'slideId': slide_id,
							'containerNode': this.getGroupContentNode('default'),
							'icon': definition.icon,
							'_sort_order': sort_order
						});
					}
					
					slide = slide.one('.su-slide-content');
					
					this._group_nodes[definition.id] = slide;
					return slide;
				}
				
			}
		},
		
		/**
		 * Create group button in HTMLEditor toolbar or PageToolbar
		 */
		createGroupButton: function (button_id, definition) {
			var command = null,
				toolbar = null,
				toolbar_group_id = this.get('toolbarGroupId');
			
			if (toolbar_group_id == 'EditorToolbar') {
				command = definition.id + '_' + String((+new Date()) + Math.random());
				
				toolbar = Manager.getAction('EditorToolbar').getToolbar();
				toolbar.addButton("main", {
					"id": button_id,
					"type": "button",
					"buttonType": "button",
					"icon": definition.icon || '/public/cms/supra/img/toolbar/icon-blank.png',
					"title": definition.label,
					"command": command
				});
				
				toolbar.on('command', function (event) {
					if (event.command === command) {
						this.toolbarButtonClickOpenGroup(button_id, {'propertyGroup': definition.id});
					}
				}, this);
				
			} else {
				toolbar = Manager.getAction('PageToolbar');
				toolbar.addActionButtons(toolbar_group_id, [{
					'id': button_id,
					'type': 'button',
					'title': definition.label,
					'icon': definition.icon || '/public/cms/supra/img/toolbar/icon-blank.png',
					'action': this,
					'actionFunction': 'toolbarButtonClickOpenGroup',
					'propertyGroup': definition.id // For use in toolbarButtonClickOpenGroup
				}]);
				
			}
		},
		
		/**
		 * On toolbar button click open property form and show inputs for
		 * that group only
		 */
		toolbarButtonClickOpenGroup: function (button_id, button_config) {
			//Since toolbar is created by single instance of properties
			//keyword "this" will have reference to the one which created,
			//not the one whic is currently active
			
			var self = Manager.PageContent.getContent().get('activeChild');
			self.properties.showPropertiesForm(button_config.propertyGroup);
		},
		
		/**
		 * Show specific group
		 * 
		 * @param {String} group_id Group ID, default is "default"
		 */
		showGroup: function (group_id) {
			//Default value
			if (!group_id || !(group_id in this._group_nodes)) group_id = 'default';
			
			for (var key in this._group_nodes) {
				if (!this._group_nodes[key].hasClass('su-slide-content')) {
					//Toggle only "top" toolbar button groups
					this._group_nodes[key].toggleClass('hidden', key !== group_id);
				}
			}
		},
		
		/**
		 * Show toolbar buttons associated with this property form
		 * 
		 * @private
		 */
		showGroupToolbarButtons: function () {
			var toolbar = null,
				buttons = this._group_toolbar_buttons,
				button = null,
				i = 0,
				ii = buttons.length;
			
			if (this.get('toolbarGroupId') == 'EditorToolbar') {
				toolbar = Manager.getAction('EditorToolbar').getToolbar();
				
				for (; i<ii; i++) {
					button = toolbar.getButton(buttons[i]);
					if (button) button.show();
				}
			} else {
				toolbar = Manager.getAction('PageToolbar');
				
				for (; i<ii; i++) {
					button = toolbar.getActionButton(buttons[i]);
					if (button) button.show();
				}
			}
		},
		
		/**
		 * Hide toolbar buttons associated with this property form
		 * 
		 * @private
		 */
		hideGroupToolbarButtons: function () {
			var toolbar = null,
				buttons = this._group_toolbar_buttons,
				button = null,
				i = 0,
				ii = buttons.length;
			
			if (this.get('toolbarGroupId') == 'EditorToolbar') {
				toolbar = Manager.getAction('EditorToolbar').getToolbar();
				
				for (; i<ii; i++) {
					button = toolbar.getButton(buttons[i]);
					if (button) button.hide();
				}
			} else {
				toolbar = Manager.getAction('PageToolbar');
				
				for (; i<ii; i++) {
					button = toolbar.getActionButton(buttons[i]);
					if (button) button.hide();
				}
			}
		},
		
		/**
		 * Show block toolbar
		 * 
		 * @private
		 */
		showGroupToolbar: function () {
			var group_id = this.get('toolbarGroupId');
			Manager.PageToolbar.setActiveAction(group_id);
			Manager.PageButtons.setActiveAction(group_id);
		},
		
		/**
		 * Hide block toolbar
		 * 
		 * @private
		 */
		hideGroupToolbar: function () {
			var group_id = this.get('toolbarGroupId');
			Manager.PageToolbar.unsetActiveAction(group_id);
			Manager.PageButtons.unsetActiveAction(group_id);
		},
		
		/**
		 * Create block toolbar
		 * 
		 * @private
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
		
		/**
		 * Returns true if there are inline inputs, otherwise false
		 * 
		 * @returns {Boolean} True if there are inline inputs
		 */
		hasInlineInputs: function () {
			return this._has_inline_properties;
		},
		
		/**
		 * Returns true if there are inline HTML inputs, otherwise false
		 * 
		 * @returns {Boolean} True if there are html inputs
		 */
		hasHtmlInputs: function () {
			return this._has_html_properties;
		},
		
		
		/*
		 * ----------------------------- ATTRIBUTES -------------------------------
		 */
		
		
		/**
		 * Property data setter
		 * 
		 * @param {Object} data
		 * @private
		 */
		_setData: function (data) {
			var data = Supra.mix({}, data),
				values = [],
				shared_properties = {};
				
			for (var name in data.properties) {
				if (data.properties[name].__shared__) {
					shared_properties[name] = data.properties[name];
				}
				
				values[name] = data.properties[name].value;
			}
			
			this._shared_properties = Supra.mix(shared_properties, this._shared_properties);
			this._original_values = values;
			
			this.setValues(values);
			
			return data;
		},
		
		/**
		 * Property data getter
		 * 
		 * @return Property data
		 * @type {Object]
		 * @private
		 */
		_getData: function (data) {
			var data = data || {},
				properties = {},
				values = null;
				
			values = this.getValues();
			for (var name in values) {
				properties[name] = {
					value: values[name],
					shared: this.isPropertyShared(name)
				}
			}
			
			data.properties = properties;
			return data;
		},
		
		/**
		 * Toolbar group ID attribute getter
		 * 
		 * @return 
		 */
		_getToolbarGroupId: function (value) {
			if (!value) {
				if (this.hasHtmlInputs()) {
					value = 'EditorToolbar';
				} else {
					value = 'BlockToolbar';
				}
			}
			
			return value;
		},
		
		/**
		 * Show/hide global block message
		 * 
		 * @param {Boolean} show
		 */
		_uiShowGlobalBlockMessage: function (show) {
			var message = this._global_block_message_node;
			
			if (!message) {
				message = Y.Node.create('<p class="description block-description">' + Supra.Intl.get(['page', 'description_block_global']) + '</p>');
				this._global_block_message_node = message;
			}
			
			if (show) {
				this.getGroupContentNode().appendChild(message);
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