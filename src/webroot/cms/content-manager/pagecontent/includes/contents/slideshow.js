YUI.add('supra.page-content-slideshow', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * Constants
	 */
	var PROPERTY_TYPE = 'Slideshow';
	
	/*
	 * Shortcuts
	 */
	var Manager = Supra.Manager,
		Page = Manager.Page,
		PageContent = Manager.PageContent;
	
	/**
	 * Content block which has editable properties
	 */
	function ContentSlideshow () {
		ContentSlideshow.superclass.constructor.apply(this, arguments);
	}
	
	ContentSlideshow.NAME = 'page-content-slideshow';
	ContentSlideshow.CLASS_NAME = Y.ClassNameManager.getClassName(ContentSlideshow.NAME);
	
	Y.extend(ContentSlideshow, PageContent.Editable, {
		
		
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
				'toolbarGroupId': ContentSlideshow.NAME
			});
			
			//Manage button is placed in block settings if there are no property groups
			//and sidebar is opened on block edit and block is saved (stop editing) when
			//sidebar is closed 
			if (!this.properties.hasTopGroups()) {
				// Hide toolbar button
				toolbar.getActionButton('slideshow_block_manage').hide();
				
				this.renderSidebarButton();
				
				//Save and close block on property save (sidebar close)
				this.on('properties:save', function () {
					this.fire('block:save');
				});
				this.on('properties:cancel', function () {
					this.fire('block:cancel');
				});
			} else {
				toolbar.getActionButton('slideshow_block_manage').show();
			}
			
			//Find all inline and HTML properties, initialize
			this.findInlineInputs();
			
			//Handle block save / cancel
			this.on('block:save', this.savePropertyChanges, this);
			this.on('block:cancel', this.cancelPropertyChanges, this);
			
			//On item click open slideshow manager
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
			
			if (!toolbar.hasActionButtons(ContentSlideshow.NAME)) {
				
				toolbar.addActionButtons(ContentSlideshow.NAME, [
					{
						'id': 'slideshow_block_manage',
						'type': 'button',
						'title': Supra.Intl.get(['slideshowmanager', 'label_button']),
						'icon': '/cms/lib/supra/img/toolbar/icon-pages.png',
						'action': this,
						'actionFunction': 'openExternalManager'
					}
				]);
				
				//Add "Done" button
				buttons.addActionButtons(ContentSlideshow.NAME, [
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
											'label': Supra.Intl.get(['slideshowmanager', 'label_button'])
										 });
			
			// Find position where to insert button
			for (; i<ii; i++) {
				if (properties[i].type == 'Slideshow') {
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
				reference.insertBefore(Y.Node.create('<p class="label">' + Supra.Intl.get(['slideshowmanager', 'label']) + '</p>'));
				reference.insertBefore(button.get('boundingBox'));
			} else {
				// Add to the begining of the form
				content.prepend(button.get('boundingBox'));
				content.prepend(Y.Node.create('<p class="label">' + Supra.Intl.get(['slideshowmanager', 'label']) + '</p>'));
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
			ContentSlideshow.superclass.onEditingStart.apply(this, arguments);
			
			if (this.properties.hasTopGroups()) {
				Manager.PageToolbar.setActiveAction(ContentSlideshow.NAME);
				Manager.PageButtons.setActiveAction(ContentSlideshow.NAME);
			}
		},
		
		/**
		 * On editing end hide toolbar buttons
		 * 
		 * @private
		 */
		onEditingEnd: function () {
			ContentSlideshow.superclass.onEditingEnd.apply(this, arguments);
			
			Manager.PageToolbar.unsetActiveAction(ContentSlideshow.NAME);
			Manager.PageButtons.unsetActiveAction(ContentSlideshow.NAME);
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
			
			var shared = self.properties.isPropertyShared('slides'),
				slideProperties = self.getSlideProperties();
			
			// If block is shared and there are no slide properties, then it's pointless to open
			// slide manager
			if ( ! slideProperties.length && shared) {
				var localeId = self.properties._shared_properties.slides.locale,
					locale = Supra.data.getLocale(localeId),
					localeTitle = locale ? locale.title : localeId;

				Supra.Manager.executeAction('Confirmation', {
					'message': Supra.Intl.get(['form', 'shared_slideshow_unavailable']).replace('{{ localeTitle }}', localeTitle),
					'useMask': true,
					'buttons': [{
						'id': 'ok', 
						'label': 'OK'
					}]
				});
				
				return;
			}
			
			// if slideshow is based on shared properties, then we will output a notice about that
			if (force !== true && shared) {
				Supra.Manager.executeAction('Confirmation', {
					'message': Supra.Intl.get(['form', 'shared_slideshow_notice']),
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
			Supra.Manager.executeAction('SlideshowManager', {
				'data': data,
				'properties': self.getSlideProperties(),
				'property_groups': self.getSlidePropertyGroups(),
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
		 * Returns slide property groups
		 * 
		 * @returns {Array} Slide property groups
		 * @private
		 */
		getSlidePropertyGroups: function () {
			var block = this.getBlockInfo(),
				properties = block.properties,
				i = 0,
				ii = properties.length,
				type = PROPERTY_TYPE;
			
			for (; i<ii; i++) {
				if (properties[i].type === type) {
					return properties[i].property_groups || [];
				}
			}
			
			return block.property_groups;
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
		 * Returns property name, which type is Slideshow
		 * 
		 * @private
		 */
		getPropertyName: function () {
			var block = this.getBlockInfo(),
				properties = block.properties,
				i = 0,
				ii = properties.length,
				type = PROPERTY_TYPE;
			
			for (; i<ii; i++) {
				if (properties[i].type === type) {
					return properties[i].id || '';
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
		}
	});
	
	// Must match PROPERTY_TYPE value
	PageContent.Slideshow = ContentSlideshow;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-editable', 'supra.page-content-droptarget']});