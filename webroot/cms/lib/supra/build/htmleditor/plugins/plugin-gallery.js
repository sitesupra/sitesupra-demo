/**
 * 
 */
YUI().add('supra.htmleditor-plugin-gallery', function (Y) {
	
	/**
	 * Default gallery image properties
	 */
	var DEFAULT_IMAGE_PROPERTIES = [
		{'id': 'title', 'type': 'String', 'label': Supra.Intl.get(['htmleditor', 'label_title']), 'value': ''}
	];
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH],
		
		/* Default image size */
		size: '200x200',
		
		/* Gallery block id */
		galleryBlockId: null
	};
	
	var Manager = Supra.Manager;
	
	Supra.HTMLEditor.addPlugin('gallery', defaultConfiguration, {
		
		/**
		 * Add gallery if folder was dropped using HTML5 drag & drop
		 */
		dropFolder: function (e) {
			var gallery_id = e.drag_id,
				target = e.drop;
			
			//If there is no folder or trying to drop on un-editable element
			if (!gallery_id || !this.htmleditor.isEditable(target)) return;
			
			var htmleditor = this.htmleditor,
				folder_data = Manager.MediaSidebar.getData(gallery_id, true);
			
			if (!folder_data || folder_data.type != Supra.MediaLibraryData.TYPE_FOLDER) {
				//Only handling folders; images should be handled by image plugin 
				return;
			}
			
			//Prevent default (which is insert folder thumbnail image) 
			if (e.halt) e.halt();
			
			
			var image_data = [],
				image;
			
			//Get first image data
			for(var i in folder_data.children) {
				image = folder_data.children[i];
				if (image.type == Supra.MediaLibraryData.TYPE_IMAGE) {
					image_data.push(folder_data.children[i]);
				}
			}
			
			//No images in gallery
			if (!image_data.length) return;
			
			//Get list
			if (Manager.PageContent) {
				this.insertGalleryBlock(image_data);
			}
			
		},
		
		insertGalleryBlock: function (images) {
			var list = Manager.PageContent.getContent().get('activeChild').get('parent'),
				gallery_block_id = this.configuration.galleryBlockId;
			
			//If list is closed or gallery is not a valid child type then cancel
			if (list.isClosed() || !list.isChildTypeAllowed(gallery_block_id)) return;
			
			//Insert block
			list.get('super').getBlockInsertData({
				'type': gallery_block_id,
				'placeholder_id': list.getId()
			}, function (data) {
				this.createChildFromData(data);
					
				//Add images to gallery block
				var block = this.get('super').get('activeChild');
				if (Y.Lang.isFunction(block.addImage)) {
					for(var i=0,ii=images.length; i<ii; i++) {
						block.addImage(images[i]);
					}
				} else {
					Y.log('Block "' + gallery_block_id + '" doesn\'t have required method "addImage"');
				}
			}, list);
		},
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @param {Object} configuration Plugin configuration
		 * @constructor
		 */
		init: function (htmleditor, configuration) {
			if (configuration.galleryBlockId) {
				//On image folder drop add gallery
				htmleditor.get('srcNode').on('dataDrop', this.dropFolder, this);
			}
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {
			
		}
		
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});