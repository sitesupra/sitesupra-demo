YUI.add('supra.page-content-mediagallery', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * Constants
	 */
	var PROPERTY_TYPE = 'MediaGallery';
	
	/*
	 * Shortcuts
	 */
	var Manager = Supra.Manager,
		Page = Manager.Page,
		PageContent = Manager.PageContent;
	
	/**
	 * Content block which has editable properties
	 */
	function Gallery () {
		Gallery.superclass.constructor.apply(this, arguments);
	}
	
	Gallery.NAME = 'page-content-mediagallery';
	Gallery.CLASS_NAME = Y.ClassNameManager.getClassName(Gallery.NAME);
	
	Y.extend(Gallery, PageContent.Editable, {
		
		
		renderUISettings: function () {
			var toolbar = Manager.PageToolbar,
				buttons = Manager.PageButtons;
			
			//Add toolbar buttons
			this.renderToolbarButton();
			
			//Initialize
			var properties = this.getProperties(),
				page_data = Page.getPageData(),
				data = this.get('data');
			
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
				'data': data,
				//Not using default group
				'toolbarGroupId': Gallery.NAME
			});
			
			//Manage button is placed in block settings if there are no property groups
			//and sidebar is opened on block edit and block is saved (stop editing) when
			//sidebar is closed 
			if (!this.properties.hasTopGroups()) {
				// Hide toolbar button
				toolbar.getActionButton('gallery_block_manage').hide();
				
				this.renderSidebarButton();
				
				//Save and close block on property save (sidebar close)
				this.on('properties:save', function () {
					this.fire('block:save');
				});
				this.on('properties:cancel', function () {
					this.fire('block:cancel');
				});
			} else {
				toolbar.getActionButton('gallery_block_manage').show();
			}
			
			//Find all inline and HTML properties, initialize
			this.findInlineInputs();
			
			//Handle block save / cancel
			this.on('block:save', this.savePropertyChanges, this);
			this.on('block:cancel', this.cancelPropertyChanges, this);
			
			//On item click open gallery manager
			this.bindItemClick();
		},
		
		/**
		 * Bind clicking on one of the items as a trigger for opening gallery manager
		 * 
		 * @private
		 */
		bindItemClick: function () {
			this.getNode().on('click', function (e) {
				this.openExternalManager();
			}, this);
		},
		
		/* -------------------------- Buttons -------------------------- */
		
		/**
		 * Render "Manage" button in toolbar and "Done" button
		 * 
		 * @private
		 */
		renderToolbarButton: function () {
			var toolbar = Manager.PageToolbar,
				buttons = Manager.PageButtons;
			
			if (!toolbar.hasActionButtons(Gallery.NAME)) {
				
				toolbar.addActionButtons(Gallery.NAME, [
					{
						'id': 'gallery_block_manage',
						'type': 'button',
						'title': Supra.Intl.get(['gallery', 'label_button']),
						'icon': '/cms/lib/supra/img/toolbar/icon-pages.png',
						'action': this,
						'actionFunction': 'openExternalManager'
					}
				]);
				
				//Add "Done" button
				buttons.addActionButtons(Gallery.NAME, [
					{
						'id': 'done',
						'context': this,
						'callback': Y.bind(this.onDoneClickStopEditing, this)
					}
				]);
			}
		},
		
		/**
		 * Render "Manage" button in sidebar
		 * 
		 * @private
		 */
		renderSidebarButton: function () {
			//Add "Manage slides" button
			var form = this.properties.get('form'),
				
				properties = this.getProperties(),
				i = 0,
				ii = properties.length,
				references = {},
				reference = null,
				has_reference = false,
				tmp = null,
				
				content = form.get('boundingBox').one('.su-slide-content > div') || form.get('contentBox'),
				button = new Supra.Button({
											'style': 'mid-blue',
											'label': Supra.Intl.get(['gallery', 'label_button'])
										 });
			
			// Find position where to insert button
			for (; i<ii; i++) {
				if (properties[i].type == PROPERTY_TYPE) {
					has_reference = true;
					reference = references[properties[i].group || 'default'];
					references = null;
					break;
				} else {
					if (Supra.Input.isContained(properties[i].type)) {
						tmp = form.getInput(properties[i].id);
						if (tmp) {
							// We map nodes by 'group', because we wan't to insert button in same group
							references[properties[i].group || 'default'] = tmp.get('boundingBox');
						}
					}
				}
			}
			
			button.render(content);
			button.addClass('su-button-fill');
			
			if (reference && has_reference) {
				// Add after reference element
				reference.insertBefore(Y.Node.create('<p class="label">' + Supra.Intl.get(['gallery', 'label']) + '</p>'));
				reference.insertBefore(button.get('boundingBox'));
			} else {
				// Add to the begining of the form
				content.prepend(button.get('boundingBox'));
				content.prepend(Y.Node.create('<p class="label">' + Supra.Intl.get(['gallery', 'label']) + '</p>'));
			}
			
			button.on('click', this.openExternalManager, this);
		},
		
		/**
		 * Returns true if blocks has property groups, otherwise false
		 * If there are property groups then "Manage slides" is placed in toolbar,
		 * otherwise it's placed in block settings
		 * 
		 * @returns {Boolean} True if blocks has property groups, otherwise false
		 * @private
		 */
		hasPropertyGroups: function () {
			var info = this.getBlockInfo();
			if (info.property_groups && info.property_groups.length) {
				return true;
			} else {
				return false;
			}
		},
		
		/* -------------------------- Editing state -------------------------- */
		
		/**
		 * On editing start show toolbar buttons if they should be there instead
		 * of sidebar
		 * 
		 * @private
		 */
		onEditingStart: function () {
			Gallery.superclass.onEditingStart.apply(this, arguments);
			
			if (this.properties.hasTopGroups()) {
				Manager.PageToolbar.setActiveAction(Gallery.NAME);
				Manager.PageButtons.setActiveAction(Gallery.NAME);
			}
		},
		
		/**
		 * On editing end hide toolbar buttons
		 * 
		 * @private
		 */
		onEditingEnd: function () {
			Gallery.superclass.onEditingEnd.apply(this, arguments);
			
			Manager.PageToolbar.unsetActiveAction(Gallery.NAME);
			Manager.PageButtons.unsetActiveAction(Gallery.NAME);
		},
		
		/**
		 * Stop editing block when user clicks on "Done" button
		 * 
		 * @private
		 */
		onDoneClickStopEditing: function () {
			var active_content = Manager.PageContent.getContent().get('activeChild');
			if (active_content) {
				active_content.fire('block:save');
				return;
			}
			
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
		},
		
		
		/* -------------------------- Manager -------------------------- */
		
		
		/**
		 * Open gallery manager and update data when it closes
		 * @private
		 */
		openExternalManager: function (force) {
			//Since toolbar is created by single instance of gallery
			//keyword "this" may have incorrect reference
			var self = Manager.PageContent.getContent().get('activeChild');
			
			//Self doesn't exist if user is not editing block
			if (!self) return;
			
			var property = this.getPropertyName(),
				shared = self.properties.isPropertyShared(property),
				slideProperties = self.getSlideProperties();
			
			// If block is shared and there are no slide properties, then it's pointless to open
			// slide manager
			if ( ! slideProperties.length && shared) {
				var localeId = self.properties._shared_properties[property].locale,
					locale = Supra.data.getLocale(localeId),
					localeTitle = locale ? locale.title : localeId;

				Supra.Manager.executeAction('Confirmation', {
					'message': Supra.Intl.get(['form', 'shared_gallery_unavailable']).replace('{{ localeTitle }}', localeTitle),
					'useMask': true,
					'buttons': [{
						'id': 'ok', 
						'label': 'OK'
					}]
				});
				
				return;
			}
			
			// if gallery is based on shared properties, then we will output a notice about that
			if (force !== true && shared) {
				Supra.Manager.executeAction('Confirmation', {
					'message': Supra.Intl.get(['form', 'shared_gallery_notice']),
					'useMask': true,
					'buttons': [
						{
							'id': 'yes', 
							'label': Supra.Intl.get(['buttons', 'continue']),
							'click': function() {
								self.openExternalManager(true);
							}
						},
						{'id': 'no', 'label': Supra.Intl.get(['buttons', 'cancel'])}
					]
				});
				
				return;
			}
			
			if (!self.properties.hasTopGroups()) {
				self.properties.hidePropertiesForm({
					'keepToolbarButtons': true
				});
			} else {
				self.properties.hidePropertiesForm();
			}
			
			//Data
			var data = self.properties.getValues(),
				property = this.getPropertyName();
			
			if (!Y.Lang.isArray(data[property])) {
				data[property] = [];
			}
			
			//Show gallery
			Supra.Manager.executeAction('Gallery', {
				'data': data,
				'properties': self.getSlideProperties(),
				'propertyName': self.getPropertyName(),
				'layouts': self.getSlideLayouts(),
				'context': self,
				'shared': shared,
				'callback': function (data, changed) {
					if (changed) {
						this.unresolved_changes = true;
						
						//Show settings
						if (!this.properties.hasTopGroups()) {
							//Manager.PageContentSettings.set('frozen', false);
							self.properties.showPropertiesForm();
						}
						
						//Update data
						this.properties.setValues(data);
						this.reloadContent();
					}
				}
			});
		},
		
		/**
		 * Hide settings form
		 * 
		 * @private
		 */
		hideSettingsForm: function () {
			var form = this.properties.get('form');
			if (form && form.get('visible')) {
				Manager.PageContentSettings.hide();
			}
		},	
		
		/* -------------------------- Data -------------------------- */
		
		/**
		 * Returns slide properties
		 * 
		 * @return List of slide properties
		 * @type {Array}
		 * @private
		 */
		getSlideProperties: function () {
			var block = this.getBlockInfo(),
				properties = block.properties,
				i = 0,
				ii = properties.length,
				type = PROPERTY_TYPE;
			
			for (; i<ii; i++) {
				if (properties[i].type === type) {
					return properties[i].properties || [];
				}
			}
			
			return properties;
		},
		
		/**
		 * Returns slide layouts
		 * 
		 * @return List of slide layouts
		 * @type {Array}
		 * @private
		 */
		getSlideLayouts: function () {
			var block = this.getBlockInfo(),
				properties = block.properties,
				i = 0,
				ii = properties.length,
				type = PROPERTY_TYPE;
			
			for (; i<ii; i++) {
				if (properties[i].type === type) {
					return properties[i].layouts || [];
				}
			}
			
			return [];
		},
		
		/**
		 * Returns property name, which type is Gallery
		 * 
		 * @private
		 */
		getPropertyName: function () {
			if (this._property_name) {
				return this._property_name;
			}
			
			var block = this.getBlockInfo(),
				properties = block.properties,
				i = 0,
				ii = properties.length,
				type = PROPERTY_TYPE;
			
			for (; i<ii; i++) {
				if (properties[i].type === type) {
					this._property_name = properties[i].id || '';
					return this._property_name;
				}
			}
			
			return null;
		},
		
		/**
		 * Process data and remove all unneeded before it's sent to server
		 * Called before save
		 * 
		 * @param {String} id Data ID
		 * @param {Object} data Data
		 * @return Processed data
		 * @type {Object}
		 */
		processData: function (data) {
			var property   = this.getPropertyName();
			
			// Shared property, can't edit?
			if (this.properties.isPropertyShared(property)) {
				data[property] = [];
				return data; 
			}
			
			var items      = [],
				item       = {},
				properties = this.getSlideProperties(),
				kk         = properties.length,
				prop       = null;
			
			//Default data
			if (!Y.Lang.isArray(data[property])) {
				data[property] = [];
			}
			
			//Extract only image ID and properties, remove all other data
			for(var i=0,ii=data[property].length; i<ii; i++) {
				// deep clone
				item = Supra.mix({}, data[property][i], true);
				items.push(item);
				
				for(var k=0; k<kk; k++) {
					if (properties[k].type === 'InlineMedia') {
						// Send only image id instead of full image data
						prop = item[properties[k].id];
						if (prop && prop.image && prop.image.id) {
							prop.image = prop.image.id;
						}
					} else if (properties[k].type === 'InlineImage' || properties[k].type === 'BlockBackground') {
						// Send only image id instead of full image data
						prop = item[properties[k].id];
						if (prop && prop.image && prop.image.image) {
							prop.image.image = prop.image.image.id;
						}
					} else if (properties[k].type === 'Image') {
						prop = item[properties[k].id];
						if (prop && prop.id && prop.sizes) {
							item[properties[k].id] = prop.id;
						}
					}
				}
			}
			
			//If there are no items, then instead of empty array
			//send false, because empty array is not actually sent
			if (!items.length) {
				items = false;
			}
			
			data[property] = items;
			
			return data;
		},
		
		/**
		 * Reload content
		 */
		reloadContent: function () {
			this.set('loading', true);
			this.reloadContentHTML(
				function(editable) { 
					editable.set('loading', false);
				}
			);
		},
		
		reloadContentHTML: function (callback) {
			return Gallery.superclass.reloadContentHTML.call(this, function (editable, changed) {
				if (changed) {
					// res will be false if content is not reloaded
					if (editable.updateImageSizes(callback)) {
						if (Y.Lang.isFunction(callback)) {
							callback(editable, changed);
						}
					}
				}
			});
		},
		
		/**
		 * Check if images fill container
		 * Needed after layout changes
		 * 
		 * @private
		 */
		updateImageSizes: function (callback) {
			/*
			var node = this.getNode(),
				script = node.one('[data-supra-id="gallerymanager-item"]'),
				selector = script ? script.getAttribute('data-supra-image-selector') : '',
				nodes = null,
				values = null,
				images = null,
				i = 0,
				ii = 0,
				reload_content = false;
			*/
			
			var node = this.getNode(),
				container = node.one('[data-supra-container="true"]'),
				nodes = null,
				values = null,
				properties = this.properties.get('properties'),
				property = null,
				subproperties = [],
				type = '',
				key = null,
				selector = node.one('[data-supra-image-selector]'),
				images = null,
				image = null,
				width = 0,
				i  = 0,
				ii = 0,
				k  = 0,
				kk = 0,
				
				old_val = null,
				new_val = null,
				
				reload_content = false;
			
			if (selector) {
				selector = selector.getAttribute('data-supra-image-selector');
			} else {
				selector = 'img';
			}
			
			if (container && selector) {
				nodes = Y.Array.filter(container.get('children'), function (node) {
					// Item without children is not actual item
					if (Y.Node(node).get('children').size()) {
						return true;
					} else {
						return false;
					}
				});
				
				// Find property id which type is MediaGallery, we need to find list of 
				// slides in 'values' object
				values = this.properties.getValues();
				for (i=0, ii=properties.length; i<ii; i++) {
					if (properties[i].type == 'MediaGallery') {
						property = properties[i];
						subproperties = [];
						
						// Find InlineImage and InlineMedia inputs
						for (k=0, kk=property.properties.length; k<kk; k++) {
							type = property.properties[k].type;
							
							if (type == 'InlineImage' || type == 'InlineMedia') {
								subproperties.push(property.properties[k]);
							}
						}
						
						break;
					}
				}
				
				if (property && subproperties.length) {
					values = values[property.id] || [];
					i = 0;
					ii = Math.min(values.length, nodes.size());
					
					for (; i<ii; i++) {
						for (k=0, kk=subproperties.length; k<kk; k++) {
							key = subproperties[k].id;
							if (key in values[i] && values[i][key]) {
								if (subproperties[k].type == 'InlineMedia' && values[i][key].type != 'image') {
									// InlineMedia, but video. We are looking only for images
									// because video is resized automatically
									continue;
								}
								
								images = nodes.item(i).all(selector);
								
								if (images.size() == 1) {
									// Should be only 1 matching item
									// otherwise we don't know which one should be changed
									
									image = images.item(0);
									width = image.ancestor().getInnerWidth();
									
									if (!width) {
										// Possibly slideshow
										width = node.getInnerWidth();
									}
									
									old_val = values[i][key],
									new_val = null;
									
									new_val = Y.DataType.Image.resize(old_val, {
										'maxCropWidth': width,
										'scale': true
									});
									
									if (
										new_val.crop_width  != old_val.crop_width ||
										new_val.crop_height != old_val.crop_height ||
										new_val.crop_left   != old_val.crop_left ||
										new_val.crop_top    != old_val.crop_top ||
										new_val.size_width  != old_val.size_width ||
										new_val.size_height != old_val.size_height)
									{
										values[i][key] = new_val;
										
										if (width > old_val.crop_width && new_val.crop_width > old_val.crop_width) {
											// If size increased comparing to old size then we need force size (atleast
											// preview will be correct size even if quality will suffer) and call reload
											// to load good quality images
											image.setStyles({
												'width': new_val.crop_width + 'px',
												'height': new_val.crop_height + 'px'
											});
											reload_content = true;
										}
									}
									
								}
								
							}
						}
					}
				}
			}
			
			if (reload_content) {
				// Reload content
				this.reloadContentHTML(callback);
				return false;
			}
			
			return true;
		}
	});
	
	// Must match PROPERTY_TYPE value
	PageContent.MediaGallery = Gallery;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-editable', 'supra.page-content-droptarget']});
