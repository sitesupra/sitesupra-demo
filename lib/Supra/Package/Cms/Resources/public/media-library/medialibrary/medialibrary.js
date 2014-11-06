Supra('supra.medialibrary-list-extended', 'supra.medialibrary-upload', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Toolbar buttons
	var TOOLBAR_BUTTONS = [
	    {
	        'id': 'mlupload',
			'title': Supra.Intl.get(['medialibrary', 'upload']),
			'icon': 'supra/img/toolbar/icon-media-upload.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    },
		{
	        'id': 'mlfolder',
			'title': Supra.Intl.get(['medialibrary', 'new_folder']),
			'icon': 'supra/img/toolbar/icon-media-folder.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    },
		{
	        'id': 'mldelete',
			'title': Supra.Intl.get(['medialibrary', 'delete']),
			'icon': 'supra/img/toolbar/icon-media-delete.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button',
			'disabled': true
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
			'icon': 'supra/img/toolbar/icon-media-private.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button',
			'disabled': true
	    },
		{
	        'id': 'mlpublic',
			'title': Supra.Intl.get(['medialibrary', 'public']),
			'icon': 'supra/img/toolbar/icon-media-public.png',
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
			'icon': 'supra/img/toolbar/icon-media-rotateleft.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    },
		{
	        'id': 'mlimagerotateright',
			'title': Supra.Intl.get(['medialibrary', 'rotate_right']),
			'icon': 'supra/img/toolbar/icon-media-rotateright.png',
			'action': 'MediaLibrary',
			'actionFunction': 'handleToolbarButton',
			'type': 'button'
	    },
		{
	        'id': 'mlimagecrop',
			'title': Supra.Intl.get(['medialibrary', 'crop']),
			'icon': 'supra/img/toolbar/icon-media-crop.png',
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
		 * Key listeners
		 * @type {Array}
		 * @private
		 */
		key_listeners: [],
		
		
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
			
			//In portal site "Private" and "Public" buttons shouldn't be available
			if (Supra.data.get(['site', 'portal'])) {
				var buttons = Manager.getAction('PageToolbar').buttons;
				buttons.mlprivate.set('visible', false);
				buttons.mlpublic.set('visible', false);
			}
			
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
				'downloadURI': Supra.Url.generate('media_library_download'),
				'viewURI': Supra.Url.generate('media_library_view'),
				'listURI': Supra.Url.generate('media_library_list'),
				'saveURI': this.getDataPath('save'),
				'moveURI': this.getDataPath('move'),
				'deleteURI': Supra.Url.generate('media_library_delete'),
				'insertURI': Supra.Url.generate('media_library_insert'),
				'imageRotateURI': Supra.Url.generate('media_library_rotate'),
				'imageCropURI': Supra.Url.generate('media_library_crop'),
				'slideshowClass': Supra.SlideshowMultiView,
				'metaProperties': Supra.data.get(['media-library', 'properties'], [])
			})).render();

			//On folder change show/hide private/public buttons
			list.slideshow.on('slideChange', this.onItemChange, this);
			
			//Pass through events
			this.bubbleEvents(list, ['replace', 'rotate', 'crop']);
			
			//Add file upload support
			list.upload = new Supra.MediaLibraryList.Uploader({
				'requestUri': Supra.Url.generate('media_library_upload'),
				'medialist': list,
				'dropTarget': list.get('boundingBox'),
				'clickTarget': Manager.PageToolbar.getActionButton('mlupload')
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
					
					// Handled by Uploder
					
					break;
					
				case 'mlfolder':
					
					var folder = this.medialist.getSelectedFolder() || {'id': 0},
						item   = this.medialist.getSelectedItem();

					//Close any opened image or file
					if (item && folder && item.id != folder.id) {
						this.medialist.open(folder.id);
					}
					
					//Add folder
					this.medialist.addFolder(folder.id, '');
					
					break;
					
				case 'mldelete':
					
					var button = Manager.PageToolbar.getActionButton('mldelete');
					button.set('loading', true);
					
					this.medialist.deleteSelectedItem().always(function () {
						button.set('loading', false);
					});
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
				data = this.medialist.get('data').cache.one(id);
			} else {
				data = this.medialist.getSelectedFolder();
				id = data.id;
			}
			
			if (!Supra.data.get(['site', 'portal'])) {
				if (data && Supra.MediaLibraryList.TYPE_FOLDER == data.type) {
					if (data['private']) {
						if (data.parent) {
							if (this.medialist.isFolderPrivate(data.parent)) {
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
			}
			
			if (data) {
				buttons.mldelete.set('disabled', false);
			} else {
				// No file or folder selected
				buttons.mldelete.set('disabled', true);
			}
		},
		
		
		/* --------------------------- KEYS --------------------------- */
		
		
		addKeyListeners: function () {
			var listeners = this.key_listeners;
			
			listeners.push(
				Y.one("doc").on("key", Y.bind(function (e) {
					// Delete key
					this.handleToolbarButton("mldelete");
					e.preventDefault();
				}, this), "down:46")
			);
		},
		
		removeKeyListeners: function () {
			var listeners = this.key_listeners,
				i = 0,
				ii = listeners ? listeners.length : 0;
				
			for (; i<ii; i++) {
				listeners[i].detach();
			}
			
			this.key_listeners = [];
		},
		
		
		/* --------------------------- OPEN / CLOSE --------------------------- */
		
		
		/**
		 * Hide
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			//Remove from header
			Manager.getAction('Header').unsetActiveApplication(this.NAME);
			
			//If editor toolbar was visible before, then show it now
			if (this.editor_toolbar_visible) {
				Manager.getAction('EditorToolbar').execute();
			}
			
			//Disable upload (otherwise all media library instances
			//will be affected by HTML5 drag and drop)
			this.medialist.upload.set('disabled', true);
			
			this.removeKeyListeners();
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			if (!Manager.getAction('PageToolbar').inHistory(this.NAME)) {
				Manager.getAction('PageToolbar').setActiveAction(this.NAME);
				Manager.getAction('PageButtons').setActiveAction(this.NAME);
			}
			
			//Add to header, because this is full screen application
			Manager.getAction('Header').setActiveApplication(this.NAME);
			
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
			this.addKeyListeners();
		}
	});
	
});