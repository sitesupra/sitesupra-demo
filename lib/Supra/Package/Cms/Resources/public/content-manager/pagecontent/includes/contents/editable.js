YUI.add('supra.page-content-editable', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
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
		'draggable': {
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
		 * Inline HTML inputs
		 * @type {Object}
		 * @private
		 */
		html_inputs: null,
		
		/**
		 * Inline HTML and Inline String input count
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
			var deferred = Supra.Deferred();
			
			if (this.properties && this.unresolved_changes) {
				this.saving = true;
				
				//For blocks use sendBlockProperties, for place holders sendPlaceHolderProperties
				//and for place holders in page use sendPagePlaceHolderProperties
				if (this.isInstanceOf('page-content-list')) {
					if (Manager.Page.getPageData().type == 'page') {
						var fn = 'sendPagePlaceHolderProperties';
					} else {
						var fn = 'sendPlaceHolderProperties';
					}
				} else {
					var fn = 'sendBlockProperties';
				}
				
				this.get('super')[fn](this, function (response_data, status) {
					
					//Enable editing
					this.set('loading', false);
					
					if (status) {
						var data = this.get('data');
						data.properties = this.properties.get('data').properties;
						this.set('data', data);
						this._reloadContentSetHTML(response_data);
						this.saving = false;
						
						deferred.resolve();
					} else {
						deferred.reject();
					}
					
				}, this);
				
				//Disable editing until 
				this.set('loading', true);
			} else {
				deferred.resolve();
			}
			
			this.unresolved_changes = false;
			return deferred.promise();
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
			//Update inline input list
			this.findInlineInputs();

			//If there are InlineHTML contents, show toolbar when editing
			var preferred_property_group = this.properties.getPreferredGroup();
			
			if (this.inline_inputs_count) {
				if (this.html_inputs_count && !preferred_property_group) {
					Manager.EditorToolbar.execute();
				}
				
				var first_id = false,
					first_html_id = false,
					inline_inputs = this.inline_inputs;
				
				for(var id in inline_inputs) {
					// Will enable only first editor
					// but preffer InlineHTML over other inline inputs
					
					if (!first_html_id && inline_inputs[id] instanceof Supra.Input.InlineHTML) {
						first_html_id = id;
						
						if (!first_id) {
							first_id = id;
						}
					} else if (!first_id) {
						first_id = id;
					} else {
						//Disable all editors, except first
						inline_inputs[id].set('disabled', true);
					}
				}
				
				if (first_html_id || first_id) {
					if (first_html_id && first_id && first_html_id != first_id) {
						inline_inputs[first_id].set('disabled', true);
					}
					
					//Set first editor as active
					this.set('active_inline_property', first_html_id || first_id);
				}
			}
			
			this.unresolved_changes = true;
			
			this.properties.set('data', this.get('data'));
			
			//Change header title
			Manager.getAction('PageHeader').setTitle("block", this.getBlockTitle());
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
			
			//Revert header title change
			Manager.getAction('PageHeader').unsetTitle("block", this.getBlockTitle());
		},
		
		renderUISettings: function () {
			//Find if there are any HTML properties
			var properties = this.getProperties(),
				has_html_properties = false,
				page_data = Page.getPageData(),
				data = this.get('data');
			
			for(var i=0,ii=properties.length; i<ii; i++) {
				if (properties[i].type == 'InlineHTML') {
					has_html_properties = true;
					break;
				}
			}
			
			//If editing template, then set "__locked__" property value
			if (page_data.type != 'page') {
				data.properties = data.properties || {};
				data.properties.__locked__ = {
					shared: false,
					value: data.locked
				}
			}
			
			//Add properties plugin (creates form)
			this.plug(PageContent.PluginProperties, {
				'data': data
			});
			
			//Find all inline and HTML properties
			this.findInlineInputs();
			
			//When properties form is hidden, unset "Settings" button down state
			this.properties.get('form').on('visibleChange', this.onFormVisibleChange, this);
			
			//If there are no inline html properties, then 
			//on properties form save / cancel trigger block save / cancel 
			this.on('properties:save', function () {
				if (!this.html_inputs_count && !this.properties.hasTopGroups()) {
					this.fire('block:save');
					
					// If previously there were html inputs and after some property
					// change it was removed, then make sure editor toolbar is closed
					var actionButtons = Manager.PageButtons,
						actionToolbar = Manager.PageToolbar;
					
					if (actionToolbar.inHistory('EditorToolbar')) {
						actionToolbar.unsetActiveAction('EditorToolbar');
						actionButtons.unsetActiveAction('EditorToolbar');
						
						//Unset settings button 'down' state
						var toolbar = Manager.EditorToolbar.getToolbar();
						toolbar.getButton('settings').set('down', false);
					}
				}
			});
			this.on('properties:cancel', function () {
				if (!this.html_inputs_count && !this.properties.hasTopGroups()) {
					this.fire('block:cancel');
				}
			});
			
			//Handle block save / cancel
			this.on('block:save', this.savePropertyChanges, this);
			this.on('block:cancel', this.cancelPropertyChanges, this);
		},
		
		/**
		 * When properties form is hidden, unset "Settings" button down state
		 */
		onFormVisibleChange: function (evt) {
			if (this.html_inputs_count) {
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
		
		/**
		 * Returns true if there are any HTML properties, otherwise false
		 */
		hasHTMLProperties: function () {
			//Find if there are any HTML properties
			var properties = this.getProperties(),
				has_html_properties = false;
			
			for(var i=0,ii=properties.length; i<ii; i++) {
				if (properties[i].type == 'InlineHTML') {
					return true;
				}
			}
			
			return false;
		},
		
		/**
		 * Render UI
		 */
		renderUI: function () {
			ContentEditable.superclass.renderUI.apply(this, arguments);
			
			var is_editable = this.get('editable'),				// Editable block
				is_placeholder = this.isPlaceholder(),			// Placeholder block
				has_permissions = !!this.get('data').owner_id;	// Has user permissions to edit template?
			
			if (is_editable || is_placeholder || has_permissions) {
				this.renderOverlay();
			
				if (is_editable) {
					//Find if there are any inline properties
					var properties = this.getProperties(),
						is_inline = false;
					
					if (properties) {
						for(var i=0,ii=properties.length; i<ii; i++) {
							is_inline = Supra.Input.isInline(properties[i].type);
							if (is_inline) {
								//Add class to allow detect if content has inline properties
								this.getNode().addClass(CLASSNAME_INLINE_EDITABLE);
								break;
							}
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
				inline_node = null,
				
				toolbar = Manager.EditorToolbar.getToolbar(),
				
				is_inline = false;
			
			
			this.inline_inputs = {};
			this.html_inputs = {};
			this.inline_inputs_count = 0;
			this.html_inputs_count = 0;
			
			for(var i=0,ii=properties.length; i<ii; i++) {
				id = properties[i].id;
				is_inline = Supra.Input.isInline(properties[i].type);
				
				if (is_inline && id in inputs) {
					
					//If there is no inline node, fail silently
					inline_node = node.one('#' + this.getNodeId() + '_' + properties[i].id);
					if (!inline_node) {
						continue;
					}
					
					this.inline_inputs[id] = inputs[id];
					this.inline_inputs_count++;
					
					if (inputs[id] instanceof Supra.Input.InlineHTML) {
						
						if (!(inputs[id] instanceof Supra.Input.InlineString)) {
							this.html_inputs[id] = inputs[id];
							this.html_inputs_count++;
						}
						
						//Bind command to editor instead of toolbar, because toolbar is shared between editors
						inputs[id].getEditor().addCommand('settings', Y.bind(this.onSettingsCommand, this));
					} else {
						// Partially inline, so HTML toolbar may be visible
						// and clicking on settings button should open block settings
						toolbar.on('command', function (event, id, input) {
							if (event.command === 'settings' && !input.get('disabled') && this.get('active_inline_property') == id) {
								this.onSettingsCommand();
							}
						}, this, id, inputs[id]);
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
						if (this.inline_inputs[old_property_id].stopEditing) {
							this.inline_inputs[old_property_id].stopEditing();
						}
						
						this.inline_inputs[old_property_id].set('disabled', true);
					}
					
					//Enable active inline input
					if (property_id && property_id in this.inline_inputs) {
						this.inline_inputs[property_id].set('disabled', false);
						
						if (this.inline_inputs[property_id].startEditing) {
							this.inline_inputs[property_id].startEditing();
							
							// Small delay because sidebar will be shown and input may need
							// to show its own sidebar
							Supra.immediate(this, function () {
								var old_property_id = this.get('active_inline_property'),
									editing_disabled = this.get('super').get('disabled'),
									disabled = this.inline_inputs[property_id].get('disabled');
								
								if (!editing_disabled && !disabled && old_property_id == property_id) {
									this.inline_inputs[property_id].startEditing();
								}
							});
						}
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
		reloadContentHTML: function (callback) {
			var deferred = new Supra.Deferred();
			
			if ( ! this.properties) {
				return deferred.resolve().promise();
			}
			
			var uri = null,
				page_data = Page.getPageData(),
				data = null;
			
			if (this.isList()) {
				if (page_data.type == 'page') {
					uri = PageContent.getDataPath('contenthtml-page-placeholder');
				} else {
					uri = PageContent.getDataPath('contenthtml-placeholder');
				}
			} else {
				uri = PageContent.getDataPath('save');
			}
			
			if ( ! this.properties) {
				throw new Error("Properties not found for object " + this.constructor.name);
			}
			
			data = {
				'page_id': page_data.id,
				'owner_page_id': this.getPageId(),
				
				'block_id': this.getId(),
				'locale': Supra.data.get('locale'),
				'properties': this.processData(this.properties.getNonInlineSaveValues())
			};
			
			//If editing template, then send also "locked" property
			if (page_data.type != 'page') {
				data.locked = data.properties.__locked__;
				delete(data.properties.__locked__);
				
				this.properties.get('data').locked = data.locked;
			} else if ('__locked__' in data.properties) {
				delete(data.properties.__locked__);
			}
			
			// If there is nothing to save, then ignore
			if (!data.properties || Y.Object.isEmpty(data.properties)) {
				if (Y.Lang.isFunction(callback)) {
					callback(this, false);
				}
				return deferred.resolve().promise();
			}
			
			Supra.io(uri, {
				'method': 'post',
				'data': data,
				'context': this,
				'on': {
					'success': function(data) {
						this._reloadContentSetHTML(data);
						
						if (Y.Lang.isFunction(callback)) {
							callback(this, true);
						}
						
						deferred.resolve();
					},
					'failure': function () {
						if (Y.Lang.isFunction(callback)) {
							callback(this, false);
						}
						
						deferred.reject()
					}
				}
			});
			
			return deferred.promise();
		},
		
		/**
		 * Save state and trigger event before settings new HTML
		 */
		beforeSetHTML: function () {
			if (this.properties) {
				//Get values
				var values = this.properties.get('form').getValues('id'),
					active_inline_property = this.get('active_inline_property'),

					children = this.children,
					id = null;

				//Unset active inline property
				if (active_inline_property) {
					this.set('active_inline_property', null);
				}

				//Save references
				this._beforeSetHTMLValues = values;
				this._beforeSetHTMLActiveInlineProperty = active_inline_property;
			}
			
			//Clean up
			this.fireContentEvent('cleanup', this.getNode().getDOMNode(), {'supra': Supra});
			
			//Traverse children
			for (id in children) {
				if (children[id].beforeSetHTML) {
					children[id].beforeSetHTML();
				}
			}
		},
		
		/**
		 * Handle state before HTML is set
		 */
		beforeSetHTMLHost: function () {},
		
		/**
		 * Restore state and trigger event after settings new HTML
		 */
		afterSetHTML: function () {
			var values = this._beforeSetHTMLValues,
				active_inline_property = this._beforeSetHTMLActiveInlineProperty,
				
				children = this.children,
				id = null;
			
			//Traverse children
			for (id in children) {
				if (children[id].afterSetHTML) {
					children[id].afterSetHTML();
				}
			}
			
			//Remove temporary data
			delete(this._beforeSetHTMLValues);
			delete(this._beforeSetHTMLActiveInlineProperty);
			
			//Trigger refresh
			this.fireContentEvent('refresh', this.getNode().getDOMNode(), {'supra': Supra});
			
			if (this.properties) {
				//Recreate inline inputs
				var properties_handler	= this.properties,
					properties			= properties_handler.get('properties'),
					id					= null,
					is_inline           = false;

				for(var i=0, ii=properties.length; i<ii; i++) {
					is_inline = Supra.Input.isInline(properties[i].type);
					
					// Inline and inline+contained properties need to be reset
					if (is_inline) {
						id = properties[i].id;
						properties_handler.resetProperty(id, values[id]);
					}
				}

				//Update inline input list
				this.findInlineInputs();
				
				if (active_inline_property) {
					if (!(active_inline_property in this.inline_inputs)) {
						for(active_inline_property in this.inline_inputs) {
							//We need only first property
							break;
						}
					}
					if (active_inline_property in this.inline_inputs) {
						this.set('active_inline_property', active_inline_property);
					} else {
						this.set('active_inline_property', null);
					}
				}
			}
		},
		
		/**
		 * Handle state before HTML is set
		 */
		afterSetHTMLHost: function () {
			//Update overlay position
			//Use timeout to make sure everything is styled before doing sync
			Supra.immediate(this, function () {
				var children = this.children,
					id = null;
				
				if (children) {
					for (id in children) {
						children[id].setHighlightMode();
					}
				}
				
				this.setHighlightMode();
				this.syncOverlayPosition(true);
			});
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
				
				//Save state and trigger event
				this.beforeSetHTMLHost();
				this.beforeSetHTML();
				
				//Replace HTML
				this.getNode().set('innerHTML', data.internal_html);
				
				//Restore state and trigger event
				this.afterSetHTML();
				this.afterSetHTMLHost();
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
			delete(this.html_inputs);
			
			ContentEditable.superclass.beforeDestroy.apply(this, arguments);
		},
	});
	
	PageContent.Editable = ContentEditable;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-proto', 'supra.htmleditor', 'supra.page-content-properties', 'supra.page-content-droptarget']});