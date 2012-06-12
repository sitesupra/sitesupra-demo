//Invoke strict mode
"use strict";

YUI.add('supra.page-content-gallery', function (Y) {
	
	/*
	 * Shortcuts
	 */
	var Manager = Supra.Manager,
		Page = Manager.Page,
		PageContent = Manager.PageContent;
	
	/**
	 * Content block which has editable properties
	 */
	function ContentGallery () {
		ContentGallery.superclass.constructor.apply(this, arguments);
	}
	
	ContentGallery.NAME = 'page-content-gallery';
	ContentGallery.CLASS_NAME = Y.ClassNameManager.getClassName(ContentGallery.NAME);
	
	Y.extend(ContentGallery, PageContent.Editable, {
		
		/**
		 * Data drag and drop object, PluginDropTarget instance
		 * @type {Object}
		 */
		drop: null,
		
		/**
		 * Gallery manage/add buttons
		*/
		buttons: {},
		
		
		renderUISettings: function () {
			//Add toolbar buttons
			var toolbar = Manager.PageToolbar,
				buttons = Manager.PageButtons;
			
			if (!toolbar.hasActionButtons(ContentGallery.NAME)) {
				toolbar.addActionButtons(ContentGallery.NAME, [
					{
						'id': 'gallery_block_manage',
						'type': 'button',
						'title': Supra.Intl.get(['gallerymanager', 'manage']),
						'icon': '/cms/lib/supra/img/toolbar/icon-pages.png',
						'action': this,
						'actionFunction': 'openGalleryManager'
					}
				]);
				
				//Add "Done" button
				buttons.addActionButtons(ContentGallery.NAME, [
					{
						'id': 'done',
						'context': this,
						'callback': Y.bind(function () {
							var active_content = Manager.PageContent.getContent().get('activeChild');
							if (active_content) {
								active_content.fire('block:save');
								return;
							}
							
							Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
						}, this)
					}
				]);
			}
			
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
				//Settings form will be opened using toolbar button
				'showOnEdit': false,
				//Not using default group
				'toolbarGroupId': ContentGallery.NAME
			});
			
			//Find all inline and HTML properties, initialize
			this.findInlineInputs();
			
			//Handle block save / cancel
			this.on('block:save', this.savePropertyChanges, this);
			this.on('block:cancel', this.cancelPropertyChanges, this);
			
			//Render buttons
			this.renderUISettingsButtons();
		},
		
		/**
		 * When form is rendered add gallery button
		 * @private
		 */
		renderUISettingsButtons: function () {
			var container = Y.Node.create('<div class="su-button-group"></div>');
			this.buttonsContainer = container;
			
			this.properties.get('buttonDelete').get('boundingBox').insert(container, 'before');
			
			//Manage image button
			this.buttons.manageButton = new Supra.Button({
				'label': Supra.Intl.get(['htmleditor', 'manage_images'])
			});
			
			this.buttons.manageButton.render(container);
			this.buttons.manageButton.on('click', this.openGalleryManager, this);
			
			//Separator
			container.append(Y.Node.create('<br />'));
			
			//Add image button
			this.buttons.addButton = new Supra.Button({
				'label': Supra.Intl.get(['htmleditor', 'add_images'])
			});
			
			this.buttons.addButton.render(container);
			this.buttons.addButton.on('click', this.openMediaLibrary, this);
			
			//Add image drag and drop support
			this.bindDnD();
		},
		
		/**
		 * Bind drag & drop to allow droping images from media library
		 * 
		 * @private
		 */
		bindDnD: function () {
			var srcNode = this.getNode(),
				doc = this.get('doc');
			
			//On drop add image or images
			srcNode.on('dataDrop', this.onDrop, this);
			
			//Enable drag & drop
			this.drop = new Manager.PageContent.PluginDropTarget({
				'srcNode': srcNode,
				'doc': doc
			});
		},
		
		bindUI: function () {
			ContentGallery.superclass.bindUI.apply(this, arguments);
			this.once('properties:show', this.checkAreImagesShared, this);
		},
		
		onEditingStart: function () {
			ContentGallery.superclass.onEditingStart.apply(this, arguments);
			
			Manager.PageToolbar.setActiveAction(ContentGallery.NAME);
			Manager.PageButtons.setActiveAction(ContentGallery.NAME);
		},
		
		onEditingEnd: function () {
			ContentGallery.superclass.onEditingEnd.apply(this, arguments);
			
			Manager.PageToolbar.unsetActiveAction(ContentGallery.NAME);
			Manager.PageButtons.unsetActiveAction(ContentGallery.NAME);
		},
		
		/**
		 * On image or folder drop add images to the gallery
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		onDrop: function (e) {
			if (!Manager.MediaSidebar) {
				//If media sidebar is not loaded, then user didn't droped image from there
				//Prevent default (which is insert folder thumbnail image) 
				if (e.halt) e.halt();
				return false;
			}
			
			var item_id = e.drag_id,
				item_data = Manager.MediaSidebar.getData(item_id),
				image = null,
				dataObject = Manager.MediaSidebar.medialist.get('dataObject');
			
			if (item_data.type == Supra.MediaLibraryData.TYPE_IMAGE) {
				
				//Add single image
				this.addImage(item_data);
				
			} else if (item_data.type == Supra.MediaLibraryData.TYPE_FOLDER) {
				
				if ( ! dataObject.hasData(item_data.id) 
					|| (item_data.children && item_data.children.length != item_data.children_count)) {
					dataObject.once('load:complete:' + item_data.id, function(event) {
						if (event.data) {
							this.onDrop(e);
						}
					}, this);
					
					return;
					
				} else {
					
					var folderHasImages = false;

					//Add all images from folder
					for(var i in item_data.children) {
						image = item_data.children[i];
						if (image.type == Supra.MediaLibraryData.TYPE_IMAGE) {
							this.addImage(item_data.children[i]);
							folderHasImages = true;
						}
					}

					//folder was without images
					if ( ! folderHasImages) {
						Supra.Manager.executeAction('Confirmation', {
							'message': '{#medialibrary.validation_error.empty_folder_drop#}',
							'useMask': true,
							'buttons': [
								{'id': 'delete', 'label': 'Ok'}
							]
						});

						return;
					}
				}
			}
			
			this.reloadContent();
			
			//Prevent default (which is insert folder thumbnail image) 
			if (e.halt) e.halt();
			
			return false;
		},
		
		/**
		 * Open settings form
		 * @private
		 */
		openSettings: function () {
			//Since toolbar is created by single instance of gallery
			//keyword "this" may have incorrect reference
			var self = Manager.PageContent.getContent().get('activeChild');
			self.properties.showPropertiesForm();
		},
		
		/**
		 * Open gallery manager and update data when it closes
		 * @private
		 */
		openGalleryManager: function (force) {
			//Since toolbar is created by single instance of gallery
			//keyword "this" may have incorrect reference
			var self = Manager.PageContent.getContent().get('activeChild');
			
			// if gallery is based on shared properties, then we will output a notice about that
			
			var shared = self.properties.isPropertyShared('images');
			
			if (force !== true && shared) {
				
				Supra.Manager.executeAction('Confirmation', {
					'message': "This gallery has shared images and some of properties could be unavailable for editing. Would you like to continue?",
					'useMask': true,
					'buttons': [
						{
							'id': 'yes', 
							'label': 'Continue',
							'click': function() {
								self.openGalleryManager(true);
							}
						},
						{'id': 'no', 'label': 'Cancel'}
					]
				});
				
				return;
			}
			
			self.properties.hidePropertiesForm();
			
			//Data
			var gallery_data = self.properties.getValues();
			gallery_data.images = gallery_data.images || [];
			
			//Show gallery
			Supra.Manager.executeAction('GalleryManager', {
				'data': gallery_data,
				'properties': self.getImageProperties(),
				'context': self,
				'shared': shared,
				'callback': function (data, changed) {
					if (changed) {
						this.unresolved_changes = true;
						
						//Update data
						this.properties.setValues(data);
						this.reloadContent();
					}
				}
			});
		},
		
		/**
		 * Open media library sidebar
		 * @private
		 */
		openMediaLibrary: function () {
			
			var button = this.buttons.addButton;
			
			button.set('loading', true);
			
			Manager.getAction('MediaSidebar').execute({
				'onselect': Y.bind(function (event) {
					this.addImage(event.image);
					this.reloadContent();
				}, this),
				'onclose': Y.bind(function () {
					this.properties.showPropertiesForm();
					button.set('loading', false);
				}, this)
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
		
		/**
		 * Add image to the gallery
		 */	
		addImage: function (image_data) {
			var values = this.properties.getValues(),
				images = (values && Y.Lang.isArray(values.images)) ? values.images : [],
				properties = this.getImageProperties(),
				property = null,
				image  = {'image': image_data, 'id': image_data.id};
				
			//Check if image doesn't exist in data already
			for(var i=0,ii=images.length; i<ii; i++) {
				if (images[i].image.id == image_data.id) return;
			}
			
			for(var i=0,ii=properties.length; i<ii; i++) {
				property = properties[i].id;
				image[property] = image_data[property] || properties[i].value || '';
			}
			
			image.title = image_data.filename;
			
			images.push(image);
			
			this.properties.setValues({
				'images': images
			});
		},
		
		/**
		 * Returns image properties
		 * 
		 * @return List of image properties
		 * @type {Array}
		 * @private
		 */
		getImageProperties: function () {
			var block = this.getBlockInfo(),
				properties = block.properties,
				i = 0,
				ii = properties.length;
			
			for (; i<ii; i++) {
				if (properties[i].type === 'Gallery') {
					return properties[i].properties || [];
				}
			}
			
			return properties;
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
			
			if (this.properties.isPropertyShared('images')) {
				data.images = [];
				
				return data; 
			}
			
			var images = [],
				image = {},
				properties = this.getImageProperties(),
				kk = properties.length;
			
			//Default data
			data.images = data.images || [];
			
			//Extract only image ID and properties, remove all other data
			for(var i=0,ii=data.images.length; i<ii; i++) {
				image = Supra.mix({}, data.images[i]);
				delete(image.image);
				
				images.push(image);
				
				for(var k=0; k<kk; k++) {
					image[properties[k].id] = data.images[i][properties[k].id] || '';
				}
			}
			
			if (images.length == 0) {
				images = 0;
			}
			
			data.images = images;
			return data;
		},
		
		reloadContent: function () {
			this.set('loading', true);
			this.properties.get('host').reloadContentHTML(
				function(editable) { 
					editable.set('loading', false);
				}
			);
		},
		
		/**
		 * Check
		 */
		checkAreImagesShared: function()
		{
			if (this.properties.isPropertyShared('images')) {
				if (this.buttons.addButton) {
					this.buttons.addButton.hide();
				}
				
//				var notice = Y.Node.create('<p class="description"></p>'),
//					template = SU.Intl.get(['form', 'shared_gallery_notice']),
//					info = this.properties.getSharedPropertyInfo('images');
//				
//				template = Supra.Template.compile(template);
//				notice.append(template(info));
//				
//				this.properties.get('buttonDelete').get('boundingBox').insert(notice, 'before');
			}
		}
	});
	
	PageContent.Gallery = ContentGallery;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-editable', 'supra.page-content-droptarget']});