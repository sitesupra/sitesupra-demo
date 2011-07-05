//Invoke strict mode
"use strict";

SU('supra.medialibrary-list-extended', function () {
	
	//Toolbar buttons
	var TOOLBAR_BUTTONS = [
	    {
	        'id': 'mlupload',
			'title': 'Upload',
			'icon': '/cms/supra/img/toolbar/icon-media-upload.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    },
		{
	        'id': 'mlfolder',
			'title': 'New folder',
			'icon': '/cms/supra/img/toolbar/icon-media-folder.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    },
		{
	        'id': 'mldelete',
			'title': 'Delete',
			'icon': '/cms/supra/img/toolbar/icon-media-delete.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    },
		{
	        'id': 'mlundo',
			'title': 'Undo history',
			'icon': '/cms/supra/img/toolbar/icon-media-undo.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    }
	];
	
	//Shortcuts
	var Manager = SU.Manager;
	var Action = Manager.Action;
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'MediaLibrary',
		
		/**
		 * Action has stylesheet, include it
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		
		/**
		 * Media list object
		 * @type {Object}
		 * @private
		 */
		medialist: null,
		
		/**
		 * "Sort by" input node
		 * @type {Object}
		 * @private
		 */
		input_sortby: null,
		
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			//Add buttons to toolbar
			SU.Manager.getAction('PageToolbar').addGroup(this.NAME, TOOLBAR_BUTTONS);
			
			//Create slideshow
			var list = this.medialist = (new Supra.MediaLibraryExtendedList({
				'srcNode': this.getContainer('#mediaLibraryList'),
				'foldersSelectable': true,
				'filesSelectable': false,
				'imagesSelectable': false,
				'requestURI': this.getDataPath() + '.php',
				'saveURI': this.getDataPath('save') + '.php',
				'slideshowClass': Supra.MediaLibrarySlideshow
			})).render();
			
			//Create "Sort by" widget
			var input = this.input_sortby = new Supra.Input.SelectList({
				'srcNode': this.getContainer('#mediaLibrarySort')
			});
			input.render();
			input.on('change', function (event) {
				this.medialist.set('sortBy', event.value);
			}, this);
		},
		
		/**
		 * Handle toolbar buttons click
		 * 
		 * @param {String} button_id Button ID
		 * @private
		 */
		handleToolbarButton: function (button_id) {
			switch (button_id) {
				case 'mlupload':
					//@TODO
					break;
				case 'mlfolder':
					var folder = this.medialist.getSelectedFolder() || {'id': 0};
					if (folder) {
						//Close any opened image or file
						this.medialist.open(folder.id);
						
						//Add folder
						this.medialist.addFolder(null, '');
					}
					break;
				case 'mldelete':
					this.medialist.deleteSelectedItem();
					break;
				case 'mlundo':
					//@TODO
					break;
			}
		},
		
		/**
		 * Hide
		 */
		hide: function () {
			SU.Manager.getAction('PageToolbar').unsetActiveGroupAction(this.NAME);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			SU.Manager.getAction('PageToolbar').setActiveGroupAction(this.NAME);
		}
	});
	
});