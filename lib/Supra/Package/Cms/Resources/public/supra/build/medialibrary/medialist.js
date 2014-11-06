/**
 * MediaLibraryList handles folder/file/image data loading and opening,
 * allows selecting files, folders and images
 */
YUI.add('supra.medialibrary-list', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * Shortcuts
	 */
	var Template = Supra.Template;
	
	/*
	 * HTML5 support
	 */
	var FILE_API_SUPPORTED = typeof FileReader !== 'undefined';
	
	/**
	 * Media list
	 * Handles data loading, scrolling, selection
	 */
	function List (config) {
		List.superclass.constructor.apply(this, arguments);
		this.property_widgets = {};
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
	 * Item types
	 */
	List.TYPE_FOLDER = 1;
	List.TYPE_IMAGE = 2;
	List.TYPE_FILE = 3;
	List.TYPE_TEMP = 4;
	
	
	
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
	List.TEMPLATE_EMPTY = Template.compile('<div class="empty' + (FILE_API_SUPPORTED ? ' empty-dnd' : '') + '" data-id="{{ id }}"></div>');
	
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
		<li class="type-file {% if known_extension %}type-file-{{ known_extension }}{% endif %} {% if broken %}type-broken{% endif %}" data-id="{{ id }}">\
			<a></a>\
			<span>{{ filename|escape }}</span>\
		</li>');
	
	/**
	 * Constant, folder item template for image
	 * @type {String}
	 */
	List.TEMPLATE_FOLDER_ITEM_IMAGE = Template.compile('\
		<li class="type-image {% if broken or !thumbnail %}type-broken{% endif %}" data-id="{{ id }}">\
			<a>{% if !broken and thumbnail %}<img src="{{ thumbnail|escape }}?t={{ timestamp }}" alt="" />{% endif %}</a>\
			<span>{{ filename|escape }}</span>\
		</li>');
	
	/**
	 * Constant, folder item template for temporary file
	 * @type {String}
	 */
	List.TEMPLATE_FOLDER_ITEM_TEMP = Template.compile('\
		<li class="type-temp' + (FILE_API_SUPPORTED ? '' : ' type-temp-legacy') + '" data-id="{{ id }}">\
			<span class="title">{{ filename|escape }}</span>\
			<a class="cancel"></a>\
			<span class="progress"><em></em></span>\
		</li>');
	
	/**
	 * Constant, file template
	 * @type {String}
	 */
	List.TEMPLATE_FILE = Template.compile('\
		<div class="file">\
			<div class="preview">\
					<img src="/public/cms/supra/build/medialibrary/assets/skins/supra/images/icons/{%if broken %}broken{% else %}file{% if known_extension %}-{{ known_extension }}{% endif %}{% endif %}-large.png" {% if known_extension %}class="known-extension"{% endif %} alt="" />\
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
			{% if allowInsert %}\
			<div class="insert">\
				<button data-id="insert" data-style="small-blue" type="button">{{ "medialibrary.insert"|intl }}</button>\
			</div>\
			{% endif %}\
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
					<img src="/public/cms/supra/build/medialibrary/assets/skins/supra/images/icons/broken-large.png" class="known-extension" alt="" />\
				{% else %}\
					<img src="{{ preview|escape }}?t={{ timestamp }}" alt="" />\
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
			{% if allowInsert %}\
			<div class="insert">\
				<button data-id="insert" data-style="small-blue" type="button">{{ "medialibrary.insert"|intl }}</button>\
			</div>\
			{% endif %}\
		</div>');
		
	
	List.ATTRS = {
		
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
		 * Show insert button
		 */
		'allowInsert': {
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
		 * Media library data, Supra.DataObject.Data instance
		 */
		'data': {
			value: null
		},
		
		/**
		 * Sorting
		 * @type {String}
		 */
		'sortBy': {
			value: 'filename'
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
		 * Image and file property inputs
		 * @type {Object}
		 * @private
		 */
		property_widgets: {},
		
		/**
		 * Supra.Slideshow instance
		 * @type {Object}
		 * @private
		 */
		slideshow: null,
		
		/**
		 * Sort by widget
		 * @type {Object}
		 * @private
		 */
		input_sortby: null,
		
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
			
			// Create data object
			this._setDataObject(this.get('displayType'));
			
			//Create "Sort by" widget
			this.renderUISortSwitch();
			
			//Create slideshow
			var slideshowClass = this.get('slideshowClass');
			var slideshow = this.slideshow = (new slideshowClass({
				'srcNode': this.get('contentBox'),
				'animationDuration': 0.35
			})).render();
		},
		
		/**
		 * Test if item data is complete
		 * 
		 * @param {Object} item Item data which will be tested
		 * @returns {Boolean} True if all item data is loaded, otherwise false
		 * @private
		 */
		isItemDataComplete: function (item) {
			if (item.type === List.TYPE_FILE) {
				return 'file_web_path' in item;
			} else if (item.type === List.TYPE_IMAGE) {
				return 'sizes' in item;
			} else if (item.type === List.TYPE_TEMP) {
				return true;
			}
			return false;
		},
		
		/**
		 * Handle data add event
		 * 
		 * @param {Object} event Event facade object
		 * @param {Object} item Item data
		 * @private
		 */
		_dataAdd: function (event, item) {
			//Nothing here
		},
		
		/**
		 * Handle data remove event
		 * 
		 * @param {Object} event Event facade object
		 * @param {Object} item Item data
		 * @private
		 */
		_dataRemove: function (event, item) {
			//Nothing here
		},
		
		/**
		 * Handle data change event
		 * 
		 * @param {Object} event Event facade object
		 * @param {Object} item Item data
		 * @private
		 */
		_dataChange: function (event, item, changes) {
			if ('parent' in changes) {
				this._dataRemove(event, changes);
				this._dataAdd(event, item);
				return;
			}
			
			this.uiViewUpdateItem(item, changes);
		},
		
		/**
		 * Update items UI based on changed data
		 * 
		 * @param {Object} item Item data
		 * @param {Object} changes Properties which changed with old values
		 * @private
		 */
		uiViewUpdateItem: function (item, changes) {
			var node = this.getItemNode(item.id),
				node_temp = null,
				folder = item.type === List.TYPE_FOLDER;
			
			if (node) {
				if ('filename' in changes) {
					node_temp = node.one('.preview-title') || node.one('span');
					if (node_temp) {
						node_temp.set('text', item.filename);
					}
				}
				if ('private' in changes && folder) {
					node.toggleClass('type-folder-private', item.private);
				}
				if ('thumbnail' in changes && !folder) {
					if (!item.broken && item.thumbnail) {
						node.one('a img').setAttribute('src', item.thumbnail + '?t=' + (+new Date()));
					}
				}
				if ('broken' in changes) {
					node.toggleClass('type-broken', item.broken);
				}
			}
			
			// Update image/file data
			var slide_node = this.getSlideNode(),
				text_node  = null,
				text       = null;
			
			if (slide_node) {
				if ('size' in changes) {
					// Update size
					text_node = slide_node.one('[data-update="size"]');
					if (text_node) {
						text = Math.round(item.size/1000) || '0';
						text_node.set('innerHTML', text + ' KB');
					}
				}
				
				// Update image size
				if ('sizes' in changes) {
					text_node = slide_node.one('[data-update="dimensions"]');
					if (text_node) {
						text = item.sizes && item.sizes.original ? item.sizes.original.width + ' x ' + item.sizes.original.height : '';
						if (text) {
							text_node.set('innerHTML', text);
						}
					}
				}
				
				// Update modified time if there is something else besides ID
				if (Y.Object.size(changes) > 1) {
					text_node = slide_node.one('[data-update="modified"]');
					if (text_node) {
						text = item.modified ? Y.DataType.Date.reformat(item.modified, 'in_datetime_short', 'out_datetime_short') : null;
						
						if (text) {
							text_node.set('innerHTML', text);
							text_node.ancestor().removeClass('hidden');
						}
					}
				}
			}
		},
		
		renderUISortSwitch: function () {
			var input = this.input_sortby = new Supra.Input.SelectList({
				'label': Supra.Intl.get(['medialibrary', 'sort_by']),
				'values': [
					{'id': 'filename', 'title': Supra.Intl.get(['medialibrary', 'sort_az'])},
					{'id': 'id', 'title': Supra.Intl.get(['medialibrary', 'sort_date'])}
				],
				'value': 'filename'
			});
			input.render(this.get('boundingBox'));
			input.addClass('input-sortby');
			input.on('change', function (event) {
				this.set('sortBy', event.value);
			}, this);
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
				
				//If loading, then skip
				if (target.hasClass('loading')) {
					return;
				}
				
				//Blur focused input if item is not selected
				if (!target.hasClass('selected')) {
					if (Y.Node(document.activeElement).test('input, textarea')) {
						document.activeElement.blur();
					}
				}
				
				//Style element
				target.addClass('selected');
				target.siblings().removeClass('selected');
				
				//Scroll to slide
				if (this.getOpenedItem().id != id) {
					this.open(id);
				}
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
			
			//On sort change redraw lists
			this.after('sortByChange', this.handleSortingChange, this);
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
				//Root folder doesn't have data in data object
				parent_id = parent;
				parent_data = {'id': parent_id};
			} else if (parent) {
				parent_id = parent;
				parent_data = this.getItemData(parent_id);
				
				if (!parent_data || parent_data.type != List.TYPE_FOLDER) {
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
				var file_id = data.id || -(~~(Math.random() * 64000)),
					type = data.type || List.TYPE_TEMP,
					filename = data.filename || '';
				
				//File should be added to folder, but since we have a path try to lookup
				//that folder or create a folder and return it instead of file
				if (data.folderPath) {
					filename = data.folderPath.split('/')[0];
					type = List.TYPE_FOLDER;
					
					var item = this.getItemDataByTitle(parent_id, filename);
					if (item) {
						// Item already exists
						return item.id;
					}
				}
				
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
					parent: parent_id,
					thumbnail: null,
					preview: null
				}, data || {}, {
					filename: filename,
					type: type,
					id: file_id
				});
				
				//Add item to the file list
				var data_object = this.get('data');
				
				this.renderItem(parent_id, [data], true);
				data_object.cache.add(data);
				
				return file_id;
			}
			
			return null;
		},
		
		/**
		 * Returns selected item data
		 * 
		 * @returns {Object} Selected item data
		 */
		getSelectedItem: function () {
			var item_id = this.slideshow.get('slide'),
				data_object = this.get('data'),
				data;
			
			if (item_id) {
				item_id = item_id.replace('slide_', '');
				data = data_object.cache.one(item_id);
				data = Supra.mix({
					'path': data_object.getPath(item_id)
				}, data);
				
				if (data) {
					if (data.type == List.TYPE_FOLDER && this.get('foldersSelectable')) {
						return data;
					} else if (data.type == List.TYPE_FILE && (!this.get('filesSelectable') || this.file_selected)) {
						//If 'filesSelectable' is false, then file doesn't need to be selected by user
						return data;
					} else if (data.type == List.TYPE_IMAGE && (!this.get('imagesSelectable') || this.image_selected)) {
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
		 * @returns {Object} Selected folder data
		 */
		getSelectedFolder: function () {
			var history = this.slideshow.getHistory(),
				item_id = String(history[history.length - 1]).replace('slide_', ''),
				folder_data = this.getItemData(item_id);
			
			while(folder_data) {
				if (folder_data.type == List.TYPE_FOLDER) return folder_data;
				folder_data = this.getItemData(folder_data.parent);
			}
			
			return null;
		},
		
		/**
		 * Returns currently opened item
		 * 
		 * @returns {Object} Opened item data
		 */
		getOpenedItem: function () {
			var history = this.slideshow.getHistory(),
				item_id = String(history[history.length - 1]).replace('slide_', '');
			
			return this.getItemData(item_id);
		},
		
		/**
		 * Returns image preview node
		 * 
		 * @private
		 */
		getImageNode: function () {
			var item = this.getSelectedItem(),
				slide = null;
			
			if (item) {
				slide = this.slideshow.getSlide('slide_' + item.id);
				if (slide) {
					return slide.one('div.preview img');
				}
			}
			
			return null;
		},
		
		/**
		 * Returns item slide node
		 * 
		 * @private
		 */
		getSlideNode: function () {
			var item = this.getSelectedItem(),
				slide = null;
			
			if (item) {
				return this.slideshow.getSlide('slide_' + item.id);
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
					if (item_data.type == List.TYPE_FILE) {
						this.file_selected = true;
						this.fire('select', {'data': item_data});
					} else if (item_data.type == List.TYPE_IMAGE) {
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
				data = this.get('data');
			
			//Get item data
			if (!id) {
				var item = this.getSelectedFolder();
				if (item) id = item.id;
				if (!id) return this;
			} else {
				item = data.cache.one(id);
				if (!item) return this;
			}
			
			//If nothing changed then skip
			force = Number(force);
			if (force == item.private) return this;
			
			//If parent is private, then folder is private and can't be changed to public
			if (item.parent && this.isFolderPrivate(item.parent)) {
				return this;
			}
			
			//Update UI private state for this and all sub-folders
			this.updatePrivateStateUI(id, force);
			this.reloadFolderContent(id);
			
			//Update data
			data.save({'id': id, 'private': force}).fail(function () {
				//Revert visual changes
				this.updatePrivateStateUI(id, !force);
				
				//Revert toolbar button
				var action = Supra.Manager.getAction('MediaLibrary');
				if (action.get('created') && action.onItemChange) {
					action.onItemChange();
				}
			}, this);
		},
		
		/**
		 * Returns true if folder is private, otherwise false
		 * 
		 * @param {String} id Folder Id
		 * @returns {Boolean} True if folder is private
		 * @private
		 */
		isFolderPrivate: function (id) {
			var item = this.get('data').cache.one(id);
			return (item && item.private);
		},
		
		/**
		 * Redraw folder content
		 * 
		 * @param {String} id Folder Id
		 * @returns {Object} Deferred object which resolves when folder is redrawn
		 * @private
		 */
		reloadFolderContent: function (id) {
			var data_object = this.get('data'),
				item = data_object.cache.one(id);
			
			if (item.children) {
				this.removeChildrenSlides(item.children);
			}
			
			this.get('data').purge(id);
			return this.open(item.id);
		},
		
		/**
		 * Removes slides for items, recursive
		 * 
		 * @param {Array} children Children data which slides will be removed
		 * @private
		 */
		removeChildrenSlides: function (children) {
			var slide = null,
				slideshow = this.slideshow,
				node = null;
			
			for(var i=0,ii=children.length; i<ii; i++) {
				slide = slideshow.getSlide('slide_' + children[i].id);
				if (slide) {
					slideshow.removeSlide('slide_' + children[i].id);
					if (children[i].children) {
						this.removeChildrenSlides(children[i].children);
					}
						
					//Fire event
					this.fire('removeSlide', {
						'id': children[i].id,
						'node': slide,
						'type': children[i].type
					});
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
			var data = this.get('data'),
				children = data.cache.all(id);
			
			//Update all children, recursive
			for(var i=0,ii=children.length; i<ii; i++) {
				
				data.cache.save({'id': children[i].id, 'private': state});
				
				if (children[i].type == List.TYPE_FOLDER) {
					this.updatePrivateStateUI(children[i].id, state);
				}
			}
			
			return this;
		},
		
		/**
		 * Load folder or file information
		 * 
		 * @param {Number} id File or folder ID
		 * @returns {Object} Deferred object
		 */
		load: function (id /* File or folder ID */) {
			// If no folder specified open root folder
			if (!id) id = this.get('rootFolderId');
			
			var data = this.get('data'),
				deferred = new Supra.Deferred();
			
			data.any(id, true)
				.done(function (data) {
					deferred.resolve([data, id]);
				})
				.fail(function () {
					deferred.reject([null, id]);
				});
			
			return deferred;
		},
		
		/**
		 * Open folder or file information
		 * Chainable.
		 * 
		 * @param {Number} id File or folder ID
		 * @param {Boolean} markFile Mark file as selected instead of opening it
		 * @returns {Object} Deferred objects promise
		 */
		open: function (id /* File or folder ID */, markFile /* Mark file instead of opening it */) {
			//If no folder specified open root folder
			if (!id) id = this.get('rootFolderId');
			
			//Open file or folder using path to item
			if (Y.Lang.isArray(id)) return this.openPath(id, markFile);
			
			var deferred = null,
				data_object = this.get('data'),
				slide = this.slideshow.getSlide('slide_' + id),
				data = data_object.cache.one(id),
				load = false,
				mark = false;
			
			if (markFile && data && data.type != List.TYPE_FOLDER) {
				mark = true;
				deferred = new Supra.Deferred();
				deferred.resolve();
			}
			
			//Create slide
			if (!mark) {
				if (!slide) {
					//File and image slides should be removed when not visible anymore
					var remove_on_hide = data_object.isFolder(id) === false;
					slide = this.slideshow.addSlide({
						'id': 'slide_' + id,
						'removeOnHide': remove_on_hide
					});
					
					//Need to load data
					load = true;
				
				} else {
					//Remove 'selected' from elements
					slide.all('li').removeClass('selected');
					
					if (!data) {
						//Need to load data
						load = true;
					} else {
						deferred = new Supra.Deferred();
						deferred.resolve();
					}
				}
				
				if (load) {
					// Loading icon
					var slide_content = slide.one('.su-slide-content, .su-multiview-slide-content');
				
					slide_content
						.empty()
						.append(this.renderTemplate(data, this.get('templateLoading')));
					
					slide_content.fire('contentResize');
					
					// Load data and render
					deferred = this.load(id).done(function (data, id) {
						this.renderItem(id, data);
					}, this).fail(function () {
						//Failure, go back to previous slide
						this.slideshow.scrollBack();
					}, this);
				}
			}
			
			//Mark item in parent slide as selected
			if (id && id != this.get('rootFolderId') && data) {
				var parent_slide = this.slideshow.getSlide('slide_' + data.parent);
				if (parent_slide) {
					var node = parent_slide.one('li[data-id="' + id + '"]');
					if (node) {
						node.siblings().removeClass('selected');
						node.addClass('selected');
						
						if (mark) {
							node.addClass('marked');
						}
					}
				}
			}
			
			if (!mark) {
				//Scroll to slide
				if (this.slideshow.isInHistory('slide_' + id)) {
					//Show slide
					this.slideshow.scrollTo('slide_' + id);
				} else {
					//Open slide and hide all slides under parent
					this.slideshow.scrollTo('slide_' + id, null, data ? 'slide_' + data.parent : null);
				}
			}
			
			return deferred;
		},
		
		/**
		 * Open item which may not be loaded yet using path to it
		 * 
		 * @param {Array} path Path
		 * @param {Boolean} markFile Mark file as selected instead of opening it
		 * @returns {Object} Deferred object
		 * @private
		 */
		openPath: function (path /* Path to open */, markFile /* Mark file instead of opening it */) {
			var slideshow = this.slideshow,
				from = 0,
				stack = path,
				root_folder_id = this.get('rootFolderId'),
				noAnimations = this.get('noAnimations'),
				deferred = new Supra.Deferred();
			
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
				var next = Y.bind(function (data) {
					if (stack.length && data) {
						var id = stack.shift(),
							attr = this.get('noAnimations');
						
						this.set('noAnimations', noAnimations)
						this.open(id, markFile).always(next);
						this.set('noAnimations', attr)
					} else {
						deferred.resolve();
					}
				}, this);
				next(true);
			} else if (path.length) {
				//Last item is already opened, only need to show it
				return this.open(path[path.length - 1], markFile);
			}
			
			return deferred.promise();
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
			this.openPath(history).always(function () {
				this.slideshow.set('noAnimations', false);
			}, this);
		},
		
		/**
		 * Reset all data
		 *
		 * @return History path
		 * @private
		 */
		reset: function () {
			var data = this.get('data'),
				slideshow = this.slideshow,
				slides = slideshow.slides,
				slide = null,
				history = null;
			
			history = Y.Array.map(slideshow.history, function (id) {
				return id.replace('slide_', '');
			});
			
			//Reset slideshow
			slideshow.history = [];
			slideshow.set('slide', null);
			
			var data_id = null;
			for(var id in slides) {
				data_id = id.replace('slide_', '');
				slide = slides[id];
				slideshow.removeSlide(id);
				
				//Fire event
				var item = data.cache.one(data_id);
				this.fire('removeSlide', {
					'id': data_id,
					'node': slide,
					'type': item ? item.type : List.TYPE_FOLDER
				});
			}
			
			//Reset data
			data.purge(this.get('rootFolderId'));
			
			return history;
		},
		
		/**
		 * Returns item node
		 * @param {Number} id File or folder ID
		 */
		getItemNode: function (id) {
			var data = this.get('data').cache.one(id);
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
				item,
				empty;
			
			//No slide to render data into, probably it was already closed
			if (!slide) return;
			slide_content = slide.one('.su-slide-content, .su-multiview-slide-content');
			
			//Get data if arguments is not passed
			if (typeof data === 'undefined' || data === null) {
				data = this.get('data').cache.any(id);
			}
			
			//Is file missing or folder is empty?
			empty = !data || (Y.Lang.isArray(data) && !data.length);
			
			if (!empty) {
				if (!Y.Lang.isArray(data)) {
					//File or image
					if (data.type == List.TYPE_FILE) {
						template = this.get('templateFile');
					} else if (data.type == List.TYPE_IMAGE) {
						template = this.get('templateImage');
					}
					
					var template_data = {'allowInsert': this.get('allowInsert')};
						template_data = Supra.mix(template_data, data);
					
					node = this.renderTemplate(template_data, template);
					node.setData('itemId', data.id);
					
					//Clean before replacing content
					this.removePropertyWidgets();
					
					slide_content.empty().append(node);
					slide_content.closest('.su-multiview-slide, .su-slide').removeClass('su-slide-full-width');
					
					//Render buttons
					node.all('button').each(this.renderItemButton, this);
					
//					//More / less
//					node.all('a.more, a.less').on('click', this.handleInfoToggleClick, this);
					
					this.fire('itemRender', {'node': node, 'id': id, 'data': data, 'type': data.type});
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
					
					if (!append) {
						//Clean before replacing content
						this.removePropertyWidgets();
						
						slide_content.empty();
					}
					
					slide_content.append(node);
					slide_content.closest('.su-multiview-slide, .su-slide').removeClass('su-slide-full-width');
					
					if (!append && FILE_API_SUPPORTED) {
						slide_content.append(Y.Node.create('<div class="dnd-marker"></div>'));
					}
					
					this.fire('itemRender', {'node': node, 'id': id, 'data': data, 'type': List.TYPE_FOLDER});
				}
			} else {
				//Empty
				node = this.renderTemplate({'id': id}, this.get('templateEmpty'));
				slide_content.empty().append(node);
				slide_content.closest('.su-multiview-slide, .su-slide').addClass('su-slide-full-width');
				this.fire('itemRender', {'node': node, 'id': id, 'data': data, 'type': List.TYPE_FOLDER});
			}
			
			slide_content.fire('contentResize');
			
			return this;
		},
		
		/**
		 * Render button inside template
		 * 
		 * @param {Object} node Button node
		 * @private
		 */
		renderItemButton: function (node) {
			if (node.getAttribute('data-id')) {
				var button = new Supra.Button({'srcNode': node});
				
				button.render();
				button.on('click', this.renderItemButtonClick, this);
			}
		},
		
		/**
		 * Handle button click
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		renderItemButtonClick: function (e) {
			var node = e.target.get('contentBox'),
				type = node.getAttribute('data-id'),
				id = node.closest('div.image, div.file').getData('itemId');
			
			if (id && type) {
				this.fire(type + 'Click', {'id': id, 'type': type + 'Click', 'target': e.target});
			}
		},
		
		/**
		 * Handle sorting change
		 * @param {Object} value
		 */
		handleSortingChange: function (evt) {
			if (evt.newVal == evt.oldVal) return;
			
			var value = evt.newVal,
				item = this.getSelectedItem(),
				root_folder_id = this.get('rootFolderId'),
				path = null,
				slides = this.slideshow.slides;
			
			if (item) {
				path = item.path.slice(1);
				path.push(item.id);
			} else {
				path = [root_folder_id];
			}
			
			this.set('noAnimations', true);
			this.open(root_folder_id);
			
			for(var id in slides) {
				if (id != 'slide_' + root_folder_id) {
					this.slideshow.removeSlide(id);
				}
			}
			
			//Render items
			this.renderItem(root_folder_id);
			this.open(path);
			this.set('noAnimations', false);
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
			var sort_by = this.get('sortBy');
			
			//Duplicate
			data = [].concat(data);
			
			//Sort
			data.sort(function (a, b) {
				//Folder always first
				if (a.type != b.type && (a.type == List.TYPE_FOLDER || b.type == List.TYPE_FOLDER)) {
					return a.type < b.type ? -1 : 1;
				}
				
				var val_a = a[sort_by],
					val_b = b[sort_by];
				
				if (typeof val_a == 'string') val_a = val_a.toLowerCase();
				if (typeof val_b == 'string') val_b = val_b.toLowerCase();
				
				return val_a < val_b ? -1 : 1;
			});
			
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
			var data = this.get('data'),
				item = data.cache.one(id),
				children = null;
			
			if (item) return item;
			
			children = data.cache.all(id);
			if (!children) {
				id = this.get('rootFolderId');
				children = data.cache.all(id);
			}
			
			if (children) {
				var loaded = data.dataLoaded[id];
				
				return {
			   		'id': this.get('rootFolderId'),
			   		'type': List.TYPE_FOLDER,
			   		'children': children,
			   		'children_count': loaded ? loaded.totalRecords : null
			   };
			}
			
			return null;
		},
		
		/**
		 * Returns loaded item data by title/filename which is inside given folder
		 * 
		 * @param {String} parent Parent ID
		 * @param {String} filename File name
		 * @returns {Object} File or folder info
		 */
		getItemDataByTitle: function (parent /* Parent ID */, filename /* File name*/) {
			var data = this.get('data'),
				items = data.cache.all(parent),
				i = 0,
				ii = items.length;
			
			for (; i<ii; i++) {
				if (items[i].filename == filename) {
					return items[i];
				}
			}
			
			return null;
		},
		
		/**
		 * Get file or image form property widgets
		 * 
		 * @return Property widgets
		 */
		getPropertyWidgets: function () {
			return this.property_widgets;
		},
		
		/**
		 * Destroy all widgets
		 */
		removePropertyWidgets: function () {
			var inp = this.property_widgets;
			
			for(var i in inp) {
				inp[i].destroy();
			}
			
			this.property_widgets = {};
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
			value = value || List.DISPLAY_ALL;
			
			if (this.get('rendered') && this.get('displayType') != value) {
				this._setDataObject(value);
			}
			
			return value;
		},
		
		/**
		 * Change data object
		 */
		_setDataObject: function (type) {
			var old = this.get('data');
			var data = Supra.DataObject.get({
				'id': 'medialibrary_' + type,
				'actionName': 'MediaLibrary',
				'completeTest': this.isItemDataComplete
			});
			
			data.setRequestParam('type', type);
			this.set('data', data);
			
			if (old) {
				old.detach('add', this._dataAdd, this);
				old.detach('add:multiple', this._dataAddMultiple, this);
				old.detach('remove', this._dataRemove, this);
				old.detach('change', this._dataChange, this);
			}
			
			// Handle data change
			data.on('add', this._dataAdd, this);
			data.on('add:multiple', this._dataAddMultiple, this);
			data.on('remove', this._dataRemove, this);
			data.on('change', this._dataChange, this);
			
			// @TODO Redraw UI
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
	
}, YUI.version, {'requires': ['widget', 'supra.slideshow', 'supra.medialibrary-data-object']});