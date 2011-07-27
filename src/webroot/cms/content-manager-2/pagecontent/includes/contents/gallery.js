//Invoke strict mode
"use strict";

YUI.add('supra.page-content-gallery', function (Y) {
	
	/**
	 * Default gallery image properties
	 */
	var DEFAULT_IMAGE_PROPERTIES = [
		{'id': 'title', 'type': 'String', 'label': 'Title', 'value': ''}
	];
	
	/*
	 * Shortcuts
	 */
	var Manager = SU.Manager,
		Action = Manager.PageContent;
	
	
	/**
	 * Content block which has editable properties
	 */
	function ContentGallery () {
		ContentGallery.superclass.constructor.apply(this, arguments);
	}
	
	ContentGallery.NAME = 'page-content-gallery';
	ContentGallery.CLASS_NAME = Y.ClassNameManager.getClassName(ContentGallery.NAME);
	
	Y.extend(ContentGallery, Action.Editable, {
		
		/**
		 * When form is rendered add gallery button
		 */
		renderUISettings: function () {
			ContentGallery.superclass.renderUISettings.apply(this, arguments);
			
			var container = this.properties.get('form').get('contentBox');
			var button_gallery = new Supra.Button({
				'label': 'Manage images'
			});
			
			button_gallery.render(container);
			button_gallery.on('click', this.openGalleryManager, this);
		},
		
		/**
		 * Open gallery manager and update data when it closes
		 */
		openGalleryManager: function () {
			
			this.properties.hidePropertiesForm();
			
			//Data
			var gallery_data = this.properties.getValues();
			
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
		 * Hide settings form
		 */
		hideSettingsForm: function () {
			if (this.settings_form && this.settings_form.get('visible')) {
				Manager.PageContentSettings.hide();
			}
		},
		
		/**
		 * Add image to the gallery
		 */	
		addImage: function (image_data) {
			var values = this.properties.getValues();
			var images = values.images || [];
			
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
		processData: function (id, data) {
			console.log(data);
			return data;
			
			var images = [],
				image = {},
				properties = Supra.data.get(['gallerymanager', 'properties'], DEFAULT_IMAGE_PROPERTIES),
				kk = properties.length;
			
			//Extract only image ID and properties, remove all other data
			for(var i=0,ii=data.images.length; i<ii; i++) {
				image = {'id': data.images[i].id};
				images.push(image);
				for(var k=0; k<kk; k++) {
					image[properties[k].id] = data.images[i][properties[k].id] || '';
				}
			}
			
			data.images = images;
			return data;
		}
		
	});
	
	Action.Gallery = ContentGallery;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-editable']});