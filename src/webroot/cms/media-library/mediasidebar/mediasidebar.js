//Invoke strict mode
"use strict";

SU('anim', 'dd-drag', 'supra.medialibrary-list-dd', 'supra.medialibrary-upload', function (Y) {
	
	//Shortcuts
	var Manager = SU.Manager,
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
				
				//Because of drag and drop we need to load sizes and descriptions
				//when loading all images in the folder, not only when opening image
				'loadItemProperties': ['sizes', 'description'],
				
				//Allow selecting files and images
				'imagesSelectable': false,
				'filesSelectable': false
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
					this.get('backButton').hide();
				} else {
					this.get('backButton').show();
				}
				
				//Get current slide data and show "Insert" if image is selected
				if (evt.newVal) {
					var item_data = this.getData(evt.newVal.replace('slide_', ''));
					if (item_data && item_data.type != Supra.MediaLibraryData.TYPE_FOLDER) {
						this.get('controlButton').set('label', '{# buttons.insert #}');
					} else {
						this.get('controlButton').set('label', '{# buttons.close #}');
					}
				}
			}, this);
		},
		
		/**
		 * Create buttons
		 * 
		 * @private
		 */
		renderHeader: function () {
			
			this.get('backButton').on('click', this.medialist.openPrevious, this.medialist);
			
			this.get('controlButton').on('click', function () {
				var slide = this.medialist.slideshow.get('slide'),
					item_data = this.getData(slide.replace('slide_', ''));
				
				if (item_data && item_data.type != Supra.MediaLibraryData.TYPE_FOLDER) {
					this.insert();
				} else {
					this.close();
				}
			}, this);
			
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
		 * Returns item data
		 * 
		 * @param {Object} id File, image or folder ID
		 * @return Item data
		 * @type {Object}
		 */
		getData: function (id /* File, image or folder ID */) {
			var data = this.medialist.get('dataObject').getData(id);
			if (data && data.type == SU.MediaLibraryData.TYPE_FOLDER) {
				data = SU.mix({}, data);
				data.children = this.medialist.get('dataObject').getChildrenData(id);
			}
			return data;
		},
		
		/**
		 * Hide
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			
			//Disable upload (otherwise all media library instances
			//will be affected by HTML5 drag and drop)
			this.medialist.upload.set('disabled', true);
		},
		
		/**
		 * Hide media sidebar and call close callback
		 */
		close: function () {
			this.hide();
			
			if (Y.Lang.isFunction(this.options.onclose)) {
				this.options.onclose();
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
				this.options.onclose();
			}
		},
		
		/**
		 * Execute action
		 */
		execute: function (options) {
			this.show();
			
			//Set options
			this.options = Supra.mix({
				'displayType': Supra.MediaLibraryList.DISPLAY_IMAGES,
				'dndEnabled': true
			}, options || {}, true);
			
			//Scroll to folder / item
			this.medialist.set('displayType', this.options.displayType);
			
			//Drag and drop
			this.medialist.set('dndEnabled', this.options.dndEnabled);
			
			this.medialist.reset();
			this.medialist.set('noAnimations', true);
			this.medialist.open(this.options.item || 0);
			this.medialist.set('noAnimations', false);
			
			//Enable upload
			this.medialist.upload.set('disabled', false);
			
			//Update slideshow
			this.medialist.slideshow.syncUI();
		}
	});
	
});