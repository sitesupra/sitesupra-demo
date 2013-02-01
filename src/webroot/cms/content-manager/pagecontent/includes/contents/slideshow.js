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
		
		/**
		 * Gallery manage/add buttons
		*/
		buttons: {},
		
		
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
				content = form.get('boundingBox').one('.su-slide-content > div') || form.get('contentBox'),
				button = new Supra.Button({
											'style': 'small-gray',
											'label': Supra.Intl.get(['slideshowmanager', 'label_button'])
										 });
			
			button.render(content);
			button.addClass('button-section');
			
			content.prepend(button.get('boundingBox'));
			content.prepend(Y.Node.create('<p class="label">' + Supra.Intl.get(['slideshowmanager', 'label']) + '</p>'));
			
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
			var self = Manager.PageContent.getContent().get('activeChild'),
				shared = self.properties.isPropertyShared('slides'),
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
			
			data[property] = data[property] || [];
			
			//Show gallery
			Supra.Manager.executeAction('SlideshowManager', {
				'data': data,
				'properties': self.getSlideProperties(),
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
			// @TODO Remove following temporary data
			return [{
          		'id': 'layout',
          		'type': 'SelectVisual',
          		'label': 'Layout',
          		'defaultValue': 'bg_text_left',
          		'separateSlide': true,
          		'values': [{
  					'id': 'bg',
  					'title': 'Background image only',
  					'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg.png'
  				}, {
  					'id': 'bg_text',
  					'title': 'Background image and text',
  					'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg-text.png',
  					'values': [{
  						'id': 'bg_text_left',
  						'title': 'Text on left side',
  						'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg-text-left.png'
  					}, {
  						'id': 'bg_text_right',
  						'title': 'Text on right side',
  						'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg-text-right.png'
  					}]
  				}]
          	}, {
          		'id': 'background',
          		'type': 'BlockBackground',
          		'label': 'Background image'
          	}, {
          		'id': 'text_main',
          		'type': 'InlineHTML',
          		'label': 'Main text',
          		'defaultValue': {
          			'data': {},
          			'html': '<h1>Lorem ipsum</h1><h2>Dolor sit amet</h2><p>Lid est laborum dolo es fugats untras. Et harums quidem rerum facilisdolores nemis omnis fugiats vitaro minimarerums unsers sadips dolores sitsers untra nemi amets.</p>'
          		},
          		'inline': true
          	}, {
          		'id': 'text_top',
          		'type': 'InlineHTML',
          		'label': 'Top text',
          		'defaultValue': {
          			'data': {},
          			'html': '<h1>Lorem ipsum</h1><h2>Dolor sit amet</h2><p>Lid est laborum dolo es fugats untras. Et harums quidem rerum facilisdolores nemis omnis fugiats vitaro minimarerums unsers sadips dolores sitsers untra nemi amets.</p>'
          		},
          		'inline': true
          	}, {
          		'id': 'media',
          		'type': 'InlineMedia',
          		'label': 'Image or video'
          	}/*, {
          		'id': 'image',
          		'type': 'InlineImage',
          		'label': 'Image'
          	}*/];
          	
          	
          	
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
			// @TODO Remove following temporary data
			return [{
					'id': 'bg',
          			'html': '<li><div class="as-wrapper"><img class="as-layer absolute fill" src="{{ property.background }}" /></div></li>'
          		}, {
					'id': 'bg_text_left',
          			'html': '<li><div class="as-wrapper"><img class="as-layer absolute fill" src="{{ property.background }}" /><div class="as-layer as-layer-left-small">{{ property.text_main }}</div><div class="as-layer as-layer-right-large">{{ property.media }}</div></li>'
          		}, {
					'id': 'bg_text_right',
          			'html': '<li><div class="as-wrapper"><img class="as-layer absolute fill" src="{{ property.background }}" /><div class="as-layer as-layer-left-large">{{ property.media }}</div><div class="as-layer as-layer-right-small">{{ property.text_main }}</div></li>'
          		}];
          		
          		
          		
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
			data[property] = data[property] || [];
			
			//Extract only image ID and properties, remove all other data
			for(var i=0,ii=data[property].length; i<ii; i++) {
				// deep clone
				item = Supra.mix({}, data[property][i], true);
				items.push(item);
				
				for(var k=0; k<kk; k++) {
					if (properties[k].type === 'InlineImage' || properties[k].type === 'BlockBackground') {
						// Send only image id instead of full image data
						prop = item[properties[k].id];
						if (prop && prop.image && prop.image.image) {
							prop.image.image = prop.image.image.id;
						}
						
						//item[properties[k].id] = item[properties[k].id] || '';
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
			this.properties.get('host').reloadContentHTML(
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