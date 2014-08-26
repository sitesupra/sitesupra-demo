Supra('anim', 'dd-drag', 'supra.medialibrary-list-dd', 'supra.medialibrary-upload', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager,
		Action = Manager.Action,
		Loader = Manager.Loader;
	
	//Create Action class
	new Action(Action.PluginLayoutSidebar, {
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'MediaSidebar',
		
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
		 * Layout container action NAME
		 * @type {String}
		 * @private
		 */
		LAYOUT_CONTAINER: 'LayoutLeftContainer',
		
		
		
		
		/**
		 * Supra.MediaLibraryList instance
		 * @type {Object}
		 */
		medialist: null,
		
		/**
		 * Media select options
		 * @type {Object}
		 */
		options: {},
		
		
		
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 * 
		 * @private
		 */
		initialize: function () {},
		
		/**
		 * Render widgets
		 * 
		 * @private
		 */
		render: function () {
			//Toolbar buttons
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			//Create media list
			this.renderMediaList();
			
			//Back, Close and App buttons
			this.renderHeader();
			this.renderFooter();
		},
		
		/**
		 * Create media library list
		 * 
		 * @private
		 */
		renderMediaList: function () {
			var container = this.one('.sidebar-content');
			var medialibrary = Manager.getAction('MediaLibrary');
			
			var list = this.medialist = new Supra.MediaLibraryList({
				//Use media library action data path
				'listURI': medialibrary.getDataPath('list'),
				'viewURI': medialibrary.getDataPath('view'),
				
				//Display only images + folders
				'displayType': Supra.MediaLibraryList.DISPLAY_IMAGES,
				
				//Show "Insert" button
				'allowInsert': true,
				
				//Allow selecting files and images
				'imagesSelectable': false,
				'filesSelectable': false
			});
			
			//Enable drag & drop support
			list.plug(Supra.MediaLibraryList.DD);
			list.render(container);
			
			//Add HTML5 file upload support
			list.upload = new Supra.MediaLibraryList.Uploader({
				'allowBrowse': false,
				'requestUri': medialibrary.getDataPath('upload'),
				'medialist': list,
				'dropTarget': list.get('boundingBox')
			});
			
			//Show/hide back button when slide changes
			list.slideshow.on('slideChange', function (evt) {
				if (list.slideshow.isRootSlide()) {
					this.get('backButton').hide();
				} else {
					this.get('backButton').show();
				}
			}, this);
			
			//On insert button click insert image
			list.on('insertClick', this.insert, this);
		},
		
		/**
		 * Create buttons
		 * 
		 * @private
		 */
		renderHeader: function () {
			this.get('backButton').on('click', this.medialist.openPrevious, this.medialist);
			this.get('controlButton').on('click', this.close, this);
			
		},
		
		/**
		 * Create footer button
		 */
		renderFooter: function () {
			var button = this.one('.sidebar-footer button');
			
			this.button_app = new Supra.Button({'srcNode': button});
			this.button_app.render();
			this.button_app.on('click', function () {
				//Disable upload (MediaLibrary has its own upload instance)
				this.medialist.upload.set('disabled', true);
				
				//Show media library
				var action = Manager.getAction('MediaLibrary');
				
				action.once('execute', this.mediaLibraryOnExecute, this);
				action.once('hide', this.mediaLibraryOnHide, this);
				
				action.execute();
			}, this);
		},
		
		/**
		 * On media library execute hide sidebar
		 * 
		 * @private
		 */
		mediaLibraryOnExecute: function () {
			//Hide sidebar
			this.one().addClass('yui3-mediasidebar-hidden');
		},
		
		/**
		 * On media library hide show sidebar
		 * 
		 * @private
		 */
		mediaLibraryOnHide: function () {
			//Show sidebar
			this.one().removeClass('yui3-mediasidebar-hidden');
			
			//Enable upload back
			this.medialist.upload.set('disabled', false);
			
			//Reload data
			this.medialist.reload();
		},
		
		/**
		 * Returns item data object
		 */
		dataObject: function () {
			return this.medialist.get('data');
		},
		
		/**
		 * Hide
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			
			//Show previous buttons
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			//Disable upload (otherwise all media library instances
			//will be affected by HTML5 drag and drop)
			this.medialist.upload.set('disabled', true);
			
			//Retore editor toolbar
			if (this.options.retoreEditorToolbar) {
				this.options.retoreEditorToolbar = false;
				Manager.getAction('EditorToolbar').execute();
			}
		},
		
		/**
		 * Hide media sidebar and call close callback
		 */
		close: function () {
			this.hide();
			
			if (Y.Lang.isFunction(this.options.onclose)) {
				this.options.onclose({
					'image': null
				});
			}
		},
		
		/**
		 * Hide media sidebar and call select and close callbacks
		 */
		insert: function () {
			this.hide();
			
			if (Y.Lang.isFunction(this.options.onselect)) {
				this.options.onselect({
					'image': this.medialist.getSelectedItem()
				});
			}
			if (Y.Lang.isFunction(this.options.onclose)) {
				this.options.onclose({
					'image': this.medialist.getSelectedItem()
				});
			}
		},
		
		/**
		 * Execute action
		 */
		execute: function (options) {
			this.show();
			
			//Set options
			this.options = Supra.mix({
				'onselect': null,
				'onclose': null,
				'displayType': Supra.MediaLibraryList.DISPLAY_IMAGES,
				'dndEnabled': true,
				'hideToolbar': false,
				'item': null
			}, options || {}, true);
			
			//Scroll to folder / item
			this.medialist.set('displayType', this.options.displayType);
			
			//Drag and drop
			this.medialist.set('dndEnabled', this.options.dndEnabled);
			
			this.medialist.reset();
			this.medialist.set('noAnimations', true);
			this.medialist.open(this.options.item || 0, true /* Mark file instead of opening it */);
			this.medialist.set('noAnimations', false);
			
			//Enable upload
			this.medialist.upload.set('disabled', false);
			
			//Update slideshow
			this.medialist.slideshow.syncUI();
			
			//Hide toolbar
			if (this.options.hideToolbar) {
				//Hide editor toolbar
				if (Manager.getAction('EditorToolbar').get('visible')) {
					this.options.retoreEditorToolbar = true;
					Manager.getAction('EditorToolbar').hide();
				}
				
				//Hide buttons
				Manager.getAction('PageToolbar').setActiveAction(this.NAME);
				Manager.getAction('PageButtons').setActiveAction(this.NAME);
			}
		}
	});
	
});