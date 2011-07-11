YUI().add('supra.htmleditor-plugin-gallery', function (Y) {
	
	/**
	 * Default gallery image properties
	 */
	var DEFAULT_IMAGE_PROPERTIES = [
		{'id': 'title', 'type': 'String', 'label': 'Title', 'value': ''}
	];
	
	var defaultConfiguration = {
		'size': '200x200'
	};
	
	var defaultProps = {
		'type': null,
		'title': '',
		'description': '',
		'align': 'right',
		'style': '',
		'images': []
	};
	
	var Manager = Supra.Manager;
	
	Supra.HTMLEditor.addPlugin('gallery', defaultConfiguration, {
		
		settings_form: null,
		selected_gallery: null,
		selected_gallery_id: null,
		original_data: null,
		silent: false,
		
		/**
		 * Generate settings form
		 */
		createSettingsForm: function () {
			//Get form placeholder
			var content = Manager.getAction('PageContentSettings').getContainer();
			if (!content) return;
			
			//Properties form
			var form_config = {
				'inputs': [
					{'id': 'title', 'type': 'String', 'label': 'Gallery title', 'value': ''},
					{'id': 'description', 'type': 'String', 'label': 'Alt text', 'value': ''},
					{'id': 'align', 'type': 'SelectList', 'label': 'Alignment', 'value': 'right', 'values': [
						{'id': 'left', 'title': 'Left', 'icon': '/cms/supra/img/htmleditor/align-left.png'},
						{'id': 'middle', 'title': 'Center', 'icon': '/cms/supra/img/htmleditor/align-center.png'},
						{'id': 'right', 'title': 'Right', 'icon': '/cms/supra/img/htmleditor/align-right.png'}
					]},
					{'id': 'style', 'type': 'SelectList', 'label': 'Style', 'value': 'default', 'values': [
						{'id': '', 'title': 'Normal', 'icon': '/cms/supra/img/htmleditor/image-style-normal.png'},
						{'id': 'border', 'title': 'Border', 'icon': '/cms/supra/img/htmleditor/image-style-border.png'},
						{'id': 'lightbox', 'title': 'Lightbox', 'icon': '/cms/supra/img/htmleditor/image-style-lightbox.png'}
					]}
				]
			};
			
			var form = new Supra.Form(form_config);
				form.render(content);
				form.get('boundingBox').addClass('yui3-form-properties');
				form.hide();
			
			//On title, description, etc. change update image data
			form.getInput('title').on('change', this.onPropertyChange, this);
			form.getInput('description').on('change', this.onPropertyChange, this);
			form.getInput('align').on('change', this.onPropertyChange, this);
			form.getInput('style').on('change', this.onPropertyChange, this);
			
			//When gallery looses focus hide settings form
			this.htmleditor.on('selectionChange', this.settingsFormApply, this);
			
			//Form heading
			var heading = Y.Node.create('<h2>Gallery properties</h2>');
			form.get('contentBox').insert(heading, 'before');
			
			
			//Buttons
			var buttons = Y.Node.create('<div class="yui3-form-buttons"></div>');
			form.get('contentBox').insert(buttons, 'before');
			
			//Save button
			var btn = new Supra.Button({'label': 'Apply', 'style': 'mid-blue'});
				btn.render(buttons).on('click', this.settingsFormApply, this);
			
			//Cancel button
			var btn = new Supra.Button({'label': 'Close', 'style': 'mid'});
				btn.render(buttons).on('click', this.settingsFormCancel, this);
			
			
			//Add 'Delete' and 'Replace buttons'
			//Replace button
			var btn = new Supra.Button({'label': 'Manage images', 'style': 'mid'});
				btn.render(form.get('contentBox'));
				btn.addClass('yui3-button-edit');
				btn.on('click', this.openGalleryManager, this);
			
			//Delete button
			var btn = new Supra.Button({'label': 'Delete', 'style': 'mid-red'});
				btn.render(form.get('contentBox'));
				btn.addClass('yui3-button-delete');
				btn.on('click', this.removeSelectedGallery, this);
			
			this.settings_form = form;
			return form;
		},
		
		/**
		 * Open gallery manager and update data when it closes
		 */
		openGalleryManager: function () {
			var gallery_id = this.selected_gallery_id,
				gallery_data = this.htmleditor.getData(gallery_id);
			
			//Hide media library if it's opened
			Manager.MediaSidebar.hide();
			
			//Hide settings form
			this.hideSettingsForm();
			
			//Show gallery
			SU.Manager.executeAction('GalleryManager', gallery_data, Y.bind(function (gallery_data, changed) {
				if (changed && this.selected_gallery) {
					gallery_data.type = this.NAME;
					
					this.htmleditor.setData(gallery_id, gallery_data);
					this.setGalleryProperty('images', gallery_data.images);
				}
				
				//Show settings form again
				Manager.getAction('PageContentSettings').execute(this.settings_form);
			}, this));
		},
		
		/**
		 * Returns true if form is visible, otherwise false
		 */
		hideSettingsForm: function () {
			if (this.settings_form && this.settings_form.get('visible')) {
				Manager.PageContentSettings.hide();
			}
		},
		
		/**
		 * Apply settings changes
		 */
		settingsFormApply: function () {
			if (this.settings_form.get('visible')) {
				if (this.selected_gallery) {
					this.selected_gallery.removeClass('yui3-gallery-selected');
				}
				this.selected_gallery = null;
				this.selected_gallery_id = null;
				this.original_data = null;
				
				this.hideSettingsForm();
				
				//Property changed, update editor 'changed' state
				this.htmleditor._changed();
			}
		},
		
		/**
		 * Cancel settings changes
		 */
		settingsFormCancel: function () {
			if (this.settings_form.get('visible')) {
				
				var gallery_id = this.selected_gallery_id,
					oldData = this.htmleditor.getData(gallery_id),
					data = this.original_data;
				
				//Restore old data
				this.htmleditor.setData(gallery_id, data);
				
				//Apply original properties to image if changed
				this.setGalleryProperty('images', data.images);
				for(var i in data) {
					if (typeof data[i] == 'string' && oldData[i] != data[i]) {
						this.setGalleryProperty(i, data[i]);
					}
				}
				
				if (this.selected_gallery) {
					this.selected_gallery.removeClass('yui3-gallery-selected');
				}
				this.selected_gallery = null;
				this.selected_gallery_id = null;
				this.original_data = null;
				
				this.hideSettingsForm();
			}
		},
		
		/**
		 * Remove selected gallery
		 */
		removeSelectedGallery: function () {
			if (this.selected_gallery) {
				this.selected_gallery.remove();
				this.selected_gallery = null;
				this.selected_gallery_id = null;
				this.original_data = null;
				this.htmleditor.refresh(true);
				this.hideSettingsForm();
			}
		},
		
		/**
		 * Handle property input value change
		 * Save data and update UI
		 * 
		 * @param {Object} event Event
		 */
		onPropertyChange: function (event) {
			if (this.silent || !this.selected_gallery) return;
			
			var target = event.target,
				id = target.get('id'),
				gallery_id = this.selected_gallery_id,
				data = this.htmleditor.getData(gallery_id),
				value = target.getValue();
			
			//Update image data
			if (gallery_id) {
				data[id] = value;
				this.htmleditor.setData(gallery_id, data);
			}
			
			this.setGalleryProperty(id, value);
		},
		
		/**
		 * Update image tag property
		 * 
		 * @param {String} id Property ID
		 * @param {String} value Property value
		 */
		setGalleryProperty: function (id, value) {
			if (id == 'title') {
				this.selected_gallery.setAttribute('title', value);
			} else if (id == 'description') {
				this.selected_gallery.setAttribute('alt', value);
			} else if (id == 'align') {
				this.selected_gallery.setAttribute('align', value);
				this.selected_gallery.removeClass('align-left').removeClass('align-right').removeClass('align-middle').addClass('align-' + value);
			} else if (id == 'style') {
				//Say what???
				//@TODO
			} else if (id == 'images') {
				if (!value.length) return this.removeSelectedGallery();
				var image = value[0];
				this.selected_gallery.setAttribute('src', this.getImageURLBySize(image));
				
				//Fix 'style' ?
				//@TODO
			}
		},
		
		/**
		 * Returns image property value
		 * 
		 * @param {String} id
		 */
		getGalleryProperty: function (id) {
			if (this.selected_gallery) {
				var data = this.htmleditor.getData(this.selected_gallery_id);
				return id in data ? data[id] : null;
			}
			return null;
		},
		
		/**
		 * Show image settings bar
		 */
		showGallerySettings: function (event) {
			if (!event.target.test('.gallery')) return;
			
			Manager.getAction('PageContentSettings').execute(this.settings_form || this.createSettingsForm());
			
			var data = this.htmleditor.getData(event.target);
			this.selected_gallery = event.target;
			this.selected_gallery.addClass('yui3-gallery-selected');
			this.selected_gallery_id = this.selected_gallery.getAttribute('id');
			this.original_data = Supra.mix({}, data);
			
			this.silent = true;
			this.settings_form.resetValues()
							  .setValues(data, 'id');
			this.silent = false;
			
			event.halt();
		},
		
		/**
		 * Convert image into gallery
		 * 
		 * @param {Object} event
		 */
		convertImageToGallery: function (target, new_image_data) {
			var htmleditor = this.htmleditor;
			
			if (!htmleditor.get('disabled') && htmleditor.isSelectionEditable(htmleditor.getSelection())) {
				var gallery_id = target.getAttribute('id'),
					image_data = htmleditor.getData(gallery_id),
					gallery_data,
					images = [image_data.image];
				
				//Ask 'image' plugin to clean up after itself
				htmleditor.pluginsCleanUpNode(target);
				
				//Images can't repeat in gallery
				if (image_data.image.id != new_image_data.id) {
					images.push(new_image_data);
				}
				
				gallery_data = Supra.mix({}, defaultProps, {
					'type': this.NAME,
					'align': image_data.align,
					'style': image_data.style,
					'title': image_data.title,
					'description': image_data.description,
					'images': images
				});
				
				//Save data
				this.htmleditor.setData(target, gallery_data);
				
				//Set properties
				target.addClass('gallery');
				
				this.selected_gallery = target;
				this.setGalleryProperty('images', gallery_data.images);
				
				for(var i in gallery_data) {
					if (typeof gallery_data[i] == 'string') {
						this.setGalleryProperty(i, gallery_data[i]);
					}
				}
				
				this.selected_gallery = null;
			}
		},
		
		/**
		 * Add image to gallery after it was dropped using HTML5 drag & drop
		 * 
		 * @param {Object} target Drop target
		 * @param {Number} image_id Image ID
		 */
		dropImage: function (target, image_id) {
			var image_data = null,
				htmleditor = this.htmleditor;
			
			if (typeof image_id == 'object') {
				image_data = image_id;
			} else {
				image_data = Manager.MediaSidebar.getData(image_id);
			}
			
			if (target.test('img.gallery')) {
				//If there already is gallery then add another image to it
				var gallery_data = htmleditor.getData(target);
				
				//Add only if image is not already in gallery
				if (!this.isInImages(image_data.id, gallery_data.images)) {
					gallery_data.images.push(image_data);
					htmleditor.setData(target, gallery_data);
				}
			} else {
				//Convert target (IMG) into gallery node
				this.convertImageToGallery(target, image_data);
			}
		},
		
		/**
		 * Add gallery if folder was dropped using HTML5 drag & drop
		 */
		dropFolder: function (e) {
			var gallery_id = e.drag_id,
				target = e.drop;
			
			//Prevent default (which is insert folder thumbnail image) 
			e.halt();
			
			//If there is no folder or trying to drop on un-editable element
			if (!gallery_id || !this.htmleditor.isEditable(target)) return;
			
			var htmleditor = this.htmleditor,
				folder_data = Manager.MediaSidebar.getData(gallery_id, true);
			
			if (folder_data.type != SU.MediaLibraryData.TYPE_FOLDER) {
				//Only handling folders; images should be handled by image plugin 
				return;
			}
			
			var uid = htmleditor.generateDataUID(),
				image_data = [],
				image;
			
			//Get first image data
			for(var i in folder_data.children) {
				image = folder_data.children[i];
				if (image.type == SU.MediaLibraryData.TYPE_IMAGE) {
					if (!this.isInImages(image.id, image_data)) {
						image_data.push(folder_data.children[i]);
					}
				}
			}
			
			//No images in gallery
			if (!image_data.length) return;
			
			var url = this.getImageURLBySize(image_data[0]);
			
			//Create image
			var img = Y.Node.create('<img class="gallery" id="' + uid + '" src="' + url + '" title="' + Y.Lang.escapeHTML(image_data[0].title) + '" alt="' + Y.Lang.escapeHTML(image_data[0].description) + '" />');
			
			if (target.test('em,i,strong,b,s,strike,sub,sup,u,a,span,big,small,img')) {
				target.insert(img, 'before');
			} else {
				target.prepend(img);
			}
			
			//Set additional gallery properties
			var data = Supra.mix({}, defaultProps, {
				'type': this.NAME,
				'title': image_data[0].title,
				'description': image_data[0].description,
				'images': image_data
			});
			
			//Save into HTML editor data about gallery
			htmleditor.setData(uid, data);
		},
		
		/**
		 * Returns image url matching size
		 * 
		 * @param {Object} data
		 * @param {String} size
		 */
		getImageURLBySize: function (data, size) {
			var size = size ? size : this.configuration.size;
			
			if (size in data.sizes) {
				return data.sizes[size].external_path;
			}
			
			return null;
		},
		
		/**
		 * Returns true if image with given ID is in list
		 * 
		 * @param {Number} image_id
		 * @param {Array} images
		 * @return True if image is in array, otherwise false
		 * @type {Boolean}
		 */
		isInImages: function (image_id, images) {
			if (typeof image_id == 'object') image_id = image_id.id;
			
			for(var i=0,ii=images.length; i<ii; i++) {
				if (images[i].id == image_id) return true;
			}
			return false;
		},
			
		/**
		 * Bind HTML5 Drag & Drop event listeners
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 */
		bindUIDnD: function (htmleditor) {
			//Allow HTML5 Drag & Drop
			var srcNode = htmleditor.get('srcNode');
			
			srcNode.on('imageDrop', function (e) {
				var image_id = e.drag_id,
					image = e.drag,
					target = e.drop;
				
				if (!image_id && !image) {
					return;
				}
				
				if (target.test('IMG')) {
					//Prevent image / text from actually droping
					e.halt();
					
					if (image_id && image_id.match(/^\d+$/)) {
						//Image was dropped from MediaSidebar
						
						var image_data = Manager.MediaSidebar.getData(image_id);
						if (image_data.type == SU.MediaLibraryData.TYPE_FOLDER) {
							//Folder was dropped from MediaSidebar
							//Get folder data with all children
							image_data = Manager.MediaSidebar.getData(image_id, true);
							
							if (target) {
								for(var i in image_data.children) {
									this.dropImage(target, image_data.children[i]);
								}
							}
						} else {
							//Image was dropped from MediaSidebar
							if (target) {
								this.dropImage(target, image_id);
							}
						}
					} else {
						//Image in content was dragged and dropped
						if (image) {
							//Get image ID
							image_id = image.getAttribute('id');
						}
						
						if (image_id) {
							image = srcNode.one('#' + image_id);
							image_data = htmleditor.getData(image);
							
							if (image_data.type == 'image') {
								//Image was dropped
								this.dropImage(target, image_data.image);
							} else if (image_data.type == this.NAME) {
								//Gallery was dropped, combine them
								var images = image_data.images;
								for(var i=0,ii=images.length; i<ii; i++) {
									this.dropImage(target, images[i]);
								}
							}
							
							//Remove image which was dragged
							image.remove();
						}
					}
				}
			}, this);
			
			srcNode.on('imageDrop', this.dropFolder, this);
		},
		
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor, configuration) {
			// When clicking on gallery image show gallery settings
			var container = htmleditor.get('srcNode');
			container.delegate('click', Y.bind(this.showGallerySettings, this), 'img.gallery');
			
			this.bindUIDnD(htmleditor);
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {
			
		},
		
		/**
		 * Process HTML and replace all nodes with macros {supra.gallery id="..."}
		 * Called before HTML is saved
		 * 
		 * @param {String} html
		 * @return Processed HTML
		 * @type {HTML}
		 */
		processHTML: function (html) {
			var htmleditor = this.htmleditor,
				NAME = this.NAME;
			
			html = html.replace(/<img [^>]*id="([^"]+)"[^>]*>/ig, function (html, id) {
				if (!id) return html;
				var data = htmleditor.getData(id);
				
				if (data && data.type == NAME) {
					return '{supra.' + NAME + ' id="' + id + '"}';
				} else {
					return html;
				}
			});
			return html;
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
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});