//Invoke strict mode
"use strict";

/**
 * Plugin to add drag and drop support to media list
 */
YUI().add('supra.medialibrary-list-dd', function (Y) {
	
	/*
	 * Shortcuts
	 */
	var TYPE_FOLDER = Supra.MediaLibraryData.TYPE_FOLDER,
		TYPE_IMAGE  = Supra.MediaLibraryData.TYPE_IMAGE;
	
	/**
	 * Add drag and drop support from media library to other actions
	 */
	function Plugin (config) {
		Plugin.superclass.constructor.apply(this, arguments);
	}
	
	Plugin.NAME = 'medialist-dd';
	Plugin.NS = 'dd';
	
	Y.extend(Plugin, Y.Plugin.Base, {
		
		/**
		 * Add event listeners
		 */
		initializer: function () {
			this.get('host').on('itemRender', this.onItemRender, this);
		},
		
		/**
		 * On item render add drag & drop support
		 */
		onItemRender: function (event) {
			var node = event.node,
				items = node.all('li'),
				image = node.one('div.preview img');
			
			//Drag preview image
			if (image) {
				image.setAttribute('draggable', 'true');
				image.on('dragstart', this.onDragStart, this);
			}
			
			//Drag list items
			items.each(function (item, index, items) {
				item.setAttribute('draggable', 'true');
				item.on('dragstart', this.onDragStart, this);
			}, this);
		},
		
		/**
		 * Handle drag start event
		 * 
		 * @param {Object} e
		 */
		onDragStart: function (e) {
			var target = e.target.closest('LI, .image');
			if (!target) return;
			
			var widget = this.get('host'),
				item_id = target.getData('itemId'),
				data = widget.get('dataObject').getData(item_id);
			
			e._event.dataTransfer.effectAllowed = 'copy';
			e._event.dataTransfer.setData('text', String(item_id));	// Use text to transfer item ID
			
			//Load data
			if (data.type == TYPE_FOLDER && !data.children) {
				//If dragging folder all content must be loaded
				widget.load(item_id);
			} else if (data.type == TYPE_IMAGE && !data.sizes) {
				//Load image data
				widget.load(item_id);
			}
		}
		
	});
	
	
	Supra.MediaLibraryList.DD = Plugin;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['plugin', 'supra.medialibrary-list']});