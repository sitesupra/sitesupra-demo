//Invoke strict mode
"use strict";

/**
 * MediaLibraryList handles folder/file/image data loading and opening,
 * allows selecting files, folders and images
 */
YUI.add('supra.medialibrary-list', function (Y) {
	
	/*
	 * Shortcuts
	 */
	var Data = Supra.MediaLibraryData,
		Template = Supra.Template;
	
	
	/**
	 * Media list
	 * Handles data loading, scrolling, selection
	 */
	function List (config) {
		List.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	List.NAME = 'medialist';
	List.CLASS_NAME = Y.ClassNameManager.getClassName(List.NAME);
	
	
	/**
	 * Constant, display all data
	 * @type {Number}
	 */
	List.DISPLAY_ALL = 0;
	
	/**
	 * Constant, display only folders
	 * @type {Number}
	 */
	List.DISPLAY_FOLDERS = 1;
	
	/**
	 * Constant, display only images
	 * @type {Number}
	 */
	List.DISPLAY_IMAGES = 2;
	
	/**
	 * Constant, display only files
	 * @type {Number}
	 */
	List.DISPLAY_FILES = 3;
	
	
	
	/**
	 * Constant, list of properties needed to display file
	 * @type {Array}
	 */
	List.FILE_PROPERTIES = ['filename', 'file_web_path', 'known_extension'];
	
	/**
	 * Constant, list of properties needed to display image
	 * @type {Array}
	 */
	List.IMAGE_PROPERTIES = ['filename', 'sizes', 'created'];
	
	
	
	/**
	 * Constant, file or folder loading template
	 * @type {String}
	 */
	List.TEMPLATE_LOADING = Template.compile('<div class="loading-icon">&nbsp;</div>');
	
	/**
	 * Constant, empty folder template
	 * @type {String}
	 */
	List.TEMPLATE_EMPTY = Template.compile('<div class="empty" data-id="{{ id }}">{{ "medialibrary.folder_empty"|intl }}</div>');
	
	/**
	 * Constant, folder template
	 * @type {String}
	 */
	List.TEMPLATE_FOLDER = Template.compile('<ul class="folder" data-id="{{ id }}"></ul>');
	
	/**
	 * Constant, folder item template for folder
	 * @type {String}
	 */
	List.TEMPLATE_FOLDER_ITEM_FOLDER = Template.compile('\
		<li class="type-folder {% if private %}type-folder-private{% endif %}" data-id="{{ id }}">\
			<a></a>\
			<span>{{ filename|escape }}</span>\
		</li>');
	
	/**
	 * Constant, folder item template for file
	 * @type {String}
	 */
	List.TEMPLATE_FOLDER_ITEM_FILE = Template.compile('\
		<li class="type-file {% if knownExtension %}type-file-{{ knownExtension }}{% endif %} {% if broken %}type-broken{% endif %}" data-id="{{ id }}">\
			<a></a>\
			<span>{{ filename|escape }}</span>\
		</li>');
	
	/**
	 * Constant, folder item template for image
	 * @type {String}
	 */
	List.TEMPLATE_FOLDER_ITEM_IMAGE = Template.compile('\
		<li class="type-image {% if broken or !thumbnail %}type-broken{% endif %}" data-id="{{ id }}">\
			<a>{% if !broken and thumbnail %}<img src="{{ thumbnail|escape }}?r={{ Math.random() }}" alt="" />{% endif %}</a>\
			<span>{{ filename|escape }}</span>\
		</li>');
	
	/**
	 * Constant, folder item template for temporary file
	 * @type {String}
	 */
	List.TEMPLATE_FOLDER_ITEM_TEMP = Template.compile('<li class="type-temp" data-id="{{ id }}"></li>');
	
	/**
	 * Constant, file template
	 * @type {String}
	 */
	List.TEMPLATE_FILE = Template.compile('\
		<div class="file">\
			<div class="preview">\
				<img src="/cms/lib/supra/img/medialibrary/icon-{% if broken %}broken{% else %}file{% if known_extension %}-{{ known_extension }}{% endif %}{% endif %}-large.png" alt="" />\
			</div>\
			\
			{% set current_locale = Supra.data.get("locale") %}\
			<div class="preview-title">{{ filename|escape }}</div>\
			\
			<div class="group">\
				<div class="info">\
					{% if extension %}\
						<div>\
							<span class="info-label">{{ "medialibrary.kind"|intl }}</span>\
							<span class="info-data">{{ extension|upper }} {{ "medialibrary.file"|intl }}</span>\
						</div>\
					{% endif %}\
					<div>\
						<span class="info-label">{{ "medialibrary.size"|intl }}</span>\
						<span class="info-data">{{ Math.round(size/1000)|default("0") }} KB</span>\
					</div>\
					{% if created %}\
						<div>\
							<span class="info-label">{{ "medialibrary.created"|intl }}</span>\
							<span class="info-data">{{ created|datetime_short|default("&nbsp;") }}</span>\
						</div>\
					{% endif %}\
				</div>\
			</div>\
		</div>');
	
	/**
	 * Constant, image template
	 * @type {String}
	 */
	List.TEMPLATE_IMAGE = Template.compile('\
		<div class="image">\
			<div class="drag-icon">\
				<span>{{ "medialibrary.drag_n_drop"|intl }}</span>\
				<span class="icon"></span>\
			</div>\
			<div class="preview">\
				{% if broken %}\
					<img src="/cms/lib/supra/img/medialibrary/icon-broken-large.png" alt="" />\
				{% else %}\
					<img src="{{ preview|escape }}?r={{ Math.random() }}" alt="" />\
				{% endif %}\
			</div>\
			\
			{% set current_locale = Supra.data.get("locale") %}\
			<div class="preview-title">{{ filename|escape }}</div>\
			\
			<div class="group">\
				<div class="info">\
					{% if extension %}\
						<div>\
							<span class="info-label">{{ "medialibrary.kind"|intl }}</span>\
							<span class="info-data">{{ extension|upper }} {{ "medialibrary.image"|intl }}</span>\
						</div>\
					{% endif %}\
					<div>\
						<span class="info-label">{{ "medialibrary.size"|intl }}</span>\
						<span class="info-data">{{ Math.round(size/1000)|default("0") }} KB</span>\
					</div>\
					{% if created %}\
						<div>\
							<span class="info-label">{{ "medialibrary.created"|intl }}</span>\
							<span class="info-data">{{ created|datetime_short|default("&nbsp;") }}</span>\
						</div>\
					{% endif %}\
					{% if sizes %}\
						<div>\
							<span class="info-label">{{ "medialibrary.dimensions"|intl }}</span>\
							<span class="info-data">{{ sizes.original.width }} x {{ sizes.original.height }}</span>\
						</div>\
					{% endif %}\
				</div>\
			</div>\
		</div>');
		
	
	List.ATTRS = {
		/**
		 * URI for save requests
		 * @type {String}
		 */
		'saveURI': {
			value: ''
		},
		
		/**
		 * URI for folder insert requests
		 * @type {String}
		 */
		'insertURI': {
			value: ''
		},
		
		/**
		 * URI for folder delete requests
		 * @type {String}
		 */
		'deleteURI': {
			value: ''
		},
		
		/**
		 * Request URI for image or file
		 * @type {String}
		 */
		'viewURI': {
			value: null
		},
		
		/**
		 * Request URI for folder, image or file list
		 * @type {String}
		 */
		'listURI': {
			value: null
		},
		
		/**
		 * Request URI for downloading, image or file
		 * @type {String}
		 */
		'downloadURI': {
			value: null
		},
		
		/**
		 * Request URI for folder move
		 * @type {String}
		 */
		'moveURI': {
			value: null
		},
		
		/**
		 * Root folder ID
		 * @type {Number}
		 */
		'rootFolderId': {
			value: 0
		},
		
		/**
		 * Folders can be selected
		 * @type {Boolean}
		 */
		'foldersSelectable': {
			value: false
		},
		
		/**
		 * Files can be selected
		 * @type {Boolean}
		 */
		'filesSelectable': {
			value: false
		},
		
		/**
		 * Images can be selected
		 * @type {Boolean}
		 */
		'imagesSelectable': {
			value: false
		},
		
		/**
		 * Display type: all, images or files
		 * @type {Number}
		 */
		'displayType': {
			value: List.DISPLAY_ALL,
			setter: '_setDisplayType'
		},
		
		/**
		 * Media library data object, Supra.MediaLibraryData instance
		 * @type {Object}
		 */
		'dataObject': {
			value: null
		},
		
		/**
		 * Drag and drop is enabled
		 */
		'dndEnabled': {
			value: true,
			setter: '_setDndEnabled'
		},
		
		
		/**
		 * Image thumbnail size id
		 * @type {String}
		 */
		'thumbnailSize': {
			value: '30x30'
		},
		
		/**
		 * Image thumbnail size id
		 * @type {String}
		 */
		'previewSize': {
			value: '200x200'
		},
		
		/**
		 * Item properties which always will be loaded
		 * @type {Array}
		 */
		'loadItemProperties': {
			value: []
		},
		
		/**
		 * Enable / disable animations
		 * @type {Boolean}
		 */
		'noAnimations': {
			value: false,
			setter: '_setNoAnimations'
		},
		
		/**
		 * Slideshow class
		 * @type {Function}
		 */
		'slideshowClass': {
			value: Supra.Slideshow
		},
		
		/**
		 * Templates
		 */
		'templateLoading': {
			value: List.TEMPLATE_LOADING
		},
		'templateEmpty': {
			value: List.TEMPLATE_EMPTY
		},
		'templateFolder': {
			value: List.TEMPLATE_FOLDER
		},
		'templateFolderItemFolder': {
			value: List.TEMPLATE_FOLDER_ITEM_FOLDER
		},
		'templateFolderItemFile': {
			value: List.TEMPLATE_FOLDER_ITEM_FILE
		},
		'templateFolderItemImage': {
			value: List.TEMPLATE_FOLDER_ITEM_IMAGE
		},
		'templateFolderItemTemp': {
			value: List.TEMPLATE_FOLDER_ITEM_TEMP
		},
		'templateFile': {
			value: List.TEMPLATE_FILE
		},
		'templateImage': {
			value: List.TEMPLATE_IMAGE
		}
	};
	
	
	Y.extend(List, Y.Widget, {
		
		/**
		 * Supra.Slideshow instance
		 * @type {Object}
		 * @private
		 */
		slideshow: null,
		
		/**
		 * File is selected
		 * @type {Boolean}
		 * @private
		 */
		file_selected: false,
		
		/**
		 * Image is selected
		 * @type {Boolean}
		 * @private
		 */
		image_selected: false,
		
		/**
		 * Render widget
		 * 
		 * @private
		 */
		renderUI: function () {
			//Create data object
			var data = this.get('dataObject');
			if (!data) {
				data = new Data({
					'listURI': this.get('listURI'),
					'viewURI': this.get('viewURI'),
					'saveURI': this.get('saveURI'),
					'moveURI': this.get('moveURI'),
					'insertURI': this.get('insertURI'),
					'deleteURI': this.get('deleteURI')
				});
				
				data.setRequestParam(Data.PARAM_DISPLAY_TYPE, this.get('displayType') || List.DISPLAY_ALL);
				this.set('dataObject', data);
			} else {
				if (this.get('displayType') !== null) {
					data.setRequestParam(Data.PARAM_DISPLAY_TYPE, this.get('displayType') || List.DISPLAY_ALL);
				}
			}
			
			//Create slideshow
			var slideshowClass = this.get('slideshowClass');
			var slideshow = this.slideshow = (new slideshowClass({
				'srcNode': this.get('contentBox'),
				'animationDuration': 0.35
			})).render();
			
			//Start loading data
			Y.later(1, this, function () {
				this.open(this.get('rootFolderId'));
			});
		},
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			var content = this.get('contentBox');
			
			//On item click open it
			content.delegate('click', function (event) {
				var target = event.target;
					target = target.closest('li');
				
				var id = target.getData('itemId') || target.getAttribute('data-id');
				
				//Style element
				target.addClass('selected');
				target.siblings().removeClass('selected');
				
				//Scroll to slide
				this.open(id);
			}, 'ul.folder > li', this);
			
			//Allow selecting files
			if (this.get('filesSelectable')) {
				content.delegate('mouseenter', function (e) {
					e.target.ancestor().addClass('hover');
				}, 'div.file div.preview img');
				content.delegate('mouseleave', function (e) {
					e.target.ancestor().removeClass('hover');
				}, 'div.file div.preview img');
				content.delegate('click', function (e) {
					
					//On file click update selected state
					this.file_selected = !this.file_selected;
					if (this.file_selected) {
						e.target.ancestor().addClass('selected');
						this.fire('select', {'data': this.getSelectedItem()});
					} else {
						e.target.ancestor().removeClass('selected');
						this.fire('deselect');
					}
					
				}, 'div.file div.preview img', this);
				
				this.slideshow.on('slideChange', function () {
					if (this.file_selected) {
						this.file_selected = false;
						this.fire('deselect');
					}
				}, this);
			}
			
			//Allow selecting images
			if (this.get('imagesSelectable')) {
				content.delegate('mouseenter', function (e) {
					e.target.ancestor().addClass('hover');
				}, 'div.image div.preview img');
				content.delegate('mouseleave', function (e) {
					e.target.ancestor().removeClass('hover');
				}, 'div.image div.preview img');
				content.delegate('click', function (e) {
					
					//@TODO Need better solution!?
					
					this.image_selected = !this.image_selected;
					if (this.image_selected) {
						e.target.ancestor().addClass('selected');
						
						//Trigger event 
						this.fire('select', {'data': this.getSelectedItem()});
					} else {
						e.target.ancestor().removeClass('selected');
					}
				}, 'div.image div.preview img', this);
				
				this.slideshow.on('slideChange', function () {
					if (this.image_selected) {
						this.image_selected = false;
						this.fire('deselect');
					}
				}, this);
			}
			
			this.slideshow.on('slideChange', function (evt) {
				var slide = this.slideshow.getSlide(evt.newVal);
				if (slide) {
					slide.all('li').removeClass('selected');
				}
			}, this);
		},
		
		/**
		 * Update widget
		 * 
		 * @private
		 */
		syncUI: function () {
			
		},
		
		/**
		 * Add file
		 */
		addFile: function (parent, data) {
			var parent_id = null,
				parent_data = null;
			
			if (parent == this.get('rootFolderId')) {
				//Root folder doesn't have data in dataObject
				parent_id = parent;
				parent_data = {'id': parent_id};
			} else if (parent) {
				parent_id = parent;
				parent_data = this.getItemData(parent_id);
				
				if (!parent_data || parent_data.type != Data.TYPE_FOLDER) {
					return false;
				}
			} else {
				parent_data = this.getSelectedFolder();
				if (parent_data) {
					parent_id = parent_data.id;
				} else {
					parent_id = this.get('rootFolderId');
					parent_data = {'id': parent_id};
				}
			}
			
			if (parent_data) {
				//Don't have an ID for this item yet, generate random number
				var file_id = -(~~(Math.random() * 64000));
				
				//If there is no slide, then skip
				var slide = this.slideshow.getSlide('slide_' + parent_id);
				if (!slide) {
					return file_id;
				} else {
					//Hide "Empty" message
					var empty = slide.one('div.empty');
					if (empty) empty.addClass('hidden');
				}
				
				var data = Supra.mix({
					id: file_id,
					parent: parent_id,
					type: Supra.MediaLibraryData.TYPE_TEMP,
					filename: '',
					thumbnail: null,
					preview: null
				}, data || {});
				
				//Add item to the file list
				var data_object = this.get('dataObject');
				
				this.renderItem(parent_id, [data], true);
				data_object.addData(parent_id, [data], true);
				
				return file_id;
			}
			
			return null;
		},
		
		/**
		 * Returns selected item data
		 * 
		 * @return Selected item data
		 * @type {Object]
		 */
		getSelectedItem: function () {
			var item_id = this.slideshow.get('slide'),
				data_object = this.get('dataObject'),
				data;
			
			if (item_id) {
				item_id = item_id.replace('slide_', '');
				data = data_object.getData(item_id);
				data = Supra.mix({
					'path': data_object.getPath(item_id)
				}, data);
				
				if (data) {
					if (data.type == Data.TYPE_FOLDER && this.get('foldersSelectable')) {
						return data;
					} else if (data.type == Data.TYPE_FILE && (!this.get('filesSelectable') || this.file_selected)) {
						//If 'filesSelectable' is false, then file doesn't need to be selected by user
						return data;
					} else if (data.type == Data.TYPE_IMAGE && (!this.get('imagesSelectable') || this.image_selected)) {
						//If 'filesSelectable' is false, then file doesn't need to be selected by user
						return data;
					}
				}
			}
			return null;
		},
		
		/**
		 * Returns currently selected folder
		 * 
		 * @return Selected folder data
		 * @type {Object}
		 */
		getSelectedFolder: function () {
			var history = this.slideshow.getHistory(),
				item_id = String(history[history.length - 1]).replace('slide_', ''),
				folder_data = this.getItemData(item_id);
			
			while(folder_data) {
				if (folder_data.type == Data.TYPE_FOLDER) return folder_data;
				folder_data = this.getItemData(folder_data.parent);
			}
			
			return null;
		},
		
		/**
		 * Set selected item
		 * Chainable
		 * 
		 * @param {Number} id File ID
		 */
		setSelectedItem: function (id) {
			var slide_id = this.slideshow.get('slide'),
				item_id = null;
			
			if (slide_id) {
				item_id = slide_id.replace('slide_', '');
				if (item_id == id) {
					var item_data = this.getItemData(item_id);
					if (item_data.type == Data.TYPE_FILE) {
						this.file_selected = true;
						this.fire('select', {'data': item_data});
					} else if (item_data.type == Data.TYPE_IMAGE) {
						this.image_selected = true;
						this.fire('select', {'data': item_data});
					}
					
					var preview = this.slideshow.getSlide(slide_id).one('.preview');
					if (preview) {
						preview.addClass('selected');
					}
				}
			}
			return this;
		},
		
		/**
		 * Set folder private state
		 * Chainable
		 * 
		 * @param {Number} id Folder ID
		 */
		setPrivateState: function (id, force) {
			var item = null,
				data_object = this.get('dataObject');
			
			//Get item data
			if (!id) {
				var item = this.getSelectedFolder();
				if (item) id = item.id;
				if (!id) return this;
			} else {
				item = data_object.getData(id);
				if (!item) return this;
			}
			
			//If nothing changed then skip
			force = Number(force);
			if (force == item['private']) return this;
			
			//If parent is private, then folder is private and can't be changed to public
			if (item.parent && data_object.isFolderPrivate(item.parent)) {
				return this;
			}
			
			//Update data
			data_object.setFolderPrivate(id, force, Y.bind(function (status) {
				if (!status) {
					//Revert visual changes
					this.updatePrivateStateUI(id, !force);
					
					//Revert toolbar button
					var action = Supra.Manager.getAction('MediaLibrary');
					if (action.get('created') && action.onItemChange) {
						action.onItemChange();
					}
				}
			}, this));
			
			//Update UI private state for this and all sub-folders
			this.updatePrivateStateUI(id, force);
			
			this.reloadFolderContent(id);
		},
		
		reloadFolderContent: function (id) {
			var data_object = this.get('dataObject'),
				item = data_object.getData(id);
			
			this.removeChildrenSlides(item.children);
			
			delete(item.children);
			this.open(item.id);
		},
		
		removeChildrenSlides: function (children) {
			var slide = null,
				slideshow = this.slideshow;
			
			for(var i=0,ii=children.length; i<ii; i++) {
				slide = slideshow.getSlide('slide_' + children[i].id);
				if (slide) {
					slideshow.removeSlide('slide_' + children[i].id);
					if (children[i].children) {
						this.removeChildrenSlides(children[i].children);
					}
				}
			}
		},
		
		/**
		 * Update UI private state
		 * Chainable
		 * 
		 * @param {Number} id Folder ID
		 */
		updatePrivateStateUI: function (id /* Folder ID */, state /* State */) {
			var node = this.getItemNode(id),
				dataObject = this.get('dataObject'),
				slide = null;
				
			if (!node) return this;
			
			node.setClass('type-folder-private', state);
			
			//Update all children
			var children = dataObject.getChildrenData(id);
			for(var i=0,ii=children.length; i<ii; i++) {
				//Update children data
				children[i]['private'] = state;
				
				dataObject.removeData(children[i].id);
				
				// clear properties, to force medialibrary reload item data (thumbnails, previews)
				if (children[i].type == Data.TYPE_FOLDER) {
					this.updatePrivateStateUI(children[i].id, state);
				}
			}
			
			return this;
		},
		
		/**
		 * Load folder or file information
		 * 
		 * @param {Number} id File or folder ID
		 * @param {Function} callback Callback function
		 * @return True if started loading data and false if data is already loaded
		 * @type {Boolean}
		 */
		load: function (id /* File or folder ID */, callback /* Callback function */) {
			//If no folder specified open root folder
			if (!id) id = this.get('rootFolderId');
			
			var data_object = this.get('dataObject'),
				data = data_object.getData(id),
				loading_folder = true,
				loaded = false;
			
			//Check if data needs to be loaded
			if (data) {
				if (data.type != Data.TYPE_FOLDER) {
					loading_folder = false;
					if (data.type == Data.TYPE_FILE && data_object.hasData(id, List.FILE_PROPERTIES)) {
						loaded = true;
					} else if (data.type == Data.TYPE_IMAGE && data_object.hasData(id, List.IMAGE_PROPERTIES)) {
						loaded = true;
					} else if (data.type == Data.TYPE_TEMP) {
						loaded = true;
					}
				} else if (data.children) {
					loaded = true;
				}
			}
			
			//Load data
			if (!loaded) {
				data_object.once('load:complete:' + id, function (event) {
					if (Y.Lang.isFunction(callback)) {
						callback(event.id, event.data);
					}
				}, this);
				
				if (loading_folder) {
					var properties = [].concat(this.get('loadItemProperties'));
					data_object.loadData(id, properties, 'list');
				} else {
					var properties = [].concat(List.FILE_PROPERTIES, List.IMAGE_PROPERTIES).concat(this.get('loadItemProperties'));
					data_object.loadData(id, properties, 'view');
				}
			} else {
				//Execute callback
				if (Y.Lang.isFunction(callback)) {
					callback(id, data);
				}
			}
			
			return !loaded;
		},
		
		/**
		 * Open folder or file information
		 * Chainable.
		 * 
		 * @param {Number} id File or folder ID
		 * @param {Function} callback Callback function
		 */
		open: function (id /* File or folder ID */, callback /* Callback function */) {
			//@TODO Replace code responsible for loading  with .load()
			
			//If no folder specified open root folder
			if (!id) id = this.get('rootFolderId');
			
			//Open file or folder using path to item
			if (Y.Lang.isArray(id)) return this.openPath(id, callback);
			
			var data_object = this.get('dataObject'),
				data = data_object.getData(id),
				loaded = false,
				loading_folder = true,
				slide = this.slideshow.getSlide('slide_' + id);
			
			//Check if data needs to be loaded
			if (data) {
				if (data.type == Data.TYPE_TEMP) {
					return this;
				} else if (data.type != Data.TYPE_FOLDER) {
					loading_folder = false;
					if (data.type == Data.TYPE_FILE && data_object.hasData(id, List.FILE_PROPERTIES)) {
						loaded = true;
					} else if (data.type == Data.TYPE_IMAGE && data_object.hasData(id, List.IMAGE_PROPERTIES)) {
						loaded = true;
					} else if (data.type == Data.TYPE_TEMP) {
						loaded = true;
					}
				} else if (data.children) {
					loaded = true;
				}
			} else if (id == this.get('rootFolderId') && data_object.getChildrenData(0).length) {
				//Root folder doesn't have any data, it has only children data
				loaded = true;
			}
			
			//Create slide
			if (!slide) {
				//File and image slides should be removed when not visible anymore
				var remove_on_hide = !loading_folder;
				slide = this.slideshow.addSlide({
					'id': 'slide_' + id,
					'removeOnHide': remove_on_hide
				});
				
				if (loaded) {
					if (data && data.type == Data.TYPE_FOLDER) {
						this.renderItem(id);
					} else {
						this.renderItem(id, [data]);
					}
				}
			} else {
				//Remove 'selected' from elements
				slide.all('li').removeClass('selected');
			}
			
			//Load data
			if (!loaded) {
				var slide_content = slide.one('.su-slide-content, .su-multiview-slide-content');
				
				slide_content
					.empty()
					.append(this.renderTemplate(data, this.get('templateLoading')));
				
				slide_content.fire('contentResize');
				
				data_object.once('load:complete:' + id, function (event) {
					if (event.data) {
						//Success
						this.renderItem(event.id, event.data);
					} else {
						//Failure, go back to previous slide
						this.slideshow.scrollBack();
					}
					if (Y.Lang.isFunction(callback)) {
						callback(event.id, event.data);
					}
				}, this);
				
				if (loading_folder) {
					var properties = [].concat(this.get('loadItemProperties'));
					data_object.loadData(id, properties, 'list');
				} else {
					var properties = [].concat(List.FILE_PROPERTIES, List.IMAGE_PROPERTIES).concat(this.get('loadItemProperties'));
					data_object.loadData(id, properties, 'view');
				}
			}
			
			//Mark item in parent slide as selected
			if (id && id != this.get('rootFolderId') && data) {
				var parent_slide = this.slideshow.getSlide('slide_' + data.parent);
				if (parent_slide) {
					var node = parent_slide.one('li[data-id="' + id + '"]');
					if (node) node.addClass('selected');
				}
			} 
			
			//Scroll to slide
			if (this.slideshow.isInHistory('slide_' + id)) {
				//Show slide
				this.slideshow.scrollTo('slide_' + id);
			} else {
				//Open slide and hide all slides under parent
				this.slideshow.scrollTo('slide_' + id, null, data ? 'slide_' + data.parent : null);
			}
			
			//Execute callback
			if (loaded) {
				if (Y.Lang.isFunction(callback)) {
					callback(id, data);
				}
			}
			
			return this;
		},
		
		/**
		 * Open item which may not be loaded yet using path to it
		 * 
		 * @param {Array} path Path
		 * @param {Function} callback Callback function
		 * @private
		 */
		openPath: function (path /* Path to open */, callback /* Callback function */) {
			var slideshow = this.slideshow,
				from = 0,
				stack = path,
				root_folder_id = this.get('rootFolderId'),
				noAnimations = this.get('noAnimations');
			
			//Check if root folder is in path, if not then add
			if (path[0] != root_folder_id) {
				path.unshift(root_folder_id);
			}
			
			//Check if one of the path folders is already opened
			for(var i=path.length-1; i>=0; i--) {
				if (slideshow.isInHistory('slide_' + path[i])) {
					stack = path.slice(i + 1);
					break;
				}
			}
			
			//Open folders one by one
			if (stack.length) {
				var next = Y.bind(function (item_id, data) {
					if (stack.length && data) {
						var id = stack[0],
							attr = this.get('noAnimations');
						
						stack = stack.slice(1);
						
						this.set('noAnimations', noAnimations)
						this.open(id, next);
						this.set('noAnimations', attr)
					} else {
						//Execute callback
						if (Y.Lang.isFunction(callback)) {
							callback(path);
						}
					}
				}, this);
				next(null, true);
			} else if (path.length) {
				//Last item is already opened, only need to show it
				this.open(path[path.length - 1]);
				
				//Execute callback
				if (Y.Lang.isFunction(callback)) {
					callback(path);
				}
			}
			
			return this;
		},
		
		/**
		 * Open parent folder
		 */
		openPrevious: function () {
			var history = this.slideshow.history,
				item = history.length > 1 ? history[history.length - 2] : null;
			
			if (item) {
				this.open(item.replace('slide_', ''));
			}
		},
		
		/**
		 * Reload all media library data
		 */
		reload: function () {
			//Reset data
			var history = this.reset();
			
			//Start loading data
			if (!history || !history.length) {
				history = [this.get('rootFolderId')];
			}
			
			this.slideshow.set('noAnimations', true);
			this.openPath(history, Y.bind(function () {
				this.slideshow.set('noAnimations', false);
			}, this));
		},
		
		/**
		 * Reset all data
		 *
		 * @return History path
		 * @private
		 */
		reset: function () {
			var data_object = this.get('dataObject'),
				slideshow = this.slideshow,
				slides = slideshow.slides,
				history = null;
			
			history = Y.Array.map(slideshow.history, function (id) {
				return id.replace('slide_', '');
			});
			
			//Reset data
			data_object.destroy();
			
			//Reset slideshow
			slideshow.history = [];
			slideshow.set('slide', null);
			for(var id in slides) {
				slideshow.removeSlide(id);
			}
			
			return history;
		},
		
		/**
		 * Returns item node
		 * @param {Number} id File or folder ID
		 */
		getItemNode: function (id) {
			var data = this.get('dataObject').getData(id);
			if (data) {
				var slide = this.slideshow.getSlide('slide_' + data.parent);
				if (slide) {
					return slide.one('li[data-id="' + id + '"]');
				}
			}
			return null;
		},
		
		/**
		 * Render item
		 * Chainable.
		 * 
		 * @param {Number} id File or folder ID
		 * @param {Object} data Item data
		 * @param {Boolean} append Append or replace, default is replace
		 * @private
		 */
		renderItem: function (id /* File or folder ID */, data /* Item data */, append /* Append or replace */) {
			var id = isNaN(id) ? id : parseInt(id, 10),
				slide = this.slideshow.getSlide('slide_' + id),
				slide_content = null,
				template,
				node,
				item;
			
			//No slide to render data into, probably it was already closed
			if (!slide) return;
			slide_content = slide.one('.su-slide-content, .su-multiview-slide-content');
			
			//Get data if arguments is not passed
			if (typeof data === 'undefined' || data === null) {
				data = this.get('dataObject').getData(id);
				if (!data || data.type == Data.TYPE_FOLDER) {
					data = this.get('dataObject').getChildrenData(id);
				}
			}
			
			if (data && data.length) {
				if (data.length == 1 && data[0].id == id && data[0].type != Data.TYPE_FOLDER) {
					//File or image
					if (data[0].type == Data.TYPE_FILE) {
						template = this.get('templateFile');
					} else if (data[0].type == Data.TYPE_IMAGE) {
						template = this.get('templateImage');
					}
					
					node = this.renderTemplate(data[0], template);
					node.setData('itemId', data[0].id);
					
					slide_content.empty().append(node);
					
//					//More / less
//					node.all('a.more, a.less').on('click', this.handleInfoToggleClick, this);
					
					this.fire('itemRender', {'node': node, 'data': data[0], 'type': data[0].type});
				} else {
					//Folder
					if (append) {
						node = slide_content.one('ul.folder');
					}
					if (!node) {
						node = this.renderTemplate({'id': id}, this.get('templateFolder'));
					}
					 
					var templates = {
						1: this.get('templateFolderItemFolder'),
						2: this.get('templateFolderItemImage'),
						3: this.get('templateFolderItemFile'),
						4: this.get('templateFolderItemTemp')
					};
					
					//Sort data
					data = this.sortData(data);
					
					for(var i=0,ii=data.length; i<ii; i++) {
						item = this.renderTemplate(data[i], templates[data[i].type]);
						item.setData('itemId', data[i].id);
						
						if (append) {
							//Add after last folder item
							var li = node.all('li.type-folder');
							if (li.size()) {
								li.item(li.size() - 1).insert(item, 'after');
							} else {
								node.prepend(item);
							}
						} else {
							node.append(item);
						}
					}
					
					slide.setData('itemId', id);
					
					if (!append) slide_content.empty();
					slide_content.append(node);
					
					this.fire('itemRender', {'node': node, 'data': data, 'type': Data.TYPE_FOLDER});
				}
			} else {
				//Empty
				node = this.renderTemplate({'id': id}, this.get('templateEmpty'));
				slide_content.empty().append(node);
				this.fire('itemRender', {'node': node, 'data': data, 'type': null});
			}
			
			slide_content.fire('contentResize');
			
			return this;
		},
		
		/**
		 * Sort or filter data
		 * 
		 * @param {Array} data
		 * @return Sorted and filtered data
		 * @type {Array}
		 * @private
		 */
		sortData: function (data) {
			return data;
		},
		
		/**
		 * Add preview and thumbnail to data if possible
		 * for use in template
		 * 
		 * @param {Object} data
		 * @return Transformed data
		 * @type {Object}
		 * @private
		 */
		getRenderData: function (data) {
			var preview_size = this.get('previewSize'),
				thumbnail_size = this.get('thumbnailSize'),
				item_data = Supra.mix({}, data || {}),
				extension = null;
			
			//Extension
			if (item_data.filename) {
				extension = item_data.filename.match(/\.([a-z0-9]+)$/);
				if (extension) {
					item_data.extension = extension[1];
				}
			}
			
			//URLs
			if (item_data.sizes) {
				if (thumbnail_size in item_data.sizes) {
					item_data['thumbnail'] = item_data.sizes[thumbnail_size].external_path;
				}
				if (preview_size in item_data.sizes) {
					item_data['preview'] = item_data.sizes[preview_size].external_path;
				}
			}
			
			return item_data;
		},
		
		/**
		 * Render template
		 * 
		 * @param {Object} data Item data
		 * @param {String} template Template
		 * @return Generated NodesList
		 * @type {Object}
		 * @private
		 */
		renderTemplate: function (data /* Item data */, template /* Template */) {
			var html = template ? template(this.getRenderData(data)) : '';
			return Y.Node.create(html);
		},
		
		/**
		 * Returns loaded item data
		 * 
		 * @param {Mumber} id Item ID
		 */
		getItemData: function (id /* Item ID */) {
			return this.get('dataObject').getData(id || this.get('rootFolderId'));
		},
		
		/**
		 * Update slideshow noAnimations attribute
		 * 
		 * @param {Object} value
		 */
		_setNoAnimations: function (value) {
			this.slideshow.set('noAnimations', value);
			return value;
		},
		
		/**
		 * Display type setter
		 */
		_setDisplayType: function (value) {
			if (this.get('displayType') != value) {
				var data_object = this.get('dataObject');
				if (data_object) {
					this.get('dataObject').setRequestParam('type', value);
					//this.reload();
				}
			}
			return value;
		},
		
		/**
		 * Enable or disable drag and drop support
		 */
		_setDndEnabled: function (value) {
			if (value) {
				this.get('boundingBox').addClass(this.getClassName('dnd'));
			} else {
				this.get('boundingBox').removeClass(this.getClassName('dnd'));
			}
			return value;
		}
	}, {});
	
	
	Supra.MediaLibraryList = List;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['widget', 'supra.slideshow', 'supra.medialibrary-data']});