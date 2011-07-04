//Invoke strict mode
"use strict";

YUI.add('supra.medialibrary-list', function (Y) {
	
	/*
	 * Shortcuts
	 */
	var Data = Supra.MediaLibraryData;
	
	
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
	List.FILE_PROPERTIES = ['title', 'filename', 'description', 'file_web_path'];
	
	/**
	 * Constant, list of properties needed to display image
	 * @type {Array}
	 */
	List.IMAGE_PROPERTIES = ['title', 'filename', 'description', 'sizes'];
	
	
	
	/**
	 * Constant, file or folder loading template
	 * @type {String}
	 */
	List.TEMPLATE_LOADING = '<div class="loading"></div>';
	
	/**
	 * Constant, empty folder template
	 * @type {String}
	 */
	List.TEMPLATE_EMPTY = '<div class="empty">No files in this folder</div>';
	
	/**
	 * Constant, folder template
	 * @type {String}
	 */
	List.TEMPLATE_FOLDER = '<ul class="folder"></ul>';
	
	/**
	 * Constant, folder item template for folder
	 * @type {String}
	 */
	List.TEMPLATE_FOLDER_ITEM_FOLDER = '\
		<li class="type-folder">\
			<a><img src="/cms/supra/img/medialibrary/icon-folder.png" alt="" /></a>\
			<span>{title}</span>\
		</li>';
	
	/**
	 * Constant, folder item template for file
	 * @type {String}
	 */
	List.TEMPLATE_FOLDER_ITEM_FILE = '\
		<li class="type-file">\
			<a><img src="/cms/supra/img/medialibrary/icon-file.png" alt="" /></a>\
			<span>{title}</span>\
		</li>';
	
	/**
	 * Constant, folder item template for image
	 * @type {String}
	 */
	List.TEMPLATE_FOLDER_ITEM_IMAGE = '\
		<li class="type-image">\
			<a><img src="/cms/supra/img/medialibrary/icon-image.png" alt="" /></a>\
			<span>{title}</span>\
		</li>';
	
	/**
	 * Constant, file template
	 * @type {String}
	 */
	List.TEMPLATE_FILE = '\
		<div class="file">\
			<div class="preview"><img src="/cms/supra/img/medialibrary/icon-file-large.png" alt="" /></div>\
			<span>{title}</span>\
			<span>{description}</span>\
		</div>';
	
	/**
	 * Constant, image template
	 * @type {String}
	 */
	List.TEMPLATE_IMAGE = '\
		<div class="image">\
			<div class="preview"><img src="{previewUrl}" alt="" /></div>\
			<span>{title}</span>\
			<span>{description}</span>\
		</div>';
	
	
	
	List.ATTRS = {
		/**
		 * URI for save requests
		 * @type {String}
		 */
		'saveUri': {
			value: ''
		},
		
		/**
		 * Request URI
		 * @type {String}
		 */
		'requestURI': {
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
			value: List.DISPLAY_ALL
		},
		
		/**
		 * Media library data object, Supra.MediaLibraryData instance
		 * @type {Object}
		 */
		'dataObject': {
			value: null
		},
		
		
		/**
		 * Image thumbnail size id
		 * @type {String}
		 */
		'thumbnailSize': {
			value: '60x60'
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
					'requestURI': this.get('requestURI'),
					'saveURI': this.get('saveURI')
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
				'srcNode': this.get('contentBox')
			})).render();
			
			//Start loading data
			this.open(this.get('rootFolderId'));
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
				
				var id = target.getData('itemId');
				
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
					
					//@TODO Need better solution!?
					
					this.file_selected = !this.file_selected;
					if (this.file_selected) {
						e.target.ancestor().addClass('selected');
						
						//Trigger event 
						this.fire('select', {'data': this.getSelectedItem()});
					} else {
						e.target.ancestor().removeClass('selected');
					}
				}, 'div.file div.preview img', this);
				
				this.slideshow.on('slideChange', function () {
					this.file_selected = false;
				}, this);
			}
			
			//Allow selecting files
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
					this.image_selected = false;
				}, this);
			}
		},
		
		/**
		 * Update widget
		 * 
		 * @private
		 */
		syncUI: function () {
			
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
				data = SU.mix({
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
					var item_data = this.get('dataObject').getData(item_id);
					if (item_data.type == Data.TYPE_FILE) {
						this.file_selected = true;
					} else if (item_data.type == Data.TYPE_IMAGE) {
						this.image_selected = true;
					}
					this.slideshow.getSlide(slide_id).one('.preview').addClass('selected');
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
					}
				} else if (data.children) {
					loaded = true;
				}
			}
			
			//Load data
			if (!loaded) {
				data_object.once('load:success:' + id, function (event) {
					if (Y.Lang.isFunction(callback)) {
						callback(event.id, event.data);
					}
				}, this);
				
				if (loading_folder) {
					var properties = [this.get('thumbnailSize') + '_url'].concat(this.get('loadItemProperties'));
				} else {
					var properties = [this.get('previewSize') + '_url'].concat(List.FILE_PROPERTIES, List.IMAGE_PROPERTIES).concat(this.get('loadItemProperties'));
				}
				data_object.loadData(id, properties);
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
				if (data.type != Data.TYPE_FOLDER) {
					loading_folder = false;
					if (data.type == Data.TYPE_FILE && data_object.hasData(id, List.FILE_PROPERTIES)) {
						loaded = true;
					} else if (data.type == Data.TYPE_IMAGE && data_object.hasData(id, List.IMAGE_PROPERTIES)) {
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
				slide = this.slideshow.addSlide('slide_' + id, remove_on_hide);
				
				if (loaded) {
					this.renderItem(id, [data]);
				}
			} else {
				//Remove 'selected' from elements
				slide.all('li').removeClass('selected');
			}
			
			//Load data
			if (!loaded) {
				slide.empty().append(this.renderTemplate(data, this.get('templateLoading')));
				
				data_object.once('load:success:' + id, function (event) {
					this.renderItem(event.id, event.data);
					
					if (Y.Lang.isFunction(callback)) {
						callback(event.id, event.data);
					}
				}, this);
				
				if (loading_folder) {
					var properties = [this.get('thumbnailSize') + '_url'].concat(this.get('loadItemProperties'));
				} else {
					var properties = [this.get('previewSize') + '_url'].concat(List.FILE_PROPERTIES, List.IMAGE_PROPERTIES).concat(this.get('loadItemProperties'));
				}
				data_object.loadData(id, properties);
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
			if (Y.Lang.isFunction(callback)) {
				callback(id, data);
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
				stack = path;
			
			//Check if one of the path folders is already opened
			for(var i=path.length-1; i>=0; i--) {
				if (slideshow.isInHistory('slide_' + path[i])) {
					stack = path.slice(i + 1);
					break;
				}
			}
			
			//Open folders one by one
			if (stack.length) {
				var next = Y.bind(function () {
					if (stack.length) {
						var id = stack[0];
						stack = stack.slice(1);
						this.open(id, next);
					} else {
						//Execute callback
						if (Y.Lang.isFunction(callback)) {
							callback(path);
						}
					}
				}, this);
				next();
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
		 * Render item
		 * Chainable.
		 * 
		 * @param {Number} id File or folder ID
		 * @param {Object} data Item dat
		 * @private
		 */
		renderItem: function (id /* File or folder ID */, data /* Item data */) {
			var slide = this.slideshow.getSlide('slide_' + id),
				template,
				node,
				item;
			
			if (data && data.length) {
				if (data.length == 1 && data[0].id == id && data[0].type != Data.TYPE_FOLDER) {
					//File or image
					template = (data[0].type == Data.TYPE_FILE ? this.get('templateFile') : this.get('templateImage'));
					
					node = this.renderTemplate(data[0], template);
					slide.empty().append(node);
					this.fire('itemRender', {'node': node, 'data': data[0], 'type': data[0].type});
				} else {
					//Folder
					node = this.renderTemplate({}, this.get('templateFolder'));
					var templates = {
						1: this.get('templateFolderItemFolder'),
						2: this.get('templateFolderItemImage'),
						3: this.get('templateFolderItemFile')
					};
					
					for(var i=0,ii=data.length; i<ii; i++) {
						item = this.renderTemplate(data[i], templates[data[i].type]);
						item.setData('itemId', data[i].id);
						node.append(item);
					}
					
					slide.setData('itemId', id);
					slide.empty().append(node);
					this.fire('itemRender', {'node': node, 'data': data, 'type': Data.TYPE_FOLDER});
				}
			} else {
				//Empty
				node = this.renderTemplate(data, this.get('templateEmpty'));
				slide.empty().append(node);
				this.fire('itemRender', {'node': node, 'data': data, 'type': null});
			}
			
			return this;
		},
		
		/**
		 * Add previewUrl and thumbnailUrl to data if possible
		 * for use in template
		 * 
		 * @param {Object} data
		 * @return Transformed data
		 * @type {Object}
		 * @private
		 */
		getRenderData: function (data) {
			var preview_key = this.get('previewSize') + '_url',
				thumbnail_key = this.get('thumbnailSize') + '_url',
				item_data = data || {};
			
			if (preview_key in item_data || thumbnail_key in item_data) {
				item_data = SU.mix({}, item_data);
				
				if (thumbnail_key in item_data) {
					item_data['thumbnailUrl'] = item_data[thumbnail_key];
				}
				if (preview_key in item_data) {
					item_data['previewUrl'] = item_data[preview_key];
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
			var html = Y.substitute(template || '', this.getRenderData(data));
			return Y.Node.create(html);
		},
		
		/**
		 * Update slideshow noAnimations attribute
		 * 
		 * @param {Object} value
		 */
		_setNoAnimations: function (value) {
			this.slideshow.set('noAnimations', value);
			return value;
		}
	}, {});
	
	
	Supra.MediaLibraryList = List;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['widget', 'supra.slideshow', 'supra.medialibrary-data', 'supra.medialibrary-list-css']});