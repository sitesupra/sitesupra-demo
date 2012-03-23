//Invoke strict mode
"use strict";

YUI.add('supra.page-content-gallery', function (Y) {
	
	/**
	 * Default gallery image properties
	 */
	var DEFAULT_IMAGE_PROPERTIES = [
		{'id': 'title', 'type': 'String', 'label': SU.Intl.get(['htmleditor', 'label_title']), 'value': ''}
	];
	
	/*
	 * Shortcuts
	 */
	var Manager = SU.Manager,
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
		 * When form is rendered add gallery button
		 * @private
		 */
		renderUISettings: function () {
			ContentGallery.superclass.renderUISettings.apply(this, arguments);
			
			/*
			var slideshow = this.properties.get('slideshow'),
				container = slideshow.getSlide('propertySlideMain').one('.su-slide-content');
			*/
			var container = Y.Node.create('<div class="su-button-group"></div>')
			
			this.properties.get('buttonDelete').get('boundingBox').insert(container, 'before');
			
			//Manage image button
			var button = new Supra.Button({
				'label': SU.Intl.get(['htmleditor', 'manage_images'])
			});
			
			button.render(container);
			button.on('click', this.openGalleryManager, this);
			
			//Separator
			container.append(Y.Node.create('<br />'));
			
			//Add image button
			var button = new Supra.Button({
				'label': SU.Intl.get(['htmleditor', 'add_images'])
			});
			
			button.render(container);
			button.on('click', this.openMediaLibrary, this);
			
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
		
		/**
		 * On image or folder drop add images to the gallery
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		onDrop: function (e) {
			var item_id = e.drag_id,
				item_data = Manager.MediaSidebar.getData(item_id),
				image = null;
			
			if (item_data.type == Supra.MediaLibraryData.TYPE_IMAGE) {
				
				//Add single image
				this.addImage(item_data);
				
			} else if (item_data.type == Supra.MediaLibraryData.TYPE_FOLDER) {
				
				//Add all images from folder
				for(var i in item_data.children) {
					image = item_data.children[i];
					if (image.type == Supra.MediaLibraryData.TYPE_IMAGE) {
						this.addImage(item_data.children[i]);
					}
				}
				
			}
		},
		
		/**
		 * Open gallery manager and update data when it closes
		 * @private
		 */
		openGalleryManager: function () {
			
			this.properties.hidePropertiesForm();
			
			//Data
			var gallery_data = this.properties.getValues();
			gallery_data.images = gallery_data.images || [];
			
			//Show gallery
			SU.Manager.executeAction('GalleryManager', gallery_data, Y.bind(function (gallery_data, changed) {
				if (changed) {
					this.unresolved_changes = true;
				}
				
				//Show settings form
				this.properties.showPropertiesForm();
			}, this));
		},
		
		/**
		 * Open media library sidebar
		 * @private
		 */
		openMediaLibrary: function () {
			
			Manager.getAction('MediaSidebar').execute({
				'onselect': Y.bind(function (event) {
					this.addImage(event.image);
				}, this),
				'onclose': Y.bind(function () {
					this.properties.showPropertiesForm();
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
			var values = this.properties.getValues();
			var images = (values && Y.Lang.isArray(values.images)) ? values.images : [];
			
			//Check if image doesn't exist in data already
			for(var i=0,ii=images.length; i<ii; i++) {
				if (images[i].id == image_data.id) return;
			}
			
			images.push(image_data);
			
			this.properties.setValues({
				'images': images
			});
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
			var images = [],
				image = {},
				properties = Supra.data.get(['gallerymanager', 'properties'], DEFAULT_IMAGE_PROPERTIES),
				kk = properties.length;
			
			//Default data
			data.images = data.images || [];
			
			//Extract only image ID and properties, remove all other data
			for(var i=0,ii=data.images.length; i<ii; i++) {
				image = {'id': data.images[i].id};
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
		}
		
	});
	
	PageContent.Gallery = ContentGallery;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-editable', 'supra.page-content-droptarget']});