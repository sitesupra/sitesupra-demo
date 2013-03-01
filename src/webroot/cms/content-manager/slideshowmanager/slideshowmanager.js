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
	'slideshowmanager.input-resizer': {
		path: 'input-resizer.js',
		requires: ['supra.input-proto']
	}
});

Supra([
	'slideshowmanager.data',
	'slideshowmanager.layouts',
	'slideshowmanager.list',
	'slideshowmanager.settings',
	'slideshowmanager.view',
	'slideshowmanager.plugin-inline-button',
	'slideshowmanager.input-resizer'
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
				this.set('activeSlideId', event.data.id);
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
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			//Add buttons
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
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
			
			// Update styles to allow 'left' animation
			node.setStyles({
				'width': width,
				'right': 'auto',
				'left': '100%'
			});
			
			node.removeClass('hidden');
			
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
		},
		
		animateOut: function () {
			var node = this.one(),
				width = Y.DOM.viewportRegion().width;
			
			// Update styles to allow 'left' animation
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
				node.addClass('hidden');
				
				this.set('visible', false);
				
				// Enable auto layout management
				this.set('layoutDisabled', false);
			}, this));
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
			
			if (!Y.Lang.isArray(options.data.slides)) {
				options.data.slides = [];
			}
			
			if (options.callback && options.context) {
				options.callback = Y.bind(options.callback, options.context);
			}
			
			// Change 'height' property to editable
			for (var property, i=0, ii=options.properties.length; i<ii; i++) {
				property = options.properties[i];
				if (property.id == 'height') {
					property.type = 'SlideshowInputResizer';
					property.inline = true;
				}
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
