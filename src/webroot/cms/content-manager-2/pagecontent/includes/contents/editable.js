//Invoke strict mode
"use strict";

YUI.add('supra.page-content-editable', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager,
		Action = Manager.PageContent;
	
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
	
	Y.extend(ContentEditable, Action.Proto, {
		
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
				this.get('super').sendBlockProperties(this, function (status, response_data) {
					
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
				has_html_properties = false;
			
			for(var i=0,ii=properties.length; i<ii; i++) {
				if (properties[i].inline && properties[i].type == 'InlineHTML') {
					has_html_properties = true;
					break;
				}
			}
			
			//Add properties plugin (creates form)
			this.plug(Action.PluginProperties, {
				'data': this.get('data'),
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
				
				for(var i=0,ii=properties.length; i<ii; i++) {
					if (properties[i].inline) {
						//Add class to allow detect if content has inline properties
						this.getNode().addClass(CLASSNAME_INLINE_EDITABLE);
						break;
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
						
						inline_node = node.one('#' + this.getNodeId() + '_' + properties[i].id);
						
						//Bind command to editor instead of toolbar, because toolbar is shared between editors
						inputs[id].getEditor().addCommand('settings', Y.bind(this.onSettingsCommand, this));
						
						//If there is no inline node, then show error
						if (!inline_node) {
							Y.error('Block "' + this.getId() + '" (' + this.getType() + ') is missing HTML node for property "' + id + '" (' + properties[i].type + ')');
							continue;
						}
						
						//When clicking on node enable corresponding editor
						inline_node.on('mousedown', function (event, id) {
							this.set('active_inline_property', id);
						}, this, id);
					}
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
			
			if (property_id != old_property_id) {
				if (old_property_id && old_property_id in this.html_inputs) {
					this.html_inputs[old_property_id].set('disabled', true);
				}
				if (property_id && property_id in this.html_inputs) {
					this.html_inputs[property_id].set('disabled', false);
				}
				return property_id;
			}
			
			return old_property_id;
		},
		
		/**
		 * Changed getter
		 */
		_getChanged: function () {
			if (this.get('editable') && this.properties) {
				return this.properties.get('normalChanged') || this.properties.get('inlineChanged');
			}
			return false;
		}
	});
	
	Action.Editable = ContentEditable;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-proto', 'supra.htmleditor', 'supra.page-content-properties']});