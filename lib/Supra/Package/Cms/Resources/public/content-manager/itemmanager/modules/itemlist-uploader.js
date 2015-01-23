YUI.add('itemmanager.itemlist-uploader', function (Y) {
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
	
	ItemListUploader.NAME = 'itemmanager-itemlist-uploader';
	ItemListUploader.NS = 'uploader';
	
	ItemListUploader.ATTRS = {};
	
	Y.extend(ItemListUploader, Y.Plugin.Base, {
		
		/**
		 * Supra.Uploader instance
		 * @type {Object}
		 * @private
		 */
		uploader: null,
		
		/**
		 * File uploader ids to item ids
		 * @type {Object}
		 * @private
		 */
		ids: null,
		
		
		/**
		 * 
		 */
		initializer: function () {
			var itemlist = this.get('host'),
				container = itemlist.get('contentElement');
			
			this.ids = {};
			
			this.listeners = [];
			this.listeners.push(itemlist.after('contentElementChange', this.reattachListeners, this));
			
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
			
			for (; i < ii; i++) listeners[i].detach();
			this.listeners = null;
		},
		
		/**
		 * Attach drag and drop listeners
		 */
		reattachListeners: function () {
			var itemlist = this.get('host'),
				container = itemlist.get('contentElement'),
				//doc = null,
				target = null;
			
			if (this.uploader) {
				this.uploader.destroy();
				this.uploader = null;
			}
			if (!container) {
				return false;
			}
			
			//Create uploader
			target = itemlist.get('iframe').one('.supra-itemmanager-wrapper');
			
			this.uploader = new Supra.Uploader({
				'dropTarget': target,
				
				'allowBrowse': false,
				'allowMultiple': true,
				'accept': 'image/*',
				
				'requestUri': Supra.Url.generate('media_library_upload'),
				'uploadFolderId': itemlist.get('host').options.imageUploadFolder
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
		 * Handle file upload start
		 */
		onFileUploadStart: function (e) {
			var data = e.details[0],
				itemlist = this.get('host'),
				item = null;
			
			// Prevent item from being opened for editing
			itemlist.initializing = true;
			item = itemlist.addItem({'title': e.title.replace(/\..+$/, '')});
			itemlist.initializing = false;
			
			this.ids[e.id] = item.__suid;
		},
		
		/**
		 * Handle file upload end
		 */
		onFileUploadEnd: function (e) {
			var data = e.details[0],
				itemlist = this.get('host'),
				itemdrop = itemlist.drop;
				
			if (e.old_id in this.ids) {
				itemdrop.updateItemInCollection(data, this.ids[e.old_id]);
				delete(this.ids[e.old_id]);
			} else {
				itemdrop.addItemToCollection(data);
			}
		},
		
		/**
		 * Handle file upload error
		 */
		onFileUploadError: function (e) {
			var itemlist = this.get('host');
			itemlist.removeItem(this.ids[e.id]);
			delete(this.ids[e.id]);
		}
		
	});
	
	Supra.ItemManagerItemListUploader = ItemListUploader;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'supra.uploader']});
