YUI.add('supra.page-content-editable', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Page = Manager.Page,
		PageContent = Manager.PageContent;
	
	//CSS classes
	var CLASSNAME_INLINE_EDITABLE = 'su-content-inline-editable';
	
	/**
	 * Content block which has editable properties
	 */
	function ContentEditable () {
		ContentEditable.superclass.constructor.apply(this, arguments);
	}
	
	ContentEditable.NAME = 'page-content-editable';
	ContentEditable.CSS_PREFIX = ContentEditable.CLASS_NAME = 'su-' + ContentEditable.NAME;
	ContentEditable.ATTRS = {
		'editable': {
			value: true,
			writeOnce: true
		},
		'draggable': {
			value: true
		},
		'activeInlinePropertyName': {
			value: null,
			setter: 'setActiveInlinePropertyName'
		}
	};
	
	Y.extend(ContentEditable, PageContent.Proto, {
		
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
		saveBlockAction: function () {
			if (!this.saving && this.properties && this.properties.get('changed')) {
				this.saving = true;
				this.set('loading', true);
				
				return this.reload(false)
					.done(function () {
						this.properties.set('changed', false);
						//this.syncOverlayPosition();
					}, this)
					.always(function () {
						this.saving = false;
						this.set('loading', false);
					}, this);
			} else {
				return Supra.Deferred().resolve().promise();
			}
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
		
		initializeProperties: function () {
			
		},
		
		onEditingStart: function () {
			// Render data
			// this.properties.set('data', this.get('data'));
			
			// Change header title
			Manager.getAction('PageHeader').setTitle("block", this.getBlockTitle());
			
			// Enable first inline property
			this.resetActiveInlineProperty();
		},
		
		/**
		 * Reset active inline property to the first inline property,
		 * if 'id' argument is passed, then prefer it over other properties;
		 * prefer html inline properties over all other inline properties
		 *
		 * @param {String} [id] Preferable property id
		 */
		resetActiveInlineProperty: function (id) {
			// Set active property
			var inputs = this.properties.getInlineInputs(),
				i = 0,
				ii = inputs.length,
				inlineInput,
				htmlInput,
				editing = this.get('editing');
			
			for (; i < ii; i++) {
				if (!editing) {
					inputs[i].set('disabled', true);
				} else if (id && inputs[i].getHierarhicalName() === id) {
					if (htmlInput) htmlInput.set('disabled', true);
					htmlInput = inputs[i];
				} else if (!htmlInput && inputs[i].isInstanceOf('input-html-inline')) {
					htmlInput = inputs[i];
				} else if (!inlineInput) {
					inlineInput = inputs[i];
				} else {
					inputs[i].set('disabled', true);
				}
			}
			
			if (editing) {
				if (htmlInput) {
					if (inlineInput) {
						inlineInput.set('disabled', true);
					}
					
					this.set('activeInlinePropertyName', htmlInput.getHierarhicalName());
					htmlInput.set('disabled', false);
				} else if (inlineInput) {
					this.set('activeInlinePropertyName', inlineInput.getHierarhicalName());
					inlineInput.set('disabled', false);
				}
			}
		},
		
		onEditingEnd: function () {
			this.set('activeInlinePropertyName', null);
			
			if (this.properties.hasHtmlInputs()) {
				if (this.properties.get('changed')) {
					this.fire('block:save');
				}
			}
			
			//Revert header title change
			Manager.getAction('PageHeader').unsetTitle("block", this.getBlockTitle());
		},
		
		renderUISettings: function () {
			//Find if there are any HTML properties
			var page_data = Page.getPageData(),
				data = this.get('data'),
				block,
				properties = [];
			
			//If editing template, then set "__locked__" property value
			if (page_data.type != 'page') {
				data.properties = data.properties || {};
				data.properties.__advanced__ = data.properties.__advanced__ || {};
				data.properties.__advanced__.__locked__ = {
					value: data.locked
				};
			}
			
			// Find properties
			if (data && data.type) {
				block = Supra.mix({}, Manager.Blocks.getBlock(data.type), true);
				properties = [].concat(block.properties || []);
			}
			
			//Add properties plugin (creates form)
			this.plug(PageContent.PluginProperties, {
				'properties': properties
			});
			
			this.properties.set('data', data);
			
			//If there are no inline html properties, then 
			//on properties form save / cancel trigger block save / cancel 
			this.on('properties:save', function () {
				if (!this.properties.hasHtmlInputs()) {
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
			
			//Handle block save / cancel
			this.on('block:save', this.saveBlockAction, this);
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
						for(var i=0,ii=properties.length; i < ii; i++) {
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
		 * Active inline property attribute setter
		 * 
		 * @param {String} propertyId Property ID
		 * @returns {String} Property ID
		 * @protected
		 */
		setActiveInlinePropertyName: function (propertyId) {
			var prevPropertyId = this.get('activeInlinePropertyName');
			
			//Can't set active inline property if editing is disabled
			var editing_disabled = this.get('super').get('disabled');
			
			if (!editing_disabled && propertyId != prevPropertyId) {
				var inputs = this.properties.get('form').getAllInputs('name');
				
				if (prevPropertyId && prevPropertyId in inputs) {
					if (inputs[prevPropertyId].stopEditing) {
						inputs[prevPropertyId].stopEditing();
					}
					
					inputs[prevPropertyId].set('disabled', true);
				}
				
				if (propertyId && propertyId in inputs) {
					inputs[propertyId].set('disabled', false);
				
					if (Supra.Input.isContained(inputs[propertyId])) {
						this.properties.showPropertiesForm();
					}
					if (inputs[propertyId].startEditing) {
						inputs[propertyId].startEditing();
					
						// Small delay because sidebar will be shown and input may need
						// to show its own sidebar
						Supra.immediate(this, function () {
							var prevPropertyId = this.get('activeInlinePropertyName'),
								editingDisabled = this.get('super').get('disabled'),
								isEditing = this.get('editing'),
								disabled = inputs[propertyId].get('disabled');
							
							if (!editingDisabled && !disabled && isEditing && prevPropertyId == propertyId) {
								inputs[propertyId].startEditing();
							}
						});
					}
						
					return propertyId;
				} else {
					return null;
				}
			}
			
			return prevPropertyId;
		},
		
		
		/* --------------------- Content reloading ---------------------- */
		
		
		/**
		 * Returns request url for block content reload
		 *
		 * @param {Object} data Page data
		 * @returns {String} Request URL
		 * @protected
		 */
		_getReloadRequestUrl: function (data) {
			if (this.isList()) {
				return PageContent.getDataPath('save-placeholder');
			} else {
				return PageContent.getDataPath('save');
			}
		},
		
		/**
		 * Reload content HTML
		 * Load html from server
		 *
		 * @returns {Object} Promise
		 */
		reload: function () {
			if (!this.properties) {
				return Supra.Deferred().resolve().promise();
			}
			
			var page_data = Page.getPageData(),
				uri = this._getReloadRequestUrl(page_data),
				data = null,
				result;
			
			data = {
				'page_id': page_data.id,
				'owner_page_id': this.getPageId(),
				
				'locale': Supra.data.get('locale'),
				'properties': this.properties.getSaveValues()
			};
			
			if (this.isList()) {
				data.place_holder_id = this.getId();
			} else {
				data.block_id = this.getId();
			}
			
			//Extract advanced properties
			if (data.properties.__advanced__) {
				Supra.mix(data.properties, data.properties.__advanced__);
				delete(data.properties.__advanced__);
			}
			
			//If editing template, then send also "locked" property
			if (page_data.type != 'page') {
				data.locked = data.properties.__locked__;
			}
			
			if ('__locked__' in data.properties) {
				delete(data.properties.__locked__);
			}
			
			// If there is nothing to save, then ignore
			if (!data.properties || Y.Object.isEmpty(data.properties)) {
				return Supra.Deferred().resolve().promise();
			}
			
			return Supra.io(uri, {'method': 'post', 'data': data}).done(function (data) {
				this.handleReloadRequestComplete(data);
				
				//Change page version title
				Manager.getAction('PageHeader').setVersionTitle('autosaved');
				
				//Global activity
				Supra.session.triggerActivity();
			}, this);
		},
		
		/**
		 * Save state and trigger event before settings new HTML
		 */
		beforeSetHTML: function () {
			var children,
				id,
				activeInlinePropertyName,
				state;
				
			state = this.reloadState = {
				'data': null,
				'activeInlinePropertyName': null
			};
			
			if (this.properties) {
				//Get values
				children = this.children;
				activeInlinePropertyName = this.get('activeInlinePropertyName');

				state.data = this.properties.get('form').getValues('id');
				state.activeInlinePropertyName = activeInlinePropertyName;
				
				//Unset active inline property
				if (activeInlinePropertyName) {
					this.set('activeInlinePropertyName', null);
				}
			}
			
			//Clean up
			this.fireContentEvent('cleanup', this.getNode().getDOMNode(), {'supra': Supra});
			
			//Traverse children
			if (children) {
				for (id in children) {
					if (children[id].beforeSetHTML) {
						children[id].beforeSetHTML();
					}
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
			var state = this.reloadState,
				values,
				activeInlinePropertyName,
				active_inline_html_property,
				
				children = this.children,
				id = null,
				form,
				block,
				properties;
			
			if (state) {
				values = state.data;
				activeInlinePropertyName = state.activeInlinePropertyName;
			}
			
			//Traverse children
			for (id in children) {
				if (children[id].afterSetHTML) {
					children[id].afterSetHTML();
				}
			}
			
			//Remove temporary data
			this.reloadState = null;
			
			//Trigger refresh
			this.fireContentEvent('refresh', this.getNode().getDOMNode(), {'supra': Supra});
			
			// Recheck inputs and restore
			// If block is not beeing edited anymore, then no need to restore anything
			if (this.properties) {
				this.properties.preventValueUpdateEventLoop = true;
				
				form = this.properties.get('form');
				block = Supra.mix({}, Manager.Blocks.getBlock(this.get('data').type), true);
				properties = [].concat(block.properties || []);
				
				// Sanitize
				properties = this.properties.getFormConfigProperties(properties);
				
				// Set values and properties, this will force recreation of inline elements for Collection and Set
				form.set('inputs', properties);
				form.setValues(Supra.mix({}, values, true), 'id', true);
				
				this.properties.reinitializeProperties();
				this.resetActiveInlineProperty(activeInlinePropertyName);
				
				this.properties.preventValueUpdateEventLoop = false;
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
		handleReloadRequestComplete: function (data) {
			if (data && (data.internal_html || data.internal_html === '')) {
				//Save state and trigger event
				this.beforeSetHTMLHost();
				this.beforeSetHTML();
				
				// Remove wrapper element from HTML
				var html = data.internal_html;
				
				html = html
						.replace(new RegExp('<div id="' + this.getNodeId() + '"[^>]+>'), '')
						.replace(/<\/div[^>]+>$/, '');
				
				//Replace HTML
				this.getNode().set('innerHTML', html);
				
				//Restore state and trigger event
				this.afterSetHTML();
				this.afterSetHTMLHost();
			}
		}
	});
	
	PageContent.Editable = ContentEditable;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-proto', 'supra.htmleditor', 'supra.page-content-properties']});
