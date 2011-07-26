//Invoke strict mode
"use strict";

SU('supra.medialibrary-list-extended', 'supra.medialibrary-upload', function (Y) {
	
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
	
	//Image editor toolbar buttons
	var TOOLBAR_IMAGEEDITOR_BUTTONS = [
	    {
	        'id': 'mlimagerotateleft',
			'title': 'Rotate left',
			'icon': '/cms/supra/img/toolbar/icon-media-rotateleft.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    },
		{
	        'id': 'mlimagerotateright',
			'title': 'Rotate right',
			'icon': '/cms/supra/img/toolbar/icon-media-rotateright.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    },
		{
	        'id': 'mlimagecrop',
			'title': 'Crop',
			'icon': '/cms/supra/img/toolbar/icon-media-crop.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    },
		{
	        'id': 'mlimageundo',
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
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		
		
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
			Manager.getAction('PageToolbar').addGroup(this.NAME + 'imageeditor', TOOLBAR_IMAGEEDITOR_BUTTONS);
			
			//Add side buttons
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'done',
				'callback': Y.bind(function () {
					this.hide();
				}, this)
			}]);
			Manager.getAction('PageButtons').addActionButtons(this.NAME + 'imageeditor', [{
				'id': 'done',
				'callback': Y.bind(function () {
					this.medialist.fire('imageeditor:close');
				}, this)
			}]);
			
			
			//Create slideshow
			var list = this.medialist = (new Supra.MediaLibraryExtendedList({
				'srcNode': this.one('#mediaLibraryList'),
				'foldersSelectable': true,
				'filesSelectable': false,
				'imagesSelectable': false,
				'viewURI': this.getDataPath('view') + '.php',
				'listURI': this.getDataPath('list') + '.php',
				'saveURI': this.getDataPath('save') + '.php',
				'slideshowClass': Supra.MediaLibrarySlideshow
			})).render();
			
			//Add file upload support
			list.plug(Supra.MediaLibraryList.Upload, {
				'requestUri': this.getDataPath('upload') + '.php',
				'dragContainer': new Y.Node(document.body)
			});
			
			//Create "Sort by" widget
			var input = this.input_sortby = new Supra.Input.SelectList({
				'srcNode': this.one('#mediaLibrarySort')
			});
			input.render();
			input.on('change', function (event) {
				this.medialist.set('sortBy', event.value);
			}, this);
			
			//On image editor show/hide update buttons
			list.on('imageeditor:open', function () {
				Manager.getAction('PageToolbar').setActiveGroupAction(this.NAME + 'imageeditor');
				Manager.getAction('PageButtons').setActiveAction(this.NAME + 'imageeditor');
			}, this);
			list.on('imageeditor:close', function () {
				Manager.getAction('PageToolbar').unsetActiveGroupAction(this.NAME + 'imageeditor');
				Manager.getAction('PageButtons').unsetActiveAction(this.NAME + 'imageeditor');
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
					this.medialist.upload.openBrowser();
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
				case 'mlimagecrop':
				case 'mlimagerotateleft':
				case 'mlimagerotateright':
				case 'mlimageundo':
					this.medialist.imageeditor.command(button_id.replace('mlimage', ''));
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
				Manager.getAction('EditorToolbar').execute();
			}
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			if (!Manager.getAction('PageToolbar').inHistory(this.NAME)) {
				Manager.getAction('PageToolbar').setActiveGroupAction(this.NAME);
				Manager.getAction('PageButtons').setActiveAction(this.NAME);
			}
			
			//Hide editor toolbar if it's visible
			if (Manager.getAction('EditorToolbar').get('visible')) {
				this.editor_toolbar_visible = true;
				Manager.getAction('EditorToolbar').hide();
			} else {
				this.editor_toolbar_visible = false;
			}
			
			this.show();
		}
	});
	
});