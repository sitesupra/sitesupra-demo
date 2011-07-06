//Invoke strict mode
"use strict";

SU('supra.medialibrary-list-extended', function (Y) {
	
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
	new Action(Action.PluginContainer, {
		
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
		 * Previous editor toolbar state
		 */
		editor_toolbar_visible: false,
		
		
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
			Manager.getAction('PageToolbar').addGroup(this.NAME, TOOLBAR_BUTTONS);
			
			//Add side buttons
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'close',
				'callback': Y.bind(function () {
					this.hide();
				}, this)
			}]);
			
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
			Action.Base.prototype.hide.apply(this, arguments);
			
			Manager.getAction('PageToolbar').unsetActiveGroupAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			//If editor toolbar was visible before, then show it now
			if (this.editor_toolbar_visible) {
				Manager.getAction('Page').showEditorToolbar();
			}
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			Manager.getAction('PageToolbar').setActiveGroupAction(this.NAME);
			Manager.getAction('PageButtons').setActiveAction(this.NAME);
			
			//Hide editor toolbar if it's visible
			if (Manager.getAction('EditorToolbar').get('visible')) {
				this.editor_toolbar_visible = true;
				Manager.getAction('Page').hideEditorToolbar();
			} else {
				this.editor_toolbar_visible = false;
			}
			
			this.show();
		}
	});
	
});