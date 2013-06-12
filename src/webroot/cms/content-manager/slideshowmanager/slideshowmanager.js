//Add module group
Supra.setModuleGroupPath('slideshowmanager', Supra.Manager.Loader.getActionFolder('SlideshowManager') + 'modules/');

//Add module definitions
Supra.addModules({
	'slideshowmanager.data': {
		path: 'data.js',
		requires: ['plugin']
	},
	'slideshowmanager.layouts': {
		path: 'layouts.js',
		requires: ['plugin', 'supra.template']
	},
	'slideshowmanager.list': {
		path: 'list.js',
		requires: ['plugin', 'supra.template']
	},
	'slideshowmanager.list-order': {
		path: 'list-order.js',
		requires: ['plugin', 'dd']
	},
	'slideshowmanager.settings': {
		path: 'settings.js',
		requires: ['plugin', 'supra.form']
	},
	'slideshowmanager.view': {
		path: 'view.js',
		requires: ['supra.iframe', 'plugin']
	},
	'slideshowmanager.plugin-inline-button': {
		path: 'plugin-inline-button.js',
		requires: ['supra.input-proto', 'plugin', 'supra.template']
	},
	'slideshowmanager.plugin-mask': {
		path: 'plugin-mask.js',
		requires: ['supra.input-proto', 'plugin']
	},
	'slideshowmanager.input-resizer': {
		path: 'input-resizer.js',
		requires: ['supra.input-proto']
	}
});

