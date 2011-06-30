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
			readOnly: true
		},
		'dragable': {
			value: true,
			readOnly: true
		},
		'title': {
			value: ''
		},
		'active_html_property': {
			value: null,
			setter: 'setActiveHTMLProperty'
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
		 * HTML inputs
		 * @type {Object}
		 * @private
		 */
		html_inputs: null,
		
		/**
		 * HTML input count
		 * @type {Number}
		 * @private
		 */
		html_inputs_count: 0,
		
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
				
				//Handle block save / cancel
				this.on('block:save', function () {
					/* @TODO Save data */
				});
				this.on('block:cancel', function () {
					/* @TODO Revert data changes */
				});
			}
		},
		
		/**
		 * Bind Settings form
		 * 
		 * @private
		 */
		bindUISettings: function () {
			//When starting editing for first time create form
			this.once('editing-start', function () {
				
				//Find if there are any HTML properties
				var has_html_properties = false,
					properties = this.getProperties();
				
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
					
					//If there are no inline properties, then 
					//on properties form save / cancel trigger block save / cancel 
					this.on('properties:save', function () {
						this.fire('block:save');
					});
					this.on('properties:cancel', function () {
						this.fire('block:cancel');
					});
					
				}
			}, this);
			
			//If there are InlineHTML contents, show toolbar when editing
			this.on('editing-start', function () {
				if (this.html_inputs_count) {
					for(var id in this.html_inputs) {
						//Enable only first editor
						this.set('active_html_property', id);
						break;
					}
					Manager.Page.showEditorToolbar();
				}
			}, this);
			
			this.on('editing-end', function () {
				if (this.html_inputs_count) {
					this.set('active_html_property', null);
					Manager.Page.hideEditorToolbar();
				}
			});
			
			//On editing-end event hide settings form
			this.on('editing-end', function () {
				var toolbar = Manager.EditorToolbar.getToolbar();
				toolbar.getButton('settings').set('down', false);
			});
		},
		
		/**
		 * Render UI
		 */
		renderUI: function () {
			ContentEditable.superclass.renderUI.apply(this, arguments);
			
			if (this.get('editable')) {
				this.renderOverlay();
				
				//Find if there are any inline properties
				var has_inline_properties = false,
					properties = this.getProperties();
				
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
						
						//When clicking on node enable corresponding editor
						inline_node.on('mousedown', function (event, id) {
							this.set('active_html_property', id);
						}, this, id);
					}
				}
			}
		},
		
		/**
		 * Set active inline property
		 * 
		 * @param {String} property_id Property ID
		 * @return Property ID
		 * @type {String}
		 * @private
		 */
		setActiveHTMLProperty: function (property_id) {
			var old_property_id = this.get('active_html_property');
			
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
		}
	});
	
	Action.Editable = ContentEditable;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-proto', 'supra.htmleditor', 'supra.page-content-properties']});