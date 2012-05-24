//Invoke strict mode
"use strict";

Supra('supra.medialibrary-list-extended', 'supra.medialibrary-upload', function (Y) {
	
	//Toolbar buttons
	var TOOLBAR_BUTTONS = [
	    {
	        'id': 'mlupload',
			'title': Supra.Intl.get(['medialibrary', 'upload']),
			'icon': '/cms/lib/supra/img/toolbar/icon-media-upload.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    },
		{
	        'id': 'mlfolder',
			'title': Supra.Intl.get(['medialibrary', 'new_folder']),
			'icon': '/cms/lib/supra/img/toolbar/icon-media-folder.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    },
		{
	        'id': 'mldelete',
			'title': Supra.Intl.get(['medialibrary', 'delete']),
			'icon': '/cms/lib/supra/img/toolbar/icon-media-delete.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    }/*,
		{
	        'id': 'mlundo',
			'title': Supra.Intl.get(['medialibrary', 'undo_history']),
			'icon': '/cms/lib/supra/img/toolbar/icon-media-undo.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    }*/,
		{
	        'id': 'mlprivate',
			'title': Supra.Intl.get(['medialibrary', 'private']),
			'icon': '/cms/lib/supra/img/toolbar/icon-media-private.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button',
			'disabled': true
	    },
		{
	        'id': 'mlpublic',
			'title': Supra.Intl.get(['medialibrary', 'public']),
			'icon': '/cms/lib/supra/img/toolbar/icon-media-public.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button',
			'visible': false,
			'disabled': true
	    }
	];
	
	//Image editor toolbar buttons
	var TOOLBAR_IMAGEEDITOR_BUTTONS = [
	    {
	        'id': 'mlimagerotateleft',
			'title': Supra.Intl.get(['medialibrary', 'rotate_left']),
			'icon': '/cms/lib/supra/img/toolbar/icon-media-rotateleft.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    },
		{
	        'id': 'mlimagerotateright',
			'title': Supra.Intl.get(['medialibrary', 'rotate_right']),
			'icon': '/cms/lib/supra/img/toolbar/icon-media-rotateright.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    },
		{
	        'id': 'mlimagecrop',
			'title': Supra.Intl.get(['medialibrary', 'crop']),
			'icon': '/cms/lib/supra/img/toolbar/icon-media-crop.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    }/*,
		{
	        'id': 'mlimageundo',
			'title': Supra.Intl.get(['medialibrary', 'undo_history']),
			'icon': '/cms/lib/supra/img/toolbar/icon-media-undo.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    }*/
	];
	
	var NAME_EDITOR = 'MediaLibraryimageeditor',
		NAME_EDITOR_CROP = 'MediaLibraryimageeditorcrop';
	
	//HTML5 Support
	var FILE_API_SUPPORTED = typeof FileReader !== 'undefined';
	
	//Shortcuts
	var Manager = Supra.Manager;
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
		 * Dependancies
		 * @type {Array}
		 */
		DEPENDANCIES: ['PageToolbar', 'PageButtons'],
		
		
		
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
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, TOOLBAR_BUTTONS);
			
			//Add side buttons
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'done',
				'context': this,
				'callback': function () {
					this.hide();
				}
			}]);
			
			//Create slideshow
			var list = this.medialist = (new Supra.MediaLibraryExtendedList({
				'srcNode': this.one('#mediaLibraryList'),
				'foldersSelectable': true,
				'filesSelectable': false,
				'imagesSelectable': false,
				'downloadURI': Manager.Loader.getDynamicPath() + Manager.Loader.getActionBasePath(this.NAME) + '/' + ('download'),
				'viewURI': this.getDataPath('view'),
				'listURI': this.getDataPath('list'),
				'saveURI': this.getDataPath('save'),
				'moveURI': this.getDataPath('move'),
				'deleteURI': this.getDataPath('delete'),
				'insertURI': this.getDataPath('insert'),
				'imageRotateURI': this.getDataPath('imagerotate'),
				'imageCropURI': this.getDataPath('imagecrop'),
				'slideshowClass': Supra.SlideshowMultiView
			})).render();
			
			//On folder change show/hide private/public buttons
			this.medialist.slideshow.on('slideChange', this.onItemChange, this);
			
			//Pass through events
			this.bubbleEvents(this.medialist, ['replace', 'rotate', 'crop']);
			
			//Add file upload support
			list.plug(Supra.MediaLibraryList.Upload, {
				'requestUri': this.getDataPath('upload'),
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
			
			//On image editor events show/hide buttons
			list.on('imageeditor:open', this.openEditorButtons, this);
			list.on('imageeditor:close', this.closeEditorButtons, this);
		},
		
		/**
		 * Open editor buttons
		 */
		openEditorButtons: function () {
			if (!Manager.getAction('PageButtons').hasActionButtons(NAME_EDITOR)) {
				
				Manager.getAction('PageToolbar').addActionButtons(NAME_EDITOR, TOOLBAR_IMAGEEDITOR_BUTTONS);
				Manager.getAction('PageButtons').addActionButtons(NAME_EDITOR, [{
					'id': 'done',
					'context': this,
					'callback': function () {
						this.medialist.fire('imageeditor:close');
					}
				}]);
				
			}
			
			Manager.getAction('PageToolbar').setActiveAction(NAME_EDITOR);
			Manager.getAction('PageButtons').setActiveAction(NAME_EDITOR);
		},
		
		/**
		 * Close editor buttons
		 */
		closeEditorButtons: function () {
			Manager.getAction('PageToolbar').unsetActiveAction(NAME_EDITOR);
			Manager.getAction('PageButtons').unsetActiveAction(NAME_EDITOR);
		},
		
		/**
		 * Open editor crop functionality buttons
		 */
		openEditorCropButtons: function () {
			if (!Manager.getAction('PageButtons').hasActionButtons(NAME_EDITOR_CROP)) {
				
				Manager.getAction('PageToolbar').addActionButtons(NAME_EDITOR_CROP, []);
				Manager.getAction('PageButtons').addActionButtons(NAME_EDITOR_CROP, [{
					'id': 'done',
					'label': Supra.Intl.get(['medialibrary', 'crop']),
					'context': this,
					'callback': function () {
						this.medialist.imageeditor.command('crop');
						this.openEditorButtons();
					}
				}, {
					'id': 'cancel',
					'context': this,
					'callback': function () {
						this.medialist.imageeditor.set('mode', '');
						this.openEditorButtons();
					}
				}]);
			}
			
			Manager.getAction('PageToolbar').setActiveAction(NAME_EDITOR_CROP);
			Manager.getAction('PageButtons').setActiveAction(NAME_EDITOR_CROP);
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
					
					if (FILE_API_SUPPORTED) {
						this.medialist.upload.openBrowser();
					}
					
					break;
					
				case 'mlfolder':
					
					var folder = this.medialist.getSelectedFolder() || {'id': 0};
					
					//Close any opened image or file
					if (this.medialist.getSelectedItem()) {
						this.medialist.open(folder.id);
					}
					
					//Add folder
					this.medialist.addFolder(null, '');
					
					break;
					
				case 'mldelete':
					
					this.medialist.deleteSelectedItem();
					break;
					
				case 'mlundo':
					
					//@TODO
					break;
					
				case 'mlimagecrop':
					
					this.medialist.imageeditor.set('mode', 'crop');
					this.openEditorCropButtons();
					break;
					
				case 'mlimagerotateleft':
				case 'mlimagerotateright':
				case 'mlimageundo':
				
					this.medialist.imageeditor.command(button_id.replace('mlimage', ''));
					break;
				
				case 'mlpublic':
					
					this.medialist.setPrivateState(null, false);
					this.onItemChange();
					break;
					
				case 'mlprivate':
					
					this.medialist.setPrivateState(null, true);
					this.onItemChange();
					break;
			}
		},
		
		/**
		 * On item change show/hide public/private buttons
		 */
		onItemChange: function (evt) {
			var id = null,
				data = null,
				buttons = Manager.getAction('PageToolbar').buttons;
			
			if (evt) {
				id = evt.newVal.replace('slide_', '');
				data = this.medialist.get('dataObject').getData(id);
			} else {
				data = this.medialist.getSelectedFolder();
				id = data.id;
			}
			
			if (data && Supra.MediaLibraryData.TYPE_FOLDER == data.type) {
				if (data['private']) {
					if (data.parent) {
						if (this.medialist.get('dataObject').isFolderPrivate(data.parent)) {
							//If parent is private, can't change folder state
							//Disable buttons
							buttons.mlprivate.set('disabled', true);
							buttons.mlpublic.set('disabled', true);
							return;
						}
					}
					
					//Show "Make public" button
					buttons.mlprivate.set('visible', false);
					buttons.mlpublic.set('visible', true);
				} else {
					//Show "Make private" button
					buttons.mlprivate.set('visible', true);
					buttons.mlpublic.set('visible', false);
				}
				
				buttons.mlprivate.set('disabled', false);
				buttons.mlpublic.set('disabled', false);
			} else {
				//Disable buttons
				buttons.mlprivate.set('disabled', true);
				buttons.mlpublic.set('disabled', true);
			}
		},
		
		/**
		 * Hide
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			//If editor toolbar was visible before, then show it now
			if (this.editor_toolbar_visible) {
				Manager.getAction('EditorToolbar').execute();
			}
			
			//Disable upload (otherwise all media library instances
			//will be affected by HTML5 drag and drop)
			this.medialist.upload.set('disabled', true);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			if (!Manager.getAction('PageToolbar').inHistory(this.NAME)) {
				Manager.getAction('PageToolbar').setActiveAction(this.NAME);
				Manager.getAction('PageButtons').setActiveAction(this.NAME);
			}
			
			//Hide editor toolbar if it's visible
			if (Manager.getAction('EditorToolbar').get('visible')) {
				this.editor_toolbar_visible = true;
				Manager.getAction('EditorToolbar').hide();
			} else {
				this.editor_toolbar_visible = false;
			}
			
			//Enable upload
			this.medialist.upload.set('disabled', false);
			
			this.show();
		}
	});
	
});