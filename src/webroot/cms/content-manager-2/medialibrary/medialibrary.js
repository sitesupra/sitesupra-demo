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
				'slideshowClass': Supra.MediaLibrarySlideshow
			})).render();
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
					break;
				case 'mlfolder':
					var folder = this.medialist.getSelectedFolder();
					
					break;
				case 'mldelete':
					break;
				case 'mlundo':
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