//Invoke strict mode
"use strict";

YUI.add('supra.page-content-editable', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager,
		Page = Manager.Page,
		PageContent = Manager.PageContent;
	
	//CSS classes
	var CLASSNAME_INLINE_EDITABLE = Y.ClassNameManager.getClassName('content', 'inline', 'editable');	//yui3-content-inline-editable
	
	/**
	 * Content block which has editable properties
	 */
	function ContentEditable () {
		ContentEditable.superclass.constructor.apply(this, arguments);
	}
	
	ContentEditable.NAME = 'page-content-editable';
	ContentEditable.CLASS_NAME = Y.ClassNameManager.getClassName(ContentEditable.NAME);
	ContentEditable.ATTRS = {
		'editable': {
			value: true,
			writeOnce: true
		},
		'dragable': {
			value: true
		},
		'active_inline_property': {
			value: null,
			setter: 'setActiveInlineProperty'
		}
	};
	
	Y.extend(ContentEditable, PageContent.Proto, {
		
		/**
		 * Inline inputs
		 * @type {Object}
		 * @private
		 */
		inline_inputs: null,
		
		/**
		 * Inline input count
		 * @type {Number}
		 * @private
		 */
		inline_inputs_count: 0,
		
		/**
		 * Active inline input
		 */
		active_inline_input: null,
		
		/**
		 * Inline HTML inputs
		 * @type {Object}
		 * @private
		 */
		html_inputs: null,
		
		/**
		 * Inline HTML input count
		 * @type {Number}
		 * @private
		 */
		html_inputs_count: 0,
		
		/**
		 * There are changes which are not saved
		 * @type {Boolean}
		 * @private
		 */
		unresolved_changes: false,
		
		/**
		 * Returns all property inputs
		 * 
		 * @return List of property inputs
		 * @type {Object}
		 */
		getPropertyInputs: function () {
			if ('properties' in this) {
				return this.properties.get('form').getInputs();
			} else {
				return null;
			}
		},
		
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			ContentEditable.superclass.bindUI.apply(this, arguments);
			
			if (this.get('editable')) {
				this.bindUISettings();
			}
		},
		
		/**
		 * Save changed properties
		 * 
		 * @private
		 */
		savePropertyChanges: function () {
			if (this.properties && this.unresolved_changes) {
				//For blocks use sendBlockProperties, for place holders sendPlaceHolderProperties
				if (this.isInstanceOf('page-content-list')) {
					var fn = 'sendPlaceHolderProperties';
				} else {
					var fn = 'sendBlockProperties';
				}
				
				this.get('super')[fn](this, function (status, response_data) {
					
					var data = this.get('data');
					data.properties = this.properties.getValues();
					this.set('data', data);
					
				}, this);
			}
			
			this.unresolved_changes = false;
		},
		
		/**
		 * Cancel property changes and revert back
		 */
		cancelPropertyChanges: function () {
			var data = this.get('data');
			this.properties.setValues(data.properties);
			this.unresolved_changes = false;
			
			this.syncOverlayPosition();
		},
		
		/**
		 * Bind Settings (Properties) form
		 * 
		 * @private
		 */
		bindUISettings: function () {
			//When starting editing for first time create form
			this.once('editing-start', this.renderUISettings, this);
			this.on('editing-start', this.onEditingStart, this);
			this.on('editing-end', this.onEditingEnd, this);
		},
		
		onEditingStart: function () {
			//If there are InlineHTML contents, show toolbar when editing
			if (this.inline_inputs_count) {
				for(var id in this.inline_inputs) {
					//Enable only first editor
					this.set('active_inline_property', id);
					break;
				}
				if (id in this.html_inputs) {
					Manager.EditorToolbar.execute();
				}
			}
			this.unresolved_changes = true;
			this.properties.set('data', this.get('data'));
		},
		
		onEditingEnd: function () {
			if (this.inline_inputs_count) {
				this.set('active_inline_property', null);
			}
			
			if (this.html_inputs_count) {
				if (this.unresolved_changes) {
					this.fire('block:save');
				}
				
				//Hide editor toolbar
				Manager.EditorToolbar.hide();
				
				//Unset settings button 'down' state
				var toolbar = Manager.EditorToolbar.getToolbar();
				toolbar.getButton('settings').set('down', false);
			}
		},
		
		renderUISettings: function () {
			//Find if there are any HTML properties
			var properties = this.getProperties(),
				has_html_properties = false,
				page_data = Page.getPageData(),
				data = this.get('data');
			
			for(var i=0,ii=properties.length; i<ii; i++) {
				if (properties[i].inline && properties[i].type == 'InlineHTML') {
					has_html_properties = true;
					break;
				}
			}
			
			//If editing template, then set "__locked__" property value
			if (page_data.type != 'page') {
				data.properties = data.properties || {};
				data.properties.__locked__ = data.locked;
			}
			
			//Add properties plugin (creates form)
			this.plug(PageContent.PluginProperties, {
				'data': data,
				//If there are inline HTML properties, then settings form is opened using toolbar buttons
				'showOnEdit': has_html_properties ? false: true
			});
			
			//Find all inline and HTML properties
			this.findInlineInputs();
			
			//When properties form is hidden, unset "Settings" button down state
			if (has_html_properties) {
				
				this.properties.get('form').on('visibleChange', function (evt) {
					if (evt.newVal != evt.prevVal && !evt.newVal) {
						var toolbar = Manager.EditorToolbar.getToolbar();
						toolbar.getButton('settings').set('down', false);
					}
				}, this);
				
			} else {
				
				//If there are no inline html properties, then 
				//on properties form save / cancel trigger block save / cancel 
				this.on('properties:save', function () {
					this.fire('block:save');
				});
				this.on('properties:cancel', function () {
					this.fire('block:cancel');
				});
				
			}
			
			//Handle block save / cancel
			this.on('block:save', this.savePropertyChanges, this);
			this.on('block:cancel', this.cancelPropertyChanges, this);
		},
		
		/**
		 * Render UI
		 */
		renderUI: function () {
			ContentEditable.superclass.renderUI.apply(this, arguments);
			
			if (this.get('editable')) {
				this.renderOverlay();
				
				//Find if there are any inline properties
				var properties = this.getProperties();
				
				if (properties) {
					for(var i=0,ii=properties.length; i<ii; i++) {
						if (properties[i].inline) {
							//Add class to allow detect if content has inline properties
							this.getNode().addClass(CLASSNAME_INLINE_EDITABLE);
							break;
						}
					}
				}
			}
		},
		
		
		/**
		 * On HTMLEditor settings command show/hide settings form
		 * 
		 * @private
		 */
		onSettingsCommand: function () {
			var toolbar = Manager.EditorToolbar.getToolbar();
			if (toolbar.getButton('settings').get('down')) {
				this.properties.showPropertiesForm();
			} else {
				this.properties.hidePropertiesForm();
			}
		},
		
		/**
		 * Returns all inline inputs
		 * 
		 * @return List of InlineHTML inputs
		 * @type {Object}
		 * @private
		 */
		findInlineInputs: function () {
			//Find if there are any inline properties and HTML properties
			var inputs = this.getPropertyInputs(),
				properties = this.getProperties(),
				id = null,
				node = this.getNode(),
				inline_node = null;
			
			this.inline_inputs = {};
			this.html_inputs = {};
			
			for(var i=0,ii=properties.length; i<ii; i++) {
				id = properties[i].id;
				
				if (properties[i].inline && id in inputs) {
					this.inline_inputs[id] = inputs[id];
					this.inline_inputs_count++;
					
					if (inputs[id] instanceof Supra.Input.InlineHTML) {
						this.html_inputs[id] = inputs[id];
						this.html_inputs_count++;
						
						//Bind command to editor instead of toolbar, because toolbar is shared between editors
						inputs[id].getEditor().addCommand('settings', Y.bind(this.onSettingsCommand, this));
					}
					
					//If there is no inline node, fail silently
					inline_node = node.one('#' + this.getNodeId() + '_' + properties[i].id);
					if (!inline_node) {
						//Y.error('Block "' + this.getId() + '" (' + this.getBlockType() + ') is missing HTML node for property "' + id + '" (' + properties[i].type + ')');
						continue;
					}
					
					//When clicking on node enable corresponding editor
					inline_node.on('mousedown', function (event, id) {
						if (this.get('editing')) {
							this.set('active_inline_property', id);
						}
					}, this, id);
				}
			}
		},
		
		/**
		 * Active inline property setter
		 * 
		 * @param {String} property_id Property ID
		 * @return Property ID
		 * @type {String}
		 * @private
		 */
		setActiveInlineProperty: function (property_id) {
			var old_property_id = this.get('active_inline_property');
			
			//Can't set active inline property if editing is disabled
			var editing_disabled = this.get('super').get('disabled');
			
			if (!editing_disabled && property_id != old_property_id) {
				if (this.inline_inputs) {
					//Disable old inline input
					if (old_property_id && old_property_id in this.inline_inputs) {
						this.inline_inputs[old_property_id].set('disabled', true);
					}
					
					//Enable active inline input
					if (property_id && property_id in this.inline_inputs) {
						this.inline_inputs[property_id].set('disabled', false);
					}
					
					return property_id;
				} else {
					return null;
				}
			}
			
			return old_property_id;
		},
		
		/**
		 * Reload content HTML
		 * Load html from server
		 */
		reloadContentHTML: function () {
			var uri = PageContent.getDataPath('contenthtml'),
				page_data = Page.getPageData(),
				data = null;
			
			data = {
				'page_id': page_data.id,
				'block_id': this.getId(),
				'locale': Supra.data.get('locale'),
				'properties': this.properties.getNonInlineSaveValues()
			};
			
			//If editing template, then send also "locked" property
			if (page_data.type != 'page') {
				data.locked = data.properties.__locked__;
				delete(data.properties.__locked__);
				
				this.properties.get('data').locked = data.locked;
			} else if ('__locked__' in data.properties) {
				delete(data.properties.__locked__);
			}
			
			Supra.io(uri, {
				'method': 'post',
				'data': data,
				'context': this,
				'on': {
					'success': this._reloadContentSetHTML
				}
			})
		},
		
		/**
		 * Update content HTML
		 * Since inline inputs has references need to destroy and
		 * recreate all inline inputs preserving current values
		 * 
		 * @param {Object} data Request response data
		 * @private
		 */
		_reloadContentSetHTML: function (data) {
			if (data && data.internal_html) {
				//Get values
				var inline_inputs = this.inline_inputs,
					values = {},
					active_inline_property = this.get('active_inline_property');
				
				for(var i in inline_inputs) {
					values[i] = inline_inputs[i].get('value');
				}
				
				//Unset active inline property
				if (active_inline_property) {
					this.set('active_inline_property', null);
				}
				
				//Replace HTML
				this.getNode().set('innerHTML', data.internal_html);
				
				//Recreate inline inputs
				var properties_handler = this.properties,
					input = null;
				
				for(var i in inline_inputs) {
					input = properties_handler.resetProperty(i, values[i]);
				}
				
				//Update inline input list
				this.findInlineInputs();
				
				//Restore current active inline property
				if (active_inline_property) {
					this.set('active_inline_property', active_inline_property);
				}
				
				//Update overlay position
				//Use timeout to make sure everything is styled before doing sync
				setTimeout(Y.bind(this.syncOverlayPosition, this), 1);
			}
		},
		
		/**
		 * Changed getter
		 */
		_getChanged: function () {
			if (this.get('editable') && this.properties) {
				return this.properties.get('normalChanged') || this.properties.get('inlineChanged');
			}
			return false;
		},
		
		/**
		 * Destructor
		 * 
		 * @private
		 */
		beforeDestroy: function () {
			delete(this.inline_inputs);
			delete(this.active_inline_input);
			delete(this.html_inputs);
			
			ContentEditable.superclass.beforeDestroy.apply(this, arguments);
		},
	});
	
	PageContent.Editable = ContentEditable;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-proto', 'supra.htmleditor', 'supra.page-content-properties', 'supra.page-content-droptarget']});