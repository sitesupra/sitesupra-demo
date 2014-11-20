//Add module group
Supra.setModuleGroupPath('gallerymanager', Supra.Manager.Loader.getActionFolder('GalleryManager') + 'modules/');

//Add module definitions
Supra.addModules({
	'gallerymanager.itemlist': {
		path: 'itemlist.js',
		requires: ['supra.iframe', 'supra.template', 'plugin']
	},
	'gallerymanager.itemlist-highlight': {
		path: 'itemlist-highlight.js',
		requires: ['plugin', 'event-mouseenter']
	},
	'gallerymanager.itemlist-order': {
		path: 'itemlist-order.js',
		requires: ['plugin', 'dd-delegate']
	},
	'gallerymanager.itemlist-drop': {
		path: 'itemlist-drop.js',
		requires: ['plugin', 'dd-delegate']
	},
	'gallerymanager.itemlist-uploader': {
		path: 'itemlist-uploader.js',
		requires: ['plugin', 'supra.uploader']
	},
	'gallerymanager.imageeditor': {
		path: 'imageeditor.js',
		requires: ['supra.imageresizer', 'widget']
	}
});

Supra(
	'dd-delegate', 'dd-drop-plugin', 'dd-constrain', 'dd-proxy',
	'supra.medialibrary-list',
	'gallerymanager.itemlist', 'gallerymanager.itemlist-highlight', 'gallerymanager.itemlist-order', 'gallerymanager.itemlist-drop', 'gallerymanager.itemlist-uploader', 'gallerymanager.imageeditor',
function (Y) {

	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	//Add as child, when EditorToolbar will be hidden GalleryManager will be hidden also (page editing is closed)
	Manager.getAction('EditorToolbar').addChildAction('GalleryManager');
	
	
	//Create Action class
	new Action(Action.PluginContainer, Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'GalleryManager',
		
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
		 * Image preview size
		 * @type {String}
		 * @private
		 */
		PREVIEW_SIZE: '200x200',
		
		/**
		 * Image to show when image is broken
		 * @type {String}
		 * @private
		 */
		PREVIEW_BROKEN: '/public/cms/content-manager/gallerymanager/images/icon-broken-large.png',
		
		
		
		/**
		 * Gallery data
		 * @type {Object}
		 * @private
		 */
		data: {},
		
		/**
		 * Callback function
		 * @type {Function}
		 * @private
		 */
		callback: null,
		
		/**
		 * Image property list
		 * 
		 * @type {Array}
		 * @private
		 */
		image_properties: null,
		
		/**
		 * Gallery property id
		 * 
		 * @type {String}
		 * @private
		 */
		gallery_property_id: null,
		
		
		
		/**
		 * Settings form
		 * @type {Object}
		 * @private
		 */
		settings_form: null,
		
		/**
		 * Selected image data
		 * @type {Object}
		 * @private
		 */
		selected_image_data: null,
		
		/**
		 * Image upload folder when using drag and drop from desktop
		 * @type {String}
		 * @private
		 */
		image_upload_folder: 0,
		
		/**
		 * UI input values are changing
		 * @type {Boolean}
		 * @private
		 */
		ui_updating: false,
		
		/**
		 * @private
		 */
		widgets: {},
		
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			
			//On visibility change update container class and disable/enable toolbar
			this.on('visibleChange', function (evt) {
				if (evt.newVal) {
					this.one().removeClass('hidden');
				} else {
					
					this.itemlist.resetAll();
					
					this.one().addClass('hidden');
					this.callback = null;
					this.data = null;
				}
				
				Manager.getAction('EditorToolbar').set('disabled', evt.newVal);
			}, this);

		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			//Add buttons to toolbar
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			
			//Add side buttons
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'done',
				'context': this,
				'callback': function () {
					this.applyChanges();
				}
			}]);
			
 			// Iframe test
 			this.renderIframe();
		},
		
		renderIframe: function () {
			
			// Item list renders all items
			this.plug(Supra.GalleryManagerItemList, {'visible': false});
			
			// Highlight will create elements for hover
			this.itemlist.plug(Supra.GalleryManagerItemListHighlight);
			
			// Order items by drag and drop
			this.itemlist.plug(Supra.GalleryManagerItemListOrder);
			this.itemlist.plug(Supra.GalleryManagerItemListDrop);
			this.itemlist.plug(Supra.GalleryManagerItemListUploader);
			
		},
		
		
		/*
		 * ---------------------------------- IMAGE SETTINGS FORM ------------------------------------
		 */
		
		
		/**
		 * Generate settings form
		 */
		createSettingsForm: function () {
			//Get form placeholder
			var content = Manager.getAction('PageContentSettings').get('contentInnerNode');
			if (!content) return;
			
			//Toolbar buttons
			Manager.getAction('PageToolbar').addActionButtons(this.NAME + 'Settings', []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME + 'Settings', []);
			
			//Properties form
			var properties = this.image_properties,
				form_config = {
					'inputs': properties,
					'style': 'vertical'
				};
			
			var form = new Supra.Form(form_config);
				form.render(content);
				form.hide();
			
			//On input value change update inline inputs and labels
			var ii = properties.length,
				i = 0,
				type = null;
			
			for (; i<ii; i++) {
				type = properties[i].type;
				if (type === 'Number' || type === 'String' || type === 'Text') {
					form.getInput(properties[i].id).on('valueChange', this.afterSettingsFormInputChange, this);
				}
			}
			
			//Mode
			var manage_label = '';
			
			if (this.data.design && this.data.design == 'icon') {
				// Icons
				manage_label = Supra.Intl.get(['gallerymanager', 'manage_icon']);
			} else {
				// Images
				manage_label = Supra.Intl.get(['gallerymanager', 'manage']);
			}
			
			//Manage button
			var btn = this.widgets.manageButton = new Supra.Button({'label': manage_label, 'style': 'mid-blue'});
				btn.render(form.get('contentBox'));
				btn.addClass('su-button-fill');
				btn.on('click', this.openMediaLibraryForReplace, this);
			
			if (this.shared) {
				btn.set('visible', false);
			}
			
			//Button separator
			form.get('contentBox').append('<br />');
				
			//Delete button
			var btn = this.widgets.deleteButton = new Supra.Button({'label': Supra.Intl.get(['buttons', 'delete']), 'style': 'small-red'});
				btn.render(form.get('contentBox'));
				btn.addClass('su-button-delete');
				btn.on('click', this.removeSelectedImage, this);
			
			this.settings_form = form;
			
			return form;
		},
		
		/**
		 * Destroy settings form
		 */
		destroySettingsForm: function () {
			if (this.settings_form) {
				var bounding = this.settings_form.get('boundingBox');
				this.settings_form.destroy();
				this.settings_form = null;
				bounding.remove(true);
			}
		},
		
		/**
		 * After settings form input value changes update image list item UI
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		afterSettingsFormInputChange: function (e) {
			//Don't do anything if all form values are being set
			if (!this.ui_updating && this.selected_image_data) {
				var input = e.target,
					property = input.get('id'),
					value = input.get('value');
				
				this.selected_image_data.properties[property] = value;
				this.updateInlineEditableUI(this.selected_image_data.id);
			}
		},
		
		/**
		 * Remove selected image
		 */
		removeSelectedImage: function () {
			if (this.itemlist.editingId) {
				this.itemlist.removeItem(this.itemlist.editingId);
			}
		},
		
		/**
		 * Show image settings bar
		 * 
		 * @param {String} id Image ID
		 */
		showImageSettings: function (id) {
			var id = id || this.itemlist.editingId,
				data = null;
			
			if (id) {
				data = this.itemlist.getDataById(id);
				
				if (this.settings_form && this.settings_form.get('visible')) {
					if (this.selected_image_data && this.selected_image_data.id != id) {
						//Save previous image data
						this.settingsFormApply(true);
					}
				}
				
				if (!data) {
					Y.log('Missing image data for image "' + id + '"', 'debug');
					return false;
				}
				
				this.ui_updating = true;
				
				//Media sidebar is closed when clicking on image
				//this.list.removeClass('mediasidebar-opened');
				
				//Make sure PageContentSettings is rendered
				var form = this.settings_form || this.createSettingsForm(),
					action = Manager.getAction('PageContentSettings');
				
				if (!form) {
					if (action.get('loaded')) {
						if (!action.get('created')) {
							action.renderAction();
							this.showImageSettings(id);
						}
					} else {
						action.once('loaded', function () {
							this.showImageSettings(id);
						}, this);
						action.load();
					}
					return false;
				}
				
				action.execute(form, {
					'doneCallback': Y.bind(this.settingsFormApply, this),
					'toolbarActionName': this.NAME + 'Settings',
					
					'title': Supra.Intl.get(['gallerymanager', 'settings_title']),
					'scrollable': true
				});
				
				this.selected_image_data = data;
	
				this.settings_form.resetValues()
								  .setValuesObject(data.properties, 'id');
								  
				if (this.widgets.deleteButton) {
					if (this.shared) {
						this.widgets.deleteButton.hide();
					} else {
						this.widgets.deleteButton.show();
					}
				}
				
				this.ui_updating = false;
				
				return true;
			}
		},
		
		/**
		 * Hide properties form
		 */
		settingsFormApply: function (dont_hide) {
			if (this.settings_form && this.settings_form.get('visible')) {
				var property_name = this.gallery_property_id,
					
					image_data_from_form = this.settings_form.getValuesObject('id'),
					image_data = this.selected_image_data,
					data = this.data;
				
				// Fix image path (#6624)
				if (image_data_from_form.image) {
					image_data_from_form.image.path = this.selected_image_data.image.path;
					this.selected_image_data.image = image_data_from_form.image;
					
					delete(image_data_from_form.image);
				}
				
				Supra.mix(image_data, {'properties': image_data_from_form});
				
				for (var i=0,ii=data[property_name].length; i<ii; i++) {
					if (data[property_name][i].id == image_data.id) {
						data[property_name][i] = image_data;
						this.updateInlineEditableUI(image_data.id);
						break;
					}
				}
				
				if (dont_hide !== true) {
					this.settingsFormCancel();
				}
			}
		},
		
		settingsFormCancel: function () {
			if (this.settings_form && this.settings_form.get('visible')) {
				Manager.PageContentSettings.hide();
				this.itemlist.blurInlineEditor();
				this.selected_image_data = null;
			}
		},
		
		
		/*
		 * ---------------------------------- INLINE EDITABLE ------------------------------------
		 */
		
		
		/**
		 * Update labels, etc.
		 * 
		 * @param {String} image_id Image ID
		 * @private
		 */
		updateInlineEditableUI: function (image_id) {
			this.itemlist.updateUI(image_id);
		},
		
		
		/*
		 * ---------------------------------- EXTERNAL INTERFACES ------------------------------------
		 */
		
		
		/**
		 * Open media library sidebar for image replace
		 * 
		 * @param {String} id Item id which should be replaced
		 * @private
		 */
		openMediaLibraryForReplace: function (id) {
			if (this.shared) return;
			
			if (typeof id === 'object') {
				// Event
				id = this.itemlist.editingId;
			}
			
			var itemlist = this.itemlist,
				data = itemlist.getDataById(id),
				path = null;
			
			if (!data) {
				return;
			}
			
			if (data.image) {
				path = [];
				if (data.image.image) {
					if (data.image.image.path) {
						path = [].concat(data.image.image.path);
					}
					path.push(data.image.image.id);
				} else {
					if (data.image.path) {
						path = [].concat(data.image.path);
					}
					path.push(data.image.id);
				}
			}
			
			// Close image editing
			if (itemlist.editingProperty === 'image' && itemlist.editingInput) {
				// There is no input if image is not set yet
				itemlist.editingInput.set('disabled', true);
			}
			
			// Style
			this.itemlist.get('listNode').addClass('supra-gallerymanager-drop');
			
			// Execute action
			var action = null;
			
			if (this.data.design && this.data.design == 'icon') {
				// Icon
				action = Manager.getAction('IconSidebar');
			} else {
				// Image
				action = Manager.getAction('MediaSidebar');
			}
			
			action.execute({
				'onselect': Y.bind(function (event) {
					if (!this.replaceImage(id, event.image || event.icon)) {
						// Image wasn't added, blur!
						this.itemlist.blurInlineEditor();
					}
				}, this),
				'onclose': Y.bind(function (event) {
					if (!event.image && !event.icon) {
						// Image wasn't added, blur!
						this.itemlist.blurInlineEditor();
					}
				}, this),
				'item': path
			});
			
			action.once('hide', function () {
				this.itemlist.get('listNode').removeClass('supra-gallerymanager-drop');
			}, this);
			
		},
		
		
		/*
		 * ---------------------------------- IMAGE LIST ------------------------------------
		 */
		
				
		/**
		 * Replace image
		 * 
		 * @param {String} id Image ID
		 * @param {Object} image New image data
		 * @private
		 */
		replaceImage: function (id, image) {
			if (image instanceof Y.DataType.Icon) {
				image.load().done(function () {
					return this.itemlist.replaceItem(id, image);
				}, this);
			} else {
				return this.itemlist.replaceItem(id, image);
			}
		},
		
		/**
		 * Add image
		 * 
		 * @param {Object} image_data Image data
		 * @private
		 */
		addImage: function (image_data) {
			if (image_data instanceof Y.DataType.Icon) {
				image_data.load().done(function () {
					return this.itemlist.addImage(image_data);
				}, this);
			} else {
				return this.itemlist.addImage(image_data);
			}
		},
		
		
		/*
		 * ---------------------------------- SHOW/HIDE ------------------------------------
		 */
		
		
		show: function () {
			if (!this.get('visible')) {
				this.set('visible', true);
				this.animateIn();
				Supra.Manager.PageHeader.back_button.hide();
			}
		},
		
		hide: function () {
			if (this.get('visible')) {
				// Hide settings form
				if (this.settings_form && this.settings_form.get('visible')) {
					Manager.PageContentSettings.hide();
				}
				
				this.animateOut();
				Supra.Manager.PageHeader.back_button.show();
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
						this.itemlist.set('visible', true);
					});
				});
			} else {
				// Fallback for IE9
				// Update styles to allow 'left' animation
				node.setStyles({
					'width': width + 'px',
					'right': 'auto',
					'left': width + 'px'
				});
				
				// Animate position using JS
				node.transition({
					'duration': 0.5,
					'left': '0px'
				}, Y.bind(function () {
					node.setStyles({
						'width': 'auto',
						'left': '0px',
						'right': '0px'
					});
					
					this.itemlist.set('visible', true);
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
				});
			} else {
				// Update styles to allow 'left' animation
				// IE9 fallback
				node.setStyles({
					'width': width + 'px',
					'right': 'auto',
					'left': '0px'
				});
				
				// Animate position using JS
				node.transition({
					'duration': 0.5,
					'left': width + 'px'
				}, Y.bind(function () {
					this.set('visible', false);
				}, this));	
			}
		},
		
		
		/*
		 * ---------------------------------- OPEN/SAVE ------------------------------------
		 */
		
		
		/**
		 * Apply changes, call callback with new data
		 * 
		 * @private
		 */
		applyChanges: function () {
			var property_name = this.gallery_property_id,
				
				items = this.itemlist.get('listNode').all(this.itemlist.getChildSelector()),
				order = {},
				data = this.data;
			
			//Get image order from node order
			if (items.size()) {
				items.each(function (item, index) {
					var id = this.getData('item-id');
					if (id) order[id] = index;
				});
			}
			
			//Sort images array
			data[property_name].sort(function (a, b) {
				var oa = order[a.id],
					ob = order[b.id];
				
				return oa > ob ? 1 : -1;
			});
			
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			if (this.callback) {
				this.callback(data, true);
			}
			
			this.destroySettingsForm();
			this.hide();
		},
		
		/**
		 * Cancel changes
		 * 
		 * @private
		 */
		cancelChanges: function () {
			
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			if (this.callback) {
				this.callback(this.data, false);
			}
			
			this.hide();
		},
		
		/**
		 * Enable/disable some functionality in shared mode
		 */
		applySharedSettings: function (shared) {
			// Manage image button
			if (this.widgets.manageButton) {
				this.widgets.manageButton.set('visible', !shared);
			}
			
			// Change shared property
			this.itemlist.set('shared', shared);
		},
		
		/**
		 * Validate ids and create dummy ones if missing
		 * 
		 * @param {Object} data Gallery data
		 * @returns {Object} Data with fixed ids
		 * @private
		 */
		transformData: function (data) {
			data = Supra.mix({}, data, true);
			
			var property_name = this.gallery_property_id,
				
				images = data[property_name] || [],
				i = images.length - 1,
				id = null,
				unique = {};
			
			for (; i >= 0; i--) {
				if (!images[i].id && images[i].image) {
					images[i].id = images[i].image.id || Y.guid();
				}
				
				// Filter out unique items only
				id = images[i].id;
				if (id in unique) {
					// Already exists, remove
					images.splice(i, 1);
				} else {
					unique[id] = true;
				}
			}
			
			return data;
		},
		
		/**
		 * Execute action
		 * 
		 * @param {Object} options Gallery options: data, callback, context, block
		 */
		execute: function (options) {
			options = Supra.mix({
				'data': {},
				'callback': null,
				'context': null,
				'properties': [],
				'galleryPropertyId': null,
				'shared': false,
				'imageUploadFolder': 0
			}, options);
			
			if (!Manager.getAction('PageToolbar').inHistory(this.NAME)) {
				Manager.getAction('PageToolbar').setActiveAction(this.NAME);
				Manager.getAction('PageButtons').setActiveAction(this.NAME);
			}
			
			this.shared = options.shared;
			this.applySharedSettings(options.shared);
			
			this.callback = options.callback ? (options.context ? Y.bind(options.callback, options.context) : options.callback) : null;
			
			this.gallery_property_id = options.galleryPropertyId;
			this.image_properties = options.properties || [];
			this.image_upload_folder = options.imageUploadFolder || 0;
			
			this.data = this.transformData(options.data);
			
			this.itemlist.set('visible', false);
			this.itemlist.reloadIframe();
			
			this.show();
		}
		
	});
	
});
