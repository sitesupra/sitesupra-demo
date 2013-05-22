//Add module group
Supra.setModuleGroupPath('gallery', Supra.Manager.Loader.getActionFolder('Gallery') + 'modules/');

//Add module definitions
Supra.addModules({
	'gallery.data': {
		path: 'data.js',
		requires: ['plugin']
	},
	'gallery.layouts': {
		path: 'layouts.js',
		requires: ['plugin', 'supra.template']
	},
	'gallery.settings': {
		path: 'settings.js',
		requires: ['plugin', 'supra.form']
	},
	'gallery.view': {
		path: 'view.js',
		requires: ['supra.iframe', 'plugin']
	},
	'gallery.view-highlight': {
		path: 'view-highlight.js',
		requires: ['plugin']
	},
	'gallery.view-order': {
		path: 'view-order.js',
		requires: ['plugin', 'dd-delegate']
	},
	'gallery.plugin-inline-button': {
		path: 'plugin-inline-button.js',
		requires: ['supra.input-proto', 'plugin', 'supra.template']
	}
});

Supra([
	'gallery.data',
	'gallery.layouts',
	'gallery.settings',
	'gallery.view',
	'gallery.view-highlight',
	'gallery.view-order',
	'gallery.plugin-inline-button'
], function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	//Add as child, when EditorToolbar will be hidden Gallery will be hidden also (page editing is closed)
	//Manager.getAction('EditorToolbar').addChildAction('Gallery');
	
	//Create Action class
	new Action(Action.PluginContainer, Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'Gallery',
		
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
		 * Slideshow manager options
		 * @type {Object}
		 * @private
		 */
		options: {},
		
		
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
					this.one().addClass('hidden');
				}
				
				Manager.getAction('EditorToolbar').set('disabled', evt.newVal);
			}, this);
			
			this.plug(Supra.GalleryData);
			this.plug(Supra.GalleryLayouts);
			this.plug(Supra.GallerySettings);
			this.plug(Supra.GalleryView);
			
			this.view.plug(Supra.GalleryViewOrder);
			this.view.plug(Supra.GalleryViewHighlight);
			
			// Attach event listeners
			// Update data on user action
			this.settings.on('removeClick', function (event) {
				this.data.removeSlideById(event.data.id);
			}, this);
			
			// Update UI when data changes
			this.data.on('add', function (event) {
				var data = event.data,
					id = data.id;
				
				this.view.renderItem(id);
				this.set('activeSlideId', id);
				this.settings.showForm();
			}, this);
			
			this.data.on('remove', function (event) {
				var data = event.data,
					id = data.id,
					active = this.get('activeSlideId'),
					next = null;
				
				if (id === active) {
					this.set('activeSlideId', null);
				}
				
				this.view.removeItem(id);
			}, this);
			
			this.view.on('orderItem', function (event, data) {
				this.data.swapSlideIndex(event.prevVal, event.newVal);
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
				
				this.view.stopEditing();
				this.view.set('activeItemId', id);
				
				this.settings.set('activeItemId', id);
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
				//custom.on('click', this.view.stopEditing, this.view);
				custom.on('click', this.listView, this);
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
					'title': Supra.Intl.get(['gallery', 'sidebar_title'])
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
		 * ---------------------------------- OPEN/SAVE/CLOSE ------------------------------------
		 */
		
		
		/**
		 * Show list view
		 */
		listView: function () {
			// Stop editing
			/*var view = this.view,
				inputs = this.settings.getForm().getInputs(),
				key = null,
				input = null;
			
			for (key in inputs) {
				input = inputs[key];
				if (input.get('focused') && input.isInstanceOf('input-media-inline') || input.isInstanceOf('block-background')) {
					input.stopEditing();
				}
			}*/
			
			// Stop editing item
			this.view.stopEditing();
			
			// Hide settings form
			this.settings.hideForm();
			
			// Unset active slide
			this.set('activeSlideId', null);
		},
		
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
			this.layouts.resetAll();
			this.view.resetAll();
			
			this.hide();
			this.restoreToolbarButtons();
		},
		
		/**
		 * Normalize options
		 * 
		 * @param {Object} options
		 * @returns {Object} Normalized options
		 * @private
		 */
		normalizeOptions: function (options) {
			options = Supra.mix({
				'data': {},
				'properties': [],
				'layouts': [],
				
				'callback': null,
				'context': null,
				
				'shared': false,
				'imageUploadFolder': 0
			}, options || {});
			
			if (Y.Lang.isArray(options.properties)) {
				var property,
					i  = 0,
					ii = options.properties.length;
				
				for (; i<ii; i++) {
					property = options.properties[i];
					if (property.type == 'InlineHTML') {
						// Disable some of the plugins
						property.plugins = {
							'image': false,
							'video': false,
							'icon': false,
							'table': false,
							'table-mobile': false
						};
					} else if (property.type == 'InlineMedia') {
						// Don't close editing when clicking outside image
						property.autoClose = false;
						property.separateSlide = false;
						property.allowZoomResize = true;
						property.allowCropZooming = true;
					}
				}
			}
			
			if (!Y.Lang.isArray(options.data.slides)) {
				options.data.slides = [];
			}
			
			if (options.callback && options.context) {
				options.callback = Y.bind(options.callback, options.context);
			}
			
          	return options;
		},
		
		/**
		 * Execute action
		 * 
		 * @param {Object} options Slideshow options: data, callback, context, block
		 */
		execute: function (options) {
			options = this.normalizeOptions(options);
			this.options = options;
			
			if (!Manager.getAction('PageToolbar').inHistory(this.NAME)) {
				Manager.getAction('PageToolbar').setActiveAction(this.NAME);
				Manager.getAction('PageButtons').setActiveAction(this.NAME);
			}
			
			this.data.set('data', options.data.slides);
			
			this.layouts.set('layouts', options.layouts);
			this.layouts.set('properties', options.properties);
			
			// Reload iframe content
			this.view.reloadIframe();
			
			this.show();
			this.changeToolbarButtons();
		}
		
	});
	
});
