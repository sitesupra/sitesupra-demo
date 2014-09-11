/**
 * Plugin to add drag and drop support to media list
 */
YUI().add('supra.medialibrary-list-dd', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * Constants
	 */
	var DRAG_API_SUPPORTED = typeof document.body.draggable === 'boolean',
		IE_API_SUPPORTED = !!document.body.dragDrop;
	
	/*
	 * Shortcuts
	 */
	var TYPE_FOLDER = Supra.MediaLibraryList.TYPE_FOLDER,
		TYPE_IMAGE  = Supra.MediaLibraryList.TYPE_IMAGE;
	
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
				
				if (!DRAG_API_SUPPORTED && IE_API_SUPPORTED) {
					item.on('selectstart', this.onDragStartIE, this);
				}
			}, this);
		},
		
		/**
		 * IE fallback if it doesn't support drag api, eq. IE9
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		onDragStartIE: function (e) {
			var node = e.target.closest('li');
			if (node) {
				node.getDOMNode().dragDrop();
				this.onDragStart(e);
				
				return false;
			}
		},
		
		/**
		 * Handle drag start event
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		onDragStart: function (e) {
			var target = e.target.closest('LI, .image');
			
			if (!target || !this.get('host').get('dndEnabled')) {
				return;
			}
			
			var widget  = this.get('host'),
				item_id = target.getData('itemId'),
				data    = widget.get('data');
			
			if (e._event.dataTransfer) {
				e._event.dataTransfer.effectAllowed = 'copy';
				e._event.dataTransfer.setData('text', String(item_id));	// Use text to transfer item ID
			}
			
			//Load data
			data.any(item_id, true);
		}
		
	});
	
	
	Supra.MediaLibraryList.DD = Plugin;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['plugin', 'supra.medialibrary-list']});