Supra([
	'slideshowmanager.data',
	'slideshowmanager.layouts',
	'slideshowmanager.list',
	'slideshowmanager.list-order',
	'slideshowmanager.settings',
	'slideshowmanager.view',
	'slideshowmanager.plugin-inline-button',
	'slideshowmanager.plugin-mask',
	'slideshowmanager.input-resizer',
	'supra.help'
], function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	//Add as child, when EditorToolbar will be hidden SlideshowManager will be hidden also (page editing is closed)
	//Manager.getAction('EditorToolbar').addChildAction('SlideshowManager');
	
	//Create Action class
	new Action(Action.PluginContainer, Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'SlideshowManager',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		/**
		 * URL to request mask image painted with given color
		 * @TODO Replace with correct path
		 * @type {String}
		 * @private
		 */
		MASK_IMAGE_REQUEST_URL: '/resources/img/sample/slideshow-mask.png?theme={{ theme }}&color={{ color }}&block_id={{ block_id }}',
		
		/**
		 * Slideshow manager options
		 * @type {Object}
		 * @private
		 */
		options: {},
		
		/**
		 * Page layout is wide, detected by checking "wide" class on BODY element
		 * @type {Boolean}
		 * @private
		 */
		page_layout_wide: null,
		
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			
			this.addAttr('activeSlideId', {'value': null});
			this.after('activeSlideIdChange', this.onActiveSlideChange, this);
			
			//On visibility change update container class and disable/enable toolbar
			this.on('visibleChange', function (evt) {
				if (evt.newVal) {
					this.one().removeClass('hidden');
				} else {
					//this.itemlist.resetAll();
					this.one().addClass('hidden');
				}
				
				Manager.getAction('EditorToolbar').set('disabled', evt.newVal);
			}, this);
			
			this.plug(Supra.SlideshowManagerData);
			this.plug(Supra.SlideshowManagerLayouts);
			this.plug(Supra.SlideshowManagerSettings);
			this.plug(Supra.SlideshowManagerList, {
				'containerNode': this.one('.su-slideshow-manager-list')
			});
			this.plug(Supra.SlideshowManagerView);
			
			// Attach event listeners
			// Update data on user action
			this.settings.on('removeClick', function (event) {
				var index = this.data.getIndexById(event.data.id),
					slide = this.data.getSlideByIndex(index + 1) || this.data.getSlideByIndex(index - 1);
				
				this.set('activeSlideId', slide.id);
				this.data.removeSlideById(event.data.id);
			}, this);
			this.list.on('addClick', function () {
				var id = this.data.addSlide(this.data.getNewSlideData());
				this.set('activeSlideId', id);
			}, this);
			this.list.on('itemClick', function (event) {
				if (this.get('activeSlideId') != event.data.id) {
					this.set('activeSlideId', event.data.id);
				} else {
					// This slide is already opened, show settings
					this.view.refocusSlide();
				}
			}, this);
			this.list.on('order', function (event) {
				this.data.swapSlideIndex(event.indexDrag, event.indexDrop);
			}, this);
			
			// Update UI when data changes
			this.data.on('add', function (event) {
				var data = event.data,
					id = data.id;
				
				this.list.addItem(data);
				this.settings.updateItemCount();
			}, this);
			
			this.data.on('remove', function (event) {
				var data = event.data,
					id = data.id,
					active = this.get('activeSlideId');
				
				this.list.removeItem(id);
				this.settings.updateItemCount();
				
				if (id === active) {
					// Change active to next slide
					this.set('activeSlideId', this.data.getSlideByIndex(0).id);
				}
			}, this);
			
			this.data.on('update', function (event) {
				var id = event.id,
					newData = event.newData,
					prevData = event.prevData,
					active = this.get('activeSlideId');
				
				if ('layout' in newData) {
					if (id === active) {
						// Layout classname
						this.view.updateLayoutClassName(prevData.layout, newData.layout);
						this.view.renderItem(id);
					}
				}
				if ('background' in newData || 'media' in newData) {
					this.list.redrawItem(id);
				}
			}, this);
			
			this.createTips();
		},
		
		createTips: function () {
			var target = this.one('.su-slideshow-manager-list');
			
			Supra.Help.tip('slideshow_order', {
				'before': target,
				'width': 150,
				'height': 100,
				'xPosition': 20,
				'yPosition': ['bottom', 24],
				'zIndex': 2
			}).done(function (widget, data) {
				target.transition({'left': '170px', 'duration': 0.35}, Y.bind(function () {
					this.list.syncScroll();
				}, this));
				
				widget.on('close', function () {
					target.transition({'left': '0px', 'duration': 0.35}, Y.bind(function () {
						this.list.syncScroll();
					}, this));
				}, this)
			}, this);
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			//Add buttons
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'done',
				'context': this,
				'callback': this.close
			}]);
		},
		
		/**
		 * 
		 */
		onActiveSlideChange: function (event) {
			if (event.newVal != event.prevVal) {
				var id = event.newVal;
				
				this.list.set('activeItemId', id);
				
				this.view.stopEditing();
				this.view.set('activeItemId', id);
				
				this.settings.set('activeItemId', id);
				
				if (event.newVal) {
					// Show form only if there is an item to edit,
					// when manager is closed activeItemId is set to null
					this.settings.showForm();
				}
			}
		},
		
		/*
		 * ---------------------------------- SHOW/HIDE USING ANIMATION ------------------------------------
		 */
		
		
		show: function () {
			if (!this.get('visible')) {
				this.set('layoutDisabled', true);
				this.set('visible', true);
				this.animateIn();
			}
		},
		
		hide: function () {
			if (this.get('visible')) {
				this.set('layoutDisabled', true);
				
				// Hide settings form
				if (this.settings_form && this.settings_form.get('visible')) {
					Manager.PageContentSettings.hide();
				}
				
				this.animateOut();
			}
		},
		
		animateIn: function () {
			var node = this.one(),
				width = Y.DOM.viewportRegion().width;
			
			if (Supra.Y.Transition.useNative) {
				// Use CSS transforms + transition
				node.addClass('hidden');
				node.setStyle('transform', 'translate(' + width + 'px, 0px)');
				
				Y.later(1, this, function () {
					// Only now remove hidden to prevent unneeded animation
					node.removeClass('hidden');
				});
				
				// Use CSS
				Y.later(32, this, function () {
					// Animate
					node.setStyle('transform', 'translate(0px, 0px)');
					
					Y.later(500, this, function () {
						// Animation completed, show UI elements
						this.settings.show();
						this.view.show();
						
						// Enable auto layout management
						this.set('layoutDisabled', false);
						
						// Make sure settings are positioned properly
						Supra.Manager.LayoutRightContainer.syncLayout();
					});
				});
			} else {
				// Fallback for IE9
				// Update styles to allow 'left' animation
				node.setStyles({
					'width': width,
					'right': 'auto',
					'left': '100%'
				});
				
				// Animate position
				node.transition({
					'duration': 0.5,
					'left': '0%'
				}, Y.bind(function () {
					node.setStyles({
						'width': 'auto',
						'left': '0px',
						'right': '0px'
					});
					
					// Animation completed, show UI elements
					this.settings.show();
					this.view.show();
					
					// Enable auto layout management
					this.set('layoutDisabled', false);
					
					// Make sure settings are positioned properly
					Supra.Manager.LayoutRightContainer.syncLayout();
				}, this));
			}
		},
		
		animateOut: function () {
			var node = this.one(),
				width = Y.DOM.viewportRegion().width;
			
			if (Supra.Y.Transition.useNative) {
				// Use CSS transforms + transition
				node.setStyle('transform', 'translate(' + width + 'px, 0px)');
				Y.later(350, this, function () {
					this.set('visible', false);
					
					// Enable auto layout management
					this.set('layoutDisabled', false);
				});
			} else {
				// Update styles to allow 'left' animation
				// IE9 fallback
				node.setStyles({
					'width': width,
					'right': 'auto',
					'left': '0%'
				});
				
				// Animate position
				node.transition({
					'duration': 0.5,
					'left': '100%'
				}, Y.bind(function () {
					this.set('visible', false);
					
					// Enable auto layout management
					this.set('layoutDisabled', false);
				}, this));
			}
		},
		
		
		/*
		 * ---------------------------------- TOOLBAR BUTTONS ------------------------------------
		 */
		
		
		_customButton: null,
		
		changeToolbarButtons: function () {
			// 'Done' button
			var button = Manager.PageButtons.getActionButtons('EditorToolbar')[0],
				custom = this._customButton;
			
			button.hide();
			
			if (this._customButton) {
				this._customButton.show();
			} else {
				// Replace 'Done' button with our own
				custom = this._customButton = new Supra.Button({
					'style': 'mid-blue',
					'label': Supra.Intl.get(['buttons', 'done'])
				});
				
				custom.render(button.get('boundingBox').ancestor());
				custom.on('click', this.view.stopEditing, this.view);
			}
			
			// 'Manage slide' button
			var toolbar = Supra.Manager.EditorToolbar.getToolbar(),
				button  = toolbar.getButton('manage');
			
			if (!button) {
				toolbar.addButton('main', {
					'id': 'manage',
					'type': 'button',
					'buttonType': 'button',
					'icon': '/cms/lib/supra/img/htmleditor/icon-settings.png',
					'command': 'manage',
					'title': Supra.Intl.get(['slideshowmanager', 'sidebar_title'])
				});
			} else {
				button.enable();
				button.show();
			}
			
			toolbar.getButton('settings').hide();
		},
		
		restoreToolbarButtons: function () {
			var button = Manager.PageButtons.getActionButtons('EditorToolbar')[0],
				custom = this._customButton,
				toolbar = Supra.Manager.EditorToolbar.getToolbar();
			
			custom.hide();
			button.show();
			
			toolbar.getButton('settings').show();
			toolbar.getButton('manage').hide();
		},
		
		/**
		 * Show settings form
		 */
		showSettings: function () {
			this.settings.show();
		},
		
		
		/*
		 * ---------------------------------- DATA ------------------------------------
		 */
		
		
		/**
		 * Returns list of color presets for Color input
		 * 
		 * @returns {Array} List of color presets
		 * @private
		 */
		getColorPresets: function () {
			// Extract color presets
			var iframe  = Supra.Manager.PageContent.getIframeHandler(),
				parser  = iframe.get('stylesheetParser'),
				styles  = parser.getSelectorsByNodeMatch(iframe.get('doc').body)["COLOR"],
				presets = [],
				i = 0,
				ii = styles.length;
			
			for (; i<ii; i++) {
				if (styles[i].attributes.color) {
					presets.push(styles[i].attributes.color);
				}
			}
			
			return presets;
		},
		
		/**
		 * Normalize options
		 * 
		 * @param {Object} options
		 * @returns {Object} Normalized options
		 * @private
		 */
		normalizeOptions: function (options) {
			this.options = options = Supra.mix({
				'data': {},
				'properties': [],
				'layouts': [],
				
				'callback': null,
				'context': null,
				
				'shared': false,
				'imageUploadFolder': 0
			}, options || {});
			
			if (!Y.Lang.isArray(options.data.slides)) {
				options.data.slides = [];
			}
			
			if (options.callback && options.context) {
				options.callback = Y.bind(options.callback, options.context);
			}
			
			var has_theme_property = false,
				has_mask_color_property = false,
				presets = this.getColorPresets(),
				page_layout_wide = this.isPageLayoutWide();
			
			// Update properties
			for (var property, i=0, ii=options.properties.length; i<ii; i++) {
				property = options.properties[i];
				
				if (property.type == 'Color') {
					// Add presets for color inputs
					property.presets = presets;
				}
				
				if (property.id == 'height') {
					// Change 'height' property to editable
					property.type = 'SlideshowInputResizer';
					property.inline = true;
					
				} else if (property.id == 'theme') {
					has_theme_property = true;
					
					if (page_layout_wide) {
						// In wide page layout mask is disabled
						for (var k=0, kk=property.values.length; k<kk; k++) {
							if (property.values[k].id == 'mask') {
								//property.values.splice(k, 1);
								property.values[k].disabled = true;
								property.values[k].description = Supra.Intl.get(['slideshowmanager', 'wide_layout_mask_description']);
								break;
							}
						}
					}
					
				} else if (property.id == 'mask_color') {
					has_mask_color_property = true;
					
					if (page_layout_wide) {
						// In wide page layout mask is disabled
						property.visible = false;
					}
				}
			}
			
			// If there is theme and mask_color properties, then add
			// mask_image property, which we will use to change
			// mask
			if (!page_layout_wide && has_theme_property && has_mask_color_property) {
				options.properties.push({
					'id': 'mask_image',
					'type': 'Hidden',
					'value': ''
				});
				
				// Add mask_image values to the slides
				var mask = this.settings.getForm().mask;
				for (var slide, i=0, ii=options.data.slides.length; i<ii; i++) {
					slide = options.data.slides[i];
					slide.mask_image = mask.getMaskImageURL(slide.theme, slide.mask_color);
				}
			}
			
          	return options;
		},
		
		/**
		 * Returns true if page layout is wide, otherwise false
		 * 
		 * @returns {Boolean} True if page layout is wide, otherwise false
		 */
		isPageLayoutWide: function () {
			var wide   = this.page_layout_wide,
				iframe = null,
				body   = null;
			
			if (wide === null) {
				wide = false;
				iframe = Supra.Manager.PageContent.getIframeHandler();
				body = Y.Node(iframe.get('doc').body);
				
				wide = body.hasClass('wide');
				this.page_layout_wide = wide;
			}
			
			return wide;
		},
		
		
		/*
		 * ---------------------------------- OPEN/SAVE/CLOSE ------------------------------------
		 */
		
		
		/**
		 * Apply changes, call callback with new data
		 * 
		 * @private
		 */
		close: function () {
			this.view.stopEditing();
			this.settings.hide();
			
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			if (this.options.callback) {
				var data = Supra.mix({}, this.options.data, {'slides': this.data.get('data')});
				this.options.callback(data, true);
			}
			
			//this.destroySettingsForm();
			this.set('activeSlideId', null);
			this.data.resetAll();
			this.list.resetAll();
			this.layouts.resetAll();
			this.view.resetAll();
			
			this.hide();
			this.restoreToolbarButtons();
		},
		
		/**
		 * Execute action
		 * 
		 * @param {Object} options Slideshow options: data, callback, context, block
		 */
		execute: function (options) {
			this.page_layout_wide = null;
			
			this.normalizeOptions(options);
			
			if (!Manager.getAction('PageToolbar').inHistory(this.NAME)) {
				Manager.getAction('PageToolbar').setActiveAction(this.NAME);
				Manager.getAction('PageButtons').setActiveAction(this.NAME);
			}
			
			this.data.set('data', options.data.slides);
			
			this.layouts.set('layouts', options.layouts);
			this.layouts.set('properties', options.properties);
			
			// If there are no slides then create one
			if (!options.data.slides.length) {
				this.data.addSlide(this.data.getNewSlideData());
			} else {
				Y.each(options.data.slides, function (item) {
					this.list.addItem(item);
					this.settings.updateItemCount();	
				}, this);
			}
			
			// Reload iframe content
			this.view.reloadIframe();
			
			// Open first slide
			this.set('activeSlideId', this.data.getSlideByIndex(0).id);
			
			this.show();
			this.changeToolbarButtons();
		}
		
	});
	
});
