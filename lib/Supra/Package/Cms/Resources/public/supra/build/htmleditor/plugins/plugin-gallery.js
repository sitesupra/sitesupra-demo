/**
 * 
 */
YUI().add('supra.htmleditor-plugin-gallery', function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH],
		
		/* Supported HTML editor use types */
		types: [Supra.HTMLEditor.TYPE_INLINE],
		
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
			if (!Manager.MediaSidebar) return true;
			
			var htmleditor = this.htmleditor,
				dataObject = Manager.MediaSidebar.dataObject(),
				folder_data = dataObject.cache.one(gallery_id);
			
			if (!folder_data || folder_data.type != Supra.MediaLibraryList.TYPE_FOLDER) {
				//Only handling folders; images should be handled by image plugin 
				return;
			}
			
			//Prevent default (which is insert folder thumbnail image) 
			if (e.halt) e.halt();
			
			var image_data = [],
				loaded = 0,
				count  = 0;
			
			var checkComplete = Y.bind(function () {
				if (count && loaded == count) {
					if (Manager.PageContent) {
						this.insertGalleryBlock(image_data);
					}
				}
			}, this);
			
			//Load all image data
			dataObject.all(gallery_id).done(function (images) {
				
				var loadDone = function (image) {
					image_data.push(image);
					loaded++;
					checkComplete();
				};
				var loadFail = function () {
					count--;
					checkComplete();
				};
				
				for(var i=0, ii=images.length; i<ii; i++) {
					if (images[i].type == Supra.MediaLibraryList.TYPE_IMAGE) {
						count++;
						dataObject.one(images[i].id, true).done(loadDone).fail(loadFail);
					}
				}
				
				checkComplete();
				
			}, this);
			
			return false;
		},
		
		insertGalleryBlock: function (images) {
			var content = Manager.PageContent.getContent().get('activeChild'),
				list = content.get('parent'),
				gallery_block_id = this.configuration.galleryBlockId;
			
			//If list is closed or gallery is not a valid child type then cancel
			if (list.isClosed() || !list.isChildTypeAllowed(gallery_block_id)) return;
			
			//Save and close current block
			content.fire('editing-end');
			
			//Insert block
			list.get('super').getBlockInsertData({
				'type': gallery_block_id,
				'placeholder_id': list.getId()
			}, function (data) {
				Manager.PageToolbar.setActiveAction("Page");
				this.createChildFromData(data);
					
				//Add images to gallery block
				var block = this.get('super').get('activeChild');
				if (Y.Lang.isFunction(block.addImage)) {
					for(var i=0,ii=images.length; i<ii; i++) {
						block.addImage(images[i]);
					}
					
					if (Y.Lang.isFunction(block.reloadContent())) {
						block.reloadContent();
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
			var block_data = Manager.Blocks.getBlock(configuration.galleryBlockId);
			if (configuration.galleryBlockId && block_data.classname) {
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