//Invoke strict mode
"use strict";

SU('anim', 'dd-drag', 'supra.medialibrary-list-dd', 'supra.medialibrary-upload', function (Y) {
	
	//Shortcuts
	var Manager = SU.Manager,
		Action = Manager.Action,
		Loader = Manager.Loader;
	
	//Add as left bar child
	Manager.getAction('LayoutLeftContainer').addChildAction('MediaSidebar');
	
	//Create Action class
	new Action(Action.PluginContainer, {
		
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
		 * Close button, Supra.Button instance
		 * @type {Object}
		 */
		button_close: null,
		
		/**
		 * Back button, Supra.Button instance
		 * @type {Object}
		 */
		button_back: null,
		
		
		
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 * 
		 * @private
		 */
		initialize: function () {
		},
		
		/**
		 * Render widgets
		 * 
		 * @private
		 */
		render: function () {
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
			var container = this.one('.yui3-sidebar-content');
			var medialibrary = Manager.getAction('MediaLibrary');
			
			var list = this.medialist = new Supra.MediaLibraryList({
				//Use media library action data path
				'listURI': medialibrary.getDataPath('list'),
				'viewURI': medialibrary.getDataPath('view'),
				
				//Display only images + folders
				'displayType': Supra.MediaLibraryList.DISPLAY_IMAGES,
				
				//Because of drag and drop we need to load sizes and descriptions
				//when loading all images in the folder, not only when opening image
				'loadItemProperties': ['sizes', 'description'],
				
				//Allow selecting image
				'imagesSelectable': true
			});
			
			//Enable drag & drop support
			list.plug(Supra.MediaLibraryList.DD);
			list.render(container);
			
			//Add HTML5 file upload support
			list.plug(Supra.MediaLibraryList.Upload, {
				'requestUri': medialibrary.getDataPath('upload'),
				'dragContainer': new Y.Node(document.body)
			});
			
			//Show/hide back button when slide changes
			list.slideshow.on('slideChange', function (evt) {
				if (list.slideshow.isRootSlide()) {
					this.button_back.hide();
				} else {
					this.button_back.show();
				}
			}, this);

			//On 'select' event trigger callback if it exists
			list.on('select', function (e) {
				if (Y.Lang.isFunction(this.options.onselect)) {
					this.options.onselect({'image': e.data});
				}
			}, this);
		},
		
		/**
		 * Create buttons
		 * 
		 * @private
		 */
		renderHeader: function () {
			var buttons = this.all('button');
			
			this.button_back = new Supra.Button({'srcNode': buttons.filter('.button-back').item(0)});
			this.button_back.render();
			this.button_back.hide();
			this.button_back.on('click', this.scrollBack, this);
			
			this.button_close = new Supra.Button({'srcNode': buttons.filter('.button-close').item(0), 'style': 'mid-blue'});
			this.button_close.render();
			this.button_close.on('click', this.hide, this);
		},
		
		/**
		 * Create footer button
		 */
		renderFooter: function () {
			var button = this.one('.yui3-sidebar-footer button');
			
			this.button_app = new Supra.Button({'srcNode': button});
			this.button_app.render();
			this.button_app.on('click', function () {
				//Disable upload (MediaLibrary has its own upload instance)
				this.medialist.upload.set('disabled', true);
				
				//Show media library
				Manager.executeAction('MediaLibrary');
				
				Manager.getAction('MediaLibrary').on('hide', function () {
					//Enable upload back
					this.medialist.upload.set('disabled', false);
				}, this);
			}, this);
		},
		
		/**
		 * Returns item data
		 * 
		 * @param {Object} id File, image or folder ID
		 * @return Item data
		 * @type {Object}
		 */
		getData: function (id /* File, image or folder ID */) {
			var data = this.medialist.get('dataObject').getData(id);
			if (data.type == SU.MediaLibraryData.TYPE_FOLDER) {
				data = SU.mix({}, data);
				data.children = this.medialist.get('dataObject').getChildrenData(id);
			}
			return data;
		},
		
		/**
		 * Scroll to previous slide. Chainable
		 */
		scrollBack: function () {
			this.medialist.slideshow.scrollBack();
			return this;
		},
		
		/**
		 * Hide
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			Manager.getAction('LayoutLeftContainer').unsetActiveAction(this.NAME);
			
			//Disable upload (otherwise all media library instances
			//will be affected by HTML5 drag and drop)
			this.medialist.upload.set('disabled', true);
		},
		
		/**
		 * Execute action
		 */
		execute: function (options) {
			//Set options
			this.options = options || {};
			
			//Scroll to folder / item
			this.medialist.set('noAnimations', true);
			this.medialist.open(this.options.item || 0);
			this.medialist.set('noAnimations', false);
			
			//Show MediaSidebar in left container
			Manager.getAction('LayoutLeftContainer').setActiveAction(this.NAME);
			
			//Enable upload
			this.medialist.upload.set('disabled', false);
		}
	});
	
});