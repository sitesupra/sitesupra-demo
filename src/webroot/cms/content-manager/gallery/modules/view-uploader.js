YUI.add('gallery.view-uploader', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.PageContent;
	
	/*
	 * Editable content
	 */
	function ViewUploader (config) {
		ViewUploader.superclass.constructor.apply(this, arguments);
	}
	
	ViewUploader.NAME = 'gallery-view-uploader';
	ViewUploader.NS = 'uploader';
	
	ViewUploader.ATTRS = {
		
		'disabled': {
			value: false
		}
		
	};
	
	Y.extend(ViewUploader, Y.Plugin.Base, {
		
		/**
		 * Supra.Uploader instance
		 * @type {Object}
		 * @private
		 */
		uploader: null,
		
		/**
		 * Image preview size
		 * @type {String}
		 * @private
		 */
		PREVIEW_SIZE: '200x200',
		
		/**
		 * Image upload folder when using drag and drop from desktop
		 * @type {String}
		 * @private
		 */
		image_upload_folder: 0,
		
		/**
		 * Image id to view ID mapping
		 * @type {Object}
		 * @private
		 */
		ids: {},
		
		/**
		 * In case of multiple images this is ID of last uploaded one
		 * @type {String}
		 * @private
		 */
		last_item_id: null,
		
		/**
		 * 
		 */
		initializer: function () {
			var view = this.get('host'),
				container = view.get('listNode');
			
			this.listeners = [];
			this.listeners.push(view.after('listNodeChange', this.reattachListeners, this));
			
			if (container) {
				this.reattachListeners();
			}
		},
		
		destructor: function () {
			this.resetAll();
			
			// Listeners
			var listeners = this.listeners,
				i = 0,
				ii = listeners.length;
			
			for (; i<ii; i++) listeners[i].detach();
			this.listeners = null;
		},
		
		/**
		 * Attach drag and drop listeners
		 */
		reattachListeners: function () {
			var view = this.get('host'),
				container = view.get('listNode'),
				//doc = null,
				target = null,
				image_upload_folder = this.image_upload_folder;
			
			if (this.uploader) {
				this.uploader.set('disabled', this.get('disabled'));
				return false;
			}
			if (!container) {
				return false;
			}
			
			//Create uploader
			//doc = view.getDocument();
			target = view.getWrapperNode();
			
			var uploadData = this.getUploaderFileUploadData();
				uploadData.force = true;
			
			this.uploader = new Supra.Uploader({
				'clickTarget': null,
				'dropTarget': target,
				
				'allowBrowse': false,
				'allowMultiple': true,
				'accept': 'image/*',
				
				'requestUri': Manager.getAction('MediaLibrary').getDataPath('upload'),
				'uploadFolderId': image_upload_folder,
				
				'data': uploadData
			});
			
			this.uploader.on('file:upload', this.onFileUploadStart, this);
			this.uploader.on('file:complete', this.onFileUploadEnd, this);
			this.uploader.on('file:error', this.onFileUploadError, this);
		},
		
		/**
		 * Reset all iframe content bindings, etc.
		 */
		resetAll: function () {
			var uploader = this.uploader;
			
			if (uploader) {
				uploader.destroy(true);
				this.uploader = null;
			}
		},
		
		/**
		 * Returns layout which has image property
		 */
		getLayout: function () {
			var gallery = this.get('host').get('host'),
				layouts = gallery.layouts,
				layout  = null;
				
			layout = layouts.getLayoutByPropertyType('InlineImage');
			
			if (!layout) {
				layout = layouts.getLayoutByPropertyType('InlineMedia', function (property) {
					// Must allow images to be added
					return property.allowImage !== false;
				});
			}
			if (!layout) {
				layout = layouts.getLayoutByPropertyType('Image');
			}
			
			return layout;
		},
		
		getProperty: function () {
			var settings = this.get('host').get('host').settings,
				property = null;
			
			property = settings.getPropertyByType('InlineImage');
			
			if (!property) {
				property = settings.getPropertyByType('InlineMedia', function (property) {
					// Must allow images to be added
					return property.allowImage !== false;
				});
			}
			if (!property) {
				property = settings.getPropertyByType('Image');
			}
			
			return property;
		},
		
		/**
		 * Returns image size based on other items
		 */
		resizeNewItemData: function (layout, property, image, old_id) {
			var data = this.get('host').get('host').data,
				i    = 0,
				ii   = data.getSize(),
				item = null,
				crop_width = image.crop_width,
				crop_height = image.crop_height,
				size_width = image.size_width,
				size_height = image.size_height,
				ratio = size_width / size_height,
				found = false;
			
			if (property.type != 'InlineImage' && property.type != 'InlineMedia') {
				return null;
			}
			
			for (; i<ii; i++) {
				item = data.getSlideByIndex(i);
				
				if (item[property.id] && item.layout == layout.id) {
					crop_width  = Math.min(crop_width,  item[property.id].crop_width);
					crop_height = Math.min(crop_height, item[property.id].crop_height);
					found = true;
					break;
				}
			}
			
			if (!found) {
				// Try using a node from temporary item
				var node = this.get('host').getNodeById(old_id),
					target = node.one('[data-supra-item-property="' + property.id + '"]');
				
				if (target) {
					crop_width  = Math.min(crop_width, target.get('offsetWidth') || crop_width);
					crop_height = ~~(crop_width / ratio);
				}
			}
			
			// Resize size to be as small as possible, while still covering
			// the area
			if (size_width > crop_width && size_height > crop_height) {
				size_width = crop_width;
				size_height = ~~(size_width / ratio);
				
				if (size_height < crop_height) {
					size_height = crop_height;
					size_width = ~~(size_height * ratio);
				}
			}
			
			// Center image
			if (size_width > crop_width) {
				image.crop_left = ~~((size_width - crop_width) / 2);
			}
			if (size_height > crop_height) {
				image.crop_top  = ~~((size_height - crop_height) / 2);
			}
			
			image.crop_width = crop_width;
			image.crop_height = crop_height;
			image.size_width = size_width;
			image.size_height = size_height;
			return image;
		},
			
			
		/* ------------------------ FILE UPLOAD ------------------------ */
		
		
		/**
		 * Returns data which will be sent when uploading file
		 * Needed for upload to include correct preview size
		 * 
		 * @private
		 */
		getUploaderFileUploadData: function () {
			var data = {'sizes': []},
				size = null,
				gallery = this.get('host').get('host');
			
			size = this.PREVIEW_SIZE.split('x');
			data.sizes.push({
				'width': size[0],
				'height': size[1],
				'crop': false
			});
			
			return data;
		},
		
		/**
		 * Handle file upload start
		 */
		onFileUploadStart: function (e) {
			var view   = this.get('host'),
				data   = view.get('host').data,
				item   = data.getNewSlideData(),
				id     = null;
			
			if (!layout) return false;
			
			item.layout = this.getLayout().id;
			item.temporary = true;
			
			id = data.addSlide(item, /* silent*/ true);
			this.ids[e.id] = id;
			this.last_item_id = id;
		},
		
		/**
		 * Handle file upload end
		 */
		onFileUploadEnd: function (e) {
			var view     = this.get('host'),
				data     = view.get('host').data,
				image    = e.details[0],
				item     = data.getNewSlideData(),
				layout   = this.getLayout(),
				property = this.getProperty(),
				size     = null,
				ratio    = 0,
				old_id   = this.ids[e.old_id],
				
				title_prop = this.get('host').get('host').settings.getProperty('title'),
				title      = (image.filename || '').replace(/.[^.]*$/, '').replace(/[_-]+/g, ' '),
				
				active   = false;
			
			item.layout = layout.id;
			
			if (property.type == 'InlineMedia' || property.type == 'InlineImage') {
				item[property.id] = this.resizeNewItemData(layout, property, {
					'align': '',
					'crop_left': 0,
					'crop_top': 0,
					'crop_width':  image.sizes.original.width,
					'crop_height': image.sizes.original.height,
					'size_width':  image.sizes.original.width,
					'size_height': image.sizes.original.height,
					'type': 'image',
					'style': '',
					'title': title,
					'image': image
				}, old_id);
			} else {
				item[property.id] = image;
			}
			
			if (title_prop && (title_prop.type == 'InlineText' || title_prop.type == 'InlineString' || title_prop.type == 'String')) {
				item.title = title;
			}
			
			if (this.last_item_id == old_id) {
				active = true;
			}
			
			data.removeSlideById(old_id);
			data.addSlide(item, !active);
		},
		
		/**
		 * Handle file upload error
		 */
		onFileUploadError: function (e) {
			var view   = this.get('host'),
				data   = view.get('host').get('data');
			
			data.removeSlideById(e.id);
		}
		
	});
	
	Supra.GalleryViewUploader = ViewUploader;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'supra.uploader']});