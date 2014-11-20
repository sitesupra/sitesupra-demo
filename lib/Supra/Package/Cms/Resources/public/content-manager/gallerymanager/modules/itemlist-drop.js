YUI.add('gallerymanager.itemlist-drop', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.PageContent;
	
	//ClassNames
	var CLASSNAME_OVER_LIST = 'supra-gallerymanager-over-list',
		CLASSNAME_OVER_ITEM = 'supra-gallerymanager-over-item';
	
	/*
	 * Editable content
	 */
	function ItemListDrop (config) {
		ItemListDrop.superclass.constructor.apply(this, arguments);
	}
	
	ItemListDrop.NAME = 'gallerymanager-itemlist-drop';
	ItemListDrop.NS = 'drop';
	
	ItemListDrop.ATTRS = {
		'disabled': {
			value: false
		}
	};
	
	Y.extend(ItemListDrop, Y.Plugin.Base, {
		
		/**
		 * Mouse is over 'item', 'list' or 'none'
		 * @type {String}
		 * @private
		 */
		listDragOver: null,
		
		
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
		
		resetAll: function () {
			var dropPlugin = this.dropPlugin;
			
			if (dropPlugin) {
				dropPlugin.destroy(true);
				this.dropPlugin = null;
			}
			
			this.listDragOver = 'none';
		},
		
		/**
		 * Attach drag and drop listeners
		 */
		reattachListeners: function () {
			if (this.get('disabled')) return false;
			
			var itemlist = this.get('host'),
				container = itemlist.get('listNode'),
				childSelector = null,
				doc  = null,
				body = null;
			
			if (!container) {
				// Nothing to attach listeners to
				return;
			}
			
			childSelector = itemlist.getChildSelector();
			
			container.on('dragenter', this.listDragEnter, this);
			container.on('dragleave', this.listDragLeave, this);
			container.delegate('dragenter', this.listItemDragEnter, childSelector, this);
			container.delegate('dragleave', this.listItemDragLeave, childSelector, this);
			
			
			//Drop from media library, add image or images
			doc = itemlist.getDocument();
			body = Y.Node(doc.body);
			
			body.on('dataDrop', this.onImageDrop, this);
			
			this.dropPlugin = new Manager.PageContent.PluginDropTarget({
				'srcNode': body,
				'doc': doc
			});
		},
		
		
		/* ----------------------- IMAGE DROP ----------------------- */
		
		
		/**
		 * On image or folder drop add images to the list
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		onImageDrop: function (e) {
			
			if (!Manager.MediaSidebar) {
				//If media sidebar is not loaded, then user didn't droped image from there
				//Prevent default (which is insert folder thumbnail image) 
				if (e.halt) e.halt();
				return false;
			}
			
			var itemList = this.get('host'),
				highlight = itemList.highlight,
				childSelector = itemList.getChildSelector(),
				target = null;
			
			var item_id = e.drag_id,
				dataObject = Manager.MediaSidebar.dataObject(),
				replace_id = null;
			
			//Hide highlight if it's shown
			highlight.hideHighlight();
			
			//Check if image was dropped on existing item
			target = e.drop.closest(childSelector);
			if (target) {
				replace_id = target.getData('item-id');
			}
			
			//Load data
			dataObject.any(item_id, true).done(function (data) {
				
				if (!Y.Lang.isArray(data)) {
					data = [data];
				}
				
				var folderHasImages = false,
					image = null;
				
				for (var i=0, ii=data.length; i<ii; i++) {
					image = data[i];
					
					if (image.type == Supra.MediaLibraryList.TYPE_IMAGE) {
						
						dataObject.one(image.id, true).done(function (data) {
							if (replace_id) {
								//Replace with first image, all other add to the list
								this.get('host').replaceItem(replace_id, data);
								replace_id = null;
							} else {
								this.get('host').addItem(data);
							}
						}, this);
						
						folderHasImages = true;
					}
				}
				
				//folder was without images
				if ( ! folderHasImages) {
					Supra.Manager.executeAction('Confirmation', {
						'message': '{#medialibrary.validation_error.empty_folder_drop#}',
						'useMask': true,
						'buttons': [
							{'id': 'delete', 'label': 'OK'}
						]
					});

					return;
				}
				
			}, this);
			
			//Prevent default (which is insert folder thumbnail image) 
			if (e.halt) e.halt();
			
			return false;
		},
		
		
		/* ----------------------- LIST DRAG OVER ----------------------- */
		
		
		/**
		 * Image or folder from media library dragged over an item or a list
		 * 
		 * @param {Event} e Event facade object
		 * @private 
		 */
		listDragEnter: function (e) {
			var itemList = this.get('host'),
				listNode = itemList.get('listNode'),
				highlight = itemList.highlight,
				childSelector = itemList.getChildSelector(),
				over = 'none',
				target = e.target.closest(childSelector);
			
			if (target) {
				over = 'item';
				highlight.showHighlight(target);
			} else if (e.target.closest(listNode)) {
				over = 'list';
				highlight.showHighlight(listNode);
			}
			
			this.listDragOver = over;
		},
		
		/**
		 * Image or folder from media library dragged out of item
		 * 
		 * @param {Event} e Event facade object
		 * @private 
		 */
		listDragLeave: function (e) {
			var itemList = this.get('host'),
				highlight = itemList.highlight,
				over = this.listDragOver;
			
			if (over && over != 'none') {
				//Left some element
				this.listDragOver = 'none';
			} else {
				//Actually left list
				highlight.hideHighlight();
			}
		},
		
		/* ----------------------- ITEM DRAG OVER ----------------------- */
		
		/**
		 * Image or folder from media library dragged over an item
		 * 
		 * @param {Event} e Event facade object
		 * @private 
		 */
		listItemDragEnter: function (e) {
			if (e._event.dataTransfer.effectAllowed == 'all') {
				// Trying to drop files from desktop, that is handled by uploader
				return;
			}
			
			var itemList = this.get('host'),
				highlight = itemList.highlight,
				childSelector = itemList.getChildSelector(),
				target = null;
			
			if (e.target.test(childSelector)) {
				target = e.target.closest(childSelector);
				highlight.showHighlight(target);
			}
		},
		
		/**
		 * Image or folder from media library dragged out of item
		 * 
		 * @param {Event} e Event facade object
		 * @private 
		 */
		listItemDragLeave: function (e) {
			var itemList = this.get('host'),
				highlight = itemList.highlight,
				childSelector = itemList.getChildSelector();
			
			if (e.target.test(childSelector)) {
				highlight.hideHighlight();
			}
		}
		
	});
	
	Supra.GalleryManagerItemListDrop = ItemListDrop;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'dd-delegate']});