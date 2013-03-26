YUI.add('gallerymanager.itemlist-uploader', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.PageContent;
	
	/*
	 * Editable content
	 */
	function ItemListUploader (config) {
		ItemListUploader.superclass.constructor.apply(this, arguments);
	}
	
	ItemListUploader.NAME = 'gallerymanager-itemlist-uploader';
	ItemListUploader.NS = 'uploader';
	
	ItemListUploader.ATTRS = {
		
		'disabled': {
			value: false
		}
		
	};
	
	Y.extend(ItemListUploader, Y.Plugin.Base, {
		
		/**
		 * Supra.Uploader instance
		 * @type {Object}
		 * @private
		 */
		uploader: null,
		
		
		/**
		 * 
		 */
		initializer: function () {
			var itemlist = this.get('host'),
				container = itemlist.get('listNode');
			
			this.listeners = [];
			this.listeners.push(itemlist.after('listNodeChange', this.reattachListeners, this));
			
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
			var itemlist = this.get('host'),
				container = itemlist.get('listNode'),
				//doc = null,
				target = null,
				image_upload_folder = null;
			
			if (this.uploader) {
				this.uploader.set('disabled', this.get('disabled'));
				return false;
			}
			if (!container) {
				return false;
			}
			
			//Create uploader
			//doc = itemlist.getDocument();
			target = itemlist.getWrapperNode();
			image_upload_folder = itemlist.get('host').image_upload_folder;
			
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
			
			size = gallery.PREVIEW_SIZE.split('x');
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
			var data = e.details[0],
				itemlist = this.get('host');
			
			itemlist.addItem({
				'id': e.id,
				'image': null,
				'title': e.title,
				'filename': e.filename,
				'temporary': true
			});
		},
		
		/**
		 * Handle file upload end
		 */
		onFileUploadEnd: function (e) {
			var data = e.details[0],
				itemlist = this.get('host');
			
			itemlist.removeItem(e.old_id);
			itemlist.addItem(data);
		},
		
		/**
		 * Handle file upload error
		 */
		onFileUploadError: function (e) {
			var itemlist = this.get('host');
			
			itemlist.removeItem(e.id);
		}
		
	});
	
	Supra.GalleryManagerItemListUploader = ItemListUploader;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'supra.uploader']});