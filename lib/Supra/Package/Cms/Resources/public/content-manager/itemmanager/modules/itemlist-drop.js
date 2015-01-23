YUI.add('itemmanager.itemlist-drop', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.PageContent;
	
	//ClassNames
	var CLASSNAME_OVER_LIST = 'supra-itemmanager-over-list',
		CLASSNAME_OVER_ITEM = 'supra-itemmanager-over-item';
	
	/*
	 * Editable content
	 */
	function ItemListDrop (config) {
		ItemListDrop.superclass.constructor.apply(this, arguments);
	}
	
	ItemListDrop.NAME = 'itemmanager-itemlist-drop';
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
				container = itemlist.get('contentElement');
			
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
				container = itemlist.get('contentElement'),
				doc  = null,
				body = null;
			
			if (!container) {
				// Nothing to attach listeners to
				return;
			}
			
			container.on('dragenter', this.listDragChange, this);
			container.on('dragleave', this.listDragChange, this);
			
			
			//Drop from media library, add image or images
			doc = itemlist.get('iframe').get('doc');
			body = Y.Node(doc.body);
			
			body.on('dataDrop', this.onImageDrop, this);
			
			this.dropPlugin = new Supra.DragDropTarget({
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
				//highlight = itemList.highlight,
				target = null,
			
				item_id = e.drag_id,
				dataObject = Manager.MediaSidebar.dataObject(),
				replace_id = null,
				onLoadComplete;
			
			onLoadComplete = function (data) {
				if (replace_id) {
					//Replace with first image, all other add to the list
					this.updateItemInCollection(data, replace_id);
					replace_id = null;
				} else {
					this.addItemToCollection(data);
				}
			};
			
			//Hide highlight if it's shown
			// highlight.hideHighlight();
			
			//Check if image was dropped on existing item
			target = e.drop.closest('[data-item]');
			
			if (target) {
				replace_id = target.getAttribute('data-item');
			}
			
			//Load data
			if (item_id) {
				dataObject.any(item_id, true).done(function (data) {
					
					if (!Y.Lang.isArray(data)) {
						data = [data];
					}
					
					var folderHasImages = false,
						image = null;
					
					for (var i=0, ii=data.length; i < ii; i++) {
						image = data[i];
						
						if (image.type == Supra.MediaLibraryList.TYPE_IMAGE) {
							dataObject.one(image.id, true).done(onLoadComplete, this);
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
			}
			
			//Prevent default (which is insert folder thumbnail image) 
			if (e.halt) e.halt();
			
			return false;
		},
		
		/**
		 * Add item
		 */
		addItemToCollection: function (image) {
			var property = this.getImagePropertyName(),
				data,
				itemlist,
				item_data,
				input;
			
			if (property) {
				// Change format to have crop and size info
				data = Y.DataType.Image.parse(image);
				itemlist = this.get('host');
				
				// Prevent item from being opened for editing
				itemlist.initializing = true;
				item_data = itemlist.addItem();
				itemlist.initializing = false;
				
				input = this.get('host').getItemInput(item_data.__suid, property);
				input.insertImage(data, true /* prevent editing */);
			}
		},
		
		updateItemInCollection: function (image, item_id) {
			var property = this.getImagePropertyName(),
				data = {},
				input,
				old_data;
			
			if (property) {
				// Change format to have crop and size info
				data = Y.DataType.Image.parse(image);
				input = this.get('host').getItemInput(item_id, property);
				input.insertImage(data, true /* prevent editing */);
			}
		},
		
		/**
		 * Returns first image property name
		 *
		 * @returns {String|Null} Property name or null
		 */
		getImagePropertyName: function () {
			var properties = this.get('host').get('properties'),
				i = 0,
				ii = properties.length,
				type;
			
			for (; i < ii; i++) {
				type = properties[i].type;
				
				if (type === 'InlineImage' || type === 'BlockBackground') {
					return properties[i].id;
				}
			}
			
			return null;
		},
		
		
		/* ----------------------- LIST DRAG OVER ----------------------- */
		
		
		listDragChange: function (e) {
			var itemList = this.get('host'),
				container = itemList.get('contentElement'),
				
				doc = this.get('host').get('iframe').get('doc'),
				element = Y.Node(doc.elementFromPoint(e.clientX, e.clientY)),
				
				over = this.listDragOver;
			
			if (element.closest('[data-item]')) {
				if (over !== 'item') {
					this.listDragOver = 'item';
				}
			} else if (element.closest(container)) {
				if (over !== 'list') {
					this.listDragOver = 'list';
				}
			} else {
				if (over !== 'none') {
					this.listDragOver = 'none';
				}
			}
		}
		
	});
	
	Supra.ItemManagerItemListDrop = ItemListDrop;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'dd-delegate']});
